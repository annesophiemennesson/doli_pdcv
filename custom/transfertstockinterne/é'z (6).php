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
require_once DOL_DOCUMENT_ROOT.'/custom/transfertstockinterne/class/transfert_produit.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/transfertstockinterne/class/transfert_lot.class.php';

// Load translation files required by the page
$langs->loadLangs(array("transfertstockinterne@transfertstockinterne"));

$action = GETPOST('action', 'aZ09');


// Security check
if (! $user->rights->transfertstockinterne->transfert_stock->create_ramasse) {
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

if ($action == 'add'){
	$qte = GETPOST('qte', 'array');
	$comment = GETPOST('comment', 'array');
	$qtelot = GETPOST('qtelot', 'array');
	$newTransfert = -1;
	$object = new Transfert_stock($db);
	$object->fk_entrepot_depart = $user->fk_warehouse;

	// On recupere l'id du depot
	$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "entrepot WHERE ref = 'Dépôt'";
	$result = $db->query($sql);
	$obj = $db->fetch_object($result);
	$object->fk_entrepot_arrivee = $obj->rowid;
	$object->label = "Ramasse magasin";

	// Pour chacun des produits
	foreach ($qte as $product => $quantite){
		// Si la quantité demandée est > 0
		if (floatval($quantite) > 0){
			// Si le transfert n'existe pas encore on le créé
			if ($newTransfert == -1){
				$newTransfert = $object->create($user);
			}
			// On enregistre les produits dans la demande
			$product_obj = new Transfert_produit($db);
			$product_obj->fk_transfert_stock = $newTransfert;
    		$product_obj->fk_product = intval($product);
    		$product_obj->qte_demande = floatval($quantite);
    		$product_obj->commentaire_demande = $comment[$product];
			$product_obj->create($user);

			foreach ($qtelot[$product] as $lot => $qty){
				if (floatval($qty) > 0){
					$obj_lot = new Transfert_lot($db);
					$obj_lot->fk_transfert_produit = $product_obj->id;
					$obj_lot->fk_product_lot = $lot;
					$obj_lot->qte_valide = floatval($qty);
					$obj_lot->create($user);
				}
			}
		}
	}
	// Envoi email
	include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
	$destinataire = 'annesophie.mennesson@gmail.com';
	// Pour les champs $expediteur / $copie / $destinataire, séparer par une virgule s'il y a plusieurs adresses
	$expediteur = 'annesophie.mennesson@gmail.com';
	$objet = 'Nouvelle demande de ramasse'; // Objet du message    
	$message = 'Une nouvelle demande de ramasse vient d\'être passée par '.$user->prenom.' '.$user->nom.'.<br/>Pensez à la valider !';
	$cmail = new CMailFile($objet, $destinataire, $expediteur, $message, array(), array(), array(), '', '', 0, 1, '', '');
	$result = $cmail->sendfile();

	header('Location: '.dol_buildpath('/custom/transfertstockinterne/transfertstockinterneindex.php?message=new', 1));
    exit();
}


/*
 * View
 */

llxHeader("", "Nouvelle demande de ramasse");

print load_fiche_titre("Nouvelle demande de ramasse", '', '');

print '<div class="fichecenter">';

// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (! empty($conf->transfertstockinterne->enabled) && $user->rights->transfertstockinterne->transfert_stock->create_ramasse)
{
	// Si l'utilisateur n'est pas lié à un magasin
	if (empty($user->fk_warehouse)){
		print '<p><strong>Vous n\'êtes lié à aucun magasin, vous ne pouvez donc faire aucune commande<br/>Effectuez la modification dans votre fiche utilisateur, déconnectez-vous et reconnectez-vous pour réessayer.</strong></p>';
	}else{
		$sql = "SELECT p.ref, p.label, s.fk_product, s.reel AS stock, IFNULL(ABS(SUM(m.value)),0) AS qte_vendue
				FROM ".MAIN_DB_PREFIX."product_stock AS s
				INNER JOIN ".MAIN_DB_PREFIX."product AS p ON (p.rowid = s.fk_product)
				LEFT JOIN ".MAIN_DB_PREFIX."categorie_product AS cp ON (cp.fk_product = p.rowid)
				LEFT JOIN ".MAIN_DB_PREFIX."categorie AS c ON (cp.fk_categorie = c.rowid)
				LEFT JOIN ".MAIN_DB_PREFIX."categorie AS cparent ON (cparent.rowid = c.fk_parent)
				LEFT JOIN ".MAIN_DB_PREFIX."stock_mouvement AS m ON (m.fk_product = p.rowid AND m.label = \"TakePOS\" AND m.fk_entrepot = ".$user->fk_warehouse." and m.tms >= DATE_SUB(m.tms, INTERVAL ".$conf->global->NB_JOURS_STATS_MAGASIN." DAY))
				WHERE s.fk_entrepot = ".$user->fk_warehouse."
				GROUP BY s.fk_product
				ORDER BY cparent.label, c.label, p.label;";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);

			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<th>Libellé produit</th><th>Stock</th><th>Quantité vendue sur '.$conf->global->NB_JOURS_STATS_MAGASIN.'j</th><th>Quantité à ramasser</th><th>Commentaire</th></tr>';
			print '<form action="ramasse_new.php?action=add&&token='.newToken().'" method="post">';
			if ($num > 0)
			{
				$i = 0;
				while ($i < $num)
				{

					$obj = $db->fetch_object($resql);

					$sql2 = "SELECT pl.eatby, pb.batch, qty, ps.fk_product, pl.rowid
							FROM ".MAIN_DB_PREFIX."product_stock AS ps
							INNER JOIN ".MAIN_DB_PREFIX."product_batch AS pb ON (ps.rowid = pb.fk_product_stock)
							INNER JOIN ".MAIN_DB_PREFIX."product_lot AS pl ON (pl.batch = pb.batch)
							WHERE ps.fk_entrepot = ".$user->fk_warehouse." AND ps.fk_product = ".$obj->fk_product;

					$resql2 = $db->query($sql2);
					$num2 = $db->num_rows($resql2);

					print '<tr onclick="$(\'#more_'.$obj->fk_product.'\').toggleClass(\'hidden\');">';
					print '<td><strong>'.$obj->label.'</strong></td>';
					print '<td>'.price2num($obj->stock, 'MS').'</td>';
					print '<td>'.$obj->qte_vendue.'</td>';
					print '<td><input type="number" '.($num2 > 0 ? "readonly" : "").' class="flat" step="0.01" min="0" max="'.$obj->stock.'" name="qte['.$obj->fk_product.']" id="qte_'.$obj->fk_product.'" value="0" required /></td>';
					print '<td><input type="text" class="flat" value="0" id="comment_'.$obj->fk_product.'" name="comment['.$obj->fk_product.']" /></td>';
					print '</tr>';

					if ($num2 > 0){
						print '<tr id="more_'.$obj->fk_product.'"><td colspan="5">';
						print '<table class="noborder centpercent">';
						print '<tr class="liste_titre">';
						print '<th>N° lot</th><th>DLC</th><th>Stock</th><th>Qté à ramasser</th></tr>';
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
							print '<td>'.price2num($obj2->qty, 'MS').'</td>';
							print '<td><input type="number" data-product="'.$obj->fk_product.'" class="qtelot" step="0.01" min="0" max="'.price2num($obj2->qty, 'MS').'" name="qtelot['.$obj2->fk_product.']['.$obj2->rowid.']" value="0" required /></td>';
							print'</tr>';
							$i2++;
						}

						print '</table></td></tr>';
					}



					$i++;
				}
				print '<tr><td colspan="7" class="center"><input class="button" type="submit" value="Valider" /></td></tr>';
			}
			else
			{

				print '<tr class="oddeven"><td colspan="7" class="center">Aucun produit</td></tr>';
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
	function qteTotale( _produit){
		let _total = 0;
		$('#more_'+_produit+' .qtelot').each(function(){
			_total += parseFloat($(this).val());
		});
		return _total;
	}

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
