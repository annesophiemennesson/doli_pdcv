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

// Load translation files required by the page
$langs->loadLangs(array("sendings", "bills", 'deliveries', 'orders'));

$action = GETPOST('action', 'aZ09');

$id = GETPOST('id', 'int');

// Security check
if (! $user->rights->inventaire->inventaire->detail) {
	accessforbidden();
}

$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$now = dol_now();


/*
 * Actions
 */

/*
 * View
 */


llxHeader("", "Détail de l'inventaire");

print load_fiche_titre("Détail de l'inventaire", '', '');

print '<div class="fichecenter">';


// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (!empty($id) && ! empty($conf->inventaire->enabled) && $user->rights->inventaire->inventaire->detail)
{
	$sql = "SELECT date_creation, ref
			FROM ".MAIN_DB_PREFIX."inventaire AS i
			INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e ON (e.rowid = i.fk_entrepot)
			WHERE i.rowid=".$id;
	
	$resql = $db->query($sql);
	$obj = $db->fetch_object($resql);

	print '<h3>Inventaire de '.$obj->ref.' du '.dol_print_date($obj->date_creation, "%d/%m/%Y").'</h3>';

	$sql = "SELECT p.label, p.rowid, CONCAT(lastname, ' ', firstname) AS user, stock_attendu, stock_reel, stock_confirm, date_inventaire, commentaire, ef.uniteachat
			FROM ".MAIN_DB_PREFIX."inventaire_produit AS i
			INNER JOIN ".MAIN_DB_PREFIX."product as p ON (i.fk_product = p.rowid)
			LEFT JOIN ".MAIN_DB_PREFIX."user as u ON (i.fk_user = u.rowid)
			LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as ef on (p.rowid = ef.fk_object) 
			WHERE fk_inventaire = ".$id;

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>Produit</th><th>Stock attendu</th><th>Stock contrôlé</th><th>Stock confirmé</th><th>Utilisateur</th><th>Commentaire</th><th>Date</th></tr>';
		if ($num > 0)
		{
			$i = 0;
			// On recupère les valeurs pour les unités achat vente
			$sqlua = "SELECT param FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'product' AND name = 'uniteachat'";
			$resqlua = $db->query($sqlua);
			$objua = $db->fetch_object($resqlua);
			$ua = jsonOrUnserialize($objua->param)['options'];
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				$valua = $ua[$obj->uniteachat];

				print '<tr class="oddeven">';
				print '<td><a target="_blank" href="'.dol_buildpath('/product/card.php?id='.$obj->rowid, 1).'"><strong>'.$obj->label.'</strong></a> ('.$valua.')</td>';
                print '<td>'.$obj->stock_attendu.'</td>';
                print '<td>'.$obj->stock_reel.'</td>';
                print '<td>'.$obj->stock_confirm.'</td>';
                print '<td>'.$obj->user.'</td>';
                print '<td>'.$obj->commentaire.'</td>';
				print '<td>'.dol_print_date($obj->date_inventaire, "%d/%m/%Y %H:%M:%S").'</td>';
                print '</tr>';
				$i++;
			}
        }
		else
		{

			print '<tr class="oddeven"><td colspan="7" class="opacitymedium">Aucun produit</td></tr>';
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
