<?php
/* Copyright (C) 2022		Anne-Sophie Mennesson	<annesophie.mennesson@gmail.com>
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


$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/custom/transfertstockinterne/class/transfert_stock.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/transfertstockinterne/class/transfert_produit.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/transfertstockinterne/class/transfert_lot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$action = GETPOST('action', 'aZ09');


/*
 * View
 */

if ($action == 'valideProduit') {
	$qte = GETPOST('qte', 'array');
	$comment = GETPOST('comment', 'array');
	$transfert_stock = GETPOST('ts', 'array');
	$transfert_produit = GETPOST('tp', 'array');
	$id_produit = GETPOST('id_produit', 'int');
	$qtelot = GETPOST('qtelot', 'array');

	foreach ($qte as $entrepot => $quantite){
		if (empty($transfert_stock) && floatval($quantite) > 0){
			$sql = "SELECT rowid
					FROM " . MAIN_DB_PREFIX . "transfert_stock as ts
					WHERE fk_entrepot_arrivee = ".$entrepot." AND date_valide IS NULL
					LIMIT 1";
			$result = $db->query($sql);
			if ($result){
				$obj = $db->fetch_object($result);
				$transfert_stock[$entrepot] = $obj->rowid;
			}else{
				$sql2 = "SELECT rowid FROM " . MAIN_DB_PREFIX . "entrepot WHERE ref = 'Dépôt'";
				$result2 = $db->query($sql2);
				$obj2 = $db->fetch_object($result2);
				$object = new Transfert_stock($db);
				$object->fk_entrepot_depart = $obj2->rowid;
				$object->fk_entrepot_arrivee = $entrepot;
				$object->label = "Commande magasin";
				$transfert_stock = $object->create($user);
			}
		}
		// Si le produit n'a pas été commandé par le magasin mais qu'on en transfère quand meme
		// On créé la ligne de transfert du produit
		if (empty($transfert_produit[$entrepot]) && floatval($quantite) > 0){
			$obj_produit = new Transfert_produit($db);
			$obj_produit->fk_transfert_stock = $transfert_stock[$entrepot];
			$obj_produit->fk_product = $id_produit;
			$obj_produit->qte_demande = floatval(0);
			$obj_produit->commentaire_demande = "";
			$obj_produit->create($user);

			$obj_produit->qte_valide = floatval($quantite);
			$obj_produit->commentaire_valide = $comment[$entrepot];
			$obj_produit->update($user);
		}elseif (!empty($transfert_produit[$entrepot]) || floatval($quantite) > 0){
			$obj_produit = new Transfert_produit($db);
			$obj_produit->fetch($transfert_produit[$entrepot]);
			$obj_produit->qte_valide = floatval($quantite);
			$obj_produit->commentaire_valide = $comment[$entrepot];
			$obj_produit->update($user);
		}

		foreach ($qtelot[$entrepot] as $lot => $qty){
			if (floatval($qty) > 0){
				$obj_lot = new Transfert_lot($db);
				$obj_lot->fk_transfert_produit = $obj_produit->id;
				$obj_lot->fk_product_lot = $lot;
				$obj_lot->qte_valide = floatval($qty);
				$obj_lot->create($user);
			}
		}

		// On vérifie si le transfert a encore des produits a valider
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "transfert_produit WHERE qte_valide IS NULL AND fk_transfert_stock = ".$transfert_stock[$entrepot];
		$result = $db->query($sql);
		$num = $db->num_rows($resql);

		if ($num == 0){
			$object = new Transfert_stock($db);
			$object->fetch($transfert_stock[$entrepot]);
			$object->fk_user_valide = $user->id;
			$object->date_valide = $db->idate(dol_now());
			$object->update($user);
		}
	}
} elseif ($action == 'prepaProduit'){
	$transfert_produit = GETPOST('transfert_produit', 'int');
	$qte = GETPOST('qte', 'int');
	$comment = GETPOST('comment', '');
	$qtelot = GETPOST('qtelot', 'array');
	$commlot = GETPOST('commlot', 'array');

	$obj_produit = new Transfert_produit($db);
	$obj_produit->fetch($transfert_produit);
	$obj_produit->fk_user_prepa = $user->id;
	$obj_produit->qte_prepa = floatval($qte);
	$obj_produit->commentaire_prepa = $comment;
	$obj_produit->update($user);
	
	foreach ($qtelot as $lot => $qty){
		$obj_lot = new Transfert_lot($db);
		$obj_lot->fetch($lot);
		$obj_lot->qte_prepa = floatval($qty);
		$obj_lot->commentaire_prepa = $commlot[$lot];
		$obj_lot->update($user);
	}

	// On vérifie si le transfert a encore des produits a préparer
	$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "transfert_produit WHERE qte_prepa IS NULL AND fk_transfert_stock = ".$obj_produit->fk_transfert_stock;
	$result = $db->query($sql);
	$num = $db->num_rows($resql);

	if ($num == 0){
		echo "askTemperature";
	}
}elseif ($action == "finPrepa"){
	$temp = GETPOST('temperature', 'int');
	$transfert_stock = GETPOST('transfert_stock', 'int');
	$object = new Transfert_stock($db);
	$object->fetch($transfert_stock);
	$object->fk_user_prepa = $user->id;
	$object->date_prepa = $db->idate(dol_now());
	$object->temperature_depart = floatval($temp);
	$object->update($user);
	echo "finPrepa";
}elseif ($action == 'reception'){
	$transfert_produit = GETPOST('transfert_produit', 'int');
	$qte = GETPOST('qte', 'int');
	$comment = GETPOST('comment', '');
	$qtelot = GETPOST('qtelot', 'array');
	$commlot = GETPOST('commlot', 'array');

	$obj_produit = new Transfert_produit($db);
	$obj_produit->fetch($transfert_produit);
	$obj_produit->fk_userreception = $user->id;
	$obj_produit->qte_reception = floatval($qte);
	$obj_produit->commentaire_reception = $comment;
	$obj_produit->update($user);
	
	foreach ($qtelot as $lot => $qty){
		$obj_lot = new Transfert_lot($db);
		$obj_lot->fetch($lot);
		$obj_lot->qte_reception = floatval($qty);
		$obj_lot->commentaire_reception = $commlot[$lot];
		$obj_lot->update($user);
	}

	// On vérifie si le transfert a encore des produits a préparer
	$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "transfert_produit WHERE qte_reception IS NULL AND fk_transfert_stock = ".$obj_produit->fk_transfert_stock;
	$result = $db->query($sql);
	$num = $db->num_rows($resql);

	$ret["action"] = "";
	if ($num == 0){
		$ret["action"] = "askTemperature";
		$ret["transfert_stock"] = $obj_produit->fk_transfert_stock;
	}
	echo json_encode($ret);
}elseif ($action == "finReception"){
	$temp = GETPOST('temperature', 'int');
	$transfert_stock = GETPOST('transfert_stock', 'int');
	$obj = new Transfert_stock($db);
	$obj->fetch($transfert_stock);
	$obj->fk_user_reception = $user->id;
	$obj->date_reception = $db->idate(dol_now());
	$obj->temperature_arrivee = floatval($temp);
	$obj->update($user);

	$entrepot_src = $obj->fk_entrepot_depart;
	$entrepot_dest = $obj->fk_entrepot_arrivee;

	$sql = "SELECT tp.fk_product, tp.qte_reception AS qte_produit, tl.qte_reception AS qte_lot, batch
			FROM " . MAIN_DB_PREFIX . "transfert_produit AS tp
			LEFT JOIN " . MAIN_DB_PREFIX . "transfert_lot AS tl ON (tp.rowid = tl.fk_transfert_produit)
			LEFT JOIN " . MAIN_DB_PREFIX . "product_lot AS pl ON (pl.rowid = tl.fk_product_lot)
			WHERE tp.qte_reception > 0 AND (tl.qte_reception > 0 OR ISNULL(tl.qte_reception)) AND fk_transfert_stock = ".$transfert_stock;
	$res = $db->query($sql);

	if ($res)
	{
		$num = $db->num_rows($res);
	
		$i = 0;
		while ($i < $num)
		{
			$objreq = $db->fetch_object($res);

			$object = new Product($db);
			$result = $object->fetch($objreq->fk_product);
			$nbpiece = price2num((!empty($objreq->qte_lot) ? $objreq->qte_lot : $objreq->qte_produit));
			$batch = $objreq->batch;

			if (!empty($batch)){
				// Remove stock
				$result1 = $object->correct_stock_batch(
					$user,
					$entrepot_src,
					$nbpiece,
					1,
					"Transfert stock interne",
					0,
					"",
					"",
					$batch,
					""
				);
	
				// Add stock
				$result2 = $object->correct_stock_batch(
					$user,
					$entrepot_dest,
					$nbpiece,
					0,
					"Transfert stock interne",
					0,
					"",
					"",
					$batch,
					""
				);
			}else{
				// Remove stock
				$result1 = $object->correct_stock(
					$user,
					$entrepot_src,
					$nbpiece,
					1,
					"Transfert stock interne",
					0,
					""
				);

				// Add stock
				$result2 = $object->correct_stock(
					$user,
					$entrepot_dest,
					$nbpiece,
					0,
					"Transfert stock interne",
					0,
					""
				);
			}
			$db->commit();
			$i++;
		}
	}

	echo "finReception";
}elseif ($action == "filter_list"){
	$search_libelle = GETPOST('search_libelle', '');
	$search_source = GETPOST('search_source', '');
	$search_dest = GETPOST('search_dest', '');
	$search_date = GETPOST('search_date', '');
	$search_statut = GETPOST('search_statut', '');


	$sql = "SELECT e.ref as depart, e2.ref as arrivee, date_creation, t.rowid, t.label, CONCAT(u.lastname, \" \", u.firstname) AS demandeur, COUNT(p.rowid) AS nb_produit, SUM(p.qte_demande) AS total_qte, 
				CASE WHEN date_prepa IS NULL THEN 'A préparer' WHEN date_reception IS NULL THEN 'A réceptionner' ELSE 'Terminé' END as statut
			FROM ".MAIN_DB_PREFIX."transfert_stock AS t
			INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e ON (e.rowid = t.fk_entrepot_depart)
			INNER JOIN ".MAIN_DB_PREFIX."entrepot AS e2 ON (e2.rowid = t.fk_entrepot_arrivee)
			INNER JOIN ".MAIN_DB_PREFIX."user AS u ON (u.rowid = t.fk_user_demande)
			INNER JOIN ".MAIN_DB_PREFIX."transfert_produit AS p ON (p.fk_transfert_stock = t.rowid)
			WHERE date_valide IS NOT NULL ";

	if (!empty($search_libelle))
		$sql .= " AND t.label = '".$search_libelle."'";
	if (!empty($search_source))
		$sql .= " AND t.fk_entrepot_depart = ".$search_source;
	if (!empty($search_dest))
		$sql .= " AND t.fk_entrepot_arrivee = ".$search_dest;
	if (!empty($search_date))
		$sql .= " AND DATE_FORMAT(t.date_creation, '%d/%m/%Y') = '".$search_date."'";
	if (!empty($search_statut)){
		switch ($search_statut){
			case 'apreparer':
				$sql .= " AND t.date_prepa IS NULL";
				break;
			case 'areceptionner':
				$sql .= " AND t.date_reception IS NULL AND t.date_prepa IS NOT NULL";
				break;
			case 'termine':
				$sql .= " AND t.date_reception IS NOT NULL";
				break;
		}
	}
		
	$sql .= " GROUP BY t.rowid ORDER BY date_creation DESC";

	$res = $db->query($sql);
	$ret = "";
	if ($res)
	{
		$num = $db->num_rows($res);	
		$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($res);
			$ret .= '<tr class="oddeven">';
            $ret .= '<td><a target="_blank" href="'.dol_buildpath('/custom/transfertstockinterne/detail.php?id='.$obj->rowid, 1).'">'.$obj->label.'</a></td>';
            $ret .= '<td>'.$obj->depart.'</td>';
            $ret .= '<td>'.$obj->arrivee.'</td>';
            $ret .= '<td>'.dol_print_date($obj->date_creation, "%d/%m/%Y %H:%M:%S").'</td>';
            $ret .= '<td>'.$obj->demandeur.'</td>';
            $ret .= '<td>'.$obj->statut.'</td>';
            $ret .= '<td>'.$obj->nb_produit.'</td>';
            $ret .= '<td>'.$obj->total_qte.'</td>';
            $ret .= '</tr>';
			$i++;
		}
	}else{
		$ret = '<tr class="oddeven"><td colspan="8" class="center">Aucun résultat avec ces filtres</td></tr>';
	}
	echo $ret;
}
