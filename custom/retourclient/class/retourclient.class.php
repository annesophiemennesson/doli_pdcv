<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2022	Anne-Sophie Mennesson	<annesophie.mennesson@gmail.com>
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
 * \file        class/retourclient.class.php
 * \ingroup     retourclient
 * \brief       This file is a CRUD class file for RetourClient (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for RetourClient
 */
class RetourClient extends CommonObject
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'retourclient';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'retourclient';

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
	 * @var string String with name of icon for retourclient. Must be the part after the 'object_' into object_retourclient.png
	 */
	public $picto = 'retourclient@retourclient';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;


	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if need to validate with $this->validateField()
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */

	public $fields=array(
		'rowid' =>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'fk_facture' =>array('type'=>'integer', 'label'=>'Fkfacture', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>15),
		'fk_user_crea' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fkusercrea', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>20),
		'statut' =>array('type'=>'enum(\'ouverte\',\'valid??e\',\'rembours??e\')', 'label'=>'Statut', 'enabled'=>1, 'visible'=>-1, 'position'=>500),
		'date_creation' =>array('type'=>'datetime', 'label'=>'Datecreation', 'enabled'=>1, 'visible'=>-1, 'position'=>30),
		'montant_ht' =>array('type'=>'double(24,8)', 'label'=>'Montantht', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>35),
		'montant_tva' =>array('type'=>'double(24,8)', 'label'=>'Montanttva', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>40),
		'montant_ttc' =>array('type'=>'double(24,8)', 'label'=>'Montantttc', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>45),
		'mode_remboursement' =>array('type'=>'enum(\'esp??ces\',\'CB\')', 'label'=>'Moderemboursement', 'enabled'=>1, 'visible'=>-1, 'position'=>50),
		'model_pdf' =>array('type'=>'varchar(255)', 'label'=>'Modelpdf', 'enabled'=>1, 'visible'=>0, 'position'=>55),
		'last_main_doc' =>array('type'=>'varchar(255)', 'label'=>'Lastmaindoc', 'enabled'=>1, 'visible'=>-1, 'position'=>60),
		); 
	public $rowid;
	public $fk_facture;
	public $fk_user_crea;
	public $statut;
	public $date_creation;
	public $montant_ht;
	public $montant_tva;
	public $montant_ttc;
	public $mode_remboursement;
	public $model_pdf;
	public $last_main_doc;
	public $lines;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'retourclient_retourclientline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_retourclient';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'RetourClientline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array();

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('retourclient_retourclientdet');

	// /**
	//  * @var RetourClientLine[]     Array of subtable lines
	//  */
	// public $lines = array();



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
		/*if ($user->rights->retourclient->retourclient->read) {
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

		dol_syslog("Retourclient::create");

		if (empty($this->model_pdf)) {
			$this->model_pdf = $conf->global->RETOURCLIENT_ADDON_PDF;
		}

		$error = 0;
		$now = dol_now();

		$this->db->begin();

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "retourclient(";
		$sql .= "fk_facture";
		$sql .= ", fk_user_crea";
		$sql .= ", statut";
		$sql .= ", date_creation";
		$sql .= ", model_pdf";
		$sql .= ") VALUES (";
		$sql .= ((int) $this->fk_facture);
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
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "retourclient");
		}
		

		// Add object linked
		if (!$error) {
			$ret = $this->add_object_linked("facture", $this->fk_facture);
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

		$sql = "SELECT rowid, fk_facture, fk_user_crea, statut, montant_ht, montant_tva, montant_ttc, mode_remboursement, ";
		$sql .= "date_creation, model_pdf, last_main_doc";
		$sql .= " FROM ".MAIN_DB_PREFIX."retourclient";
		$sql .= " WHERE rowid = ".((int) $id);

		dol_syslog("Demande_avoir::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);

			$this->id = $obj->rowid;
			$this->fk_facture = $obj->fk_facture;
			$this->fk_user_crea = $obj->fk_user_crea;
			$this->statut = $obj->statut;
			$this->date_creation = $obj->date_creation;
			$this->montant_ht = $obj->montant_ht;
			$this->montant_tva = $obj->montant_tva;
			$this->montant_ttc = $obj->montant_ttc;
			$this->mode_remboursement = $obj->mode_remboursement;
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

		if (!empty($this->statut))
			$sql .= " statut='".$this->statut."',";
		if (!empty($this->mode_remboursement))
			$sql .= " mode_remboursement='".$this->mode_remboursement."',";
		if (!empty($this->montant_ht))
			$sql .= " montant_ht='".$this->montant_ht."',";
		if (!empty($this->montant_tva))
			$sql .= " montant_tva='".$this->montant_tva."',";
		if (!empty($this->montant_ttc))
			$sql .= " montant_ttc='".$this->montant_ttc."',";

		$sql .= " last_main_doc='".$this->last_main_doc."', ";		
		$sql .= " model_pdf='".$this->model_pdf."'";
		
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

		$langs->load("retourclient@retourclient");

		if (!dol_strlen($modele)) {
			$modele = 'retourclient';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->RETOURCLIENT_ADDON_PDF)) {
				$modele = $conf->global->RETOURCLIENT_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/retourclient/doc/";

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

		$sql = "SELECT fk_product, qty, montant_ht, total_ht, total_tva, total_ttc, commentaire, destination, batch, label, taux_tva, d.rowid
				FROM ".MAIN_DB_PREFIX."retourclientdet as d
				INNER JOIN ".MAIN_DB_PREFIX."product AS p ON (p.rowid = d.fk_product)
				WHERE fk_retourclient = ".((int) $this->id);

		dol_syslog(get_class($this)."::loadProduits", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$this->lines[$i]['rowid'] = $obj->rowid;
				$this->lines[$i]['label'] = $obj->label;
				$this->lines[$i]['montant_ht'] = $obj->montant_ht;
				$this->lines[$i]['total_ht'] = $obj->total_ht;
				$this->lines[$i]['total_tva'] = $obj->total_tva;
				$this->lines[$i]['total_ttc'] = $obj->total_ttc;
				$this->lines[$i]['qty'] = $obj->qty;
				$this->lines[$i]['batch'] = $obj->batch;
				$this->lines[$i]['destination'] = $obj->destination;
				$this->lines[$i]['fk_product'] = $obj->fk_product;
				$this->lines[$i]['commentaire'] = $obj->commentaire;
				$this->lines[$i]['taux_tva'] = $obj->taux_tva;
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
 * Class RetourClientLine. You can also remove this and generate a CRUD class for lines objects.
 */
class RetourClientLine extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'retourclientdet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'retourclientdet';
	
	public $fields=array(
		'rowid' =>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'fk_retourclient' =>array('type'=>'integer', 'label'=>'Fkretourclient', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>15),
		'fk_product' =>array('type'=>'integer:Product:product/class/product.class.php:1', 'label'=>'Fkproduct', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>20),
		'batch' =>array('type'=>'varchar(128)', 'label'=>'Batch', 'enabled'=>1, 'visible'=>-1, 'position'=>25),
		'qty' =>array('type'=>'double', 'label'=>'Qty', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>30),
		'montant_ht' =>array('type'=>'double(24,8)', 'label'=>'Montantht', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>35),
		'total_ht' =>array('type'=>'double(24,8)', 'label'=>'Totalht', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>40),
		'total_tva' =>array('type'=>'double(24,8)', 'label'=>'Totaltva', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>45),
		'total_ttc' =>array('type'=>'double(24,8)', 'label'=>'Totalttc', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>50),
		'taux_tva' =>array('type'=>'double(24,8)', 'label'=>'Tauxtva', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>55),
		'commentaire' =>array('type'=>'varchar(255)', 'label'=>'Commentaire', 'enabled'=>1, 'visible'=>-1, 'position'=>60),
		'destination' =>array('type'=>'enum(\'remise en stock magasin\',\'remise en stock depot\',\'destruction\')', 'label'=>'Destination', 'enabled'=>1, 'visible'=>-1, 'position'=>65),
		); 

	public $id;
	public $fk_retourclient;
	public $fk_product;
	public $qty;
	public $montant_ht;
	public $total_ht;
	public $total_tva;
	public $total_ttc;
	public $taux_tva;
	public $destination;
	public $commentaire;
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

	   dol_syslog("RetourClientLigne::create");

	   $error = 0;
	   $now = dol_now();

	   $this->db->begin();

	   
	   // Insert request
	   $sql = "INSERT INTO " . MAIN_DB_PREFIX . "retourclientdet(";
	   $sql .= "fk_retourclient";
	   $sql .= ", fk_product";
	   $sql .= ", qty";
	   $sql .= ", montant_ht";
	   $sql .= ", total_ht";
	   $sql .= ", total_tva";
	   $sql .= ", total_ttc";
	   $sql .= ", taux_tva";
	   $sql .= ", destination";
	   $sql .= ", commentaire";
	   $sql .= ", batch";
	   $sql .= ") VALUES (";
	   $sql .= ((int) $this->fk_retourclient);
	   $sql .= ", ".((int) $this->fk_product);
	   $sql .= ", ".$this->qty;
	   $sql .= ", '".$this->montant_ht."'";
	   $sql .= ", '".$this->total_ht."'";
	   $sql .= ", '".$this->total_tva."'";
	   $sql .= ", '".$this->total_ttc."'";
	   $sql .= ", '".$this->taux_tva."'";
	   $sql .= ", '".$this->destination."'";
	   $sql .= ", '".$this->commentaire."'";
	   $sql .= ", '".$this->batch."'";
	   $sql .= ")";

	   dol_syslog(get_class($this)."::create", LOG_DEBUG);
	   $resql = $this->db->query($sql);
	   if (!$resql) {
		   $error++;
		   $this->errors[] = "Error " . $this->db->lasterror();
	   }

	   if (!$error) {
		   $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "retourclientligne");
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

	   $sql = "SELECT rowid, fk_retourclient, fk_product, qty, destinatation, commentaire, batch, montant_ht, total_ht, total_tva,total_ttc, taux_tva";
	   $sql .= " FROM ".MAIN_DB_PREFIX."retourclientdet";
	   $sql .= " WHERE rowid = ".((int) $id);

	   dol_syslog("DemandeAvoirLigne::fetch", LOG_DEBUG);
	   $resql = $this->db->query($sql);
	   if ($resql) {
		   $obj = $this->db->fetch_object($resql);

		   $this->id = $obj->rowid;
		   $this->fk_retourclient = $obj->fk_retourclient;
		   $this->fk_product = $obj->fk_product;
		   $this->qty = $obj->qty;
		   $this->destinatation = $obj->destinatation;
		   $this->commentaire = $obj->commentaire;
		   $this->batch = $obj->batch;
		   $this->montant_ht = $obj->montant_ht;
		   $this->total_ht = $obj->total_ht;
		   $this->total_tva = $obj->total_tva;
		   $this->total_ttc = $obj->total_ttc;
		   $this->taux_tva = $obj->taux_tva;

		   $this->db->free($resql);
		   return $this->id;
	   } else {
		   dol_print_error($this->db);
		   return -1;
	   }
   }
}
