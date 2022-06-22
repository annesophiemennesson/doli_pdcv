<?php
/* Copyright (C) 2014-2019		Charlene BENKE	<charlie@patas-monkey.com>
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
 *  \file	   htdocs/extraprice/admin/extraprice.php
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");

$langs->load("admin");
$langs->load("other");
$langs->load("extraprice@extraprice");

// Security check
if (! $user->admin || $user->design) accessforbidden();

$action = GETPOST('action', 'alpha');
$type = GETPOST('type', 'alpha');
$id = GETPOST('id');

$object = new Categorie($db);

if ($id > 0) {
	$result = $object->fetch($id);
	$upload_dir = $conf->categorie->multidir_output[$object->entity];
}

/*
 * Actions
 */

if ($action == 'setvalue') {
	// on ajoute des \ devant les dollars
	
	// save the setting
	dolibarr_set_const(
					$db, "ExtraPriceFormulaCateg".$type."-".$id,
					GETPOST('ExtraPriceFormulaCateg', 'text'),
					 'chaine', 0, '', $conf->entity
	);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}


/*
 * View
 */

$page_name = $langs->trans("ExtraPriceSetup");
llxHeader('', $page_name);

if ($type == 0) {
	$title=$langs->trans("ProductsCategoryShort");
	$ExtraPriceFormulaCateg=dolibarr_get_const($db, "ExtraPriceFormulaCateg0-".$id);
} elseif ($type == 2) {
	$title=$langs->trans("CustomersCategoryShort");
	$ExtraPriceFormulaCateg=dolibarr_get_const($db, "ExtraPriceFormulaCateg2"."-".$id);
}

$head = categories_prepare_head($object, $type);

dol_fiche_head($head, 'extraprice', $title, -1, "category");

if ((int) DOL_VERSION >= 5){
	$linkback = '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("BackToList").'</a>';
	$object->next_prev_filter=" type = ".$object->type;
	$object->ref = $object->label;
	$morehtmlref='<br><div class="refidno"><a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
	$ways = $object->print_all_ways(" &gt;&gt; ", '', 1);
	foreach ($ways as $way)
	{
	    $morehtmlref.=$way."<br>\n";
	}
	$morehtmlref.='</div>';

	dol_banner_tab($object, 'label', $linkback, ($user->societe_id?0:1), 'label', 'label', $morehtmlref, '', 0, '', '', 1);

}
else
	print_titre($langs->trans("ExtrapriceFormulaSetting"));

print '<br>';
print '<form method="post" action="categories.php">';
print '<input type="hidden" name="action" value="setvalue">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="id" value="'.$id.'">';
print '<table class="noborder" >';
print '<tr class="liste_titre">';
print '<td colspan=2 width=100% align=left>'.$langs->trans("ExtrapriceFormula").'</td>';
print '</tr>'."\n";
print '<tr >';
print '<td width=50%  align=left>'.$langs->trans("ExtrapriceExplication").'</td>';
print '<td  align=left><textarea rows=5 cols=120 name=ExtraPriceFormulaCateg>'.$ExtraPriceFormulaCateg.'</textarea ></td>';
print '</tr>'."\n";

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

// Show messages
dol_htmloutput_mesg($object->mesg, '', 'ok');

llxFooter();
$db->close();