<?php
/* Copyright (C) 2022	Anne-Sophie Mennesson	<annesophie.mennesson@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/inventaire/class/inventaire.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/inventaire/class/inventaire_produit.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/inventaire/class/inventaire_config.class.php';

// Load translation files required by the page
$langs->loadLangs(array("inventaire@inventaire"));

$action = GETPOST('action', 'aZ09');


// Security check
/*if (! $user->rights->inventaire->transfert_stock->create) {
	accessforbidden();
}*/

$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$now = dol_now();


/*
 * Actions
 */

if ($action == 'gen'){
    $entrepot = GETPOST('id', 'int');
    $object = new Inventaire($db);
    $object->fk_entrepot = $entrepot;
    $object->create($user);
    $id = $object->id;

    // Recup nb jour dans la config
    $obj = new Inventaire_config($db);
    $obj->fetch("", $entrepot);
    $nb_j = $obj->nb_jours;
	
    // Nb de produit en stock à l'entrepot
    $sql = "SELECT COUNT(*) AS nb
            FROM ".MAIN_DB_PREFIX."product_stock AS s
            INNER JOIN ".MAIN_DB_PREFIX."product AS p ON (p.rowid = s.fk_product)
            WHERE fk_entrepot = ".$entrepot;
    $result = $db->query($sql);
    $objnb = $db->fetch_object($result);
    $nb_p = $objnb->nb;

    //Nb produit à attribuer par jour
    $nb_add = ceil($nb_p / $nb_j);

    // On récupère les produits à ajouter qui n'ont pas été inventoriés depuis le nb de jours
    // et qui ne sont pas déjà dans la liste à faire
    $sql = "SELECT s.fk_product
            FROM ".MAIN_DB_PREFIX."product_stock AS s
            WHERE fk_entrepot = ".$entrepot." AND s.fk_product NOT IN (
                SELECT fk_product
                FROM ".MAIN_DB_PREFIX."inventaire_produit as p
                INNER JOIN ".MAIN_DB_PREFIX."inventaire as i ON (i.rowid = p.fk_inventaire)
                WHERE fk_entrepot = ".$entrepot." AND CAST(date_inventaire AS date) >= '".date('Y-m-d',strtotime('- '.$nb_j.' day'))."'  OR date_inventaire IS NULL
            )
            ORDER BY RAND()
            LIMIT ".$nb_add;

    $result = $db->query($sql);
    if ($result){
        $num = $db->num_rows($result);
        if ($num > 0)
        {
            $i = 0;
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $objp = new Inventaire_produit($db);
                $objp->fk_inventaire = $id;
                $objp->fk_product = $obj->fk_product;
                $objp->create($user);
                $i++;
            }
        }
        // Si il n'y en a pas assez alors on ajoute au hasard des produits 
        // qui ne font pas déjà partis de la liste à inventorier
        if ($num < $nb_add){
            $delta = $nb_add - $num;
            $sql = "SELECT s.fk_product
                    FROM ".MAIN_DB_PREFIX."product_stock AS s
                    WHERE fk_entrepot = ".$entrepot." AND s.fk_product NOT IN (
                        SELECT fk_product
                        FROM ".MAIN_DB_PREFIX."inventaire_produit as p
                        INNER JOIN ".MAIN_DB_PREFIX."inventaire as i ON (i.rowid = p.fk_inventaire)
                        WHERE fk_entrepot = ".$entrepot." AND date_inventaire IS NULL
                    )
                    ORDER BY RAND()
                    LIMIT ".$delta;
            $result = $db->query($sql);
            $num = $db->num_rows($result);
            if ($num > 0)
            {
                $i = 0;
                while ($i < $num)
                {
                    $obj = $db->fetch_object($result);
                    $objp = new Inventaire_produit($db);
                    $objp->fk_inventaire = $id;
                    $objp->fk_product = $obj->fk_product;
                    $objp->create($user);
                    $i++;
                }
            }
        }
    }
}


/*
 * View
 */

llxHeader("", "Nouvel inventaire");

print load_fiche_titre("Nouvel inventaire", '', '');

print '<div class="fichecenter">';

// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (! empty($conf->inventaire->enabled) /*&& $user->rights->transfertstockinterne->transfert_stock->create*/)
{
    $sql = "SELECT e.rowid, e.ref, nb_jours
            FROM ".MAIN_DB_PREFIX."entrepot AS e
            INNER JOIN ".MAIN_DB_PREFIX."inventaire_config AS c ON (c.fk_entrepot = e.rowid)
            WHERE statut = 1;";
    $result = $db->query($sql);
    if ($result){
        $num = $db->num_rows($result);
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<th>Entrepôt</th><th>Action</th></tr>';
        if ($num > 0)
        {
            $i = 0;
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                print '<tr class="'.($i%2 == 0 ? 'pair' : 'impair').'">';
                print '<td><strong>'.$obj->ref.'</strong></td>';
                print '<td><a href="'.dol_buildpath('/custom/inventaire/new.php?id='.$obj->rowid.'&action=gen&token='.newToken(), 1).'">Créer</a></td>';
                print '</tr>';
                $i++;
            }
        }
        else
        {
            print '<tr class="oddeven"><td colspan="2" class="center">Aucun entrepôt</td></tr>';
        }
        print "</form></table><br>";

        $db->free($resql);
    }
    else
    {
        dol_print_error($db);
    }
}
//END MODULEBUILDER DRAFT MYOBJECT */


print '</div>';

// End of page
llxFooter();
$db->close();
