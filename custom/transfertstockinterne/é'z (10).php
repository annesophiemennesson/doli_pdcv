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


/*
 * View
 */

llxHeader('', "Liste des demandes de transfert à valider");

print load_fiche_titre("Liste des demandes de transfert à valider", '', '');

print '<div class="fichecenter">';


// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (! empty($conf->transfertstockinterne->enabled) && $user->rights->transfertstockinterne->transfert_stock->valid)
{
	
	$today = new DateTime();
	$sql = "SELECT p.ref, p.label, s.fk_product, s.reel AS stock_depot, 
				ifnull(SUM(qte_demande),0) AS qte_dmd, ifnull(SUM(qte_valide),0) AS qte_val
			FROM ".MAIN_DB_PREFIX."product_stock AS s 
			INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e ON (e.rowid = s.fk_entrepot) 
			INNER JOIN ".MAIN_DB_PREFIX."product AS p ON (p.rowid = s.fk_product) 
			LEFT JOIN ".MAIN_DB_PREFIX."categorie_product AS cp ON (cp.fk_product = p.rowid) 
			LEFT JOIN ".MAIN_DB_PREFIX."categorie AS c ON (cp.fk_categorie = c.rowid) 
			LEFT JOIN ".MAIN_DB_PREFIX."categorie AS cparent ON (cparent.rowid = c.fk_parent) 
			LEFT JOIN ".MAIN_DB_PREFIX."transfert_stock AS t ON ( t.fk_entrepot_depart = e.rowid AND t.date_valide IS NULL) 
			LEFT JOIN ".MAIN_DB_PREFIX."transfert_produit AS tp ON (tp.fk_product = s.fk_product AND tp.fk_transfert_stock = t.rowid) 
			WHERE e.ref = \"Dépôt\"
			GROUP BY s.fk_product
			ORDER BY SUM(qte_demande) desc, SUM(qte_valide), cparent.label, c.label, p.label;";
	
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>Libellé produit</th><th>Stock dispo (dépôt)</th><th>Qté totale demandée</th></tr>';
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				
				print '<tr id="produit_'.$obj->fk_product.'" class="'.($obj->qte_dmd == 0 ? 'blue' : ($obj->qte_val > 0 ? 'green' : '')).'" onclick="$(\'#prod_'.$obj->fk_product.'\').toggleClass(\'hidden\');">';
                print '<td><strong>'.$obj->label.'</strong></td>';
                print '<td>'.price2num($obj->stock_depot, 'MS').'</td>';
                print '<td>'.$obj->qte_dmd.'</td>';
                print '</tr>';

				$sql2 = "SELECT e.rowid, e.ref, qte_demande, IFNULL(qte_valide,0) as qte_valide, commentaire_demande, IFNULL(ABS(SUM(m.value)),0) AS qte_vendue, ifnull(SUM(ps.reel), 0) AS stock_magasin, ts.rowid as transfert_stock, tp.rowid as transfert_produit, commentaire_valide 
						FROM ".MAIN_DB_PREFIX."entrepot AS e
						LEFT JOIN ".MAIN_DB_PREFIX."transfert_stock AS ts ON (ts.fk_entrepot_arrivee = e.rowid AND ts.date_valide IS NULL)
						LEFT JOIN ".MAIN_DB_PREFIX."transfert_produit AS tp ON (tp.fk_transfert_stock = ts.rowid AND tp.fk_product = ".$obj->fk_product.")
            			LEFT JOIN ".MAIN_DB_PREFIX."stock_mouvement AS m ON (m.fk_product = tp.fk_product AND m.label = \"TakePOS\" AND m.fk_entrepot = e.rowid and m.tms >= DATE_SUB(m.tms, INTERVAL ".$conf->global->NB_JOURS_STATS_MAGASIN." DAY))
						LEFT JOIN ".MAIN_DB_PREFIX."product_stock ps ON (ps.fk_product = ".$obj->fk_product." AND ps.fk_entrepot = e.rowid)
						WHERE e.statut = 1 AND e.ref != \"Dépôt\"
						group by e.rowid;";

				$resql2 = $db->query($sql2);

				$listeEntrepot = array();

				// Recup des lots
				$sql3 = "SELECT pb.eatby, pb.batch, qty, ps.fk_product, pl.rowid
						FROM ".MAIN_DB_PREFIX."product_stock AS ps
						INNER JOIN ".MAIN_DB_PREFIX."product_batch AS pb ON (ps.rowid = pb.fk_product_stock)
						INNER JOIN ".MAIN_DB_PREFIX."entrepot as e ON (e.rowid = ps.fk_entrepot)
						INNER JOIN ".MAIN_DB_PREFIX."product_lot AS pl ON (pl.batch = pb.batch)
						WHERE e.ref = 'Dépôt' AND ps.fk_product = ".$obj->fk_product;
				
				$resql3 = $db->query($sql3);
				$num3 = $db->num_rows($resql3);

				print '<tr class="'.($obj->qte_dmd == 0 || $obj->qte_val > 0 ? 'hidden': '').'" id="prod_'.$obj->fk_product.'"><td colspan="3"><table class="noborder centpercent">';
				print '<tr class="liste_titre">';
				print '<th>Magasin</th><th>Stock magasin</th><th>Qté vendue sur '.$conf->global->NB_JOURS_STATS_MAGASIN.'j</th><th>Qté demandée</th><th>Comm. demande</th><th>Qté validée</th><th>Comm. validation</th></tr>';
				print '<form action="#" id="form_'.$obj->fk_product.'">';
				print '<input type="hidden" name="token" value="'.newToken().'" />';
				print '<input type="hidden" name="id_produit" value="'.$obj->fk_product.'" />';
				print '<input type="hidden" id="stock_'.$obj->fk_product.'" name="stock" value="'.price2num($obj->stock_depot, 'MS').'" />';
				if ($resql2)
				{
					$num2 = $db->num_rows($resql2);
					$i2 = 0;
					while ($i2 < $num2)
					{
						$obj2 = $db->fetch_object($resql2);
						$listeEntrepot['ref'][$obj2->rowid] = $obj2->ref;
						$listeEntrepot['qte'][$obj2->rowid] = price2num($obj2->qte_demande, 'MS');
						print '<tr class="'.($obj2->qte_valide > 0 ? 'green': '').'">';
						print '<td>'.$obj2->ref.'</td>';
						print '<td>'.price2num($obj2->stock_magasin, 'MS').'</td>';
						print '<td>'.$obj2->qte_vendue.'</td>';
						print '<td>'.price2num($obj2->qte_demande, 'MS').'</td>';
						print '<td>'.$obj2->commentaire_demande.'</td>';
						print '<td><input type="number" '.($num3 > 0 ? "readonly" : "").' class="flat quantite_'.$obj->fk_product.'" id="quantite_'.$obj->fk_product.'_'.$obj2->rowid.'" step="0.01" min="0" max="'.price2num($obj->stock_depot, 'MS').'" name="qte['.$obj2->rowid.']" value="'.($obj2->qte_valide > 0 ? price2num($obj2->qte_valide, 'MS') : ($obj->stock_depot >= $obj->qte_dmd ? price2num($obj2->qte_demande, 'MS') : "0")).'" required /></td>';
                		print '<td><input type="text" class="flat" value="'.$obj2->commentaire_valide.'" name="comment['.$obj2->rowid.']" /></td>';
						print '<input type="hidden" name="ts['.$obj2->rowid.']" value="'.$obj2->transfert_stock.'" />';
						print '<input type="hidden" name="tp['.$obj2->rowid.']" value="'.$obj2->transfert_produit.'" />';
						print '</tr>';
						$i2++;
					}
				}
				print '<tr><td colspan="7" class="center">';
				if ($num3 > 0){ // Numéros de lot
					print '<input onclick="openModal('.$obj->fk_product.')" class="button" type="button" value="Sélectionner les lots" />';
				}
				print '<input id="valideproduit_'.$obj->fk_product.'" onclick="valideProduit('.$obj->fk_product.')" '.($num3 > 0 ? "disabled" : "").' class="button" type="button" value="Valider" />';
				print '</td></tr>';
				if ($num3 > 0){
					print '<tr><td colspan="7"><div id="modal_'.$obj->fk_product.'" class="modal">';
						print '<div class="modal-content">';
							print '<span class="close">&times;</span>';
							print '<table class="noborder centpercent">';
							print '<tr class="liste_titre">';
							print '<th>Libellé produit</th><th>Stock dispo (dépôt)</th><th>Qté totale demandée</th></tr>';
							print '<tr>';
							print '<td><strong>'.$obj->label.'</strong></td>';
							print '<td class="stock_depot">'.price2num($obj->stock_depot, 'MS').'</td>';
							print '<td class="total_dmd">'.price2num($obj->qte_dmd, 'MS').'</td></tr></table>';
							print '<table class="noborder centpercent">';
							print '<tr class="liste_titre">';
							$nbCol = 0;
							foreach ($listeEntrepot['ref'] as $id => $ref){
								$value = $listeEntrepot['qte'][$id];
								$class = "";
								if (($num3 == 1 && $obj->stock_depot >= $obj->qte_dmd) || $listeEntrepot['qte'][$id] == 0){ // si il y a un seul lot
									$value = 0;
									$class ="label_ok";
								}
								print '<th>'.$ref.'<br/>Qté: <p class="label '.$class.'"><span id="entrepotqt_'.$obj->fk_product.'_'.$id.'">'.$value.'</span>/<span id="entrepotmax_'.$obj->fk_product.'_'.$id.'">'.$listeEntrepot['qte'][$id].'</span></p></th>';
								$nbCol++;
							}
							print '<th>Lot</th></tr>';
							$nbCol++;
							$i3 = 0;
							while ($i3 < $num3)
							{
								$obj3 = $db->fetch_object($resql3);
								$dlc = "NC";
								$nbJours = "NC";
								$class2 = "";
								if (!empty($obj3->eatby)){
									$datelimite = new DateTime($obj3->eatby);;
									$dlc = dol_print_date($obj3->eatby, "%d/%m/%Y");
									$date = $today->diff(new DateTime( $obj3->eatby));
									$nbJours = "J - ".$date->days;
									if ($date->days > 10){
										$class2 = "label_ok";
									}elseif ($date->days > 5){
										$class2 = "label_nok";
									}else{
										$class2 = "label_ko";
									}
								}
								print '<tr>';
								foreach ($listeEntrepot['ref'] as $id => $ref){
									$value = 0;
									if ($num3 == 1 && $obj->stock_depot >= $obj->qte_dmd){ // si il y a un seul lot
										$value = $listeEntrepot['qte'][$id];
										//($obj2->qte_valide > 0 ? price2num($obj2->qte_valide, 'MS') : ($obj->stock_depot >= $obj->qte_dmd ? price2num($obj2->qte_demande, 'MS') : "0"));
									}
									print '<td><input type="number" data-batch="'.$obj3->batch.'" data-product="'.$obj->fk_product.'" data-entrepot="'.$id.'" id="qte_lot_'.$obj->fk_product.'_'.$obj3->batch.'_'.$id.'" class="qtelot" step="0.01" min="0" max="'.price2num($obj3->qty, 'MS').'" name="qtelot['.$id.']['.$obj3->rowid.']" value="'.$value.'" required /></td>';
								}

								$delta = price2num($obj3->qty, 'MS');
								$class = "";
								if ($num3 == 1 && $obj->stock_depot >= $obj->qte_dmd){ // si il y a un seul lot
									$delta = price2num($obj3->qty - $obj->qte_dmd, 'MS');
									$class ="label_ok";
								}
								
								print '<td>Stock: <p class="label '.$class.'"><span id="lotqt_'.$obj->fk_product.'_'.$obj3->batch.'">'.$delta.'</span>/<span id="maxqt_'.$obj->fk_product.'_'.$obj3->batch.'">'.price2num($obj3->qty, 'MS').'</span></p>';
								if ($dlc != 'NC'){
									print '<br/>'.$dlc.'<br/><p class="label '.$class2.'">'.$nbJours.'</p>';
								}
								print '<br/>#'.$obj3->batch.'</td>';
								print '</tr>';
								$i3++;
							}
							print '<tr><td class="center" colspan="'.$nbCol.'"><input onclick="valideLot('.$obj->fk_product.')" type="button" class="button" value="Valider" '.(($num3 == 1 && $obj->stock_depot >= $obj->qte_dmd ? "" : "disabled")).' /></td></tr>';
							print '</table>';
						print '</div>';
					print '</div></td></tr>';
				}
				print '</form>';
				print '</table></td></tr>';

				
				$i++;
			}
            
		}
		else
		{

			print '<tr class="oddeven"><td colspan="3" class="center">Aucun produit</td></tr>';
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
print '</div>';?>

<script>
	function valideProduit(id_produit){
		let err = verifStock(id_produit);
		if (err == ""){
			$.ajax({
				type: "POST", 
				url: "ajax/ajax.php?action=valideProduit",
				data: $('#form_'+id_produit).serialize()
			})
			.done(function (data) {
				$('#prod_'+id_produit).toggleClass('hidden');
				$('#prod_'+id_produit).addClass('green');
				$('#produit_'+id_produit).addClass('green');
			});
		}else{
			alert(err);
		}
	}

	function verifStock(id_produit){
		let stock = $('#stock_'+id_produit).val();
		let total = 0;
		let err = "";
		$('.quantite_'+id_produit).each(function(){
			let val = parseFloat($(this).val());
			if (val < 0 || isNaN(val))
				err += " ERREUR: Quantités incorrectes (ça doit être des nombres positifs) ";
			total += val;
		});
		if (total > stock){
			err += " ERREUR: Le total des quantités est > au stock ";
		}
		return err;
	}

	function openModal(id_produit){
		$('#modal_'+id_produit).show();
	}

	$('.close').on('click', function(){
		$(this).closest('.modal').hide();
	});

	$('.qtelot').on('change', function(){
		let _val = parseFloat($(this).val());
		if (_val < 0 || isNaN(_val)){
			alert("ERREUR: Quantités incorrectes (ça doit être des nombres positifs) ");
			$(this).val("0");
		}
		changeQuantite($(this).data('entrepot'), $(this).data('batch'), $(this).data('product'));
	});

	function valideLot(_produit){
		let _total = [];
		$('#modal_'+_produit+' .qtelot').each(function(){
			if (_total[$(this).data('entrepot')] == undefined){
				_total[$(this).data('entrepot')] = 0;
			}
			_total[$(this).data('entrepot')] += parseFloat($(this).val());
		});

		_total.forEach((_qte, _entrepot) => {
			$('#quantite_'+_produit+'_'+_entrepot).val(_qte);
		});

		$('#modal_'+_produit).hide();
		$('#valideproduit_'+_produit).removeAttr('disabled');
	}

	function verifProduit(_produit){
		let _total = 0;
		$('#modal_'+_produit+' .qtelot').each(function(){
			_total += parseFloat($(this).val());
		});

		const _stockdepot = parseFloat($('#modal_'+_produit+' .stock_depot').html());
		const _totaldmd = parseFloat($('#modal_'+_produit+' .total_dmd').html());

		if (_total >= _totaldmd || _total == _stockdepot){
			$('#modal_'+_produit+' input[type="button"]').removeAttr('disabled');
		}
	}

	function qteTotaleEntrepot(_entrepot, _produit){
		let _total = 0;
		$('#modal_'+_produit+' .qtelot[data-entrepot="'+_entrepot+'"]').each(function(){
			_total += parseFloat($(this).val());
		});
		return _total;
	}

	function qteTotaleLot(_lot, _produit){
		let _total = 0;
		$('#modal_'+_produit+' .qtelot[data-batch="'+_lot+'"]').each(function(){
			_total += parseFloat($(this).val());
		});
		return _total;
	}

	function changeQuantite(_entrepot, _lot, _produit){
		let _maxlot = parseFloat($('#maxqt_'+_produit+'_'+_lot).html());
		let _totallot = qteTotaleLot(_lot, _produit);

		if (_totallot > _maxlot){
			alert("ERREUR: Les quantités pour le lot #"+_lot+" sont trop élevées, le total ne doit pas dépasser "+_maxlot);
			$('.qtelot[data-entrepot="'+_entrepot+'"][data-batch="'+_lot+'"][data-product="'+_produit+'"]').val("0");
			changeQuantite(_entrepot, _lot, _produit);
		}else{
			let _restelot = parseFloat(_maxlot - _totallot);
			let _label = $('#lotqt_'+_produit+'_'+_lot).closest('.label');
			$('#lotqt_'+_produit+'_'+_lot).html(_restelot);
			if (_restelot == 0){
				_label.removeClass('label_ok');
				_label.addClass('label_ok');
			}else{
				_label.removeClass('label_ok');
			}

			let _maxentrepot = parseFloat($('#entrepotmax_'+_produit+'_'+_entrepot).html());
			let _totalentrepot = qteTotaleEntrepot(_entrepot, _produit);
			let _resteentrepot = parseFloat(_maxentrepot - _totalentrepot);
			_resteentrepot = (_resteentrepot < 0 ? 0 : _resteentrepot);
			$('#entrepotqt_'+_produit+'_'+_entrepot).html(_resteentrepot);
			_label = $('#entrepotqt_'+_produit+'_'+_entrepot).closest('.label');
			if (_resteentrepot == 0){
				_label.removeClass('label_ok');
				_label.addClass('label_ok');
			}else{
				_label.removeClass('label_ok');
			}

			verifProduit(_produit);
		}
	}

</script>
<?php
// End of page
llxFooter();
$db->close();
