<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020      MB Informatique      <info@mb-informatique.fr>
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
 * \file    mbitpe/admin/setup.php
 * \ingroup mbitpe
 * \brief   MBITPE setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/mbitpe.lib.php';
try {
	if (!file_exists(DOL_DOCUMENT_ROOT . '/custom/mbitpe/core/modules/modMBITPE.class.php'))
		throw new Exception ('Does not exist');
	else
		$path = "/custom/mbitpe";
} catch (Exception $e) {
	$path = "/mbitpe";
}

require_once DOL_DOCUMENT_ROOT. $path . '/core/modules/modMBITPE.class.php';

// Translations
$langs->loadLangs(array("admin", "mbitpe@mbitpe"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$value = GETPOST('value', 'alpha');

$arrayofparameters = array(
	'MBI_TERMINAL_DEFAULT_BANK'=>array('css'=>'minwidth500', 'enabled'=>1),
	'MBI_TERMINAL_NAME1'=>array('css'=>'minwidth500', 'enabled'=>1),
	'MBI_TERMINAL_IP1'=>array('css'=>'minwidth500', 'enabled'=>1),
	'MBI_TERMINAL_PORT1'=>array('css'=>'minwidth500', 'enabled'=>1),
	'MBI_TERMINAL_NAME2'=>array('css'=>'minwidth500', 'enabled'=>1),
	'MBI_TERMINAL_IP2'=>array('css'=>'minwidth500', 'enabled'=>1),
	'MBI_TERMINAL_PORT2'=>array('css'=>'minwidth500', 'enabled'=>1),
	'MBI_TERMINAL_NAME3'=>array('css'=>'minwidth500', 'enabled'=>1),
	'MBI_TERMINAL_IP3'=>array('css'=>'minwidth500', 'enabled'=>1),
	'MBI_TERMINAL_PORT3'=>array('css'=>'minwidth500', 'enabled'=>1)
);

$resql=$db->query("SELECT name FROM " . MAIN_DB_PREFIX . "const WHERE name LIKE 'MBI_TERMINAL_NAME%'");
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	if ($num) {
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				if ($obj->name !== "MBI_TERMINAL_NAME1" && $obj->name !== "MBI_TERMINAL_NAME2" && $obj->name !== "MBI_TERMINAL_NAME3") {
					$arrayofparameters["MBI_TERMINAL_NAME" . (int) filter_var($obj->name, FILTER_SANITIZE_NUMBER_INT)] = array('css'=>'minwidth500', 'enabled'=>1);
					$arrayofparameters["MBI_TERMINAL_IP" . (int) filter_var($obj->name, FILTER_SANITIZE_NUMBER_INT)] = array('css'=>'minwidth500', 'enabled'=>1);
					$arrayofparameters["MBI_TERMINAL_PORT" . (int) filter_var($obj->name, FILTER_SANITIZE_NUMBER_INT)] = array('css'=>'minwidth500', 'enabled'=>1);
				}
			}
			$i++;
		}
	}
}

$error = 0;
$setupnotempty = 0;

/*
 * Actions
 */

if ((float) DOL_VERSION >= 6)
{
	include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
}

if ($action == "addTerminal") {
	$resql=$db->query("SELECT name FROM " . MAIN_DB_PREFIX . "const WHERE name LIKE 'MBI_TERMINAL_NAME%'");
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$i++;
			}
			$i++;
			$db->begin();
			$db->query("INSERT INTO " . MAIN_DB_PREFIX . "const (name, entity) VALUES ('MBI_TERMINAL_NAME" . $i++ . "', " . $conf->entity . ")");
			$db->commit();
		}
	}
}

/*
 * View
 */

$form = new Form($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$page_name = "MBITPESetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_logo@mbitpe');

// Configuration header
$head = mbitpeAdminPrepareHead();
dol_fiche_head($head, 'settings', '', -1, "mbitpe@mbitpe");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("MBITPESetupPage").'</span><br><br>';

$mbitpe = new modMBITPE($db);
$context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));
$last_version = file_get_contents($mbitpe->url_last_version, false, $context);
$last_version = preg_replace("/\s+/", "", $last_version);

