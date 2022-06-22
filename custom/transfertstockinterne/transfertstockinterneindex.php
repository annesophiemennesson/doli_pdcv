<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       transfertstockinterne/transfertstockinterneindex.php
 *	\ingroup    transfertstockinterne
 *	\brief      Home page of transfertstockinterne top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("transfertstockinterne@transfertstockinterne"));

$action = GETPOST('action', 'aZ09');
$message = GETPOST('message', 'alpha');


/*
 * Actions
 */

// None


/*
 * View
 */

llxHeader("", "Transfert de stock interne");

print load_fiche_titre("Transfert de stock interne", '', '');

print '<div class="fichecenter">';
switch ($message){
	case 'new':
		print '<p class="message">Demande de transfert envoyée !</p>';
		break;
	case 'valid':
		print '<p class="message">Demande validée !</p>';
		break;
}

print '<a href="'.dol_buildpath('/custom/transfertstockinterne/new.php', 1).'">Nouvelle demande de transfert</a><br/>';
print '<a href="'.dol_buildpath('/custom/transfertstockinterne/list.php', 1).'">Liste des demandes</a><br/>';
print '<a href="'.dol_buildpath('/custom/transfertstockinterne/prepa.php', 1).'">Transferts à préparer</a><br/>';
print '<a href="'.dol_buildpath('/custom/transfertstockinterne/recep.php', 1).'">Transferts à réceptionner</a><br/>';
print '<a href="'.dol_buildpath('/custom/transfertstockinterne/ramasse_new.php', 1).'">Nouvelle demande de ramasse</a><br/>';
print '</div>';


// End of page
llxFooter();
$db->close();
