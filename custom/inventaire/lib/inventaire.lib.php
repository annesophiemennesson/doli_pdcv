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
 */

/**
 * \file    inventaire/lib/inventaire.lib.php
 * \ingroup inventaire
 * \brief   Library files with common functions for Inventaire
 */

require_once DOL_DOCUMENT_ROOT.'/custom/inventaire/class/inventaire.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/inventaire/class/inventaire_produit.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/inventaire/class/inventaire_config.class.php';

/**
 * Prepare admin pages header
 *
 * @return array
 */
function inventaireAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("inventaire@inventaire");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/inventaire/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/inventaire/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/inventaire/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@inventaire:/inventaire/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@inventaire:/inventaire/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'inventaire@inventaire');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'inventaire@inventaire', 'remove');

	return $head;
}

function ajoutProduitInventaire($entrepot, $nb_j = 0){
	global $conf, $db, $user;
	if (empty($nb_j)){
		$obj = new Inventaire_config($db);
		$obj->fetch("", $entrepot);
		$nb_j = $obj->nb_jours;
	}
	
    $object = new Inventaire($db);
    $object->fk_entrepot = $entrepot;
    $object->create($user);
    $id = $object->id;

	$sql = "SELECT COUNT(*) AS nb
            FROM ".MAIN_DB_PREFIX."product_stock AS s
            INNER JOIN ".MAIN_DB_PREFIX."product AS p ON (p.rowid = s.fk_product)
            WHERE fk_entrepot = ".$entrepot;
    $result = $db->query($sql);
    $objnb = $db->fetch_object($result);
    $nb_p = $objnb->nb;

    //Nb produit à attribuer par jour
    $nb_add = ceil($nb_p / $nb_j);

    // On récupère les produits à ajouter qui n'ont pas été inventoriés depuis le nb de jours
    // et qui ne sont pas déjà dans la liste à faire
    $sql = "SELECT s.fk_product
            FROM ".MAIN_DB_PREFIX."product_stock AS s
            WHERE fk_entrepot = ".$entrepot." AND s.fk_product NOT IN (
                SELECT fk_product
                FROM ".MAIN_DB_PREFIX."inventaire_produit as p
                INNER JOIN ".MAIN_DB_PREFIX."inventaire as i ON (i.rowid = p.fk_inventaire)
                WHERE fk_entrepot = ".$entrepot." AND CAST(date_inventaire AS date) >= '".date('Y-m-d',strtotime('- '.$nb_j.' day'))."'  OR date_inventaire IS NULL
            )
            ORDER BY RAND()
            LIMIT ".$nb_add;

    $result = $db->query($sql);
    if ($result){
        $num = $db->num_rows($result);
        if ($num > 0)
        {
            $i = 0;
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $objp = new Inventaire_produit($db);
                $objp->fk_inventaire = $id;
                $objp->fk_product = $obj->fk_product;
                $objp->create($user);
                $i++;
            }
        }
        // Si il n'y en a pas assez alors on ajoute au hasard des produits 
        // qui ne font pas déjà partis de la liste à inventorier
        if ($num < $nb_add){
            $delta = $nb_add - $num;
            $sql = "SELECT s.fk_product
                    FROM ".MAIN_DB_PREFIX."product_stock AS s
                    WHERE fk_entrepot = ".$entrepot." AND s.fk_product NOT IN (
                        SELECT fk_product
                        FROM ".MAIN_DB_PREFIX."inventaire_produit as p
                        INNER JOIN ".MAIN_DB_PREFIX."inventaire as i ON (i.rowid = p.fk_inventaire)
                        WHERE fk_entrepot = ".$entrepot." AND date_inventaire IS NULL
                    )
                    ORDER BY RAND()
                    LIMIT ".$delta;
            $result = $db->query($sql);
            $num = $db->num_rows($result);
            if ($num > 0)
            {
                $i = 0;
                while ($i < $num)
                {
                    $obj = $db->fetch_object($result);
                    $objp = new Inventaire_produit($db);
                    $objp->fk_inventaire = $id;
                    $objp->fk_product = $obj->fk_product;
                    $objp->create($user);
                    $i++;
                }
            }
        }
    }
}