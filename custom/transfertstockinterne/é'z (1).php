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
require_once DOL_DOCUMENT_ROOT.'/custom/transfertstockinterne/class/transfert_stock.class.php';

// Load translation files required by the page
$langs->loadLangs(array("sendings", "bills", 'deliveries', 'orders'));

$action = GETPOST('action', 'aZ09');

$id = GETPOST('id', 'int');

// Security check
if (! $user->rights->transfertstockinterne->transfert_stock->detail) {
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


include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

if ($action == 'builddoc') {
	$object = new Transfert_stock($db);
	$object->fetch($id);

	$result = $object->generateDocument('standard', $langs);
	if ($result < 0) {
		dol_print_error($db, $result);
	}
}

/*
 * View
 */


llxHeader("", "Détail de la demande de transfert");

print load_fiche_titre("Détail de la demande de transfert", '', '');

print '<div class="fichecenter">';


// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (!empty($id) && ! empty($conf->transfertstockinterne->enabled) && $user->rights->transfertstockinterne->transfert_stock->detail)
{
	$sql = "SELECT s.label, CONCAT(demande.lastname, ' ', demande.firstname) AS user_d, 
				CONCAT(valide.lastname, ' ', valide.firstname) AS user_v, 
				CONCAT(prepa.lastname, ' ', prepa.firstname) AS user_p, 
				CONCAT(reception.lastname, ' ', reception.firstname) AS user_r, date_creation,
				date_valide, date_prepa, date_reception, temperature_depart, temperature_arrivee,
				e.ref as depart, e2.ref as arrivee
			FROM ".MAIN_DB_PREFIX."transfert_stock AS s
			INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e ON (e.rowid = s.fk_entrepot_depart)
			INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e2 ON (e2.rowid = s.fk_entrepot_arrivee)
			INNER JOIN ".MAIN_DB_PREFIX."user AS demande ON (demande.rowid = s.fk_user_demande)
			INNER JOIN ".MAIN_DB_PREFIX."user AS valide ON (valide.rowid = s.fk_user_valide)
			left JOIN ".MAIN_DB_PREFIX."user AS prepa ON (prepa.rowid = s.fk_user_prepa)
			left JOIN ".MAIN_DB_PREFIX."user AS reception ON (reception.rowid = s.fk_user_reception)
			WHERE s.rowid=".$id;
	
	$resql = $db->query($sql);
	$obj = $db->fetch_object($resql);

	print '<h3>'.$obj->label.'</h3>';
	print '<div id="container"><div class="col-xs-6">';
	print '<p>Source: <strong>'.$obj->depart.'</strong></p>';
	
	$formfile = new FormFile($db);
	$objectref = dol_sanitizeFileName($id);
	$filedir = $conf->transfertstockinterne->dir_output."/".$objectref;
	$urlsource = $_SERVER["PHP_SELF"]."?id=".$id;

	print $formfile->showdocuments('transfertstockinterne:transfert_stock', $objectref, $filedir, $urlsource, 1, 0, "standard", 1, 0, 0, 28, 0, '', '', '', '');

	print '</div>';
	print '<div class="col-xs-6">';
	print '<p>Destination: <strong>'.$obj->arrivee.'</strong></p>';
	print '<p>Demandée le '.dol_print_date($obj->date_creation, "%d/%m/%Y %H:%M:%S").' par '.$obj->user_d.'</p>';
	print '<p>Validée le '.dol_print_date($obj->date_valide, "%d/%m/%Y %H:%M:%S").' par '.$obj->user_v.'</p>';
	if (!empty($obj->date_prepa)){
		print '<p>Préparée le '.dol_print_date($obj->date_prepa, "%d/%m/%Y %H:%M:%S").' par '.$obj->user_p.'</p>';
	}
	if (!empty($obj->date_reception)){
		print '<p>Réceptionnée le '.dol_print_date($obj->date_reception, "%d/%m/%Y %H:%M:%S").' par '.$obj->user_r.'</p>';
	}
	if (!empty($obj->temperature_depart)){
		print '<p>Température départ: '.price2num($obj->temperature_depart, 'MS').'°C</p>';
	}
	if (!empty($obj->temperature_arrivee)){
		print '<p>Température arrivée: '.price2num($obj->temperature_arrivee).'°C</p>';
	}	
	print '</div></div>';




	$sql = "SELECT p.label, p.ref, qte_demande, qte_valide, qte_prepa, qte_reception, commentaire_demande,
				commentaire_valide, commentaire_prepa, commentaire_reception, tp.rowid
			FROM ".MAIN_DB_PREFIX."transfert_produit AS tp
			INNER JOIN ".MAIN_DB_PREFIX."product as p ON (tp.fk_product = p.rowid)
			WHERE fk_transfert_stock = ".$id;


	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>Libellé</th><th>Qté demande</th><th>Comm. demande</th><th>Qté validée</th><th>Comm. validation</th><th>Qté préparée</th><th>Comm. prépa</th><th>Qté réceptionnée</th><th>Comm. réception</th></tr>';
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);

				$sql2 = "SELECT tl.rowid, qte_reception, commentaire_prepa, batch, eatby, qte_prepa, commentaire_reception, qte_valide
				FROM ".MAIN_DB_PREFIX."transfert_lot AS tl
				INNER JOIN ".MAIN_DB_PREFIX."product_lot AS pl ON (pl.rowid = tl.fk_product_lot)
				WHERE tl.fk_transfert_produit = ".$obj->rowid;

				$resql2 = $db->query($sql2);
				$num2 = $db->num_rows($resql2);


				print '<tr class="oddeven">';
                print '<td><strong>'.$obj->label.'</strong></td>';
                print '<td>'.$obj->qte_demande.'</td>';
                print '<td>'.$obj->commentaire_demande.'</td>';
                print '<td>'.$obj->qte_valide.'</td>';
                print '<td>'.$obj->commentaire_valide.'</td>';
                print '<td>'.$obj->qte_prepa.'</td>';
                print '<td>'.$obj->commentaire_prepa.'</td>';
                print '<td>'.$obj->qte_reception.'</td>';
                print '<td>'.$obj->commentaire_reception.'</td>';
                print '</tr>';
				if ($num2 > 0){
					print '<t><td colspan="9">';
					print '<table class="noborder centpercent">';
					print '<tr class="liste_titre">';
					print '<th>N° lot</th><th>DLC</th><th>Qté validée</th><th>Qté préparée</th><th>Qté réceptionnée</th><th>Comm. prépa</th><th>Comm. réception</th></tr>';
					$i2 = 0;
					while ($i2 < $num2)
					{
						$obj2 = $db->fetch_object($resql2);
						$dlc = "NC";
						if (!empty($obj2->eatby)){
							$datelimite = new DateTime($obj2->eatby);;
							$dlc = dol_print_date($obj2->eatby, "%d/%m/%Y");
						}
						print '<tr>';
						print '<td>'.$obj2->batch.'</td>';
						print '<td>'.$dlc.'</td>';
						print '<td>'.price2num($obj2->qte_valide, 'MS').'</td>';
						print '<td>'.price2num($obj2->qte_prepa, 'MS').'</td>';
						print '<td>'.price2num($obj2->qte_reception, 'MS').'</td>';
						print '<td>'.$obj2->commentaire_prepa.'</td>';
						print '<td>'.$obj2->commentaire_reception.'</td>';
						print'</tr>';
						$i2++;
					}

					print '</table></td></tr>';
				}
				$i++;
			}
        }
		else
		{

			print '<tr class="oddeven"><td colspan="10" class="opacitymedium">Aucun produit</td></tr>';
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
