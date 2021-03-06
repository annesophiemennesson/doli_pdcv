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
$langs->loadLangs(array("sendings", "bills", 'deliveries', 'orders'));

$action = GETPOST('action', 'aZ09');

$id = GETPOST('id', 'int');

// Security check
if (! $user->rights->inventaire->inventaire->do) {
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


llxHeader("", "Inventaire");

print load_fiche_titre("Inventaire", '', '');

print '<div class="fichecenter">';


// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if ( !empty($conf->inventaire->enabled) && $user->rights->inventaire->inventaire->do)
{
    if (empty($user->fk_warehouse)){
        print '<p><strong>Vous n\'??tes li?? ?? aucun entrep??t, vous ne pouvez donc faire aucun inventaire<br/>Effectuez la modification dans votre fiche utilisateur, d??connectez-vous et reconnectez-vous pour r??essayer.</strong></p>';
    }else{
        $sql = "SELECT p.label, ef.uniteachat, ip.fk_product, ip.rowid 
                FROM ".MAIN_DB_PREFIX."inventaire as i
                INNER JOIN ".MAIN_DB_PREFIX."inventaire_produit AS ip ON (ip.fk_inventaire = i.rowid)
                INNER JOIN ".MAIN_DB_PREFIX."product AS p ON (ip.fk_product = p.rowid)
                INNER JOIN ".MAIN_DB_PREFIX."product_stock AS s ON (s.fk_product = p.rowid AND s.fk_entrepot = i.fk_entrepot)
                LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as ef on (p.rowid = ef.fk_object) 
                LEFT JOIN ".MAIN_DB_PREFIX."categorie_product AS cp ON (cp.fk_product = p.rowid)
                LEFT JOIN ".MAIN_DB_PREFIX."categorie AS c ON (cp.fk_categorie = c.rowid)
                LEFT JOIN ".MAIN_DB_PREFIX."categorie AS cparent ON (cparent.rowid = c.fk_parent)
                WHERE i.fk_entrepot = ".$user->fk_warehouse." AND date_inventaire IS NULL 
                ORDER BY cparent.label, c.label, p.label";
      
        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            print '<table class="noborder centpercent">';
            if ($num > 0)
            {
                $i = 1;
                // On recup??re les valeurs pour les unit??s achat vente
                $sqlua = "SELECT param FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'product' AND name = 'uniteachat'";
                $resqlua = $db->query($sqlua);
                $objua = $db->fetch_object($resqlua);
                $ua = jsonOrUnserialize($objua->param)['options'];
                while ($i <= $num)
                {
                    $obj = $db->fetch_object($resql);
                    $valua = $ua[$obj->uniteachat];

                    // Lots
                    $sql2 = "SELECT pb.eatby, pb.batch, pb.qty, ps.fk_product
                            FROM ".MAIN_DB_PREFIX."product_stock AS ps
                            INNER JOIN ".MAIN_DB_PREFIX."product_batch AS pb ON (ps.rowid = pb.fk_product_stock)
                            WHERE ps.fk_entrepot = ".$user->fk_warehouse." AND ps.fk_product = ".$obj->fk_product;
               
                    $resql2 = $db->query($sql2);
                    $nb = $db->num_rows($resql2);
                 
                    print '<div class="panel panel-default '.($i == 1 ? "" : "hidden").'" id="panel_'.$i.'">';
                    print '<form action="#" method="POST" id="inv_'.$i.'">';
                    print '<div class="panel-heading">Inventaire <span class="floatright">'.$i.'/'.$num.'</span></div>';
                    print '<div class="panel-body text-center"><input type="hidden" name="nb_prod" value="'.$num.'" id="nb_'.$i.'" />';
                    print '<input type="hidden" name="produit" value="'.$obj->fk_product.'" id="produit_'.$i.'" />';
                    print '<input type="hidden" name="invproduit" value="'.$obj->rowid.'" id="invproduit_'.$i.'" />';
                    print '<p><strong>'.$obj->label.'</strong> ('.$valua.')</p>';
                    print '<p class="reel"><label for="stock_'.$i.'">Stock</label><input type="number" name="stock" min="0" id="stock_'.$i.'" /></p>';
                    print '<hr/>';
                    print '<p class="hidden message"><strong>Stock diff??rent, merci de confirmer et mettre un commentaire</strong></p>';
                    print '<p class="hidden confirm"><label for="confirm_'.$i.'">Stock confirm??</label><input type="number" name="confirm" min="0" id="confirm_'.$i.'" /></p>';
                    print '<p class="hidden commentaire"><label for="commentaire_'.$i.'">Commentaire</label><input type="text" name="commentaire" id="commentaire_'.$i.'" /></p>';
                    if ($nb > 0){
                        $j = 0;
                        print '<hr/>';
                        print '<div class="lot hidden">';
                        print '<p><strong>Merci d\'entrer les stocks pour chacun des lots suivants</strong></p>';
                        while ($j < $nb){
                            $lot = $db->fetch_object($resql2);
                            $dlc = "NC";
                            if (!empty($lot->eatby))
                                $dlc = dol_print_date($lot->eatby, "%d/%m/%Y");                            
                            print '<p><label for="lot['.$lot->batch.']">Stock pour le lot #'.$lot->batch.' (DLC: '.$dlc.')</label><input type="number" class="stocklot" name="lot['.$lot->batch.']" min="0" id="lot['.$lot->batch.']" value="0" /></p>';
                            $j++;
                        }
                        print '<div class="pluslot hidden"></div>';
                        print '<p class="autrelot"><a class="button btn_lot" href="#" onclick="autrelot('.$i.')">Autre lot</a></p>';
                        print '</div>';
                    }                   
                    print '<a class="button btn_valid" href="#" onclick="validerinventaire('.$i.')">Valider</a></div>';
                    print '</div>';
                    print '</form>';
                    print '</div>';
                    $i++;
                }
            }
            else
            {

                print '<tr class="oddeven"><td class="opacitymedium">Aucun produit ?? inventorier</td></tr>';
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


print '</div>';?>
<script>
let _compteur = 0;    
$('.stocklot').on('change', function(){
    let _pos = $(this).closest('.panel').attr('id').split('_')[1];
    let qte = parseFloat($(this).val());
    if (qte < 0 || isNaN(qte) ){
        alert(" ERREUR: La quantit?? ne peut ??tre inf??rieure ?? 0 ");
        $(this).val(0);
    }
    let _total = 0;
	$('#panel_'+_pos+' .stocklot').each(function(){
		_total += parseFloat($(this).val());
	});
    $('#confirm_'+_pos).val(_total);      
});
</script>
<?php
// End of page
llxFooter();
$db->close();
