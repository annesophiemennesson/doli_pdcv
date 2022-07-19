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
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/retourclient/class/retourclient.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');


// Security check
/*if (! $user->rights->retourclient->retourclient->create) {
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

if ($action == 'add'){
	$qte = GETPOST('qte', 'array');
	$comment = GETPOST('comment', 'array');
	$destination = GETPOST('destination', 'array');
	$pu = GETPOST('pu', 'array');
	$tva = GETPOST('tva', 'array');
	$object = new RetourClient($db);
	$object->fk_facture = $id;
	$id_retour = $object->create($user);

	$montant_ht = 0;
	$montant_tva = 0;
	$montant_ttc = 0;

	$sql2 = "SELECT rowid FROM ".MAIN_DB_PREFIX."entrepot WHERE ref = 'Retour client'";
	$res2 = $db->query($sql2);
	$obj = $db->fetch_object($res2);
	$entrepot = $obj->rowid;

	// Pour chacun des produits
	foreach ($qte as $product => $quantite){
		// Si la quantité demandée est > 0
		if (floatval($quantite) > 0){
			$id_produit = explode('_', $product)[0];
			$batch = explode('_', $product)[1];
			// On enregistre les produits dans la demande
			$obj_ligne = new RetourClientLine($db);
			$obj_ligne->fk_retourclient = $id_retour;
    		$obj_ligne->fk_product = intval($id_produit);
    		$obj_ligne->qty = floatval($quantite);
    		$obj_ligne->batch = $batch;
    		$obj_ligne->commentaire = $comment[$product];
    		$obj_ligne->destination = $destination[$product];
    		$obj_ligne->montant_ht = $pu[$product];
    		$obj_ligne->taux_tva = $tva[$product];

			$total_ht = floatval($quantite * $pu[$product]);
			$total_tva = floatval($total_ht * $tva[$product] / 100);
			$total_ttc = floatval($total_tva  + $total_ht);

    		$obj_ligne->total_ht = $total_ht;
    		$obj_ligne->total_tva = $total_tva;
    		$obj_ligne->total_ttc = $total_ttc;
	
			$obj_ligne->create($user);

			$montant_ht += $total_ht;
			$montant_tva += $total_tva;
			$montant_ttc += $total_ttc;

			$prod = new Product($db);
			$result = $prod->fetch($id_produit);

			if (empty($batch)){
				// Add stock
				$result2 = $prod->correct_stock(
					$user,
					$entrepot,
					floatval($quantite),
					0,
					"Retour client",
					0,
					""
				);
			}else{
				// Add stock
				$result2 = $prod->correct_stock_batch(
					$user,
					$entrepot,
					floatval($quantite),
					0,
					"Retour client",
					0,
					"",
					"",
					$batch,
					""
				);
			}
		}
	}

	$object->montant_ht = $montant_ht;
	$object->montant_tva = $montant_tva;
	$object->montant_ttc = $montant_ttc;
	$object->update($user);


}


/*
 * View
 */

llxHeader("", "Enregistrer un retour");

print load_fiche_titre("Enregistrer un retour", '', '');

print '<div class="fichecenter">';

// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (!empty($id) && ! empty($conf->retourclient->enabled) /*&& $user->rights->transfertstockinterne->transfert_stock->create*/)
{
	// Si l'utilisateur n'est pas lié à un magasin
	if (empty($user->fk_warehouse)){
		print '<p><strong>Vous n\'êtes lié à aucun magasin, vous ne pouvez donc enregistrer aucune retour</strong></p>';
	}else{
		$object = new Facture($db);
		$object->fetch($id);
		$object->fetchObjectLinked();

		print '<h3>Retour sur la facture '.$object->ref.'</h3>';
		print '<div id="container"><div class="col-xs-6">';

		$form = new Form($db);
		$somethingshown = $form->showLinkedObjectBlock($object, '');
		print '</div></div>';


		$sql = "SELECT p.label, f.tva_tx, f.qty, f.total_ht/f.qty AS pu, ef.uniteachat, f.fk_product, ifnull(SUM(d.qty),0) AS qte_ret, f.batch
				FROM ".MAIN_DB_PREFIX."facturedet AS f 
				INNER JOIN ".MAIN_DB_PREFIX."product AS p ON (p.rowid = f.fk_product) 
				LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as ef on (p.rowid = ef.fk_object) 
				LEFT JOIN ".MAIN_DB_PREFIX."retourclient AS r ON (r.fk_facture = f.fk_facture)
				LEFT JOIN ".MAIN_DB_PREFIX."retourclientdet AS d ON (d.fk_retourclient = r.rowid AND d.fk_product = p.rowid AND f.batch = d.batch)
				WHERE f.fk_facture = ".$id."
				GROUP BY f.batch, p.rowid";
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);	

			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<th>Libellé produit</th><th>Qté totale (qté déjà retournée)</th><th>Qté retournée</th><th>Destination</th><th>Commentaire</th><th>PU HT (remisé)</th></tr>';
			print '<form action="new.php?id='.$id.'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'" />';
			print '<input type="hidden" name="action" value="add" />';
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
					print '<tr class="'.($i%2 == 0 ? 'pair' : 'impair').'">';
					print '<td><strong>'.$obj->label.'</strong>';
					if (!empty($obj->batch)){
						print ' #'.$obj->batch;
					}
					print ' ('.$valua.')</td>';
					print '<td>'.price2num($obj->qty, 'MS').' ('.price2num($obj->qte_ret, 'MS').')</td>';
					print '<td><input class="qte" type="number" class="flat" step="0.01" min="0" max="'.price2num($obj->qty - $obj->qte_ret, 'MS').'" name="qte['.$obj->fk_product.'_'.$obj->batch.']" value="0" required /></td>';
					print '<td><select name="destination['.$obj->fk_product.'_'.$obj->batch.']"><option value="remise en stock depot">Remise en stock dépôt</option><option value="remise en stock magasin">Remise en stock magasin</option><option value="destruction">Destruction</option></select></td>';
					print '<td><input class="comment" type="text" class="flat" value="" name="comment['.$obj->fk_product.'_'.$obj->batch.']" id="comment'.$obj->fk_product.'_'.$obj->batch.'" /></td>';
					print '<td><input type="text" readonly value="'.price2num($obj->pu, 'MS').'" name="pu['.$obj->fk_product.'_'.$obj->batch.']" /></td>';
					print '<input type="hidden" value="'.price2num($obj->tva_tx, 'MS').'" name="tva['.$obj->fk_product.'_'.$obj->batch.']" />';
					print '</tr>';
					$i++;
				}
				print '<tr><td colspan="5" class="center"><input class="button" type="submit" value="Valider" /></td></tr>';
			}
			else
			{

				print '<tr class="oddeven"><td colspan="5" class="center">Aucun produit</td></tr>';
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


print '</div>'; ?>
<script>
	$(document).ready(function(){
		$('.qte').on('change', function(){
			let _name = $(this).attr('name');
			let _max = $(this).attr('max');
			let _val = parseFloat($(this).val());
			let _attr = _name.replace('qte', 'comment').replace('[', '').replace(']', '');
			if (isNaN(_val) || _val > _max){
				$(this).val(0);
				$('#'+_attr).removeAttr('required');
				alert("ERREUR: La quantité doit être un entier <= "+_max);
			}else{
				if (_val > 0){
					$('#'+_attr).attr('required', '');
				}else{
					$('#'+_attr).removeAttr('required');
				}
			}
		});
	});
</script>
<?php
 // End of page
llxFooter();
$db->close();
