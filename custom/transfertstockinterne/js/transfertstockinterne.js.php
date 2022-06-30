<?php
/* Copyright (C) 2022 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}


/**
 * \file    transfertstockinterne/js/transfertstockinterne.js.php
 * \ingroup transfertstockinterne
 * \brief   JavaScript file for module TransfertStockInterne.
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
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=3600, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}
?>

/* Javascript library of module TransfertStockInterne */
function search_filter(){
	$.ajax({
			method: "POST",
			url: 'ajax/ajax.php',
			data: { 
			action: 'filter_list', 
			search_libelle: $('#search_libelle').val(),
			search_source: $('#search_source').val(),
			search_dest: $('#search_dest').val(),
			search_date: $('#search_date').val(),
			search_statut: $('#search_statut').val(),
			token: '<?php echo newToken(); ?>' 
		},
			success: function(data) {
			$('#toutes_demandes .oddeven').each(function(){
				$(this).remove();
			});
			$(data).insertAfter('#toutes_demandes #liste_titre');
		}
	});
}

function reset_search (){
	$('#toutes_demandes #search_date').val("");
	$('#toutes_demandes select').each(function(){
		$(this).val("");
	});
}

function valideReception(rowid){
	let err = verifQte(rowid, 'reception');
	if (err == ""){
		$.ajax({
			type: "POST", 
			url: "ajax/ajax.php?action=reception",
			data: $('#form_'+rowid).serialize()
		})
		.done(function (data) {
			$('#produit_'+rowid).addClass('green');
			$('.more_'+rowid).addClass('hidden');
			data = JSON.parse(data);
			if (data.action == "askTemperature"){
				temperature(data.transfert_stock, 'reception');
			}
		});
	}else{
		alert(err);
	}
}

function validePrepa(rowid){
	let err = verifQte(rowid);
	if (err == ""){
		$.ajax({
			type: "POST", 
			url: "ajax/ajax.php?action=prepaProduit",
			data: $('#form_'+rowid).serialize()
		})
		.done(function (data) {
			$('#produit_'+rowid).addClass('green');
			$('.more_'+rowid).addClass('hidden');
			if (data == "askTemperature"){
				temperature($('#transfert_stock').val());
			}
		});
	}else{
		alert(err);
	}
}

function openModal(id_produit){
	$('#modal_'+id_produit).show();
}

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

function temperature(transfert, type = 'prepa'){
	if (type == 'reception'){
		let temp = prompt("Entrez la température à l'arrivée: ");
		while (isNaN(temp)){            
			temp = prompt("Entrez la température à l'arrivée: ");
		}
		$.ajax({
			type: "POST", 
			url: "ajax/ajax.php?action=finReception",
			data: {
				'transfert_stock': transfert,
				'temperature': temp,
				'token' : "<?php echo newToken() ?>"
			}
		})
		.done(function (data) {
			if (data == 'finReception'){
				window.location.href = "<?php echo dol_buildpath('/custom/transfertstockinterne/recep.php?message=fin', 1); ?>";
			}
		});
	}else{
		let temp = prompt("Entrez la température au départ: ");
		while (isNaN(temp)){            
			temp = prompt("Entrez la température au départ: ");
		}
		$.ajax({
			type: "POST", 
			url: "ajax/ajax.php?action=finPrepa",
			data: {
				'transfert_stock': transfert,
				'temperature': temp,
				'token' : "<?php echo newToken() ?>"
			}
		})
		.done(function (data) {
			if (data == 'finPrepa'){
				window.location.href = "<?php echo dol_buildpath('/custom/transfertstockinterne/prepa.php?message=fin', 1); ?>";
			}
		});
	}
}

function qteTotale( _produit){
	let _total = 0;
	$('.more_'+_produit+' .qtelot').each(function(){
		_total += parseFloat($(this).val());
	});
	return _total;
}

function verifQte(rowid, type = 'prepa'){
	let qte = parseFloat($('#qte_'+rowid).val());
	let qte_valide = parseFloat($('#qte_'+rowid).attr('max'));
	let err = "";
	if (type == 'prepa'){
		if (qte > qte_valide){
			err += " ERREUR: La quantité préparée ne peut être supérieure à celle validée ";
		}
	}
	if (qte != qte_valide){
		let comm = $('#comment_'+rowid).val();
		if (comm.length == 0){
			err += " ERREUR: Commentaire obligatoire si les quantités sont différentes ";
		}
	}
	return err;
}