if (version_compare($mbitpe->version, $last_version, '<')) {
	echo "<div style='font-size: 18px; color: red;'>";
	echo "<hr><i class='fas fa-download'></i> " . $langs->trans('MBI_UPDATE_AVAILABLE') . " " . $mbitpe->version . " <i class='fas fa-long-arrow-alt-right'></i> " . $last_version . "<br><a href='https://www.dolistore.com/fr/modules/1342-MBI-TPE.html' target='_blank'>" . $langs->trans('MBI_DOWNLOAD_HERE') . "</a><br><hr><br>";
	echo "</div>";
} else if (version_compare($mbitpe->version, $last_version, '=')) {
	echo "<div style='font-size: 18px;'>";
	echo "<hr>" . $langs->trans('MBI_UPTODATE') . " <i class='fas fa-check-circle' style='color: green;'></i><br><hr><br>";
	echo "</div>";
}

if ($action == 'edit')
{
	if (extension_loaded('sockets')) {
		echo "<p>" . $langs->trans("MBITPESocketOK") . " <i class='far fa-check-circle' style='color: green;'></i></p>";
	} else {
		echo "<p>" . $langs->trans("MBITPESocketKO") . " <i class='fas fa-exclamation-triangle' style='color: red;'></i></p>";
	}
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	foreach ($arrayofparameters as $key => $val)
	{
		if ($key == "MBI_TERMINAL_DEFAULT_BANK") {
			if (empty($conf->global->$key)) {
				$conf->global->$key = 1;
			}
			print '<tr class="oddeven"><td>';
			$tooltiphelp = (($langs->trans($key.'Tooltip') != $key.'Tooltip') ? $langs->trans($key.'Tooltip') : '');
			print $form->textwithpicto($langs->trans($key), $tooltiphelp);
			print '</td><td><select name="' . $key . '">';
			$resql=$db->query("SELECT rowid, ref FROM " . MAIN_DB_PREFIX . "bank_account WHERE rowid = " . $conf->global->$key);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				if ($num) {
					while ($i < $num) {
						$obj = $db->fetch_object($resql);
						if ($obj) {
							print "<option value='" . $obj->rowid . "'>$obj->ref</option>";
						}
						$i++;
					}
				}
			}
			$resql=$db->query("SELECT rowid, ref FROM " . MAIN_DB_PREFIX . "bank_account WHERE rowid != " . $conf->global->$key);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				if ($num) {
					while ($i < $num) {
						$obj = $db->fetch_object($resql);
						if ($obj) {
							print "<option value='" . $obj->rowid . "'>$obj->ref</option>";
						}
						$i++;
					}
				}
			}
			echo "</select></td></tr>";
		} else if (strpos($key, 'MBI_TERMINAL_NAME') !== false) {
			print '<tr class="oddeven"><td>';
			$tooltiphelp = (($langs->trans($key . 'Tooltip') != $key . 'Tooltip') ? $langs->trans($key . 'Tooltip') : '');
			print $form->textwithpicto($langs->trans("MBI_TERMINAL_NAME") . " " . (int) filter_var($key, FILTER_SANITIZE_NUMBER_INT), $tooltiphelp);
			print '</td><td><input name="' . $key . '"  class="flat ' . (empty($val['css']) ? 'minwidth200' : $val['css']) . '" value="' . $conf->global->$key . '"></td></tr>';
		} else if (strpos($key, 'MBI_TERMINAL_IP') !== false) {
			print '<tr class="oddeven"><td>';
			$tooltiphelp = (($langs->trans($key . 'Tooltip') != $key . 'Tooltip') ? $langs->trans($key . 'Tooltip') : '');
			print $form->textwithpicto($langs->trans("MBI_TERMINAL_IP") . " " . (int) filter_var($key, FILTER_SANITIZE_NUMBER_INT), $tooltiphelp);
			print '</td><td><input name="' . $key . '"  class="flat ' . (empty($val['css']) ? 'minwidth200' : $val['css']) . '" value="' . $conf->global->$key . '"></td></tr>';
		} else if (strpos($key, 'MBI_TERMINAL_PORT') !== false) {
			print '<tr class="oddeven"><td>';
			$tooltiphelp = (($langs->trans($key . 'Tooltip') != $key . 'Tooltip') ? $langs->trans($key . 'Tooltip') : '');
			print $form->textwithpicto($langs->trans("MBI_TERMINAL_PORT") . " " . (int) filter_var($key, FILTER_SANITIZE_NUMBER_INT), $tooltiphelp);
			print '</td><td><input name="' . $key . '"  class="flat ' . (empty($val['css']) ? 'minwidth200' : $val['css']) . '" value="' . $conf->global->$key . '"></td></tr>';
		} else {
			print '<tr class="oddeven"><td>';
			$tooltiphelp = (($langs->trans($key . 'Tooltip') != $key . 'Tooltip') ? $langs->trans($key . 'Tooltip') : '');
			print $form->textwithpicto($langs->trans($key), $tooltiphelp);
			print '</td><td><input name="' . $key . '"  class="flat ' . (empty($val['css']) ? 'minwidth200' : $val['css']) . '" value="' . $conf->global->$key . '"></td></tr>';
		}
	}
	print '</table>';

	print '<br><div class="center">';
	print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
	print '</div>';

	print '</form>';
	print '<br>';
} else {
	if (!empty($arrayofparameters))
	{
		if (extension_loaded('sockets')) {
			echo "<p>" . $langs->trans("MBITPESocketOK") . " <i class='far fa-check-circle' style='color: green;'></i></p>";
		} else {
			echo "<p>" . $langs->trans("MBITPESocketKO") . " <i class='fas fa-exclamation-triangle' style='color: red;'></i></p>";
		}
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

		foreach ($arrayofparameters as $key => $val)
		{
			$setupnotempty++;

			if ($key == "MBI_TERMINAL_DEFAULT_BANK") {
				print '<tr class="oddeven"><td>';
				$tooltiphelp = (($langs->trans($key . 'Tooltip') != $key . 'Tooltip') ? $langs->trans($key . 'Tooltip') : '');
				print $form->textwithpicto($langs->trans($key), $tooltiphelp);
				$resql=$db->query("SELECT rowid, ref FROM " . MAIN_DB_PREFIX . "bank_account WHERE rowid = " . $conf->global->$key);
				if ($resql) {
					$num = $db->num_rows($resql);
					$i = 0;
					if ($num) {
						while ($i < $num) {
							$obj = $db->fetch_object($resql);
							if ($obj) {
								print '</td><td>' . $obj->ref . '</td></tr>';
							}
							$i++;
						}
					}
				}
			} else if (strpos($key, 'MBI_TERMINAL_NAME') !== false) {
				print '<tr class="oddeven"><td>';
				$tooltiphelp = (($langs->trans($key . 'Tooltip') != $key . 'Tooltip') ? $langs->trans($key . 'Tooltip') : '');
				print $form->textwithpicto($langs->trans("MBI_TERMINAL_NAME") . " " . (int) filter_var($key, FILTER_SANITIZE_NUMBER_INT), $tooltiphelp);
				print '</td><td>' . $conf->global->$key . '</td></tr>';
			} else if (strpos($key, 'MBI_TERMINAL_IP') !== false) {
				print '<tr class="oddeven"><td>';
				$tooltiphelp = (($langs->trans($key . 'Tooltip') != $key . 'Tooltip') ? $langs->trans($key . 'Tooltip') : '');
				print $form->textwithpicto($langs->trans("MBI_TERMINAL_IP") . " " . (int) filter_var($key, FILTER_SANITIZE_NUMBER_INT), $tooltiphelp);
				print '</td><td>' . $conf->global->$key . '</td></tr>';
			} else if (strpos($key, 'MBI_TERMINAL_PORT') !== false) {
				print '<tr class="oddeven"><td>';
				$tooltiphelp = (($langs->trans($key . 'Tooltip') != $key . 'Tooltip') ? $langs->trans($key . 'Tooltip') : '');
				print $form->textwithpicto($langs->trans("MBI_TERMINAL_PORT") . " " . (int) filter_var($key, FILTER_SANITIZE_NUMBER_INT), $tooltiphelp);
				print '</td><td>' . $conf->global->$key . '</td></tr>';
			} else {
				print '<tr class="oddeven"><td>';
				$tooltiphelp = (($langs->trans($key . 'Tooltip') != $key . 'Tooltip') ? $langs->trans($key . 'Tooltip') : '');
				print $form->textwithpicto($langs->trans($key), $tooltiphelp);
				print '</td><td>' . $conf->global->$key . '</td></tr>';
			}
		}

		print '</table>';

		print '<div class="tabsAction">';
		// print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=addTerminal">Ajouter un terminal</a>';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
		print '</div>';
	}
}

// Page end
dol_fiche_end();

llxFooter();
$db->close();
