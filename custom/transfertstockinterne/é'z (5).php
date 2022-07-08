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
$langs->loadLangs(array("transfertstockinterne@transfertstockinterne"));

$action = GETPOST('action', 'aZ09');

$id = GETPOST('id', 'int');


// Security check
if (! $user->rights->transfertstockinterne->transfert_stock->prepare) {
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


llxHeader("", "Préparation du transfert");

print load_fiche_titre("Préparation du transfert", '', '');

print '<div class="fichecenter">';


// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (! empty($conf->transfertstockinterne->enabled) && !empty($id) && $user->rights->transfertstockinterne->transfert_stock->prepare)
{
    $sql = "SELECT date_prepa, label, e.ref as depart, e2.ref as arrivee, date_creation
            FROM " . MAIN_DB_PREFIX . "transfert_stock as t
			INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e ON (e.rowid = t.fk_entrepot_depart)
			INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e2 ON (e2.rowid = t.fk_entrepot_arrivee)
            WHERE t.rowid = ".$id;

    $result = $db->query($sql);
    $object = $db->fetch_object($result);
    
    print '<h3>'.$object->label.'</h3>';
    print '<p>Date demande: '.dol_print_date($object->date_creation, "%d/%m/%Y %H:%M:%S").'</p>';
    print '<p>Source: <strong>'.$object->depart.'</strong></p>';
    print '<p>Destination: <strong>'.$object->arrivee.'</strong></p>';

    //Verif commande pas preparée
    if (!empty($object->date_prepa)){
        print '<p>Commande déjà préparée</p>';
    }else{
        $sql = "SELECT tp.rowid, p.label AS lib_produit, tp.qte_valide AS qte_produit, 
                    tp.commentaire_valide AS com_produit, p.ref, IFNULL(tp.qte_prepa, 0) as qte_prepa, tp.commentaire_prepa
                FROM ".MAIN_DB_PREFIX."transfert_produit AS tp
                INNER JOIN ".MAIN_DB_PREFIX."product AS p ON (p.rowid = tp.fk_product)                
                LEFT JOIN ".MAIN_DB_PREFIX."categorie_product AS cp ON (cp.fk_product = p.rowid) 
                LEFT JOIN ".MAIN_DB_PREFIX."categorie AS c ON (cp.fk_categorie = c.rowid) 
                LEFT JOIN ".MAIN_DB_PREFIX."categorie AS cparent ON (cparent.rowid = c.fk_parent) 
                WHERE tp.fk_transfert_stock = ".$id."
                ORDER BY cparent.label, c.label, p.label";
       
        $resql = $db->query($sql);
        if ($resql)
        {
            $total = 0;
            $num = $db->num_rows($resql);

            print '<input type="hidden" name="transfert_stock" id="transfert_stock" value="'.$id.'" />';
            print '<table class="noborder centpercent">';
            print '<tr class="liste_titre">';
            print '<th>Libellé produit</th><th>Qté à préparer</th><th>Commentaire</th><th>Qté préparée</th><th>Commentaire prépa</th></tr>';
            if ($num > 0)
            {
                $i = 0;
                while ($i < $num)
                {

                    $obj = $db->fetch_object($resql);

                    $sql2 = "SELECT tl.rowid, qte_valide, commentaire_prepa, batch, eatby, qte_prepa
                            FROM ".MAIN_DB_PREFIX."transfert_lot AS tl
                            INNER JOIN ".MAIN_DB_PREFIX."product_lot AS pl ON (pl.rowid = tl.fk_product_lot)
                            WHERE tl.fk_transfert_produit = ".$obj->rowid;

                    $resql2 = $db->query($sql2);
                    $num2 = $db->num_rows($resql2);

                    print '<tr id="produit_'.$obj->rowid.'" class="'.($obj->qte_prepa > 0 ? 'green' : '').'" onclick="$(\'.more_'.$obj->rowid.'\').toggleClass(\'hidden\');">';
                    print '<form action="#" id="form_'.$obj->rowid.'">';
                    print '<input type="hidden" name="token" value="'.newToken().'" />';
                    print '<input type="hidden" name="transfert_produit" value="'.$obj->rowid.'" />';
                    print '<td><strong>'.$obj->lib_produit.'</strong></td>';
                    print '<td>'.$obj->qte_produit.'</td>';
                    print '<td>'.$obj->com_produit.'</td>';
                    print '<td><input type="number" '.($num2 > 0 ? "readonly" : "").' class="flat" step="0.01" min="0" max="'.price2num($obj->qte_produit, 'MS').'" name="qte" value="'.($obj->qte_prepa > 0 ? price2num($obj->qte_prepa, 'MS') : price2num($obj->qte_produit, 'MS')).'" id="qte_'.$obj->rowid.'" required /></td>';
                	print '<td><input type="text" class="flat" value="'.$obj->commentaire_prepa.'" name="comment" id="comment_'.$obj->rowid.'" /></td>';
                    print '</tr>';

                    if ($num2 > 0){
                        print '<tr class="more_'.$obj->rowid.' '.($obj->qte_prepa > 0 ? 'hidden' : '').'"><td colspan="5">';
                        print '<table class="noborder centpercent">';
                        print '<tr class="liste_titre">';
                        print '<th>N° lot</th><th>DLC</th><th>Qté validée</th><th>Qté préparée</th><th>Comm. prépa</th></tr>';
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
                            print '<td><input type="number" data-id="'.$obj2->rowid.'" data-product="'.$obj->rowid.'" class="qtelot" step="0.01" min="0" max="'.price2num($obj2->qte_valide, 'MS').'" name="qtelot['.$obj2->rowid.']" value="'.($obj2->qte_prepa > 0 ? price2num($obj2->qte_prepa, 'MS') : price2num($obj2->qte_valide, 'MS')).'" required /></td>';
                            print '<td><input type="text" data-id="'.$obj2->rowid.'" data-product="'.$obj->rowid.'" class="commlot"  name="commlot['.$obj2->rowid.']" value="'.$obj2->commentaire_prepa.'" /></td>';
                            print'</tr>';
                            $i2++;
                        }

                        print '</table></td></tr>';
                    }
                    print '<tr class="more_'.$obj->rowid.' '.($obj->qte_prepa > 0 ? 'hidden' : '').'"><td class="center" colspan="5"><a class="button" onclick="validePrepa('.$obj->rowid.')">Préparer</a></td></tr>';
                    


                    print '</form>';
                    $i++;
                }
            }
            else
            {

                print '<tr class="oddeven"><td colspan="5" class="center">Aucun produit à préparer</td></tr>';
            }
            print "</table><br>";

            $db->free($resql);
        }
        else
        {
            dol_print_error($db);
        }
    }
    
}
//END MODULEBUILDER DRAFT MYOBJECT */


