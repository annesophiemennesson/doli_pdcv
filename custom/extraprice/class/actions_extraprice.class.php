<?php
/* Copyright (C) 2016-2021	Charlene Benke	<charlene@patas-monkey.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file	   htdocs/extraprice/class/actions_extraprice.class.php
 * 	\ingroup	extraprice
 * 	\brief	  Fichier de la classe des actions/hooks de extraprice
 */
 
class ActionsExtraprice // extends CommonObject 
{
	/** Overloading the formContactTpl function : replacing the parent's function with the one below 
	 *  @param	  parameters  meta datas of the hook (context, etc...) 
	 *  @param	  object			 the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...) 
	 *  @param	  action			 current action (if set). Generally create or edit or null 
	 *  @return	   void 
	 */

	function addMoreActionsButtons($parameters, $object, $action) 
	{
		global $conf, $langs, $db;
		global $user, $bc;
		
		$langs->load("extraprice@extraprice");
		
		$userstatic = new User($db);
		$form = new Form($db);
		
		if ((   $object->element == 'propal' 
			|| $object->element == 'commande' 
			|| $object->element == 'facture' 
			|| $object->element == 'order_supplier' 
			|| $object->element == 'invoice_supplier' 
			)
			&& $object->statut == 0			// uniquement sur les �l�ments � l'�tat brouillon
			&& $conf->global->ExtraPriceFormula != "") // et si il y a une formule � appliquer
		{
			print '<div class="inline-block divButAction">';
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=refreshextraprice&amp;id='.$object->id.'">';
			print $langs->trans("RefreshExtraprice");
			print '</a></div>';
		}
	}


	function doActions($parameters, $object, $action) 
	{
		include_once (DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php');
		dol_include_once ('/extraprice/class/extraprice.class.php');

		global $conf, $langs, $db;
		global $user;
		if ($action=="refreshextraprice") {
			// on récupère le nouveau prix 
			$extrapricestatic=new Extraprice($db);
			foreach ($object->lines as $objectline) {
				$autreprix=$extrapricestatic->PriceWithExtrafields($object, $objectline);
				// si le prix a été changé
				if (! $autreprix == false) {
					$pu =price2num($autreprix);
					$price_base_type='HT';

					$remise_percent=$objectline->remise;

					// détermination du prix 
					$tabprice = calcul_price_total(
									$objectline->qty, $pu, $remise_percent,
									$objectline->tva_tx, $objectline->localtax1_tx, $objectline->localtax2,
									0, $price_base_type, $objectline->info_bits, $objectline->product_type
					);

					$total_ht  = $tabprice[0];
					$total_tva = $tabprice[1];
					$total_ttc = $tabprice[2];
					$total_localtax1=$tabprice[9];
					$total_localtax2=$tabprice[10];
					$pu_ht  = $tabprice[3];
					$pu_tva = $tabprice[4];
					$pu_ttc = $tabprice[5];

					$remise = 0;
					if ($remise_percent > 0) {
						$remise = round(($pu * $remise_percent / 100),2);
						$price = ($pu - $remise);
					}

					$price	= price2num($price);

					$objectline->remise_percent		= $remise_percent;
					$objectline->subprice			= ($object->type==2?-1:1)*abs($pu);

					$objectline->total_ht			= ($object->type==2?-1:1)*abs($total_ht);
					$objectline->total_tva			= ($object->type==2?-1:1)*abs($total_tva);
					$objectline->total_localtax1	= ($object->type==2?-1:1)*abs($total_localtax1);
					$objectline->total_localtax2	= ($object->type==2?-1:1)*abs($total_localtax2);
					$objectline->total_ttc			= ($object->type==2?-1:1)*abs($total_ttc);

					// on met à jour mais on n'execute pas le trigger (sinon on boucle en MAJ)
					$result=$objectline->update($user, 1);
					if ($result > 0)
						$object->update_price();
				}
			}
		}
	}
}