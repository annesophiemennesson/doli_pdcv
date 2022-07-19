<?php
/* Copyright (C) 2022	Anne-Sophie MENNESSON	<annesophie.mennesson@gmail.com>
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

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}


print "<!-- BEGIN PHP TEMPLATE /custom/gestionrebuts/tpl/linkedopjectblock.tpl.php -->\n";


global $user;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

$langs->load("orders");

$total = 0;
$ilink = 0;
foreach ($linkedObjectBlock as $key => $objectlink) {
	$ilink++;

	$trclass = 'oddeven';
	if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
		$trclass .= ' liste_sub_total';
	}
	?>
	<tr class="<?php echo $trclass; ?>">
		<td>Demande d'avoir</td>
		<td><a href="<?php echo DOL_URL_ROOT.'/custom/gestionrebuts/detail.php?id='.$objectlink->id ?>">N°<?php echo $objectlink->id; ?></a></td>
		<td class="left"></td>
		<td class="center"><?php echo dol_print_date($objectlink->date_creation, 'day'); ?></td>
		<td class="right"></td>
		<td class="right"></td>
		<td class="right"></td>
	</tr>
	<?php
}

print "<!-- END PHP TEMPLATE -->\n";
