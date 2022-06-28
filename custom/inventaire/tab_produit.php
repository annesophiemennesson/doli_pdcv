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
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("sendings", "bills", 'deliveries', 'orders'));

$action = GETPOST('action', 'aZ09');

$id = GETPOST('id', 'int');

// Security check
/*if (! $user->rights->transfertstockinterne->transfert_stock->detail) {
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

/*
 * View
 */


llxHeader("", "Historique des inventaires");

print load_fiche_titre("Historique des inventaires", '', '');

print '<div class="fichecenter">';


// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (!empty($id) && ! empty($conf->inventaire->enabled) /*&& $user->rights->transfertstockinterne->transfert_stock->detail*/)
{
	$object = new Product($db);
	$result = $object->fetch($id);
	$head = product_prepare_head($object);
	$titre = "Historique des inventaires";
	print dol_get_fiche_head($head, 'inventaire', $titre, -1, 'product');

	$sql = "SELECT e.ref, stock_attendu, stock_confirm, commentaire, date_inventaire, CONCAT(lastname, ' ', firstname) AS user, CONCAT((if (stock_confirm > stock_attendu , '+' , '')), stock_confirm-stock_attendu) as delta
	 		FROM ".MAIN_DB_PREFIX."inventaire_produit AS ip
            INNER JOIN ".MAIN_DB_PREFIX."inventaire AS i ON (i.rowid = ip.fk_inventaire)
            INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e ON (e.rowid = i.fk_entrepot)
            INNER JOIN ".MAIN_DB_PREFIX."user AS u ON (u.rowid = ip.fk_user)
			WHERE ip.fk_product = ".$id."
            ORDER BY date_inventaire desc;";
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		$req = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."entrepot WHERE statut = 1";
		$res = $db->query($req);
		$search_entrepot = '';
		while ($ob = $db->fetch_object($res)){
			$search_entrepot .= '<option value="'.$ob->rowid.'">'.$ob->ref.'</option>';
		}

		print '<table class="noborder centpercent" id="toutes_demandes">';
		print '<tr class="liste_titre_filter">';
		print '<td><select id="search_entrepot" name="search_entrepot"><option value="">Sélectionnez</option>'.$search_entrepot.'</select></td>';
		print '<td colspan="2"></td>';
		print '<td><select id="search_delta" name="search_delta"><option value="">Sélectionnez</option><option value="sup">> 0</option><option value="inf">< 0</option><option value="egal">=</option></select></td>';		
		print '<td></td>';
		print '<td class="liste_titre center">';
		print $form->selectDate(-1, 'search_date', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', 'Choisissez');
		print '</td>';
		print '<td class="right"><button onclick="search_filters('.$id.');" class="liste_titre button_search reposition" name="button_search_x" value="x"><span class="fa fa-search"></span></button><button onclick="reset_search();" class="liste_titre button_removefilter reposition" name="button_removefilter_x" value="x"><span class="fa fa-remove"></span></button></td></tr>';
		
		print '<tr class="liste_titre" id="liste_titre">';
		print '<th>Entrepôt</th><th>Stock attendu</th><th>Stock confirmé</th><th>Delta</th><th>Utilisateur</th><th>Date</th><th>Commentaire</th></tr>';
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven">';
				print '<td>'.$obj->ref.'</td>';
				print '<td>'.$obj->stock_attendu.'</td>';
				print '<td>'.$obj->stock_confirm.'</td>';
				print '<td>'.$obj->delta.'</td>';
				print '<td>'.$obj->user.'</td>';
				print '<td>'.dol_print_date($obj->date_inventaire, "%d/%m/%Y %H:%M:%S").'</td>';
				print '<td>'.$obj->commentaire.'</td>';
				print '</tr>';
				$i++;
			}
		}
		else
		{
			print '<tr class="oddeven"><td colspan="6" class="opacitymedium">Aucun inventaire</td></tr>';
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
