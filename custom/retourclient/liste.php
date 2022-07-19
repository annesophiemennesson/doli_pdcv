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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

// Load translation files required by the page
$langs->loadLangs(array("retourclient@retourclient"));

$action = GETPOST('action', 'aZ09');
$message = GETPOST('message', 'alpha');

// Security check
/*if (! $user->rights->retourclient->retourclient->read) {
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


llxHeader("", "Liste des retours clients");

print load_fiche_titre("Liste des retours clients", '', '');

print '<div class="fichecenter">';

// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject

if (! empty($conf->retourclient->enabled) /*&& $user->rights->retourclient->retourclient->read*/)
{
	$sql = "SELECT r.rowid, date_creation, SUM(qty) AS nb, montant_ttc
			FROM ".MAIN_DB_PREFIX."retourclient AS r
			INNER JOIN ".MAIN_DB_PREFIX."retourclientdet AS d ON (d.fk_retourclient = r.rowid)
			WHERE r.statut = \"ouverte\"
			GROUP BY r.rowid;";

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<h3>Liste des retours en attente</h3>';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>Id</th><th>Date création</th><th>Quantité produits</th><th>Total TTC</th></tr>';
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';
				print '<td><a target="_blank" href="'.dol_buildpath('/custom/retourclient/detail.php?id='.$obj->rowid, 1).'">'.$obj->rowid.'</a></td>';
                print '<td>'.dol_print_date($obj->date_creation, "%d/%m/%Y %H:%M:%S").'</td>';
                print '<td>'.$obj->nb.'</td>';
                print '<td>'.price2num($obj->montant_ttc, 'MS').'</td>';
                print '</tr>';
				$i++;
			}
        }
		else
		{
			print '<tr class="oddeven"><td colspan="4" class="center">Aucun retour</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}


	
	$sql = "SELECT r.rowid, date_creation, SUM(qty) AS nb, montant_ttc
			FROM ".MAIN_DB_PREFIX."retourclient AS r
			INNER JOIN ".MAIN_DB_PREFIX."retourclientdet AS d ON (d.fk_retourclient = r.rowid)
			WHERE r.statut = \"validée\"
			GROUP BY r.rowid;";

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<h3>Liste des retours validés</h3>';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>Id</th><th>Date création</th><th>Quantité produits</th><th>Total TTC</th></tr>';
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';
				print '<td><a target="_blank" href="'.dol_buildpath('/custom/retourclient/detail.php?id='.$obj->rowid, 1).'">'.$obj->rowid.'</a></td>';
                print '<td>'.dol_print_date($obj->date_creation, "%d/%m/%Y %H:%M:%S").'</td>';
                print '<td>'.$obj->nb.'</td>';
                print '<td>'.price2num($obj->montant_ttc, 'MS').'</td>';
                print '</tr>';
				$i++;
			}
        }
		else
		{
			print '<tr class="oddeven"><td colspan="4" class="center">Aucun retour</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

	
	$sql = "SELECT r.rowid, date_creation, SUM(qty) AS nb, montant_ttc, mode_remboursement
			FROM ".MAIN_DB_PREFIX."retourclient AS r
			INNER JOIN ".MAIN_DB_PREFIX."retourclientdet AS d ON (d.fk_retourclient = r.rowid)
			WHERE r.statut = \"remboursée\"
			GROUP BY r.rowid;";

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<h3>Liste des retours remboursés</h3>';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>Id</th><th>Date création</th><th>Quantité produits</th><th>Total TTC</th><th>Mode remboursement</th></tr>';
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';
				print '<td><a target="_blank" href="'.dol_buildpath('/custom/retourclient/detail.php?id='.$obj->rowid, 1).'">'.$obj->rowid.'</a></td>';
                print '<td>'.dol_print_date($obj->date_creation, "%d/%m/%Y %H:%M:%S").'</td>';
                print '<td>'.$obj->nb.'</td>';
                print '<td>'.price2num($obj->montant_ttc, 'MS').'</td>';
                print '<td>'.$obj->mode_remboursement.'</td>';
                print '</tr>';
				$i++;
			}
        }
		else
		{
			print '<tr class="oddeven"><td colspan="5" class="center">Aucun retour</td></tr>';
		}
		print "</table><br>";

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
