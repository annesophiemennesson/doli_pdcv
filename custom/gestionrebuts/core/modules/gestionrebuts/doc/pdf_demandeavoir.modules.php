<?php
/* Copyright (C) 2004-2014 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2008      Chiptronik
 * Copyright (C) 2011-2021 Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2015      Marcos García         <marcosgdf@gmail.com>
 * Copyright (C) 2022      Anne-Sophie MENNESSON <annesophie.mennesson@gmail.com>

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
 * or see https://www.gnu.org/
 */

/**
 *  \file       core/modules/gestionrebuts/doc/pdf_demandeavoir.modules.php
 *  \ingroup    gestionrebuts
 *  \brief      File of class to generate document from demandeavoir template
 */

dol_include_once('/gestionrebuts/core/modules/gestionrebuts/modules_demandeavoir.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Class to manage PDF template standard
 */
class pdf_demandeavoir extends ModelePDFDemandeAvoir
{
	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var string model name
	 */
	public $name;

	/**
	 * @var string model description (short text)
	 */
	public $description;

	/**
	 * @var int     Save the name of generated file as the main doc when generating a doc with this template
	 */
	public $update_main_doc_field;

	/**
	 * @var string document type
	 */
	public $type;

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.6 = array(5, 6)
	 */
	public $phpmin = array(5, 6);

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr';

	/**
	 * @var int page_largeur
	 */
	public $page_largeur;

	/**
	 * @var int page_hauteur
	 */
	public $page_hauteur;

	/**
	 * @var array format
	 */
	public $format;

	/**
	 * @var int marge_gauche
	 */
	public $marge_gauche;

	/**
	 * @var int marge_droite
	 */
	public $marge_droite;

	/**
	 * @var int marge_haute
	 */
	public $marge_haute;

	/**
	 * @var int marge_basse
	 */
	public $marge_basse;

	/**
	 * Issuer
	 * @var Societe Object that emits
	 */
	public $emetteur;

	/**
	 * @var bool Situation invoice type
	 */
	public $situationinvoice;

	/**
	 * @var float X position for the situation progress column
	 */
	public $posxprogress;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main", "bills"));

		$this->db = $db;
		$this->name = "demandeavoir";
		$this->description = $langs->trans('PDFCrabeDescription');
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template

		// Dimension page
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
		$this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
		$this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
		$this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

		$this->option_logo = 1; // Display logo
		$this->option_tva = 1; // Manage the vat option FACTURE_TVAOPTION
		$this->option_modereg = 1; // Display payment mode
		$this->option_condreg = 1; // Display payment terms
		$this->option_codeproduitservice = 1; // Display product-service code
		$this->option_multilang = 0; // Available in several languages
		$this->option_escompte = 1; // Displays if there has been a discount
		$this->option_credit_note = 0; // Support credit notes
		$this->option_freetext = 1; // Support add of a personalised text
		$this->option_draft_watermark = 0; // Support add of a watermark on drafts

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined
		}

		// Define position of columns
		$this->posxdesc = $this->marge_gauche + 1;
		$this->posxrebut = 80;
		$this->posxup = 118;
		$this->posxqty = 135;
		$this->posxtva = 145;
		$this->posxprogress = 151; // Only displayed for situation invoices
		$this->posxdiscount = 162;
		$this->posxprogress = 174;
		$this->postotalht = 174;
		if (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) || !empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) {
			$this->posxrebut = $this->posxup;
		}
		$this->posxpicture = $this->posxrebut - (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH) ? 20 : $conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH); // width of images
		if ($this->page_largeur < 210) { // To work with US executive format
			$this->posxpicture -= 20;
			$this->posxrebut -= 20;
			$this->posxup -= 20;
			$this->posxqty -= 20;
			$this->posxtva -= 20;
			$this->posxdiscount -= 20;
			$this->posxprogress -= 20;
			$this->postotalht -= 20;
		}

		$this->tva = 0;
		$this->totalHT = 0;
		$this->totalTTC = 0;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		Object		$object				Object to generate
	 *  @param		Translate	$outputlangs		Lang output object
	 *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *  @return     int         	    			1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $hookmanager, $nblines;

		dol_syslog("write_file outputlangs->defaultlang=".(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (!empty($conf->global->MAIN_USE_FPDF)) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies"));

		global $outputlangsbis;
		$outputlangsbis = null;
		if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && $outputlangs->defaultlang != $conf->global->PDF_USE_ALSO_LANGUAGE_CODE) {
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang($conf->global->PDF_USE_ALSO_LANGUAGE_CODE);
			$outputlangsbis->loadLangs(array("main", "bills", "products", "dict", "companies"));
		}
		$object->loadProduits();
		$nblines = (is_array($object->lines) ? count($object->lines) : 0);

		// Loop on each lines to detect if there is at least one image to show
		$realpatharray = array();
		if (count($realpatharray) == 0) {
			$this->posxpicture = $this->posxrebut;
		}

		if ($conf->facture->dir_output) {
			$object->fetch_thirdparty();

	
			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->gestionrebuts->dir_output.'/';
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName('DemandeAvoir_'.$object->id.'_'.date('YmdHis'));
				$dir = $conf->gestionrebuts->dir_output.'/'.$object->id;
				$file = $dir."/".$objectref.".pdf";
			}
			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir)) {
				// Add pdfgeneration hook
				if (!is_object($hookmanager)) {
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

				// Set nblines with the new facture lines content after hook
				$nblines = count($object->lines);

				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1, 0);

				$heightforinfotot = 50; // Height reserved to output the info and total part and payment part
				if ($heightforinfotot > 220) {
					$heightforinfotot = 220;
				}
				$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)
				if (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS)) {
					$heightforfooter += 6;
				}

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));

				// Set path to the background PDF File
				/*if (!empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
					$logodir = $conf->mycompany->dir_output;
					if (!empty($conf->mycompany->multidir_output[$object->entity])) {
						$logodir = $conf->mycompany->multidir_output[$object->entity];
					}
					$pagecount = $pdf->setSourceFile($logodir.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $pdf->importPage(1);
				}*/

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset("Demande d'avoir"));
				$pdf->SetSubject("Demande d'avoir");
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset("Demande d'avoir"));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
					$pdf->SetCompression(false);
				}

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

				// Set $this->atleastonediscount if you have at least one discount
				/*for ($i = 0; $i < $nblines; $i++) {
					if ($object->lines[$i]->remise_percent) {
						$this->atleastonediscount++;
					}
				}*/
				if (empty($this->atleastonediscount)) {    // retrieve space not used by discount
					$delta = ($this->posxprogress - $this->posxdiscount);
					$this->posxpicture += $delta;
					$this->posxrebut += $delta;
					$this->posxup += $delta;
					$this->posxqty += $delta;
					$this->posxtva += $delta;
					$this->posxdiscount += $delta;
					// post of fields after are not modified, stay at same position
				}

				$progress_width = 0;
				// Situation invoice handling
				/*if ($object->situation_cycle_ref && empty($conf->global->MAIN_PDF_HIDE_SITUATION)) {
					$this->situationinvoice = true;
					$progress_width = 10;
					$this->posxpicture -= $progress_width;
					$this->posxrebut -= $progress_width;
					$this->posxup -= $progress_width;
					$this->posxqty -= $progress_width;
					$this->posxtva -= $progress_width;
					$this->posxdiscount -= $progress_width;
					$this->posxprogress -= $progress_width;
				}*/

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}
				$pagenb++;

				// Output header (logo, ref and address blocks). This is first call for first page.
				$top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				// $pdf->GetY() here can't be used. It is bottom of the second addresse box but first one may be higher

				// $tab_top is y where we must continue content (90 = 42 + 48: 42 is height of logo and ref, 48 is address blocks)
				$tab_top = 90 + $top_shift;		// top_shift is an addition for linked objects or addons (0 in most cases)
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? 42 + $top_shift : 10);

				// You can add more thing under header here, if you increase $extra_under_address_shift too.
				$extra_under_address_shift = 0;
				/*if (! empty($conf->global->INVOICE_ADD_ZATCA_QR_CODE)) {
					$qrcodestring = $object->buildZATCAQRString();
					$qrcodecolor = array('25', '25', '25');
					// set style for QR-code
					$styleQr = array(
						'border' => false,
						'padding' => 0,
						'fgcolor' => $qrcodecolor,
						'bgcolor' => false, //array(255,255,255)
						'module_width' => 1, // width of a single module in points
						'module_height' => 1 // height of a single module in points
					);
					$pdf->write2DBarcode($qrcodestring, 'QRCODE,M', $this->marge_gauche, $tab_top - 5, 25, 25, $styleQr, 'N');
					$extra_under_address_shift += 25;
				}*/

				// Call hook printUnderHeaderPDFline
				$parameters = array(
					'object' => $object,
					'i' => $i,
					'pdf' =>& $pdf,
					'outputlangs' => $outputlangs,
					'hidedetails' => $hidedetails
				);
				$reshook = $hookmanager->executeHooks('printUnderHeaderPDFline', $parameters, $this); // Note that $object may have been modified by hook
				if (!empty($hookmanager->resArray['extra_under_address_shift'])) {
					$extra_under_address_shift += $hookmanager->resArray['extra_under_header_shift'];
				}

				$tab_top += $extra_under_address_shift;
				$tab_top_newpage += 0;

				// Incoterm
				$height_incoterms = 0;
				$height_note = 0;

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

				// Loop on each lines
				for ($i = 0; $i < $nblines; $i++) {
					$curY = $nexY;
					$pdf->SetFont('', '', $default_font_size - 1); // Into loop to work with multipage
					$pdf->SetTextColor(0, 0, 0);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext + $heightforinfotot); // The only function to edit the bottom margin of current page to set it.
					$pageposbefore = $pdf->getPage();

					$showpricebeforepagebreak = 1;
					// Description of product line
					$curX = $this->posxdesc - 1;

					$pdf->startTransaction();
					pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxpicture - $curX - $progress_width, 3, $curX, $curY, $hideref, $hidedesc);
					$pageposafter = $pdf->getPage();
					if ($pageposafter > $pageposbefore) {	// There is a pagebreak
						$pdf->rollbackTransaction(true);
						$pageposafter = $pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation('', 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.
						pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxpicture - $curX - $progress_width, 3, $curX, $curY, $hideref, $hidedesc);
						$pageposafter = $pdf->getPage();
						$posyafter = $pdf->GetY();
						//var_dump($posyafter); var_dump(($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot))); exit;
						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {	// There is no space left for total+free text
							if ($i == ($nblines - 1)) {	// No more lines, and no space left to show total, so we create a new page
								$pdf->AddPage('', '', true);
								if (!empty($tplidx)) {
									$pdf->useTemplate($tplidx);
								}
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
									$this->_pagehead($pdf, $object, 0, $outputlangs);
								}
								$pdf->setPage($pageposafter + 1);
							}
						} else {
							// We found a page break
							// Allows data in the first page if description is long enough to break in multiples pages
							if (!empty($conf->global->MAIN_PDF_DATA_ON_FIRST_PAGE)) {
								$showpricebeforepagebreak = 1;
							} else {
								$showpricebeforepagebreak = 0;
							}
						}
					} else // No pagebreak
					{
						$pdf->commitTransaction();
					}
					$posYAfterDescription = $pdf->GetY();

					$nexY = $pdf->GetY();
					$pageposafter = $pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}

					$pdf->SetFont('', '', $default_font_size - 1); // We reposition the default font

					$prod = $object->lines[$i]['label'];
					if (!empty($object->lines[$i]['batch'])){
						$prod .= ' (#'.$object->lines[$i]['batch'];
						if (!empty($object->lines[$i]['eatby'])){
							$prod .= ', DLC: '.dol_print_date($object->lines[$i]['eatby'], "%d/%m/%Y");
						}
						$prod .= ')';
					}
					$pdf->SetXY($this->posxdesc, $curY);
					$pdf->MultiCell($this->posxrebut - $this->posxdesc, 3, $prod, 0, 'L');

					// Commentaire
					$pdf->SetXY($this->posxrebut, $curY);
					$pdf->MultiCell($this->posxrebut, 3, $object->lines[$i]['commentaire'], 0, 'L');


					// Unit price before discount
					$pdf->SetXY($this->posxup, $curY);
					$pdf->MultiCell($this->posxqty - $this->posxup - 0.8, 3, price($object->lines[$i]['price']), 0, 'R', 0);

					// Quantity
					$pdf->SetXY($this->posxqty, $curY);
					$pdf->MultiCell($this->posxtva - $this->posxqty - 0.8, 4, $object->lines[$i]['qty'], 0, 'R'); // Enough for 6 chars

					// TVA
					$pdf->SetXY($this->posxtva, $curY);
					$pdf->MultiCell($this->posxdiscount - $this->posxtva - 0.8, 4, price($object->lines[$i]['tva_tx']).'%', 0, 'R');


					// Total HT line
					$total_excl_tax = $object->lines[$i]['qty'] * $object->lines[$i]['price'];
					$pdf->SetXY($this->postotalht, $curY);
					$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->postotalht, 3, price($total_excl_tax), 0, 'R', 0);

					$tva = $total_excl_tax * $object->lines[$i]['tva_tx'] /100;

					$this->tva += $tva;
					$this->totalHT += $total_excl_tax;
					$this->totalTTC += $tva + $total_excl_tax;

					if ($posYAfterImage > $posYAfterDescription) {
						$nexY = $posYAfterImage;
					}

					// Add line
					if (!empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblines - 1)) {
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash'=>'1,1', 'color'=>array(80, 80, 80)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
						$pdf->SetLineStyle(array('dash'=>0));
					}

					$nexY += 2; // Add space between lines

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						if ($pagenb == 1) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
					}
					if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
						if ($pagenb == 1) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						// New page
						$pdf->AddPage();
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						$pagenb++;
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
					}
				}

				// Show square
				if ($pagenb == 1) {
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0, $object->multicurrency_code);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}
				dol_syslog("bottomlasttab=".$bottomlasttab." this->page_hauteur=".$this->page_hauteur." heightforinfotot=".$heightforinfotot." heightforfreetext=".$heightforfreetext." heightforfooter=".$heightforfooter);

				// Display info area
				$posy = $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs, $outputlangsbis);

				// Display total area
				$posy = $this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs, $outputlangsbis);

				// Pagefoot
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}

				$pdf->Close();

				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
				}

				if (!empty($conf->global->MAIN_UMASK)) {
					@chmod($file, octdec($conf->global->MAIN_UMASK));
				}

				$this->result = array('fullpath'=>$file);

				return 1; // No error
			} else {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->transnoentities("ErrorConstantNotDefined", "FAC_OUTPUTDIR");
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		Facture		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param  	Translate	$outputlangsbis	Object lang for output bis
	 *   @return	int							Pos y
	 */
	protected function _tableau_info(&$pdf, $object, $posy, $outputlangs, $outputlangsbis)
	{
		// phpcs:enable
		global $conf, $mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('', '', $default_font_size - 1);

		// If France, show VAT mention if not applicable
		if ($this->emetteur->country_code == 'FR' && empty($mysoc->tva_assuj)) {
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			if ($mysoc->forme_juridique_code == 92) {
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoiceAsso"), 0, 'L', 0);
			} else {
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);
			}

			$posy = $pdf->GetY() + 4;
		}

		$posxval = 52;

		return $posy;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *	Show total to pay
	 *
	 *	@param	TCPDF		$pdf            Object PDF
	 *	@param  Facture		$object         Object invoice
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *	@return int							Position pour suite
	 */
	protected function _tableau_tot(&$pdf, $object, $posy, $outputlangs, $outputlangsbis)
	{
		// phpcs:enable
		global $conf, $mysoc, $hookmanager;

		$sign = 1;
		if ($object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) {
			$sign = -1;
		}

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$outputlangsbis = null;
		if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && $outputlangs->defaultlang != $conf->global->PDF_USE_ALSO_LANGUAGE_CODE) {
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang($conf->global->PDF_USE_ALSO_LANGUAGE_CODE);
			$outputlangsbis->loadLangs(array("main", "dict", "companies", "bills", "products", "propal"));
			$default_font_size--;
		}

		$tab2_top = $posy;
		$tab2_hl = 4;
		$pdf->SetFont('', '', $default_font_size - 1);

		// Total table
		$col1x = 120;
		$col2x = 170;
		if ($this->page_largeur < 210) { // To work with US executive format
			$col1x -= 15;
			$col2x -= 10;
		}
		$largcol2 = ($this->page_largeur - $this->marge_droite - $col2x);

		$useborder = 0;
		$index = 0;

		// Total HT
		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetXY($col1x, $tab2_top + 0);
		$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities(empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) ? "TotalHT" : "Total").(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transnoentities(empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) ? "TotalHT" : "Total") : ''), 0, 'L', 1);

		$pdf->SetXY($col2x, $tab2_top + 0);
		$pdf->MultiCell($largcol2, $tab2_hl, price(price2num($this->totalHT, 'MT'), 0, $outputlangs), 0, 'R', 1);

		// Show VAT by rates and total
		$pdf->SetFillColor(248, 248, 248);

		// VAT
		$index++;
		$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
		$totalvat = $outputlangs->transcountrynoentities("TotalVAT", $mysoc->country_code).(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transcountrynoentities("TotalVAT", $mysoc->country_code) : '');

		$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

		$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
		$pdf->MultiCell($largcol2, $tab2_hl, price(price2num($this->tva, 'MT'), 0, $outputlangs), 0, 'R', 1);
	

		// Total TTC
		$index++;
		$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFillColor(224, 224, 224);
		$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

		$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
		$pdf->MultiCell($largcol2, $tab2_hl, price(price2num($this->totalTTC, 'MT'), 0, $outputlangs), $useborder, 'R', 1);


		$pdf->SetTextColor(0, 0, 0);

		$index++;
		return ($tab2_top + ($tab2_hl * $index));
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '')
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom = 0;
		if ($hidetop) {
			$hidetop = -1;
		}

		$currency = !empty($currency) ? $currency : $conf->currency;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);

		if (empty($hidetop)) {
			$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
			$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			//$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
			if (!empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) {
				$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, 5, 'F', null, explode(',', $conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR));
			}
		}

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		// Output Rect
		$this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect takes a length in 3rd parameter and 4th parameter

		if (empty($hidetop)) {
			$pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur - $this->marge_droite, $tab_top + 5); // line takes a position y in 2nd parameter and 4th parameter

			$pdf->SetXY($this->posxdesc - 1, $tab_top + 1);
			$pdf->MultiCell(108, 2, "Produit", '', 'L');
		}

		if (!empty($conf->global->MAIN_GENERATE_INVOICES_WITH_PICTURE)) {
			//$pdf->line($this->posxpicture - 1, $tab_top, $this->posxpicture - 1, $tab_top + $tab_height);
			if (empty($hidetop)) {
				//$pdf->SetXY($this->posxpicture-1, $tab_top+1);
				//$pdf->MultiCell($this->posxrebut-$this->posxpicture-1,2, $outputlangs->transnoentities("Photo"),'','C');
			}
		}

		//if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) {
			$pdf->line($this->posxrebut - 1, $tab_top, $this->posxrebut - 1, $tab_top + $tab_height);
			if (empty($hidetop)) {
				$pdf->SetXY($this->posxrebut - 3, $tab_top + 1);
				$pdf->MultiCell($this->posxup - $this->posxrebut + 3, 2, $outputlangs->transnoentities("Commentaire"), '', 'C');
			}
		//}

		$pdf->line($this->posxup - 1, $tab_top, $this->posxup - 1, $tab_top + $tab_height);
		if (empty($hidetop)) {
			$pdf->SetXY($this->posxup - 1, $tab_top + 1);
			$pdf->MultiCell($this->posxqty - $this->posxup - 1, 2, $outputlangs->transnoentities("PriceUHT"), '', 'C');
		}

		$pdf->line($this->posxqty - 1, $tab_top, $this->posxqty - 1, $tab_top + $tab_height);
		if (empty($hidetop)) {
			$pdf->SetXY($this->posxqty - 1, $tab_top + 1);
			$pdf->MultiCell($this->posxtva - $this->posxqty - 1, 2, $outputlangs->transnoentities("Qty"), '', 'C');
		}

		//if (!empty($conf->global->PRODUCT_USE_UNITS)) {
			$pdf->line($this->posxtva - 1, $tab_top, $this->posxtva - 1, $tab_top + $tab_height);
			if (empty($hidetop)) {
				$pdf->SetXY($this->posxtva - 1, $tab_top + 1);
				$pdf->MultiCell($this->posxdiscount - $this->posxtva - 1, 2, $outputlangs->transnoentities("VAT"), '', 'C');
			}
		//}

		//if ($this->atleastonediscount) {
		/*	$pdf->line($this->posxdiscount - 1, $tab_top, $this->posxdiscount - 1, $tab_top + $tab_height);
			if (empty($hidetop)) {
				$pdf->SetXY($this->posxdiscount - 1, $tab_top + 1);
				$pdf->MultiCell($this->posxprogress - $this->posxdiscount + 1, 2, $outputlangs->transnoentities("ReductionShort"), '', 'C');
			}*/
		//}

		//if ($this->situationinvoice) {
		/*	$pdf->line($this->posxprogress - 1, $tab_top, $this->posxprogress - 1, $tab_top + $tab_height);
			if (empty($hidetop)) {
				$pdf->SetXY($this->posxprogress, $tab_top + 1);
				$pdf->MultiCell($this->postotalht - $this->posxprogress, 2, $outputlangs->transnoentities("ProgressShort"), '', 'C');
			}*/
		//}

		$pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
		if (empty($hidetop)) {
			$pdf->SetXY($this->postotalht - 1, $tab_top + 1);
			$pdf->MultiCell(30, 2, $outputlangs->transnoentities("TotalHT"), '', 'C');
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Facture		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *  @return	int							top shift of linked object lines
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
	{
		global $conf, $langs, $db;

		$ltrdirection = 'L';
		if ($outputlangs->trans("DIRECTION") == 'rtl') $ltrdirection = 'R';

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "bills", "propal", "companies"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$w = 110;

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - $w;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		$text = $this->emetteur->name;
		$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, $ltrdirection);

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title = "Demande d'avoir";

		$pdf->MultiCell($w, 3, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size);

		$posy += 3;
		$pdf->SetFont('', '', $default_font_size - 2);
		
		$posy += 4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title = "Date réception";
		$pdf->MultiCell($w, 3, $title." : ".dol_print_date($object->date_creation, "day", false, $outputlangs, true), '', 'R');

	

		$posy += 1;

		$top_shift = 0;
		// Show list of linked objects
		$current_y = $pdf->getY();
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, 3, 'R', $default_font_size);
		if ($current_y < $pdf->getY()) {
			$top_shift = $pdf->getY() - $current_y;
		}

		if ($showaddress) {
			// Sender properties
			$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, '', '', 0, 'source', $object);

			// Show sender
			$posy = 42;
			$posy += $top_shift;
			$posx = $this->marge_gauche;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) {
				$posx = $this->page_largeur - $this->marge_droite - 80;
			}

			$hautcadre = 40;
			$widthrecbox = 82;


			// Show sender frame
			if (empty($conf->global->MAIN_PDF_NO_SENDER_FRAME)) {
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posx, $posy - 5);
				$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillFrom"), 0, $ltrdirection);
				$pdf->SetXY($posx, $posy);
				$pdf->SetFillColor(230, 230, 230);
				$pdf->MultiCell($widthrecbox, $hautcadre, "", 0, 'R', 1);
				$pdf->SetTextColor(0, 0, 60);
			}

			// Show sender name
			if (empty($conf->global->MAIN_PDF_HIDE_SENDER_NAME)) {
				$pdf->SetXY($posx + 2, $posy + 3);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, $ltrdirection);
				$posy = $pdf->getY();
			}

			// Show sender information
			$pdf->SetXY($posx + 2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, $ltrdirection);

			// Client destinataire
			$posy = 42;
			$posx = 102;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) {
				$posx = $this->marge_gauche;
			}
			/*$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx, $posy - 5);
			$pdf->MultiCell(80, 5, "Destination", 0, 'L');*/

			// If BILLING contact defined on invoice, we use it
			$usecontact = false;

			
			$sql = "SELECT r.fk_soc
					FROM ".MAIN_DB_PREFIX."demande_avoir AS a
					INNER JOIN ".MAIN_DB_PREFIX."reception AS r ON (a.fk_reception = r.rowid)
					WHERE a.rowid = ".$object->id;
	
			$resql = $db->query($sql);
			$obj = $db->fetch_object($resql);
			$fournisseur = new Societe($db);
			$fournisseur->fetch($obj->fk_soc);

			$carac_client = pdf_build_address($outputlangs, $fournisseur, $fournisseur, '', $usecontact, 'target', $object);
			// Show recipient
			$widthrecbox = 100;
			if ($this->page_largeur < 210) {
				$widthrecbox = 84; // To work with US executive format
			}
			$posy = 42;
			$posy += $top_shift;
			$posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) {
				$posx = $this->marge_gauche;
			}

			// Show recipient frame
			if (empty($conf->global->MAIN_PDF_NO_RECIPENT_FRAME)) {
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posx + 2, $posy - 5);
				$pdf->MultiCell($widthrecbox - 2, 5, $outputlangs->transnoentities("BillTo"), 0, $ltrdirection);
				$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);
			}

			// Show recipient name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox - 2, 2, $fournisseur->nom, 0, $ltrdirection);

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy);
			$pdf->MultiCell($widthrecbox - 2, 4, $carac_client, 0, $ltrdirection);
		}

		$pdf->SetTextColor(0, 0, 0);
		return $top_shift;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	TCPDF		$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 0 : $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf, $outputlangs, 'INVOICE_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}
}
