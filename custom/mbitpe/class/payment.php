<?php

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

global $db, $conf, $user, $langs;

$langs->loadLangs(array("mbitpe@mbitpe"));

require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

function get_string_between2($string, $start, $end)
{
	$string = ' ' . $string;
	$ini = strpos($string, $start);
	if ($ini == 0) return '';
	$ini += strlen($start);
	$len = strpos($string, $end, $ini) - $ini;
	return substr($string, $ini, $len);
}

function status($user, $db, $conf, $object, $langs)
{
	error_reporting(0);

	$totalttc = $object->multicurrency_total_ttc;																					// TOTAL TTC
	$dejaregle = $object->getSommePaiement(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);				// DEJA REGLE
	$resteapayer = price2num($totalttc - $dejaregle - $object->getSumCreditNotesUsed() - $object->getSumDepositsUsed());

	$terminalIP = "MBI_TERMINAL_IP" . $_POST['terminal'];
	$terminalPort = "MBI_TERMINAL_PORT" . $_POST['terminal'];

	$host = $conf->global->$terminalIP;                                                                                             // IP TERMINAL
	$port = $conf->global->$terminalPort;                                                                                           // PORT TERMINAL
	$version = "CZ0040300";                                                                                                         // VERSION PROTOCOLE
	$identifiant = "CJ012247300123456";																								// IDENTIFIANT
	$caisse = "CA00201";                                                                                                            // NUMERO CAISSE
	$montantTerminal = $_POST['montantTerminal'];                                                                               	// RESTE A PAYER
	$amount = "CB00" . strlen($montantTerminal * 100) . "" . $montantTerminal * 100;                                         // MONTANT
	$action = "CD0010";                                                                                                             // ACTION (DEBIT)
	$devise = "CE003978";                                                                                                           // DEVISE (EURO)
	$message = $version . "" . $identifiant . "" . $caisse . "" . $amount . "" . $action . "" . $devise;                            // INSTRUCTIONS

	if (!extension_loaded('sockets')) {
		echo $langs->trans("MBITPESOCKETERROR");
		exit;
	}
	$socket = socket_create(AF_INET, SOCK_STREAM, 0);                   									// CREATION SOCKET
	if ($socket === false) {
		echo $langs->trans("MBITPECREATEERROR");
		exit;
	}
	$result = socket_connect($socket, $host, $port);                              													// CONNEXION
	if ($result === false) {
		echo $langs->trans("MBITPECONNECTIONERROR");
		exit;
	}
	$result = socket_write($socket, $message, strlen($message));                                    								// ENVOI INSTRUCTIONS
	if ($result === false) {
		echo $langs->trans("MBITPEWRITEERROR");
		exit;
	}
	$result = socket_read($socket, 1024) . "-" or die("Echec de lecture de la réponse\n");                                	// REPONSE

	if ($result !== "" && $result !== false) {
		$status = get_string_between2($result, 'AE002', '-');                                                            // STATUT
		$status = substr($status, 0, 2);

		if ($status == "10") {                                                                                                		// SI OPERATION EFFECTUE
			echo "1";
			require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
			$accountid = $conf->global->MBI_TERMINAL_DEFAULT_BANK;
			$amounts = array($object->id => $montantTerminal);
			$paiement = new Paiement($db);
			$paiement->datepaye = dol_now();
			$paiement->amounts = $amounts;
			$paiement->multicurrency_amounts = $amounts;
			$paiement->paiementid = 6; // CB
			$paiement->num_paiement = '';
			$paiement->note = '';
			$paiement_id = $paiement->create($user);
			$paiement->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $accountid, '', '');							// ENREGISTREMENT DU PAIEMENT DANS LA BANQUE
			if ($montantTerminal >= $resteapayer) {
				$object->set_paid($user);                                                                                          	// FACTURE DECLAREE PAYEE
			}
			$primary_account_number = get_string_between2($result, 'AA', 'CF');
			$private_data = get_string_between2($result, 'CF', 'CC');
			$db->begin();
			$db->query("INSERT INTO " . MAIN_DB_PREFIX . "mbi_tpe (entity, object, primary_account_number, private_data, full_data) VALUES ('" . $conf->entity . "', '" . $object->id . "', '" . $primary_account_number . "', '" . $private_data . "', '" . $result . "')");
			$db->commit();
		} else {
			echo "0";
		}

		socket_close($socket);
	} else {
		exit;
	}
	return $result;
}

$object = new Facture($db);
$object->fetch($_POST['object']);
status($user, $db, $conf, $object, $langs);
