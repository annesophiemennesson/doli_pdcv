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
require_once DOL_DOCUMENT_ROOT.'/custom/gestionrebuts/class/demandeavoir.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

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


include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

if ($action == 'builddoc') {
	$object = new DemandeAvoir($db);
	$object->fetch($id);

	$result = $object->generateDocument('demandeavoir', $langs);
	if ($result < 0) {
		dol_print_error($db, $result);
	}
}elseif ($action == "change"){    
    $statut = GETPOST("statut", 'alpha');
    $commentaire = GETPOST("commentaire");
	$destination = GETPOST("destination");

	$object = new DemandeAvoir($db);
	$object->fetch($id);
    $object->statut = $statut;
    $object->commentaire = $commentaire. " (produits: ".$destination.")";
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
if (!empty($id) /*&& ! empty($conf->transfertstockinterne->enabled) && $user->rights->transfertstockinterne->transfert_stock->detail*/)
{
    $sql = "SELECT a.fk_reception, a.commentaire, a.statut, r.ref, a.date_creation, CONCAT(lastname, \" \", firstname) AS user, nom
            FROM ".MAIN_DB_PREFIX."demande_avoir AS a
            INNER JOIN ".MAIN_DB_PREFIX."user AS u ON (u.rowid = a.fk_user)
            INNER JOIN ".MAIN_DB_PREFIX."reception AS r ON (a.fk_reception = r.rowid)
            INNER JOIN ".MAIN_DB_PREFIX."societe AS s ON (r.fk_soc = s.rowid)
            WHERE a.rowid = ".$id;
	
	$resql = $db->query($sql);
	$obj = $db->fetch_object($resql);

	print '<h3>Demande d\'avoir n°'.$id.'</h3>';
	print '<div id="container"><div class="col-xs-6">';    
	print '<p>Faite le '.dol_print_date($obj->date_creation, "%d/%m/%Y %H:%M:%S").' par '.$obj->user.'</p>';
    print '<p>Fournisseur: '.$obj->nom.', Réception: <a href="'.dol_buildpath('/reception/card.php?id='.$obj->fk_reception, 1).'" target="_blank">'.$obj->ref.'</a></p>';
    print '<form action="detail.php?action=change&id='.$id.'&&token='.newToken().'" method="post">';
    print '<p>Statut: ';
	if ($obj->statut == "ouverte"){
		print '<select name="statut">';
		print '<option '.($obj->statut == "ouverte" ? "selected" : "").' value="ouverte">Ouverte</option>';
		print '<option '.($obj->statut == "validée" ? "selected" : "").' value="validée">Validée</option>';
		print '<option '.($obj->statut == "annulée" ? "selected" : "").' value="annulée">Annulée</option>';
		print '</select></p>';
		print '<p>Commentaire: <input required type="text" name="commentaire" value="'.$obj->commentaire.'" /></p>';
		print '<p>Destination des produits: <select name="destination"><option value="retour producteur">Retour fournisseur</option><option value="destruction">Destruction</option></select>';
		print '<p><button type="submit" class="butAction">Changer</button></p>';
	}else{
		print $obj->statut.'</p>';
		print '<p>Commentaire: '.$obj->commentaire.'</p>';
	}
    print '</form>';
    print '</p>'; 
	print '</div>';
	print '<div class="col-xs-6">';	
	$formfile = new FormFile($db);
	$objectref = dol_sanitizeFileName($id);
	$filedir = $conf->gestionrebuts->dir_output."/".$objectref;
	$urlsource = $_SERVER["PHP_SELF"]."?id=".$id;
	print $formfile->showdocuments('gestionrebuts:demandeavoir', $objectref, $filedir, $urlsource, 1, 0, "demandeavoir", 1, 0, 0, 28, 0, '', '', '', '');
	print '</div></div>';

    $sql = "SELECT fk_product, qty, d.price, commentaire, eatby, batch, label
            FROM ".MAIN_DB_PREFIX."demande_avoirdet as d
            INNER JOIN ".MAIN_DB_PREFIX."product AS p ON (p.rowid = d.fk_product)
            WHERE fk_demande_avoir =".$id;


	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>Libellé</th><th>Quantité</th><th>N° lot</th><th>DLC</th><th>PU HT</th><th>Commentaire</th></tr>';
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven">';
				print '<td><a target="_blank" href="'.dol_buildpath('/product/card.php?id='.$obj->fk_product, 1).'"><strong>'.$obj->label.'</strong></a></td>';
                print '<td>'.$obj->qty.'</td>';
                print '<td>'.$obj->batch.'</td>';
                print '<td>'.dol_print_date($obj->eatby, "%d/%m/%Y").'</td>';
                print '<td>'.price2num($obj->price, 'MS').'</td>';
                print '<td>'.$obj->commentaire.'</td>';
                print '</tr>';
				$i++;
			}
        }
		else
		{

			print '<tr class="oddeven"><td colspan="6" class="opacitymedium">Aucun produit</td></tr>';
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
