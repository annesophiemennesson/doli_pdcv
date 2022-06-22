<?php
/* Copyright (C) 2014-2020	Charlene BENKE	<charlie@patas-monkey.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file	   htdocs/extraprice/admin/setup.php
 *  \ingroup	extraprice
 *  \brief	  Page d'administration-configuration du module extraprice
 */

$res=0;
if (! $res && file_exists("../../main.inc.php")) 
	$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists("../../../main.inc.php")) 
	$res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once("/extraprice/core/lib/extraprice.lib.php");

require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");

$langs->load("admin");
$langs->load("other");
$langs->load("extraprice@extraprice");

// Security check
if (! $user->admin || $user->design) accessforbidden();

$action = GETPOST('action', 'alpha');

/*
 * Actions
 */

if ($action == 'setvalue') {
	// on ajoute des \ devant les dollars
	
	// save the setting
	dolibarr_set_const($db, "ExtraPriceFormula", GETPOST('ExtraPriceFormula', 'text'), 'chaine', 0, '', $conf->entity);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}
if ($action == 'setproducttvaexport') {
	// on ajoute des \ devant les dollars

	// save the setting
	dolibarr_set_const($db, "EXTRAPRICE_PRODUCT_TVAEXPORT", GETPOST('ExtraPriceproducttvaexport', 'int'), 'chaine', 0, '', $conf->entity);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
	if (GETPOST('ExtraPriceproducttvaexport', 'int') > 0)
		dolibarr_set_const($db, "MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND", 2, 'chaine', 1, '', $conf->entity);
	else
		dolibarr_set_const($db, "MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND", 0, 'chaine', 1, '', $conf->entity);
}


/*
 * View
 */
$help_url='http://wiki.patas-monkey.com/index.php?title=Extraprice';

$page_name = $langs->trans("ExtraPriceSetup"). ' - ' . $langs->trans("extrapriceGeneralSetting");
llxHeader('', $page_name, $help_url);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($page_name, $linkback, 'title_setup');

$form = new Form($db);

$ExtraPriceFormula=$conf->global->ExtraPriceFormula;
$ExtraPriceproducttvaexport=$conf->global->EXTRAPRICE_PRODUCT_TVAEXPORT;

$head = extraprice_prepare_head();

dol_fiche_head($head, 'setup', $langs->trans("ExtraPrice"), -1, "extraprice@extraprice");

print_titre($langs->trans("ExtrapriceFormulaSetting"));
print '<br>';
print '<form method="post" action="setup.php">';
print '<input type="hidden" name="action" value="setvalue">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" >';
print '<tr class="liste_titre">';
print '<td colspan=2 width=100% align=left>'.$langs->trans("ExtrapriceFormula").'</td>';
print '</tr>'."\n";
print '<tr >';
print '<td width=50%  align=left>'.$langs->trans("ExtrapriceExplication").'</td>';
print '<td  align=left><textarea rows=5 cols=120 name=ExtraPriceFormula>'.$ExtraPriceFormula.'</textarea ></td>';
print '</tr>'."\n";

print '<tr ><td colspan=2>';
// Boutons d'action
print '<div class="tabsAction">';
print '<input type="submit" class="butAction" value="'.$langs->trans("Modify").'">';
print '</div>';
print '</td></tr>'."\n";
print '</table>';
print '</form>';

print_titre($langs->trans("ExtrapriceProductTVAEXport"));
print '<br>';
print '<form method="post" action="setup.php">';
print '<input type="hidden" name="action" value="setproducttvaexport">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" >';
print '<tr >';
print '<td width=50%  align=left>'.$langs->trans("ProductTVAExportExplication").'</td><td>';
print $form->select_produits(
				$ExtraPriceproducttvaexport, 'ExtraPriceproducttvaexport', 0, 
				$conf->product->limit_size, 0, -1, 2, '', 0
);
print '</td></tr>'."\n";

print '<tr ><td colspan=2>';
// Boutons d'action
print '<div class="tabsAction">';
print '<input type="submit" class="butAction" value="'.$langs->trans("Modify").'">';
print '</div>';
print '</td></tr>'."\n";
print '</table>';
print '</form>';
// Show errors
dol_htmloutput_errors($object->error, $object->errors);


// skip check version of our modules
if ($action == 'patasMonkeySkipCheckVersion') {
	dolibarr_set_const($db, "PATASMONKEY_SKIP_CHECKVERSION", GETPOST('value'), 'chaine', 0, '', $conf->entity);
}
$patasMonkeySkipCheckVersion=$conf->global->PATASMONKEY_SKIP_CHECKVERSION;
print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">';

print '<td width="350px">'.$langs->trans("PatasMonkeySkipCheckVersion").'</td>';
print '<td>'.$langs->trans("InfoPatasMonkeySkipCheckVersion").'</td>';
print '<td width=100px align=left>';
if ( $patasMonkeySkipCheckVersion == 1) {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=patasMonkeySkipCheckVersion&value=0">';
	print img_picto($langs->trans("Enabled"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=patasMonkeySkipCheckVersion&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td></tr>';
print "</table>";

/*
 *  Infos pour le support
 */
print '<br>';
libxml_use_internal_errors(true);
$sxe = simplexml_load_string(nl2br(file_get_contents('../changelog.xml')));
if ($sxe === false) {
	echo "Erreur lors du chargement du XML\n";
	foreach (libxml_get_errors() as $error) 
		print $error->message;
	exit;
} else
	$tblversions=$sxe->Version;

$currentversion = $tblversions[count($tblversions)-1];

print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td width=20%>'.$langs->trans("SupportModuleInformation").'</td>'."\n";
print '<td>'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";
print '<tr '.$bc[false].'><td >'.$langs->trans("DolibarrVersion").'</td><td>'.DOL_VERSION.'</td></tr>'."\n";
print '<tr '.$bc[true].'><td >'.$langs->trans("ModuleVersion").'</td>';
print '<td>'.$currentversion->attributes()->Number." (".$currentversion->attributes()->MonthVersion.')</td></tr>'."\n";
print '<tr '.$bc[false].'><td>'.$langs->trans("PHPVersion").'</td><td>'.version_php().'</td></tr>'."\n";
print '<tr '.$bc[true].'><td>'.$langs->trans("DatabaseVersion").'</td><td>'.$db::LABEL." ".$db->getVersion().'</td></tr>'."\n";
print '<tr '.$bc[false].'><td>'.$langs->trans("WebServerVersion").'</td><td>'.$_SERVER["SERVER_SOFTWARE"].'</td></tr>'."\n";
print '<tr>'."\n";
print '<td colspan="2">'.$langs->trans("SupportModuleInformationDesc").'</td></tr>'."\n";
print "</table>\n";

// Show messages
dol_htmloutput_mesg($object->mesg,'','ok');

// Footer
llxFooter();
$db->close();