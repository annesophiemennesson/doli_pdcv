<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020       MB Informatique      	<info@mb-informatique.fr>
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
 * 	\defgroup   mbitpe     Module MBITPE
 *  \brief      MBITPE module descriptor.
 *
 *  \file       htdocs/mbitpe/core/modules/modMBITPE.class.php
 *  \ingroup    mbitpe
 *  \brief      Description and activation file for module MBITPE
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module MBITPE
 */
class modMBITPE extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;
		$this->db = $db;
		$this->numero = 172365;
		$this->rights_class = 'mbitpe';
		$this->family = "other";
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "MBITPEDescription";
		$this->descriptionlong = "MBITPE description (Long)";
		$this->editor_name = 'MB Informatique';
		$this->editor_url = 'https://www.mb-informatique.fr';
		$this->version = '1.0.6';
		$this->url_last_version = 'https://raw.githubusercontent.com/mbinformatique68/mbi_modules_last_version/main/mbitpe.txt';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'logo@mbitpe';
		$this->module_parts = array(
			'triggers' => 0,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'theme' => 0,
			'css' => array(),
			'js' => array(),
			'hooks' => array('invoicecard'),
			'moduleforexternal' => 0,
		);
		$this->dirs = array("/mbitpe/temp");
		$this->config_page_url = array("setup.php@mbitpe");
		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->langfiles = array("mbitpe@mbitpe");
		$this->phpmin = array(5, 5); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(8, 0); // Minimum version of Dolibarr required by module
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)

		$this->const = array();

		if (!isset($conf->mbitpe) || !isset($conf->mbitpe->enabled)) {
			$conf->mbitpe = new stdClass();
			$conf->mbitpe->enabled = 0;
		}

		$this->tabs = array();
		$this->dictionaries = array();
		$this->boxes = array();
		$this->cronjobs = array();

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read objects of MBITPE'; // Permission label
		$this->rights[$r][4] = 'myobject'; // In php code, permission will be checked by test if ($user->rights->mbitpe->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->mbitpe->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Update objects of MBITPE'; // Permission label
		$this->rights[$r][4] = 'myobject'; // In php code, permission will be checked by test if ($user->rights->mbitpe->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->mbitpe->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete objects of MBITPE'; // Permission label
		$this->rights[$r][4] = 'myobject'; // In php code, permission will be checked by test if ($user->rights->mbitpe->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->mbitpe->level1->level2)
		$r++;
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		$result = $this->_load_tables('/mbitpe/sql/');
		if ($result < 0) return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')

		// Permissions
		$this->remove($options);

		$sql=array("INSERT IGNORE INTO " . MAIN_DB_PREFIX . "const (name, entity) VALUES ('MBI_TERMINAL_NAME1', " . $conf->entity . ")", "INSERT IGNORE INTO " . MAIN_DB_PREFIX . "const (name, entity) VALUES ('MBI_TERMINAL_NAME2', " . $conf->entity . ")", "INSERT IGNORE INTO " . MAIN_DB_PREFIX . "const (name, entity) VALUES ('MBI_TERMINAL_NAME3', " . $conf->entity . ")");

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
