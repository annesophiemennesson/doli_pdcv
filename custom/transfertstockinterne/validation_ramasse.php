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

require_once DOL_DOCUMENT_ROOT.'/custom/transfertstockinterne/class/transfert_stock.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/transfertstockinterne/class/transfert_produit.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/transfertstockinterne/class/transfert_lot.class.php';

// Load translation files required by the page
$langs->loadLangs(array("transfertstockinterne@transfertstockinterne"));

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');


// Security check
if (! $user->rights->transfertstockinterne->transfert_stock->valid) {
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
if ($action == 'valid'){
	$qte = GETPOST('qte', 'array');
	$comment = GETPOST('comment', 'array');
	$qtelot = GETPOST('qtelot', 'array');

	// Pour chacun des produits
	foreach ($qte as $product => $quantite){
		$obj_produit = new Transfert_produit($db);
		$obj_produit->fetch($product);
		$obj_produit->qte_valide = floatval($quantite);
		$obj_produit->commentaire_valide = $comment[$product];
		$obj_produit->update($user);

		foreach ($qtelot[$product] as $lot => $qty){
			$obj_lot = new Transfert_lot($db);
			$obj_lot->fetch($lot);
			$obj_lot->qte_valide = floatval($qty);
			$obj_produit->update($user);
		}
	}

	$object = new Transfert_stock($db);
	$object->fetch($id);
	$object->fk_user_valide = $user->id;
	$object->date_valide = $db->idate(dol_now());
	$object->update($user);
	
	header('Location: '.dol_buildpath('/custom/transfertstockinterne/transfertstockinterneindex.php?message=valid', 1));
    exit();
}

/*
 * View
 */

llxHeader('', "Demande de ramasse à valider");

print load_fiche_titre("Demande de ramasse à valider", '', '');

print '<div class="fichecenter">';


// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (! empty($conf->transfertstockinterne->enabled) && $user->rights->transfertstockinterne->transfert_stock->valid)
{
	
	$today = new DateTime();
	$sql = "SELECT p.label, s.fk_product, s.reel AS stock, qte_demande, qte_valide, tp.rowid, commentaire_demande, commentaire_valide, t.fk_entrepot_depart, ef.uniteachat
			FROM ".MAIN_DB_PREFIX."transfert_stock AS t
			INNER JOIN ".MAIN_DB_PREFIX."transfert_produit AS tp ON (tp.fk_transfert_stock = t.rowid) 
			INNER JOIN ".MAIN_DB_PREFIX."product AS p ON (p.rowid = tp.fk_product) 
			INNER JOIN ".MAIN_DB_PREFIX."product_stock AS s ON (s.fk_product = p.rowid AND s.fk_entrepot = t.fk_entrepot_depart)
			LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as ef on (p.rowid = ef.fk_object) 
			WHERE t.rowid = ".$id."
			GROUP BY s.fk_product
			ORDER BY p.label;";
	
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		print '<form action="validation_ramasse.php?action=valid&id='.$id.'&&token='.newToken().'" method="post">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>Libellé produit</th><th>Stock magasin</th><th>Qté demandée</th><th>Comm. demande</th><th>Qté validée</th><th>Comm. validation</th></tr>';
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

				$sql2 = "SELECT tl.rowid, pl.eatby, qty, pb.batch, qte_valide, ps.fk_product, pl.rowid
						FROM ".MAIN_DB_PREFIX."transfert_lot AS tl
						INNER JOIN ".MAIN_DB_PREFIX."product_lot AS pl ON (pl.rowid = tl.fk_product_lot)
						INNER JOIN ".MAIN_DB_PREFIX."product_batch AS pb ON (pb.batch = pl.batch)
						INNER JOIN ".MAIN_DB_PREFIX."product_stock AS ps ON (ps.rowid = pb.fk_product_stock)
						WHERE tl.fk_transfert_produit = ".$obj->rowid." AND ps.fk_entrepot = ".$obj->fk_entrepot_depart;
		
				$resql2 = $db->query($sql2);
				$num2 = $db->num_rows($resql2);

				print '<tr onclick="$(\'#more_'.$obj->fk_product.'\').toggleClass(\'hidden\');">';
                print '<td><strong>'.$obj->label.'</strong> ('.$valua.')</td>';
                print '<td>'.price2num($obj->stock, 'MS').'</td>';
                print '<td>'.$obj->qte_demande.'</td>';
                print '<td>'.$obj->commentaire_demande.'</td>';
                print '<td><input type="number" '.($num2 > 0 ? "readonly" : "").' class="flat" step="0.01" min="0" max="'.$obj->stock.'" name="qte['.$obj->rowid.']" id="qte_'.$obj->fk_product.'" value="'.(!empty($obj->qte_valide) ? $obj->qte_valide : $obj->qte_demande).'" required /></td>';
                print '<td><input type="text" class="flat" value="'.$obj->commentaire_valide.'" id="comment_'.$obj->fk_product.'" name="comment['.$obj->rowid.']" /></td>';
                print '</tr>';

				if ($num2 > 0){
					print '<tr id="more_'.$obj->fk_product.'" class="more_'.$obj->fk_product.' '.($obj->qte_valide > 0 ? 'hidden' : '').'"><td colspan="6">';
					print '<table class="noborder centpercent">';
					print '<tr class="liste_titre">';
					print '<th>N° lot</th><th>DLC</th><th>Qté validée</th></tr>';
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
						print '<td><input type="number" data-id="'.$obj2->rowid.'" data-product="'.$obj->fk_product.'" class="qtelot" step="0.01" min="0" max="'.price2num($obj2->qty, 'MS').'" name="qtelot['.$obj2->rowid.']" value="'.price2num($obj2->qte_valide, 'MS').'" required /></td>';
						print'</tr>';
						$i2++;
					}

					print '</table></td></tr>';
				}
				$i++;
			}
			print '<tr><td class="center" colspan="6"><input class="button" type="submit" value="Valider" /></td></tr>';
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
print '</div>';?>

<script>
    $('.qtelot').on('change', function(){
		let qte = parseFloat($(this).val());
        let qte_valide = parseFloat($(this).attr('max'));
        let _product = $(this).data('product');
        if (qte < 0 || isNaN(qte) || qte > qte_valide){
            alert(" ERREUR: La quantité demandée ne peut être inférieure à 0 ni supérieure à la quantité en stock");
            $(this).val("0");
        }
        let qte_tot = qteTotale(_product);
        $('#qte_'+_product).val(qte_tot);      
	});
</script>
<?php
// End of page
llxFooter();
$db->close();
