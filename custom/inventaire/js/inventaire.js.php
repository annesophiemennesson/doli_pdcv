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
 * \file    inventaire/js/inventaire.js.php
 * \ingroup inventaire
 * \brief   JavaScript file for module Inventaire.
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

/* Javascript library of module Inventaire */

function validerinventaire(_pos){
    let _stock = parseFloat($('#stock_'+_pos).val());
    if (isNaN(_stock) || _stock < 0){
        alert(" ERREUR : Stock incorrect ");
    }else{
        $.ajax({
            type: "POST", 
            url: "ajax/ajax.php?action=valider&token=<?php echo newToken(); ?>",
            data: $('#inv_'+_pos).serialize()
        })
        .done(function (data) {
            if (data == "ok"){
                validOK(_pos);
            }else{
                $('#panel_'+_pos+' .message').removeClass('hidden');
                $('#panel_'+_pos+' .confirm').removeClass('hidden');
                $('#panel_'+_pos+' .commentaire').removeClass('hidden');
                $('#stock_'+_pos).attr('readonly', true);
                $('#panel_'+_pos+' .btn_valid').attr('onclick', 'confirmerinventaire('+_pos+')');
                if ($('#panel_'+_pos+' .lot').length == 1){
                    $('#panel_'+_pos+' .lot').removeClass('hidden');
                    $('#confirm_'+_pos).attr('readonly', true);
                }
            }
        });
    }
}

function confirmerinventaire(_pos){
    let _confirm = parseFloat($('#confirm_'+_pos).val());
    let _comm = $('#commentaire_'+_pos).val();
    if (isNaN(_confirm) || _confirm < 0){
        alert(" ERREUR : Stock confirmé incorrect ");
    }else if (_comm.length == 0){
        alert(" ERREUR : Commentaire obligatoire ");
    }else{
        let _err = "";
        $('.lotform').remove();
        if ($('select.autrelot').length > 0){
            $('select.autrelot').each(function(){
                if ($(this).val() != ""){
                    let _num = $(this).attr('id').split('_')[1];		
                    let _batch = $(this).val(); 
                    let _qte = $('#autre_lot_'+_num).val();
                    if ($('input[name="autrelot['+_batch+']"]').length > 0){
                        _err += " ERREUR : Vous avez ajouté plusieurs fois le lot: #"+_batch;
                    }else{
                        let _input = "<input class='lotform' type='hidden' name='autrelot["+_batch+"]' value='"+_qte+"' />";
                        $('#inv_'+_pos).append(_input);
                    }
                }
            });
        }
        if (_err == ""){
            $.ajax({
                type: "POST", 
                url: "ajax/ajax.php?action=confirmer&token=<?php echo newToken(); ?>",
                data: $('#inv_'+_pos).serialize()
            })
            .done(function (data) {
                validOK(_pos);
            });
        }else{
            alert(_err);
        }
    }
}

function autrelot(_pos){  
    _compteur++;  
    $.ajax({
        type: "POST", 
        url: "ajax/ajax.php?action=autrelot&token=<?php echo newToken(); ?>&compteur="+_compteur,
        data: $('#inv_'+_pos).serialize()
    })
    .done(function (data) {
        $('#panel_'+_pos+' .autrelot').removeClass('hidden');
        $('#panel_'+_pos+' .autrelot').append(data);
        $('.stocklot').on('change', function(){
            let _pos = $(this).closest('.panel').attr('id').split('_')[1];
            let qte = parseFloat($(this).val());
            if (qte < 0 || isNaN(qte) ){
                alert(" ERREUR: La quantité ne peut être inférieure à 0 ");
                $(this).val(0);
            }
            let _total = 0;
            $('#panel_'+_pos+' .stocklot').each(function(){
                _total += parseFloat($(this).val());
            });
            $('#confirm_'+_pos).val(_total);      
        });
    });
}

function validOK(_pos){
    let _nb = parseInt($('#nb_'+_pos).val());
    _pos = parseInt(_pos);
    if (_pos == _nb){
        // Terminé
        location.reload();
    }else{
        // On passe au suivant
        $('#panel_'+_pos).remove();
        let _next = parseInt(_pos+1);
        $('#panel_'+_next).removeClass('hidden');
    }
}
    
function search_filters(_id = ""){
	$.ajax({
			method: "POST",
			url: 'ajax/ajax.php',
			data: { 
                action: 'search_filters', 
                id: _id,
                search_entrepot: $('#search_entrepot').val(),
                search_ref: $('#search_ref').val(),
                search_delta: $('#search_delta').val(),
                search_date: $('#search_date').val(),
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

function ajoutProduitInventaire(_id){
    let _entrepot = $('#entrepot').val();
    if (_entrepot == ""){
        alert("Vous devez sélectionner un entrepôt / magasin");
    }else{
        $.ajax({
            type: "POST", 
            url: "ajax/ajax.php",
			data: { 
                action: 'ajout_produit', 
                id: _id,
                entrepot: _entrepot,
                token: '<?php echo newToken(); ?>' 
            },
        })
        .done(function (data) {
            if (data == "OK"){
                alert("Produit ajouté !");
            }else{
                alert("Produit déjà dans la liste");
            }
        });
    }
}
