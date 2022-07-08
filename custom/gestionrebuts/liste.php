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
$langs->loadLangs(array("gestionrebuts@gestionrebuts"));

$action = GETPOST('action', 'aZ09');
$message = GETPOST('message', 'alpha');

// Security check
/*if (! $user->rights->transfertstockinterne->transfert_stock->list) {
	accessforbidden();
}
*/


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


llxHeader("", "Liste des demandes d'avoir'");

print load_fiche_titre("Liste des demandes d'avoir'", '', '');

print '<div class="fichecenter">';

// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject

if (! empty($conf->gestionrebuts->enabled) /*&& $user->rights->transfertstockinterne->transfert_stock->list*/)
{
	$sql = "SELECT a.rowid, a.date_creation, nom, COUNT(fk_product) AS nb, SUM(qty * price) AS totalHT, fk_reception
			FROM ".MAIN_DB_PREFIX."demande_avoir AS a
			INNER JOIN ".MAIN_DB_PREFIX."reception AS r ON (a.fk_reception = r.rowid)
			INNER JOIN ".MAIN_DB_PREFIX."societe AS s ON (r.fk_soc = s.rowid)
			INNER JOIN ".MAIN_DB_PREFIX."demande_avoirdet AS d ON (d.fk_demande_avoir = a.rowid)
			WHERE a.statut = \"ouverte\"
			GROUP BY a.rowid;";

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<h3>Liste des demandes ouvertes</h3>';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>Id</th><th>Fournisseur</th><th>Date création</th><th>Nb produits</th><th>Total HT</th></tr>';
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';
				print '<td><a target="_blank" href="'.dol_buildpath('/custom/gestionrebuts/detail.php?id='.$obj->rowid, 1).'">'.$obj->rowid.'</a></td>';
                print '<td><a href="'.dol_buildpath('/reception/card.php?id='.$obj->fk_reception, 1).'" target="_blank">'.$obj->nom.'</a></td>';
                print '<td>'.dol_print_date($obj->date_creation, "%d/%m/%Y %H:%M:%S").'</td>';
                print '<td>'.$obj->nb.'</td>';
                print '<td>'.$obj->totalHT.'</td>';
                print '</tr>';
				$i++;
			}
        }
		else
		{
			print '<tr class="oddeven"><td colspan="5" class="center">Aucune demande</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}


	$sql = "SELECT a.rowid, a.date_creation, nom, COUNT(fk_product) AS nb, SUM(qty * price) AS totalHT, fk_reception
			FROM ".MAIN_DB_PREFIX."demande_avoir AS a
			INNER JOIN ".MAIN_DB_PREFIX."reception AS r ON (a.fk_reception = r.rowid)
			INNER JOIN ".MAIN_DB_PREFIX."societe AS s ON (r.fk_soc = s.rowid)
			INNER JOIN ".MAIN_DB_PREFIX."demande_avoirdet AS d ON (d.fk_demande_avoir = a.rowid)
			WHERE a.statut = \"validée\"
			GROUP BY a.rowid;";

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<h3>Liste des demandes validées</h3>';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>Id</th><th>Fournisseur</th><th>Date création</th><th>Nb produits</th><th>Total HT</th></tr>';
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';
				print '<td><a target="_blank" href="'.dol_buildpath('/custom/gestionrebuts/detail.php?id='.$obj->rowid, 1).'">'.$obj->rowid.'</a></td>';
                print '<td><a href="'.dol_buildpath('/reception/card.php?id='.$obj->fk_reception, 1).'" target="_blank">'.$obj->nom.'</a></td>';
                print '<td>'.dol_print_date($obj->date_creation, "%d/%m/%Y %H:%M:%S").'</td>';
                print '<td>'.$obj->nb.'</td>';
                print '<td>'.$obj->totalHT.'</td>';
                print '</tr>';
				$i++;
			}
        }
		else
		{
			print '<tr class="oddeven"><td colspan="5" class="center">Aucune demande</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}


	$sql = "SELECT a.rowid, a.date_creation, nom, COUNT(fk_product) AS nb, SUM(qty * price) AS totalHT, fk_reception
			FROM ".MAIN_DB_PREFIX."demande_avoir AS a
			INNER JOIN ".MAIN_DB_PREFIX."reception AS r ON (a.fk_reception = r.rowid)
			INNER JOIN ".MAIN_DB_PREFIX."societe AS s ON (r.fk_soc = s.rowid)
			INNER JOIN ".MAIN_DB_PREFIX."demande_avoirdet AS d ON (d.fk_demande_avoir = a.rowid)
			WHERE a.statut = \"annulées\"
			GROUP BY a.rowid;";

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<h3>Liste des demandes annulées</h3>';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>Id</th><th>Fournisseur</th><th>Date création</th><th>Nb produits</th><th>Total HT</th></tr>';
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';
				print '<td><a target="_blank" href="'.dol_buildpath('/custom/gestionrebuts/detail.php?id='.$obj->rowid, 1).'">'.$obj->rowid.'</a></td>';
                print '<td><a href="'.dol_buildpath('/reception/card.php?id='.$obj->fk_reception, 1).'" target="_blank">'.$obj->nom.'</a></td>';
                print '<td>'.dol_print_date($obj->date_creation, "%d/%m/%Y %H:%M:%S").'</td>';
                print '<td>'.$obj->nb.'</td>';
                print '<td>'.$obj->totalHT.'</td>';
                print '</tr>';
				$i++;
			}
        }
		else
		{
			print '<tr class="oddeven"><td colspan="5" class="center">Aucune demande</td></tr>';
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
