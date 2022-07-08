<?php
/* Copyright (C) 2022  Anne-Sophie Mennesson <annesophie.mennesson@gmail.com>
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
 * \file        class/inventaire.class.php
 * \ingroup     inventaire
 * \brief       This file is a CRUD class file for Transfert_stock (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Inventaire
 */
class Inventaire_produit extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'inventaire';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'inventaire_produit';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'inventaire_produit';

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
	 * @var string String with name of icon for inventaire. Must be the part after the 'object_' into object_inventaire.png
	 */
	public $picto = 'inventaire_produit@inventaire';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>0,),
		'fk_inventaire' => array('type'=>'integer', 'label'=>'Fkentrepot', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>-1,),
		'fk_product' => array('type'=>'integer:Product:product/class/product.class.php:1', 'label'=>'Fkproduct', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>-1,),
		'stock_attendu' => array('type'=>'double', 'label'=>'Stockattendu', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>-1,),
		'stock_reel' => array('type'=>'double', 'label'=>'Stockreel', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>-1,),
		'stock_confirm' => array('type'=>'double', 'label'=>'Stockconfirm', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>-1,),
		'fk_user' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fkuser', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>-1,),
		'commentaire' => array('type'=>'varchar(255)', 'label'=>'Commentaire', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>-1,),
		'date_inventaire' => array('type'=>'datetime', 'label'=>'Dateinventaire', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>-1,),
	);
	public $rowid;
	public $fk_inventaire;
	public $fk_product;
	public $stock_attendu;
	public $stock_reel;
	public $stock_confirm;
	public $fk_user;
	public $commentaire;
	public $date_inventaire;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

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

		dol_syslog("Inventaire_produit::create");

		$error = 0;
		$now = dol_now();

		$this->db->begin();

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "inventaire_produit(";
		$sql .= "fk_inventaire";
		$sql .= ", fk_product";
		$sql .= ") VALUES (";
		$sql .= ((int) $this->fk_inventaire);
		$sql .= ", ".((int) $this->fk_product);
		$sql .= ")";
		
		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "inventaire_produit");
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

		$sql = "SELECT rowid, fk_inventaire, fk_product, stock_attendu, stock_reel, ";
		$sql .= "stock_confirm, fk_user, commentaire, date_inventaire ";
		$sql .= " FROM ".MAIN_DB_PREFIX."inventaire_produit";
		$sql .= " WHERE rowid = ".((int) $id);

		dol_syslog("Inventaire_produit::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);

			$this->id = $obj->rowid;
			$this->fk_inventaire = $obj->fk_inventaire;
			$this->fk_product = $obj->fk_product;
			$this->stock_attendu = $obj->stock_attendu;
			$this->stock_reel = $obj->stock_reel;
			$this->stock_confirm = $obj->stock_confirm;
			$this->fk_user = $obj->fk_user;
			$this->commentaire = $obj->commentaire;
			$this->date_inventaire = $obj->date_inventaire;

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

		$sql .= " stock_attendu=".(isset($this->stock_attendu) ? $this->stock_attendu : "null").",";
		$sql .= " stock_reel=".(isset($this->stock_reel) ? $this->stock_reel : "null").",";
		$sql .= " stock_confirm=".(isset($this->stock_confirm) ? $this->stock_confirm : "null").",";
		$sql .= " fk_user=".(isset($this->fk_user) ? $this->fk_user : "null").",";
		$sql .= " commentaire=".(isset($this->commentaire) ? "'".$this->db->escape($this->commentaire)."'" : "null").",";
		$sql .= " date_inventaire=".(strval($this->date_inventaire) != '' ? "'".$this->date_inventaire."'" : 'null');

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