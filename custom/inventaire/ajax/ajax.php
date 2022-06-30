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
require_once DOL_DOCUMENT_ROOT.'/custom/inventaire/class/inventaire_produit.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/inventaire/class/inventaire.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$action = GETPOST('action', 'aZ09');


/*
 * View
 */

if ($action == 'valider') {
	$produit = GETPOST('produit', 'int');
	$inv_produit = GETPOST('invproduit', 'int');
    $entrepot = $user->fk_warehouse;
    $stock = GETPOST('stock', 'int');

    // On vérifie le stock
    $sql = "SELECT reel FROM " . MAIN_DB_PREFIX . "product_stock WHERE fk_product = ".$produit." AND fk_entrepot = ".$entrepot;
	$result = $db->query($sql);
    $obj = $db->fetch_object($result);
    $stock_attendu = $obj->reel;

    $inventaire = new Inventaire_produit($db);
    $inventaire->fetch($inv_produit);
    $inventaire->stock_attendu = $stock_attendu;
    $inventaire->stock_reel = $stock;
    if ($stock_attendu == $stock){
        $inventaire->stock_confirm = $stock;
        $inventaire->date_inventaire = $db->idate(dol_now());
        $inventaire->fk_user = $user->id;
    }
    $inventaire->update($user);

    if ($stock_attendu == $stock){
        echo "ok";
    }else{
        echo "aconfirmer";
    }
} elseif ($action == 'confirmer'){
	$inv_produit = GETPOST('invproduit', 'int');
    $confirm = GETPOST('confirm', 'int');
    $comm = GETPOST('commentaire', '');
    $fk_product = GETPOST('produit', 'int');
    $lot = GETPOST('lot', 'array');
    $entrepot = $user->fk_warehouse;

    // Lots
    $sql = "SELECT pb.batch, pb.qty
            FROM ".MAIN_DB_PREFIX."product_stock AS ps
            INNER JOIN ".MAIN_DB_PREFIX."product_batch AS pb ON (ps.rowid = pb.fk_product_stock)
            WHERE ps.fk_entrepot = ".$entrepot." AND ps.fk_product = ".$fk_product;
    
    $resql = $db->query($sql);
    $num = $db->num_rows($resql);
        
    $object = new Product($db);
    $result = $object->fetch($fk_product);

    $sql2 = "SELECT rowid FROM ".MAIN_DB_PREFIX."entrepot WHERE ref = 'Perte inventaire'";
    $res2 = $db->query($sql2);
    $obj = $db->fetch_object($res2);
    $perte = $obj->rowid;

    $sql3 = "SELECT ref FROM ".MAIN_DB_PREFIX."entrepot WHERE rowid = ".$entrepot;
    $res3 = $db->query($sql3);
    $obj3 = $db->fetch_object($res3);
    $nom_entrepot = $obj3->ref;

    if ($num == 0){
        $inventaire = new Inventaire_produit($db);
        $inventaire->fetch($inv_produit);
        $inventaire->stock_confirm = $confirm;
        $inventaire->commentaire = $comm;
        $inventaire->date_inventaire = $db->idate(dol_now());
        $inventaire->fk_user = $user->id;
        $inventaire->update($user);

        if ($inventaire->stock_confirm > $inventaire->stock_attendu){ 
            $nbpiece = price2num($inventaire->stock_confirm - $inventaire->stock_attendu);
            // Add stock
            $result2 = $object->correct_stock(
                $user,
                $entrepot,
                $nbpiece,
                0,
                "Correction suite inventaire",
                0,
                ""
            );
        }else{
            $nbpiece = price2num($inventaire->stock_attendu - $inventaire->stock_confirm);
            // Remove stock
            $result1 = $object->correct_stock(
                $user,
                $entrepot,
                $nbpiece,
                1,
                "Correction suite inventaire",
                0,
                ""
            );

            // Add stock
            $result2 = $object->correct_stock(
                $user,
                $perte,
                $nbpiece,
                0,
                "Correction suite inventaire (".$nom_entrepot.")",
                0,
                ""
            );
        }
    }else{       
        $i = 0;
		while ($i < $num)
		{
			$objreq = $db->fetch_object($resql);
            $stock = $objreq->qty;
            $qtelot = $lot[''.$objreq->batch.''];
            if ($stock != $qtelot){
                if ($qtelot > $stock){ 
                    $nbpiece = price2num($qtelot - $stock); 
    
                    // Add stock
                    $result2 = $object->correct_stock_batch(
                        $user,
                        $entrepot,
                        $nbpiece,
                        0,
                        "Correction suite inventaire",
                        0,
                        "",
                        "",
                        $objreq->batch,
                        ""
                    );
                }else{
                    $nbpiece = price2num($stock - $qtelot);
                    // Remove stock
                    $result1 = $object->correct_stock_batch(
                        $user,
                        $entrepot,
                        $nbpiece,
                        1,
                        "Correction suite inventaire",
                        0,
                        "",
                        "",
                        $objreq->batch,
                        ""
                    );
             
                    // Add stock
                    $result2 = $object->correct_stock_batch(
                        $user,
                        $perte,
                        $nbpiece,
                        0,
                        "Correction suite inventaire (".$nom_entrepot.")",
                        0,
                        "",
                        "",
                        $objreq->batch,
                        ""
                    );
                    var_dump($result2);
                }
            }
            
			$i++;
		}
        $autrelot = GETPOST('autrelot', 'array');
        if (!empty($autrelot)){
            foreach ($autrelot as $batch => $qtelot){
                if (!empty($batch)){
                    // Add stock
                    $result2 = $object->correct_stock_batch(
                        $user,
                        $entrepot,
                        $qtelot,
                        0,
                        "Correction suite inventaire",
                        0,
                        "",
                        "",
                        $batch,
                        ""
                    );
                }
            }
        }
        
        $inventaire = new Inventaire_produit($db);
        $inventaire->fetch($inv_produit);
        $inventaire->stock_confirm = $confirm;
        $inventaire->commentaire = $comm;
        $inventaire->date_inventaire = $db->idate(dol_now());
        $inventaire->fk_user = $user->id;
        $inventaire->update($user);
    }

}elseif ($action == "search_filters"){
	$search_entrepot = GETPOST('search_entrepot', '');
	$search_ref = GETPOST('search_ref', '');
	$search_delta = GETPOST('search_delta', '');
	$search_date = GETPOST('search_date', '');
	$id = GETPOST('id', '');



	$sql = 'SELECT e.ref, p.label, stock_attendu, stock_confirm, commentaire, date_inventaire, CONCAT(lastname, " ", firstname) AS user, CONCAT((if (stock_confirm > stock_attendu , "+" , "")), stock_confirm-stock_attendu) as delta
            FROM '.MAIN_DB_PREFIX.'inventaire_produit AS ip
            INNER JOIN '.MAIN_DB_PREFIX.'inventaire AS i ON (i.rowid = ip.fk_inventaire)
            INNER JOIN '.MAIN_DB_PREFIX.'entrepot AS e ON (e.rowid = i.fk_entrepot)
            INNER JOIN '.MAIN_DB_PREFIX.'product AS p ON (p.rowid = ip.fk_product)
            INNER JOIN '.MAIN_DB_PREFIX.'user AS u ON (u.rowid = ip.fk_user)
            WHERE ';
    
	if (!empty($id))
        $sql .= " ip.fk_product = ".$id;
    else        
        $sql .= " stock_attendu != stock_confirm";

    if (!empty($search_entrepot))
        $sql .= " AND i.fk_entrepot = ".$search_entrepot;
    if (!empty($search_date))
        $sql .= " AND DATE_FORMAT(date_inventaire, '%d/%m/%Y') = '".$search_date."'";
    if (!empty($search_ref))
        $sql .= " AND p.ref LIKE '%".$search_ref."%'";

    switch ($search_delta){
        case "sup":
            $sql .= " AND stock_confirm > stock_attendu";
            break;
        case "inf":
            $sql .= " AND stock_confirm < stock_attendu";
            break;
        case "egal":
            $sql .= " AND stock_confirm = stock_attendu";
            break;
    }
            
    $sql .= " ORDER BY date_inventaire desc;";

	$res = $db->query($sql);
	$ret = "";
	if ($res)
	{
		$num = $db->num_rows($res);	
		$i = 0;
        if ($num == 0){
            $ret = '<tr class="oddeven"><td colspan="'.(empty($id) ? "8" : "7").'" class="center">Aucun résultat avec ces filtres</td></tr>';
        }else{
            while ($i < $num)
            {
                $obj = $db->fetch_object($res);
                $ret .= '<tr class="oddeven">';
                $ret .= '<td>'.$obj->ref.'</td>';
                if (empty($id))
                    $ret .= '<td>'.$obj->label.'</td>';
                $ret .= '<td>'.$obj->stock_attendu.'</td>';
                $ret .= '<td>'.$obj->stock_confirm.'</td>';
                $ret .= '<td>'.$obj->delta.'</td>';
                $ret .= '<td>'.$obj->user.'</td>';
                $ret .= '<td>'.dol_print_date($obj->date_inventaire, "%d/%m/%Y %H:%M:%S").'</td>';
                $ret .= '<td>'.$obj->commentaire.'</td>';
                $ret .= '</tr>';
                $i++;
            }
        }
	}else{
		$ret = '<tr class="oddeven"><td colspan="'.(empty($id) ? "8" : "7").'" class="center">Aucun résultat avec ces filtres</td></tr>';
	}
	echo $ret;
}elseif ($action == "ajout_produit"){
    $fk_product = GETPOST('id', 'int');
    $entrepot = GETPOST('entrepot', 'int');

    $sql2 = "SELECT i.rowid
            FROM ".MAIN_DB_PREFIX."inventaire AS i
            LEFT JOIN ".MAIN_DB_PREFIX."inventaire_produit AS ip ON (i.rowid = ip.fk_inventaire)
            WHERE fk_entrepot = ".$entrepot." AND stock_confirm IS NULL AND fk_product = ".$fk_product;
    $result2 = $db->query($sql2);
    $num2 = $db->num_rows($result2);

    if ($num2 > 0){
        echo "NOK";
    }else{
        $sql = "SELECT rowid
                FROM ".MAIN_DB_PREFIX."inventaire 
                WHERE fk_entrepot = ".$entrepot." AND CAST(date_creation AS DATE) = cast(NOW() AS DATE);";
        $result = $db->query($sql);
        $num = $db->num_rows($result);
        if ($num > 0){
            $obj = $db->fetch_object($result);
            $id = $obj->rowid;
        }else{
            $object = new Inventaire($db);
            $object->fk_entrepot = $entrepot;
            $object->create($user);
            $id = $object->id;
        }
        $objp = new Inventaire_produit($db);
        $objp->fk_inventaire = $id;
        $objp->fk_product = $fk_product;
        $objp->create($user);
        echo "OK";
    }
}elseif ($action == 'autrelot'){
    $fk_product = GETPOST('produit', 'int');
    $compteur = GETPOST('compteur', 'int');
    $entrepot = $user->fk_warehouse;

    $sql = "SELECT batch, eatby
            FROM ".MAIN_DB_PREFIX."product_lot
            WHERE fk_product = 1006 AND batch NOT IN 
            (
                SELECT pb.batch
                FROM  ".MAIN_DB_PREFIX."product_stock AS ps 
                INNER JOIN ".MAIN_DB_PREFIX."product_batch AS pb ON (ps.rowid = pb.fk_product_stock) 
                WHERE ps.fk_entrepot = ".$entrepot." AND ps.fk_product = ".$fk_product."
            )
            ORDER BY eatby desc;";

    $resql = $db->query($sql);
    $num = $db->num_rows($resql);

    if ($num == 0){
        echo "<p>Aucun autre lot</p>";
    }else{
        $i = 0;
        $opt = "<option value=''>Choisissez un lot</option>";
        while ($i < $num){
            $obj = $db->fetch_object($resql);
            $dlc = "NC";
            if (!empty($lot->eatby))
                $dlc = dol_print_date($obj->eatby, "%d/%m/%Y");    
            $opt .= '<option value="'.$obj->batch.'">lot #'.$obj->batch.' (DLC: '.$dlc.')</option>';
            $i++;
        }
        echo '<p><select class="autrelot" id="autrelot_'.$compteur.'">'.$opt.'</select><input type="number" class="stocklot" min="0" id="autre_lot_'.$compteur.'" value="0" /></p>';
    }

}
