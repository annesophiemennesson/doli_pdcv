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

// Load translation files required by the page
$langs->loadLangs(array("transfertstockinterne@transfertstockinterne"));

$action = GETPOST('action', 'aZ09');


// Security check
if (! $user->rights->transfertstockinterne->transfert_stock->create) {
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
	$newTransfert = -1;
	$object = new Transfert_stock($db);
	$object->fk_entrepot_arrivee = $user->fk_warehouse;
	$object->label = "Commande magasin";

	// On recupere l'id du depot
	$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "entrepot WHERE ref = 'Dépôt'";
	$result = $db->query($sql);
	$obj = $db->fetch_object($result);
	$object->fk_entrepot_depart = $obj->rowid;

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
		}
	}
	// Envoi email
	$destinataire = 'annesophie.mennesson@gmail.com';
	// Pour les champs $expediteur / $copie / $destinataire, séparer par une virgule s'il y a plusieurs adresses
	$expediteur = 'annesophie.mennesson@gmail.com';
	$objet = 'Nouvelle commande magasin'; // Objet du message     
	$message = 'Une nouvelle commande magasin vient d\'être faite par '.$user->prenom.' '.$user->nom.'.<br/>Pensez à la valider !';
	$cmail = new CMailFile($objet, $destinataire, $expediteur, $message, array(), array(), array(), '', '', 0, 1, '', '');
	$result = $cmail->sendfile();

	header('Location: '.dol_buildpath('/custom/transfertstockinterne/transfertstockinterneindex.php?message=new', 1));
    exit();
}


/*
 * View
 */

llxHeader("", "Nouvelle demande de transfert");

print load_fiche_titre("Nouvelle demande de transfert", '', '');

print '<div class="fichecenter">';

// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (! empty($conf->transfertstockinterne->enabled) && $user->rights->transfertstockinterne->transfert_stock->create)
{
	$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "entrepot WHERE ref = 'Dépôt'";
	$result = $db->query($sql);
	$obj = $db->fetch_object($result);
	// Si l'utilisateur n'est pas lié à un magasin
	if (empty($user->fk_warehouse)){
		print '<p><strong>Vous n\'êtes lié à aucun magasin, vous ne pouvez donc faire aucune commande<br/>Effectuez la modification dans votre fiche utilisateur, déconnectez-vous et reconnectez-vous pour réessayer.</strong></p>';
	}elseif ($user->fk_warehouse == $obj->rowid){
		print '<p><strong>Vous êtes lié au dépôt, vous ne pouvez donc faire aucune demande de ramasse<br/>Effectuez la modification dans votre fiche utilisateur, déconnectez-vous et reconnectez-vous pour réessayer.</strong></p>';
	}else{
		// On vérifie si il y a déjà une commande pour ce magasin
		$_sql = "SELECT rowid
				FROM ".MAIN_DB_PREFIX."transfert_stock 
				WHERE label = 'Commande magasin' AND date_reception IS NULL AND fk_entrepot_arrivee = ".$user->fk_warehouse;
		$_res = $db->query($_sql);
		$_num = $db->num_rows($_res);
		if ($_num > 0){
			print '<p><strong>Une commande est déjà en cours, si vous n\'en avez pas passé aujourd\'hui, vérifiez que la réception est bien terminée.<br/>Si vous avez déjà passé une commande et que vous voulez la modifier, contactez le responsable du dépôt.</strong></p>';
		}else{
			$sql = "SELECT p.ref, p.label, s.fk_product, s.reel AS stock_depot, IFNULL(ABS(SUM(m.value)),0) AS qte_vendue, ifnull(ps.reel, 0) AS stock_magasin, ef.uniteachat
					FROM ".MAIN_DB_PREFIX."product_stock AS s
					INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e ON (e.rowid = s.fk_entrepot)
					INNER JOIN ".MAIN_DB_PREFIX."product AS p ON (p.rowid = s.fk_product)
					LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as ef on (p.rowid = ef.fk_object) 
					LEFT JOIN ".MAIN_DB_PREFIX."categorie_product AS cp ON (cp.fk_product = p.rowid)
					LEFT JOIN ".MAIN_DB_PREFIX."categorie AS c ON (cp.fk_categorie = c.rowid)
					LEFT JOIN ".MAIN_DB_PREFIX."categorie AS cparent ON (cparent.rowid = c.fk_parent)
					LEFT JOIN ".MAIN_DB_PREFIX."stock_mouvement AS m ON (m.fk_product = p.rowid AND m.label = \"TakePOS\" AND m.fk_entrepot = ".$user->fk_warehouse." and m.tms >= DATE_SUB(m.tms, INTERVAL ".$conf->global->NB_JOURS_STATS_MAGASIN." DAY))
					LEFT JOIN ".MAIN_DB_PREFIX."product_stock ps ON (p.rowid = ps.fk_product AND ps.fk_entrepot = ".$user->fk_warehouse.")
					WHERE e.ref = \"Dépôt\"
					GROUP BY s.fk_product
					ORDER BY cparent.label, c.label, p.label;";

			$resql = $db->query($sql);
			if ($resql)
			{
				$total = 0;
				$num = $db->num_rows($resql);


				print '<table class="noborder centpercent">';
				print '<tr class="liste_titre">';
				print '<th>Libellé produit</th><th>Stock au dépôt</th><th>Quantité vendue sur '.$conf->global->NB_JOURS_STATS_MAGASIN.'j</th><th>Stock magasin</th><th>Quantité à commander</th><th>Commentaire</th></tr>';
				print '<form action="new.php?action=add&&token='.newToken().'" method="post">';
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
						print '<td><strong>'.$obj->label.'</strong> ('.$valua.')</td>';
						print '<td>'.price2num($obj->stock_depot, 'MS').'</td>';
						print '<td>'.$obj->qte_vendue.'</td>';
						print '<td>'.price2num($obj->stock_magasin, 'MS').'</td>';
						print '<td><input type="number" class="flat" step="0.01" min="0" max="'.$obj->stock_depot.'" name="qte['.$obj->fk_product.']" value="0" required /></td>';
						print '<td><input type="text" class="flat" value="" name="comment['.$obj->fk_product.']"</td>';
						print '</tr>';
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
}
//END MODULEBUILDER DRAFT MYOBJECT */


print '</div>';

// End of page
llxFooter();
$db->close();