print '</div>';?>

<script>
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

	function verifQte(rowid){
		let qte = parseFloat($('#qte_'+rowid).val());
        let qte_valide = parseFloat($('#qte_'+rowid).attr('max'));
		let err = "";
        if (qte > qte_valide){
            err += " ERREUR: La quantité préparée ne peut être supérieure à celle validée ";
        }else if (qte != qte_valide){
            let comm = $('#comment_'+rowid).val();
            if (comm.length == 0){
                err += "ERREUR: Commentaire obligatoire si les quantités sont différentes ";
            }
        }
		return err;
	}

    function temperature(transfert){
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

    function qteTotale( _produit){
		let _total = 0;
		$('.more_'+_produit+' .qtelot').each(function(){
			_total += parseFloat($(this).val());
		});
		return _total;
	}

    $('.qtelot').on('change', function(){
		let qte = parseFloat($(this).val());
        let qte_valide = parseFloat($(this).attr('max'));
        let _product = $(this).data('product');
        if (qte > qte_valide || qte < 0 || isNaN(qte) ){
            alert(" ERREUR: La quantité préparée ne peut être supérieure à celle validée, ni être < 0 ");
            $(this).val(qte_valide);
        }
        let qte_tot = qteTotale(_product);
        $('#qte_'+_product).val(qte_tot);      
	});
</script>
<?php

// End of page
llxFooter();
$db->close();
