<?php
/* Copyright (C) 2020       MB Informatique         <info@mb-informatique.fr> */

class ActionsMBITPE
{
	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param array()         $parameters     Hook metadatas (context, etc...)
	 * @param CommonObject    &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string          &$action Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */

	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $db;
		$langs->load("mbitpe@mbitpe");
		try {
			if (!file_exists(DOL_DOCUMENT_ROOT . '/custom/mbitpe/class/payment.php'))
				throw new Exception ('Does not exist');
			else
				$path = "/custom/mbitpe";
		} catch (Exception $e) {
			$path = "/mbitpe";
		}

		$mbitpe_color = $conf->global->THEME_ELDY_TOPMENU_BACK1 ? "rgb(" . $conf->global->THEME_ELDY_TOPMENU_BACK1 . ")" : "#263c5c";

		$totalttc = $object->multicurrency_total_ttc;																					// TOTAL TTC
		$dejaregle = $object->getSommePaiement(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);				// DEJA REGLE
		$resteapayer = price2num($totalttc - $dejaregle - $object->getSumCreditNotesUsed() - $object->getSumDepositsUsed());																				// RESTE A PAYER

		if ($object->statut != Facture::STATUS_DRAFT && $resteapayer > 0) {
			if (!empty($conf->global->MBI_TERMINAL_NAME1)) {
				echo "<link rel=\"stylesheet\" href='" . DOL_URL_ROOT . $path . "/load-awesome/css/pacman.min.css'>";
				echo "<a class='butAction' onclick='MBITerminalDialog()' target='_blank'>" . $langs->trans('MBITPEPaymentButton1') . "</a>";
				echo "<script>function MBITerminalDialog() {
						  $( function() {
							$( \"#dialog-confirm-MBITerminal\" ).dialog({
							  resizable: false,
							  height: \"auto\",
							  width: 400,
							  modal: true
							});
						  } ); }
						  </script>";
				echo "<div id=\"dialog-confirm-MBITerminal\" title=\"" . $langs->trans('MBITPEPaymentButton1') . "\" style='display: none; text-align: center;'>";
				echo "<div id='chooseTerminal'>";
				echo "<style>
						#montantTerminal::-webkit-outer-spin-button,
						#montantTerminal::-webkit-inner-spin-button {
						  -webkit-appearance: none;
						  margin: 0;
						}

						#montantTerminal {
						  -moz-appearance: textfield;
						}
						</style>";
				echo "<input id='montantTerminal' type='number' style='text-align: right; margin-bottom: 10px; font-size: 20px;' value='" . $resteapayer . "' max='" . $resteapayer . "' step='any'><span style='font-size: 20px;'>€</span><br>";
				echo "<input id='montantTerminalPercentage' type='number' min='0' max='100' step='10' style='text-align: right; margin-bottom: 10px; font-size: 20px;' value='100'><span style='font-size: 20px;'>%</span><br><br>";
				echo "<script>$('#montantTerminalPercentage').on('input', function() { $('#montantTerminal').val(Math.floor(($(this).val() / 100) * " . $resteapayer . " * 100) / 100) });</script>";


