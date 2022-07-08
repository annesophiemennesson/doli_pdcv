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
 * \file        class/transfert_lot.class.php
 * \ingroup     transfertstockinterne
 * \brief       This file is a CRUD class file for Transfert_lot (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Transfert_lot
 */
class Transfert_lot extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'transfertstockinterne';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'transfert_lot';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'transfert_lot';

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
	 * @var string String with name of icon for transfert_lot. Must be the part after the 'object_' into object_transfert_lot.png
	 */
	public $picto = 'transfert_lot@transfertstockinterne';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' =>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'fk_product_lot' =>array('type'=>'integer:Product:product/class/product.class.php:1', 'label'=>'Fkproductlot', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>15),
		'fk_transfert_produit' =>array('type'=>'integer', 'label'=>'Fktransfertproduit', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>20),
		'qte_valide' =>array('type'=>'double', 'label'=>'Qtevalide', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>25),
		'qte_prepa' =>array('type'=>'double', 'label'=>'Qteprepa', 'enabled'=>1, 'visible'=>-1, 'position'=>30),
		'qte_reception' =>array('type'=>'double', 'label'=>'Qtereception', 'enabled'=>1, 'visible'=>-1, 'position'=>35),
		'commentaire_prepa' =>array('type'=>'varchar(255)', 'label'=>'Commentaireprepa', 'enabled'=>1, 'visible'=>-1, 'position'=>45),
		'commentaire_reception' =>array('type'=>'varchar(255)', 'label'=>'Commentairereception', 'enabled'=>1, 'visible'=>-1, 'position'=>50),
		); 
	public $id;
	public $fk_product_lot;
	public $fk_transfert_produit;
	public $qte_valide;
	public $qte_prepa;
	public $qte_reception;
	public $commentaire_prepa;
	public $commentaire_reception;
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
		/*if ($user->rights->transfertstockinterne->transfert_lot->read) {
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
		$error = 0;
		$now = dol_now();

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "transfert_lot(";
		$sql .= "fk_product_lot";
		$sql .= ", fk_transfert_produit";
		$sql .= ", qte_valide";
		$sql .= ") VALUES (";
		$sql .=  (int) $this->fk_product_lot;
		$sql .= ", ".((int) $this->fk_transfert_produit);
		$sql .= ", ".round($this->qte_valide, 2);
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "transfert_lot");
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

		$sql = "SELECT rowid, fk_transfert_produit, fk_product_lot, qte_valide, ";
		$sql .= "qte_prepa, qte_reception, commentaire_prepa, ";
		$sql .= "commentaire_reception";
		$sql .= " FROM ".MAIN_DB_PREFIX."transfert_lot";
		$sql .= " WHERE rowid = ".((int) $id);

		dol_syslog("Transfert_lot::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);

			$this->id = $obj->rowid;
			$this->fk_transfert_produit = $obj->fk_transfert_produit;
			$this->fk_product_lot = $obj->fk_product_lot;
			$this->qte_valide = $obj->qte_valide;
			$this->qte_prepa = $obj->qte_prepa;
			$this->qte_reception = $obj->qte_reception;
			$this->commentaire_prepa = $obj->commentaire_prepa;
			$this->commentaire_reception = $obj->commentaire_reception;

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

		$sql .= " qte_prepa=".(isset($this->qte_prepa) ? $this->qte_prepa : "null").",";
		$sql .= " qte_reception=".(isset($this->qte_reception) ? $this->qte_reception : "null").",";
		$sql .= " commentaire_prepa=".(isset($this->commentaire_prepa) ? "'".$this->db->escape($this->commentaire_prepa)."'" : "null").",";
		$sql .= " commentaire_reception=".(isset($this->commentaire_reception) ? "'".$this->db->escape($this->commentaire_reception)."'" : "null");
		
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

}

