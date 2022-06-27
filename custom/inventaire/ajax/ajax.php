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
//require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$action = GETPOST('action', 'aZ09');


/*
 * View
 */

if ($action == 'valider') {
	$produit = GETPOST('produit', 'int');
	$inv_produit = GETPOST('invproduit', 'int');
    $entrepot = GETPOST('entrepot', 'int');
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

    $inventaire = new Inventaire_produit($db);
    $inventaire->fetch($inv_produit);
    $inventaire->stock_confirm = $confirm;
    $inventaire->commentaire = $comm;
    $inventaire->date_inventaire = $db->idate(dol_now());
    $inventaire->fk_user = $user->id;
    $inventaire->update($user);

}