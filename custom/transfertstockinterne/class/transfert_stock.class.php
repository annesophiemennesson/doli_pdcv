<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2022  Anne-Sophie Mennesson <annesophie.mennesson@gmail.com>
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

/**
 * \file        class/transfert_stock.class.php
 * \ingroup     transfertstockinterne
 * \brief       This file is a CRUD class file for Transfert_stock (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Transfert_stock
 */
class Transfert_stock extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'transfertstockinterne';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'transfert_stock';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'transfert_stock';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for transfert_stock. Must be the part after the 'object_' into object_transfert_stock.png
	 */
	public $picto = 'transfert_stock@transfertstockinterne';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;


	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' =>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'label' =>array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>1, 'visible'=>-1, 'position'=>15),
		'temperature_depart' =>array('type'=>'double(7,4)', 'label'=>'Temperaturedepart', 'enabled'=>1, 'visible'=>-1, 'position'=>20),
		'temperature_arrivee' =>array('type'=>'double(7,4)', 'label'=>'Temperaturearrivee', 'enabled'=>1, 'visible'=>-1, 'position'=>25),
		'fk_entrepot_depart' =>array('type'=>'integer', 'label'=>'Fkentrepotdepart', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>30),
		'fk_entrepot_arrivee' =>array('type'=>'integer', 'label'=>'Fkentrepotarrivee', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>35,),
		'fk_user_demande' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fkuserdemande', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>40),
		'fk_user_valide' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fkuservalide', 'enabled'=>1, 'visible'=>-1, 'position'=>45),
		'fk_user_prepa' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fkuserprepa', 'enabled'=>1, 'visible'=>-1, 'position'=>50),
		'fk_user_reception' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fkuserreception', 'enabled'=>1, 'visible'=>-1, 'position'=>55),
		'date_creation' =>array('type'=>'datetime', 'label'=>'Datecreation', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>60),
		'date_valide' =>array('type'=>'datetime', 'label'=>'Datevalide', 'enabled'=>1, 'visible'=>-1, 'position'=>65),
		'date_prepa' =>array('type'=>'datetime', 'label'=>'Dateprepa', 'enabled'=>1, 'visible'=>-1, 'position'=>70),
		'date_reception' =>array('type'=>'datetime', 'label'=>'Datereception', 'enabled'=>1, 'visible'=>-1, 'position'=>75),
		'main_pdf' =>array('type'=>'varchar(255)', 'label'=>'ModelPdf', 'enabled'=>1, 'visible'=>-1, 'position'=>15),
		'last_main_doc' =>array('type'=>'varchar(255)', 'label'=>'LastMainDoc', 'enabled'=>1, 'visible'=>-1, 'position'=>15)
	); 
	public $id;
	public $label;
	public $temperature_depart;
	public $temperature_arrivee;
	public $fk_entrepot_depart;
	public $fk_entrepot_arrivee;
	public $fk_user_demande;
	public $fk_user_valide;
	public $fk_user_prepa;
	public $fk_user_reception;
	public $date_creation;
	public $date_valide;
	public $date_prepa;
	public $date_reception;
	public $model_pdf;
	public $last_main_doc;

	public $lines;
	// END MODULEBUILDER PROPERTIES



	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->transfertstockinterne->transfert_stock->read) {
			$this->fields['myfield']['visible'] = 1;
			$this->fields['myfield']['noteditable'] = 0;
		}*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		global $conf;

		dol_syslog("Transfert_stock::create");

		if (empty($this->model_pdf)) {
			$this->model_pdf = $conf->global->TRANSFERT_STOCK_ADDON_PDF;
		}

		$error = 0;
		$now = dol_now();

		$this->db->begin();

		$sql = "SELECT ref FROM " . MAIN_DB_PREFIX . "entrepot WHERE rowid = ".$this->fk_entrepot_arrivee;
		$result = $this->db->query($sql);
		$obj = $this->db->fetch_object($result);

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "transfert_stock(";
		$sql .= "label";
		$sql .= ", fk_entrepot_depart";
		$sql .= ", fk_entrepot_arrivee";
		$sql .= ", fk_user_demande";
		$sql .= ", date_creation";
		$sql .= ", model_pdf";
		$sql .= ") VALUES (";
		$sql .= "'".$this->label."'";
		$sql .= ", ".((int) $this->fk_entrepot_depart);
		$sql .= ", ".((int) $this->fk_entrepot_arrivee);
		$sql .= ", ".((int) $user->id);
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", ".(!empty($this->model_pdf) ? "'".$this->db->escape($this->model_pdf)."'" : "null");
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "transfert_stock");
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		global $conf;

		$sql = "SELECT rowid, label, temperature_depart, temperature_arrivee, fk_entrepot_depart, ";
		$sql .= "fk_entrepot_arrivee, fk_user_demande, fk_user_valide, fk_user_prepa, fk_user_reception, ";
		$sql .= "date_creation, date_valide, date_prepa, date_reception, model_pdf, last_main_doc";
		$sql .= " FROM ".MAIN_DB_PREFIX."transfert_stock";
		$sql .= " WHERE rowid = ".((int) $id);

		dol_syslog("Transfert_stock::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);

			$this->id = $obj->rowid;
			$this->label = $obj->label;
			$this->temperature_depart = $obj->temperature_depart;
			$this->temperature_arrivee = $obj->temperature_arrivee;
			$this->fk_entrepot_depart = $obj->fk_entrepot_depart;
			$this->fk_entrepot_arrivee = $obj->fk_entrepot_arrivee;
			$this->fk_user_demande = $obj->fk_user_demande;
			$this->fk_user_valide = $obj->fk_user_valide;
			$this->fk_user_prepa = $obj->fk_user_prepa;
			$this->fk_user_reception = $obj->fk_user_reception;
			$this->date_creation = $obj->date_creation;
			$this->date_valide = $obj->date_valide;
			$this->date_prepa = $obj->date_prepa;
			$this->date_reception = $obj->date_reception;
			$this->model_pdf = $obj->model_pdf;
			$this->last_main_doc = $obj->last_main_doc;

			$this->db->free($resql);
			return $this->id;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		global $conf;

		$error = 0;

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";

		$sql .= " temperature_depart=".(isset($this->temperature_depart) ? $this->temperature_depart : "null").",";
		$sql .= " temperature_arrivee=".(isset($this->temperature_arrivee) ? $this->temperature_arrivee : "null").",";
		$sql .= " fk_user_valide=".(isset($this->fk_user_valide) ? $this->fk_user_valide : "null").",";
		$sql .= " fk_user_prepa=".(isset($this->fk_user_prepa) ? $this->fk_user_prepa : "null").",";
		$sql .= " fk_user_reception=".(isset($this->fk_user_reception) ? $this->fk_user_reception : "null").",";
		$sql .= " date_valide=".(strval($this->date_valide) != '' ? "'".$this->date_valide."'" : 'null').",";
		$sql .= " date_prepa=".(strval($this->date_prepa) != '' ? "'".$this->date_prepa."'" : 'null').",";
		$sql .= " date_reception=".(strval($this->date_reception) != '' ? "'".$this->date_reception."'" : 'null').",";
		$sql .= " model_pdf=".(strval($this->model_pdf) != '' ? "'".$this->model_pdf."'" : 'null').",";
		$sql .= " last_main_doc=".(strval($this->last_main_doc) != '' ? "'".$this->last_main_doc."'" : 'null');

		$sql .= " WHERE rowid=".((int) $this->id);
	
		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param      null|array  $moreparams     Array to provide more information
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;
		$result = 0;
		$includedocgeneration = 1;

		$langs->load("transfertstockinterne@transfertstockinterne");

		if (!dol_strlen($modele)) {
			$modele = 'standard';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->TRANSFERT_STOCK_ADDON_PDF)) {
				$modele = $conf->global->TRANSFERT_STOCK_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/transfertstockinterne/doc/";

		if ($includedocgeneration && !empty($modele)) {
			$result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		}
		return $result;
	}

	/**
	 *	Load array this->produits 
	 *
	 */
	public function loadProduits()
	{
		$this->lines = array();

		$sql = "SELECT p.label, tp.qte_prepa as qte_prod, tl.qte_prepa as qte_lot, batch, eatby, ef.uniteachat
				FROM ".MAIN_DB_PREFIX."transfert_produit AS tp
				INNER JOIN ".MAIN_DB_PREFIX."product AS p ON (p.rowid = tp.fk_product)             
				LEFT JOIN ".MAIN_DB_PREFIX."transfert_lot AS tl ON (tp.rowid = tl.fk_transfert_produit)
				LEFT JOIN ".MAIN_DB_PREFIX."product_lot AS pl ON (pl.rowid = tl.fk_product_lot)
				LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as ef on (p.rowid = ef.fk_object) 
				WHERE tp.qte_prepa > 0 and tp.fk_transfert_stock = ".((int) $this->id);
		//print $sql;

		dol_syslog(get_class($this)."::loadProduits", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$sqlua = "SELECT param FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'product' AND name = 'uniteachat'";
			$resqlua = $this->db->query($sqlua);
			$objua = $this->db->fetch_object($resqlua);
			$ua = jsonOrUnserialize($objua->param)['options'];
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$valua = $ua[$obj->uniteachat];
				$this->lines[$i]['label'] = $obj->label." (".$valua.")";
				$this->lines[$i]['qte_prod'] = $obj->qte_prod;
				$this->lines[$i]['qte_lot'] = $obj->qte_lot;
				$this->lines[$i]['batch'] = $obj->batch;
				$this->lines[$i]['eatby'] = $obj->eatby;
				$i++;
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}
}