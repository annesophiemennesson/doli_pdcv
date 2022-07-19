<?php
/* Copyright (C) 2022	Anne-Sophie MENNESSON	<annesophie.mennesson@gmail.com>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file        class/demandeavoir.class.php
 * \ingroup     gestionrebuts
 * \brief       This file is a CRUD class file for DemandeAvoir (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for DemandeAvoir
 */
class DemandeAvoir extends CommonObject
{

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'demandeavoir';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'demande_avoir';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' =>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'fk_reception' =>array('type'=>'integer', 'label'=>'Fkreception', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>15),
		'fk_user' =>array('type'=>'integer', 'label'=>'Fkuser', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>15),
		'statut' =>array('type'=>'enum(\'en attente\',\'validée producteur\', \'validée pdcv\',\'refusée\')', 'label'=>'Statut', 'enabled'=>1, 'visible'=>-1, 'position'=>500),
		'commentaire' =>array('type'=>'varchar(255)', 'label'=>'Commentaire', 'enabled'=>1, 'visible'=>-1, 'position'=>25),
		'date_creation' =>array('type'=>'datetime', 'label'=>'Datecreation', 'enabled'=>1, 'visible'=>-1, 'position'=>30),
		'model_pdf' =>array('type'=>'varchar(255)', 'label'=>'Modelpdf', 'enabled'=>1, 'visible'=>0, 'position'=>35),
		'last_main_doc' =>array('type'=>'varchar(255)', 'label'=>'Lastmaindoc', 'enabled'=>1, 'visible'=>-1, 'position'=>40),
		); 
	public $rowid;
	public $fk_reception;
	public $fk_user;
	public $statut;
	public $commentaire;
	public $date_creation;
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
		/*if ($user->rights->gestionrebuts->demandeavoir->read) {
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

		dol_syslog("Demandeavoir::create");

		if (empty($this->model_pdf)) {
			$this->model_pdf = $conf->global->DEMANDEAVOIR_ADDON_PDF;
		}

		$error = 0;
		$now = dol_now();

		$this->db->begin();

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "demande_avoir(";
		$sql .= "fk_reception";
		$sql .= ", fk_user";
		$sql .= ", statut";
		$sql .= ", date_creation";
		$sql .= ", model_pdf";
		$sql .= ") VALUES (";
		$sql .= ((int) $this->fk_reception);
		$sql .= ", ".((int) $user->id);
		$sql .= ", 'ouverte'";
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
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "demande_avoir");
		}
		

		// Add object linked
		if (!$error) {
			$ret = $this->add_object_linked("reception", $this->fk_reception);
			if (!$ret) {
				$this->error = $this->db->lasterror();
				$error++;
			}
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

		$sql = "SELECT rowid, fk_reception, fk_user, statut, commentaire, ";
		$sql .= "date_creation, model_pdf, last_main_doc";
		$sql .= " FROM ".MAIN_DB_PREFIX."demande_avoir";
		$sql .= " WHERE rowid = ".((int) $id);

		dol_syslog("Demande_avoir::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);

			$this->id = $obj->rowid;
			$this->fk_reception = $obj->fk_reception;
			$this->fk_user = $obj->fk_user;
			$this->statut = $obj->statut;
			$this->commentaire = $obj->commentaire;
			$this->date_creation = $obj->date_creation;
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

		$sql .= " statut=".(strval($this->statut) != '' ? "'".$this->statut."'" : 'null').",";
		$sql .= " commentaire=".(strval($this->commentaire) != '' ? "'".$this->commentaire."'" : 'null').",";
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

		$langs->load("gestionrebuts@gestionrebuts");

		if (!dol_strlen($modele)) {
			$modele = 'demandeavoir';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->DEMANDEAVOIR_ADDON_PDF)) {
				$modele = $conf->global->DEMANDEAVOIR_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/gestionrebuts/doc/";

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

		$sql = "SELECT fk_product, qty, d.price, commentaire, eatby, batch, label, tva_tx, d.rowid
				FROM ".MAIN_DB_PREFIX."demande_avoirdet as d
				INNER JOIN ".MAIN_DB_PREFIX."product AS p ON (p.rowid = d.fk_product)
				WHERE fk_demande_avoir = ".((int) $this->id);

		dol_syslog(get_class($this)."::loadProduits", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$this->lines[$i]['rowid'] = $obj->rowid;
				$this->lines[$i]['label'] = $obj->label;
				$this->lines[$i]['price'] = $obj->price;
				$this->lines[$i]['qty'] = $obj->qty;
				$this->lines[$i]['batch'] = $obj->batch;
				$this->lines[$i]['eatby'] = $obj->eatby;
				$this->lines[$i]['fk_product'] = $obj->fk_product;
				$this->lines[$i]['commentaire'] = $obj->commentaire;
				$this->lines[$i]['tva_tx'] = $obj->tva_tx;
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


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class DemandeAvoirLine. You can also remove this and generate a CRUD class for lines objects.
 */
