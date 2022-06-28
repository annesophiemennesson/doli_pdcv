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
require_once DOL_DOCUMENT_ROOT.'/custom/inventaire/lib/inventaire.lib.php';

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
    ajoutProduitInventaire($entrepot);
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
