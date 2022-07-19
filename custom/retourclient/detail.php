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
require_once DOL_DOCUMENT_ROOT.'/custom/retourclient/class/retourclient.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// Load translation files required by the page
$langs->loadLangs(array("sendings", "bills", 'deliveries', 'orders'));

$action = GETPOST('action', 'aZ09');

$id = GETPOST('id', 'int');

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


include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

if ($action == 'builddoc') {
	$object = new RetourClient($db);
	$object->fetch($id);

	$result = $object->generateDocument('retourclient', $langs);
	if ($result < 0) {
		dol_print_error($db, $result);
	}
}elseif ($action == "change"){    
    /*$statut = GETPOST("statut", 'alpha');
    $commentaire = GETPOST("commentaire");
	$destination = GETPOST("destination");

	$object = new DemandeAvoir($db);
	$object->fetch($id);
    $object->statut = $statut;
    $object->commentaire = "[".dol_print_date($now, "%d/%m/%Y %H:%M:%S")."] ".$commentaire. " (produits: ".$destination.")";
    $object->update($user);	

	$object->loadProduits();

	$sql2 = "SELECT rowid FROM ".MAIN_DB_PREFIX."entrepot WHERE ref = 'Rebut'";
	$res2 = $db->query($sql2);
	$obj = $db->fetch_object($res2);
	$entrepot_rebut = $obj->rowid;


	foreach ($object->lines as $prod){
		$produit = new Product($db);
		$result = $produit->fetch($prod['fk_product']);
		
		if (empty($prod['batch'])){
			// Remove stock
			$result1 = $produit->correct_stock(
				$user,
				$entrepot_rebut,
				$prod['qty'],
				1,
				$destination,
				0,
				""
			);
		}else{
			// Remove stock
			$result1 = $produit->correct_stock_batch(
				$user,
				$entrepot_rebut,
				$prod['qty'],
				1,
				$destination,
				0,
				"",
				"",
				$prod['batch'],
				""
			);
		}
	}*/

}

/*
 * View
 */


llxHeader("", "Détail du retour client");

print load_fiche_titre("Détail du retour client", '', '');

print '<div class="fichecenter">';


// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (!empty($id) && ! empty($conf->retourclient->enabled) /*&& $user->rights->retourclient->retourclient->read*/)
{
    $sql = "SELECT fk_facture, r.statut, montant_ht, montant_tva, montant_ttc, mode_remboursement, date_creation, CONCAT(lastname, \" \", firstname) AS user
			FROM ".MAIN_DB_PREFIX."retourclient AS r
			INNER JOIN ".MAIN_DB_PREFIX."user AS u ON (u.rowid = r.fk_user_crea)
            WHERE r.rowid = ".$id;
	
	$resql = $db->query($sql);
	$obj = $db->fetch_object($resql);
	$object = new RetourClient($db);
	$object->fetch($id);
	$object->fetchObjectLinked();

	print '<h3>Retour client n°'.$id.'</h3>';
	print '<div id="container"><div class="col-xs-6">';    
	print '<p>Enregistrée le '.dol_print_date($obj->date_creation, "%d/%m/%Y %H:%M:%S").' par '.$obj->user.'</p>';
    print '<form action="detail.php?action=change&id='.$id.'&&token='.newToken().'" method="post">';
    print '<p>Montant HT: '.price2num($obj->montant_ht, 'MS').'</p>';
	print '<p>Montant TVA: '.price2num($obj->montant_tva, 'MS').'</p>';
	print '<p>Montant TTC: '.price2num($obj->montant_ttc, 'MS').'</p>';
	print '<p>Statut: ';
	if ($obj->statut == "ouverte"){
		print '<select name="statut">';
		print '<option selected value="ouverte">Ouverte</option>';
		print '<option value="validée">Validée</option>';
		print '<option value="remboursée">Remboursée</option>';
		print '</select></p>';
		print '<p>Mode de rembousement: <select name="remboursement"><option value="espèces">Espèces</option><option value="cb">CB</option></select>';
		//if ($user->rights->retourclient->retourclient->write){
			print '<p><button type="submit" class="butAction">Changer</button></p>';
		//}
	}else{
		print $obj->statut.'</p>';
		print '<p>Mode de remboursement: '.$obj->mode_remboursement.'</p>';
	}
    print '</form>';
    print '</p>'; 
	print '</div>';
	print '<div class="col-xs-6">';	
	$formfile = new FormFile($db);
	$objectref = dol_sanitizeFileName($id);
	$filedir = $conf->retourclient->dir_output."/".$objectref;
	$urlsource = $_SERVER["PHP_SELF"]."?id=".$id;
	print $formfile->showdocuments('retourclient:retourclient', $objectref, $filedir, $urlsource, 1, 0, "retourclient", 1, 0, 0, 28, 0, '', '', '', '');
	
	$form = new Form($db);
	$somethingshown = $form->showLinkedObjectBlock($object, '');

	print '</div></div>';

    $sql = "SELECT d.fk_product, qty, commentaire, destination, batch, p.label, montant_ht, taux_tva, total_ht, total_tva, total_ttc, ef.uniteachat
            FROM ".MAIN_DB_PREFIX."retourclientdet as d
            INNER JOIN ".MAIN_DB_PREFIX."product AS p ON (p.rowid = d.fk_product)
			LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as ef on (p.rowid = ef.fk_object) 
            WHERE fk_retourclient =".$id;

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>Produit</th><th>Quantité</th><th>PU HT</th><th>Total HT</th><th>Taux TVA</th><th>Total TVA</th><th>Total TTC</th><th>Destination</th><th>Commentaire</th></tr>';
		if ($num > 0)
		{
			// On recupère les valeurs pour les unités achat vente
			$sqlua = "SELECT param FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'product' AND name = 'uniteachat'";
			$resqlua = $db->query($sqlua);
			$objua = $db->fetch_object($resqlua);
			$ua = jsonOrUnserialize($objua->param)['options'];

			$i = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				$valua = $ua[$obj->uniteachat];
				

				print '<tr class="oddeven">';
				print '<td><a target="_blank" href="'.dol_buildpath('/product/card.php?id='.$obj->fk_product, 1).'"><strong>'.$obj->label.'</strong>';
				if (!empty($obj->batch)){
					print ' #'.$obj->batch;
				}
				print ' ('.$valua.')</a></td>';
                print '<td>'.$obj->qty.'</td>';
                print '<td>'.price2num($obj->montant_ht, 'MS').'</td>';
                print '<td>'.price2num($obj->total_ht, 'MS').'</td>';
                print '<td>'.price2num($obj->taux_tva, 'MS').'%</td>';
                print '<td>'.price2num($obj->total_tva, 'MS').'</td>';
                print '<td>'.price2num($obj->total_ttc, 'MS').'</td>';
                print '<td>'.$obj->destination.'</td>';
                print '<td>'.$obj->commentaire.'</td>';
                print '</tr>';
				$i++;
			}
        }
		else
		{

			print '<tr class="oddeven"><td colspan="8" class="opacitymedium">Aucun produit</td></tr>';
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
