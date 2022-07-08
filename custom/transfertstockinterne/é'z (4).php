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
$langs->loadLangs(array("transfertstockinterne@transfertstockinterne"));

$action = GETPOST('action', 'aZ09');
$message = GETPOST('message', 'alpha');


// Security check
if (! $user->rights->transfertstockinterne->transfert_stock->prepare) {
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


llxHeader("", "Liste des transferts à préparer");

print load_fiche_titre("Liste des transferts à préparer", '', '');

print '<div class="fichecenter">';
if ($message == 'fin'){
	print '<p class="message">Transfert préparé !</p>';
}

// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (! empty($conf->transfertstockinterne->enabled) && $user->rights->transfertstockinterne->transfert_stock->prepare)
{
	// Si l'utilisateur n'est pas lié à un magasin
	if (empty($user->fk_warehouse)){
		print '<p><strong>Vous n\'êtes lié à aucun magasin, vous ne pouvez donc faire aucune commande<br/>Effectuez la modification dans votre fiche utilisateur, déconnectez-vous et reconnectez-vous pour réessayer.</strong></p>';
	}else{
		$sql = "SELECT e.ref as depart, e2.ref as arrivee, date_creation, t.label, COUNT(p.rowid) AS nb_produit, SUM(p.qte_valide) AS total_qte, t.rowid
				FROM ".MAIN_DB_PREFIX."transfert_stock AS t
				INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e ON (e.rowid = t.fk_entrepot_depart)
				INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e2 ON (e2.rowid = t.fk_entrepot_arrivee)
				INNER JOIN ".MAIN_DB_PREFIX."transfert_produit AS p ON (p.fk_transfert_stock = t.rowid)
				WHERE date_prepa IS NULL AND date_valide IS NOT NULL AND fk_entrepot_depart = ".$user->fk_warehouse."
				GROUP BY t.rowid;";

		$resql = $db->query($sql);
		if ($resql)
		{
			$total = 0;
			$num = $db->num_rows($resql);

			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<th>Libellé</th><th>Source</th><th>Destination</th><th>Date demande</th><th>Nb produit</th><th>Qté totale</th><th>Action</th></tr>';
			if ($num > 0)
			{
				$i = 0;
				while ($i < $num)
				{

					$obj = $db->fetch_object($resql);
					print '<tr class="oddeven">';
					print '<td>'.$obj->label.'</td>';
					print '<td>'.$obj->depart.'</td>';
					print '<td>'.$obj->arrivee.'</td>';
					print '<td>'.dol_print_date($obj->date_creation, "%d/%m/%Y %H:%M:%S").'</td>';
					print '<td>'.$obj->nb_produit.'</td>';
					print '<td>'.$obj->total_qte.'</td>';
					print '<td><a class="button" href="'.dol_buildpath('/custom/transfertstockinterne/preparation.php?id='.$obj->rowid, 1).'">Préparer</a></td>';
					print '</tr>';
					$i++;
				}
			}
			else
			{

				print '<tr class="oddeven"><td colspan="7" class="center">Aucun transfert à préparer</td></tr>';
			}
			print "</form></table><br>";

			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
	}
}
//END MODULEBUILDER DRAFT MYOBJECT */


print '</div>';

// End of page
llxFooter();
$db->close();