//				$resql=$db->query("SELECT name FROM " . MAIN_DB_PREFIX . "const WHERE name LIKE 'MBI_TERMINAL_NAME%'");
//				if ($resql) {
//					$num = $db->num_rows($resql);
//					$i = 0;
//					if ($num) {
//						while ($i < $num) {
//							$obj = $db->fetch_object($resql);
//							if ($obj) {
//								$name = $obj->name;
//								if (!empty($conf->global->$name)) {
//									echo $conf->global->$name . "<br>";
//									echo "<input type='button' class='MBITerminalButton' value='" . (int) filter_var($obj->name, FILTER_SANITIZE_NUMBER_INT) . "' style='width: 200px; height: 40px; background: " . $mbitpe_color . "; font-weight: bold; color: white; border: 0 none; border-radius: 1px; cursor: pointer; padding: 10px 5px; margin: 10px 5px; font-size: 18px;'><br>";
//								}
//							}
//							$i++;
//						}
//					}
//				}


				if (!empty($conf->global->MBI_TERMINAL_NAME1)) {
					echo $conf->global->MBI_TERMINAL_NAME1 . "<br>";
					echo "<input type='button' class='MBITerminalButton' value='1' style='width: 200px; height: 40px; background: " . $mbitpe_color . "; font-weight: bold; color: white; border: 0 none; border-radius: 1px; cursor: pointer; padding: 10px 5px; margin: 10px 5px; font-size: 18px;'><br>";
				}
				if (!empty($conf->global->MBI_TERMINAL_NAME2)) {
					echo $conf->global->MBI_TERMINAL_NAME2 . "<br>";
					echo "<input type='button' class='MBITerminalButton' value='2' style='width: 200px; height: 40px; background: " . $mbitpe_color . "; font-weight: bold; color: white; border: 0 none; border-radius: 1px; cursor: pointer; padding: 10px 5px; margin: 10px 5px; font-size: 18px;'><br>";
				}
				if (!empty($conf->global->MBI_TERMINAL_NAME3)) {
					echo $conf->global->MBI_TERMINAL_NAME3 . "<br>";
					echo "<input type='button' class='MBITerminalButton' value='3' style='width: 200px; height: 40px; background: " . $mbitpe_color . "; font-weight: bold; color: white; border: 0 none; border-radius: 1px; cursor: pointer; padding: 10px 5px; margin: 10px 5px; font-size: 18px;'><br>";
				}
				echo "</div>";
				echo "<div style='width: 100%; display: none; text-align : center; margin-top: 10px;' id='loaderMessage'>" . $langs->trans('MBITPEFollowInstructions') . "</div>";
				echo "<div style='width: 100%; display: none; text-align : center; margin-top: 10px;' id='cancelMessage'>" . $langs->trans('MBITPECancelMessage') . "</div>";
				echo "<br>";
				echo "<div class=\"la-pacman\" style='display: none; margin-left: 160px; margin-top: 20px; margin-bottom: 20px; color: " . $mbitpe_color . ";' id='MBITerminalLoader'><div></div><div></div><div></div><div></div><div></div><div></div></div>";
				echo "<div id='errorMessage'></div>";
				echo "<div id='status'></div>";
				echo "</div>";

				echo "<script>var post = undefined;
					$('.MBITerminalButton').click(function() {
						$('#MBITerminalLoader').show(); $('#MBITerminalCancel').show(); $('#loaderMessage').show();
						$('#chooseTerminal').hide(); post = $.post( '" . DOL_URL_ROOT . $path . "/class/payment.php', { terminal: this.value, montantTerminal: $('#montantTerminal').val(), object: " . $object->id . " },
						function(data) { $('#MBITerminalLoader').hide(); $('#MBITerminalCancel').hide(); $('#loaderMessage').hide();
						console.log(data);
						if (data == '1') {
							$('<span>" . $langs->trans('MBITPEOperationPerformed') . " </span><br><br><i style=\"color: green; margin-bottom: 20px;\" class=\"fas fa-3x fa-check-circle\"></i>').appendTo('#status');
						}
						else if (data == '0') {
						    $('<span>" . $langs->trans('MBITPEOperationNotPerformed') . "</span><br><br><i style=\"color: red; margin-bottom: 20px;\" class=\"fas fa-3x fa-exclamation-circle\"></i>').appendTo('#status');
						}
						else {
							$('<span>" . $langs->trans('MBITPEOperationNotPerformed') . "</span><br><br><i style=\"color: red; margin-bottom: 20px;\" class=\"fas fa-3x fa-exclamation-circle\"></i>').appendTo('#status');
							$('<span>' + data + '</span>').appendTo('#errorMessage');
						}
						setTimeout(function () { $('#dialog-confirm-MBITerminal').dialog('close'); document.location.reload(); }, 2000); })
					});
					</script>";
			}
		}

		return 0;
	}
}
