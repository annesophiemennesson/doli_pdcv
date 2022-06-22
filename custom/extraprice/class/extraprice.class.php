<?php
/*	Copyright (C) 2014-2016 Charlene BENKE  <charlie@patas-monkey.com>

 *	  \class	  Skeleton_class
 *	  \brief	  Put here description of your class
 *		\remarks	Put here some comments
 */
class Extraprice // extends CommonObject
{
	var $db;							//!< To store db handler

	function __construct($db)
	{
		$this->db = $db;
		return 1;
	}
		
	// return new price
	// $Extrafields = les extrafields de la ligne de la piece
	// $objecttrigger = la piece
	// $object = la ligne de la piece
	function PriceWithExtrafields( $mainelement, $object)
	{
		global $conf;

		require_once (DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php');
		require_once (DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php');

		$societe = new Societe($this->db);
		$societe->fetch($mainelement->socid);
		$extrafieldssociete = new ExtraFields($this->db); // les extrafields de la société
		// on récupère les champs dans extralabels
		$extralabels=$extrafieldssociete->fetch_name_optionals_label($societe->table_element);
		// on récupère le résultat dans $societe->array_options["options_NOMDUCHAMP"];
		$res=$societe->fetch_optionals($societe->id, $extralabels);
		$societeextravalue=$societe->array_options;

		// si c'est un produit référencé
		if ($object->fk_product) {
			require_once (DOL_DOCUMENT_ROOT."/product/class/product.class.php");
			$product = new Product($this->db);
			$product->fetch($object->fk_product);

			$price=0;
			$price_level_client=$societe->price_level;

			if ($product->price_base_type == 'TTC') {
				if (isset($price_level_client) && $conf->global->PRODUIT_MULTIPRICES)
					$origineprice = price2num($product->multiprices_ttc[$price_level_client]);
				else
					$origineprice = price2num($product->price_ttc);
			} else {
				if (isset($price_level_client) && $conf->global->PRODUIT_MULTIPRICES)
					$origineprice = price2num($product->multiprices[$price_level_client]);
				else
					$origineprice = price2num($product->price);
			}

			$extrafieldsproduct = new ExtraFields($this->db);	// les extrafields du produit
			// fetch optionals attributes and labels
			$extralabels=$extrafieldssociete->fetch_name_optionals_label($product->table_element);
			$res=$product->fetch_optionals($product->id, $extralabels);
			$productextravalue=$product->array_options;
		} else {
			// si c'est un produit saisie
			$origineprice = price2num($object->subprice);

		}

		$elementtrigger = new ExtraFields($this->db);	// les extrafields de la pièce
		// fetch optionals attributes and labels
		$extralabels=$elementtrigger->fetch_name_optionals_label($mainelement->table_element);
		$res=$mainelement->fetch_optionals($mainelement->id, $extralabels);
		$elementextravalue=$mainelement->array_options;

		$extrafields = new ExtraFields($this->db);		// les extrafields de la ligne de la pièce
		// fetch optionals attributes and labels
		$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);
		$res=$object->fetch_optionals($object->rowid, $extralabels);
		$objectvalue=$object->array_options;

		// maintenant on fait sa cuisine pour le calcul du prix
		// on récupère la catégorie du produit
		$sql="select fk_categorie from ".MAIN_DB_PREFIX."categorie_product where fk_product=".$object->fk_product;
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql) > 0) {
				$res = $this->db->fetch_array($resql);
				$categproductid = $res['fk_categorie'];
			}
			$val.=strval(dolibarr_get_const($this->db, "ExtraPriceFormulaCateg0-".$categproductid));
		}

		// on récupère la catégorie de l'utilisateur
		$sql="select fk_categorie from ".MAIN_DB_PREFIX."categorie_societe where fk_societe=".$societe->id;
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql) > 0) {
				$res = $this->db->fetch_array($resql);
				$categsocieteid = $res['fk_categorie'];
			}
			$val.=strval(dolibarr_get_const($this->db, "ExtraPriceFormulaCateg2-".$categsocieteid));
		}

		$val.=strval($conf->global->ExtraPriceFormula);
		eval($val);

		// ensuite la formule selon les extrafields
		return $newprice;
	}
}
?>