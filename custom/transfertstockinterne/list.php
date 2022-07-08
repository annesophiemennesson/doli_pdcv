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
$langs->loadLangs(array("transfertstockinterne@transfertstockinterne"));

$action = GETPOST('action', 'aZ09');
$message = GETPOST('message', 'alpha');

// Security check
if (! $user->rights->transfertstockinterne->transfert_stock->list) {
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


llxHeader("", "Liste des demandes de transfert");

print load_fiche_titre("Liste des demandes de transfert", '', '');

print '<div class="fichecenter">';
if ($message == 'fin'){
	print '<p class="message">Transfert validé !</p>';
}
$form = new Form($db);


// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject

if (! empty($conf->transfertstockinterne->enabled) && $user->rights->transfertstockinterne->transfert_stock->list)
{
	$sql = "SELECT e.ref as depart, e2.ref as arrivee, date_creation, t.label, CONCAT(u.lastname, \" \", u.firstname) AS demandeur, COUNT(p.rowid) AS nb_produit, SUM(p.qte_demande) AS total_qte 
			FROM ".MAIN_DB_PREFIX."transfert_stock AS t
			INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e ON (e.rowid = t.fk_entrepot_depart)
			INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e2 ON (e2.rowid = t.fk_entrepot_arrivee)
			INNER JOIN ".MAIN_DB_PREFIX."user AS u ON (u.rowid = t.fk_user_demande)
			INNER JOIN ".MAIN_DB_PREFIX."transfert_produit AS p ON (p.fk_transfert_stock = t.rowid)
			WHERE date_valide IS NULL AND t.label = \"Commande magasin\"
			GROUP BY t.rowid;";

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<h3>Liste des commandes à traiter</h3>';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>Libellé</th><th>Source</th><th>Destination</th><th>Date demande</th><th>Demandeur</th><th>Nb produit</th><th>Qté totale</th></tr>';
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
                print '<td>'.$obj->demandeur.'</td>';
                print '<td>'.$obj->nb_produit.'</td>';
                print '<td>'.$obj->total_qte.'</td>';
                print '</tr>';
				$i++;
			}
            print '<tr><td colspan="7" class="center"><a href="'.dol_buildpath('/custom/transfertstockinterne/validation.php', 1).'" class="button">Traiter les demandes</a></td></tr>';
		}
		else
		{

			print '<tr class="oddeven"><td colspan="7" class="center">Aucune commande à traiter</td></tr>';
		}
		print "</form></table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}


	
	$sql = "SELECT e.ref as depart, e2.ref as arrivee, date_creation, t.label, CONCAT(u.lastname, \" \", u.firstname) AS demandeur, COUNT(p.rowid) AS nb_produit, SUM(p.qte_demande) AS total_qte, t.rowid 
			FROM ".MAIN_DB_PREFIX."transfert_stock AS t
			INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e ON (e.rowid = t.fk_entrepot_depart)
			INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e2 ON (e2.rowid = t.fk_entrepot_arrivee)
			INNER JOIN ".MAIN_DB_PREFIX."user AS u ON (u.rowid = t.fk_user_demande)
			INNER JOIN ".MAIN_DB_PREFIX."transfert_produit AS p ON (p.fk_transfert_stock = t.rowid)
			WHERE date_valide IS NULL AND label = \"Ramasse magasin\"
			GROUP BY t.rowid;";


	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<h3>Liste des demandes de ramasse à traiter</h3>';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>Libellé</th><th>Source</th><th>Destination</th><th>Date demande</th><th>Demandeur</th><th>Nb produit</th><th>Qté totale</th><th>Action</th></tr>';
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
                print '<td>'.$obj->demandeur.'</td>';
                print '<td>'.$obj->nb_produit.'</td>';
                print '<td>'.$obj->total_qte.'</td>';
				print '<td class="center"><a href="'.dol_buildpath('/custom/transfertstockinterne/validation_ramasse.php?id='.$obj->rowid, 1).'" class="button">Valider</a></td>';
                print '</tr>';
				$i++;
			}
		}
		else
		{

			print '<tr class="oddeven"><td colspan="7" class="center">Aucune demande à traiter</td></tr>';
		}
		print "</form></table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}


	$sql = "SELECT e.ref as depart, e2.ref as arrivee, date_creation, t.rowid, t.label, CONCAT(u.lastname, \" \", u.firstname) AS demandeur, COUNT(p.rowid) AS nb_produit, SUM(p.qte_demande) AS total_qte, 
				CASE WHEN date_prepa IS NULL THEN 'A préparer' WHEN date_reception IS NULL THEN 'A réceptionner' ELSE 'Terminé' END as statut
			FROM ".MAIN_DB_PREFIX."transfert_stock AS t
			INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e ON (e.rowid = t.fk_entrepot_depart)
			INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e2 ON (e2.rowid = t.fk_entrepot_arrivee)
			INNER JOIN ".MAIN_DB_PREFIX."user AS u ON (u.rowid = t.fk_user_demande)
			INNER JOIN ".MAIN_DB_PREFIX."transfert_produit AS p ON (p.fk_transfert_stock = t.rowid)
			WHERE date_valide IS NOT NULL
			GROUP BY t.rowid
			ORDER BY date_creation DESC
			LIMIT 25;";

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		$req = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."entrepot";
		$res = $db->query($req);
		$search_entrepot = '';
		while ($ob = $db->fetch_object($res)){
			$search_entrepot .= '<option value="'.$ob->rowid.'">'.$ob->ref.'</option>';
		}

		print '<h3>Liste des demandes <small>(par défaut les 25 dernières, utilisez les filtres pour en voir d\'autres)</small></h3>';
		print '<table class="noborder centpercent" id="toutes_demandes">';
		print '<tr class="liste_titre_filter">';
		print '<td><select id="search_libelle" name="search_libelle"><option value="">Sélectionnez</option><option value="Commande magasin">Commande magasin</option><option value="Ramasse magasin">Ramasse magasin</option></select></td>';
		print '<td><select id="search_source" name="search_source"><option value="">Sélectionnez</option>'.$search_entrepot.'</select></td>';
		print '<td><select id="search_dest" name="search_dest"><option value="">Sélectionnez</option>'.$search_entrepot.'</select></td>';
			
		print '<td class="liste_titre center">';
		print $form->selectDate(-1, 'search_date', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', 'Choisissez');
		print '</td>';
		print '<td></td>';
		print '<td><select id="search_statut" name="search_statut"><option value="">Sélectionnez</option><option value="apreparer">A préparer</option><option value="areceptionner">A réceptionner</option><option value="termine">Terminé</option></select></td>';
		print '<td colspan="2" class="right"><button onclick="search_filter();" class="liste_titre button_search reposition" name="button_search_x" value="x"><span class="fa fa-search"></span></button><button onclick="reset_search();" class="liste_titre button_removefilter reposition" name="button_removefilter_x" value="x"><span class="fa fa-remove"></span></button></td></tr>';
		print '<tr class="liste_titre" id="liste_titre">';
		print '<th>Libellé</th><th>Source</th><th>Destination</th><th>Date demande</th><th>Demandeur</th><th>Statut</th><th>Nb produit</th><th>Qté totale</th></tr>';
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';
                print '<td><a target="_blank" href="'.dol_buildpath('/custom/transfertstockinterne/detail.php?id='.$obj->rowid, 1).'">'.$obj->label.'</a></td>';
                print '<td>'.$obj->depart.'</td>';
                print '<td>'.$obj->arrivee.'</td>';
                print '<td>'.dol_print_date($obj->date_creation, "%d/%m/%Y %H:%M:%S").'</td>';
                print '<td>'.$obj->demandeur.'</td>';
                print '<td>'.$obj->statut.'</td>';
                print '<td>'.$obj->nb_produit.'</td>';
                print '<td>'.$obj->total_qte.'</td>';
                print '</tr>';
				$i++;
			}
		}
		else
		{

			print '<tr class="oddeven"><td colspan="8" class="center">Aucune demande</td></tr>';
		}
		print "</table></form><br>";

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