class DemandeAvoirLigne extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'demande_avoirdet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'demande_avoirdet';

	public $fields=array(
		'rowid' =>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'fk_demande_avoir' =>array('type'=>'integer', 'label'=>'Fkdemandeavoir', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>15),
		'fk_product' =>array('type'=>'integer:Product:product/class/product.class.php:1', 'label'=>'Fkproduct', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>20),
		'qty' =>array('type'=>'real', 'label'=>'Qty', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>25),
		'price' =>array('type'=>'double(24,8)', 'label'=>'Price', 'enabled'=>1, 'visible'=>-1, 'position'=>30),
		'commentaire' =>array('type'=>'varchar(255)', 'label'=>'Commentaire', 'enabled'=>1, 'visible'=>-1, 'position'=>35),
		'eatby' =>array('type'=>'datetime', 'label'=>'Eatby', 'enabled'=>1, 'visible'=>-1, 'position'=>40),
		'batch' =>array('type'=>'varchar(128)', 'label'=>'Batch', 'enabled'=>1, 'visible'=>-1, 'position'=>45),
		); 

	public $id;
	public $fk_demande_avoir;
	public $fk_product;
	public $qty;
	public $price;
	public $commentaire;
	public $eatby;
	public $batch;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
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

	   dol_syslog("DemandeAvoirLigne::create");

	   $error = 0;
	   $now = dol_now();

	   $this->db->begin();

	   
	   // Insert request
	   $sql = "INSERT INTO " . MAIN_DB_PREFIX . "demande_avoirdet(";
	   $sql .= "fk_demande_avoir";
	   $sql .= ", fk_product";
	   $sql .= ", qty";
	   $sql .= ", price";
	   $sql .= ", commentaire";
	   $sql .= ", eatby";
	   $sql .= ", batch";
	   $sql .= ") VALUES (";
	   $sql .= ((int) $this->fk_demande_avoir);
	   $sql .= ", ".((int) $this->fk_product);
	   $sql .= ", ".$this->qty;
	   $sql .= ", ".$this->price;
	   $sql .= ", '".$this->commentaire."'";
	   $sql .= ", '".$this->eatby."'";
	   $sql .= ", '".$this->batch."'";
	   $sql .= ")";

	   dol_syslog(get_class($this)."::create", LOG_DEBUG);
	   $resql = $this->db->query($sql);
	   if (!$resql) {
		   $error++;
		   $this->errors[] = "Error " . $this->db->lasterror();
	   }

	   if (!$error) {
		   $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "demandeavoirligne");
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

	   $sql = "SELECT rowid, fk_demande_avoir, fk_product, qty, price, commentaire, eatby, batch";
	   $sql .= " FROM ".MAIN_DB_PREFIX."demande_avoirdet";
	   $sql .= " WHERE rowid = ".((int) $id);

	   dol_syslog("DemandeAvoirLigne::fetch", LOG_DEBUG);
	   $resql = $this->db->query($sql);
	   if ($resql) {
		   $obj = $this->db->fetch_object($resql);

		   $this->id = $obj->rowid;
		   $this->fk_demande_avoir = $obj->fk_demande_avoir;
		   $this->fk_product = $obj->fk_product;
		   $this->qty = $obj->qty;
		   $this->price = $obj->price;
		   $this->commentaire = $obj->commentaire;
		   $this->eatby = $obj->eatby;
		   $this->batch = $obj->batch;

		   $this->db->free($resql);
		   return $this->id;
	   } else {
		   dol_print_error($this->db);
		   return -1;
	   }
   }
}
