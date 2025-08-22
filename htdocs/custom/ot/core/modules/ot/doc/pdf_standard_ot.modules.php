<?php

// Inclusions des fichiers nécessaires
dol_include_once('/ot/core/modules/ot/modules_ot.php');
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/custom/ot/lib/pdf.lib.php';
/**
 * Classe pour gérer le modèle PDF standard OT
 */
class pdf_standard_ot extends ModelePDFOt
{
    /**
     * @var DoliDb Database handler
     */
    public $db;

    /**
     * @var string Nom du modèle
     */
    public $name;

    /**
     * @var string Description du modèle
     */
    public $description;

    /**
     * @var int Met à jour le champ principal du document généré
     */
    public $update_main_doc_field;

    /**
     * @var string Type de document
     */
    public $type;

    /**
     * @var array Version minimale de PHP requise
     */
    public $phpmin = array(7, 0);

    /**
     * @var string Version de Dolibarr
     */
    public $version = 'dolibarr';

    /**
     * @var Societe Objet représentant l'émetteur
     */
    public $emetteur;

    /**
     * @var bool Type de facture de situation
     */
    public $situationinvoice;

    /**
     * @var array Colonnes du tableau de document
     */
    public $cols;

    /**
     * Constructeur
     *
     * @param DoliDB $db Handler de la base de données
     */
    public function __construct($db)
    {
        global $conf, $langs, $mysoc;

        // Chargement des traductions
        $langs->loadLangs(array("main", "bills"));

        // Initialisation des propriétés
        $this->db = $db;
        $this->name = "standard";
        $this->description = $langs->trans('DocumentModelStandardPDF');
        $this->update_main_doc_field = 1; // Sauvegarde le nom du fichier généré
        $this->type = 'pdf';

        // Définition des dimensions de la page
        $formatarray = pdf_getFormat();
        $this->page_largeur = $formatarray['width'];
        $this->page_hauteur = $formatarray['height'];
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
        $this->marge_droite = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
        $this->marge_haute = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
        $this->marge_basse = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);

        // Récupérer l'émetteur (société)
        $this->emetteur = $mysoc;
        if (empty($this->emetteur->country_code)) {
            $this->emetteur->country_code = substr($langs->defaultlang, -2); // Valeur par défaut
        }

        // Définir la position des colonnes
        $this->posxdesc = $this->marge_gauche + 1; // utilisée pour les notes et autres informations
        $this->tabTitleHeight = 5; // Hauteur par défaut

        // Utilisation du nouveau système pour la position des colonnes
        $this->tva = array();
        $this->localtax1 = array();
        $this->localtax2 = array();
        $this->atleastoneratenotnull = 0;
        $this->atleastonediscount = 0;
        $this->situationinvoice = false;
    }

    /**
     * Fonction pour générer le PDF sur disque
     *
     * @param Object $object Objet à générer
     * @param Translate $outputlangs Langue pour la sortie
     * @param string $srctemplatepath Chemin du modèle source
     * @param int $hidedetails Masquer les détails
     * @param int $hidedesc Masquer la description
     * @param int $hideref Masquer la référence
     * @return int 1=OK, 0=KO
     */
    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

		dol_syslog("write_file outputlangs->defaultlang=".(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalInt('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies"));

		if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && $outputlangs->defaultlang != getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE')) {
			global $outputlangsbis;
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang(getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE'));
			$outputlangsbis->loadLangs(array("main", "bills", "products", "dict", "companies"));
		}

		$nblines = (is_array($object->lines) ? count($object->lines) : 0);

		$hidetop = 0;
		if (getDolGlobalString('MAIN_PDF_DISABLE_COL_HEAD_TITLE')) {
			$hidetop = getDolGlobalString('MAIN_PDF_DISABLE_COL_HEAD_TITLE');
		}

		// Loop on each lines to detect if there is at least one image to show
		$realpatharray = array();
		$this->atleastonephoto = false;
		
		if ($conf->ot->dir_output.'/ot') {
			$object->fetch_thirdparty();

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->ot->dir_output.'/ot';
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->ot->dir_output.'/ot/'.$objectref;
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
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

				// Set nblines with the new facture lines content after hook
				$nblines = (is_array($object->lines) ? count($object->lines) : 0);

				// Créer le PDF
				$pdf = pdf_getInstanceCustomOt($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);
				
				// Configuration du PDF
				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
					$pdf->SetMargins(10, 10, 10);
					$pdf->SetAutoPageBreak(TRUE, 15);
					
					// Définir les polices par défaut
					$pdf->SetFont('helvetica', '', $default_font_size);
					$pdf->SetFont('helvetica', 'B', $default_font_size);
					$pdf->SetFont('helvetica', 'I', $default_font_size);
					$pdf->SetFont('helvetica', 'BI', $default_font_size);
				}
				
				// Passer les objets nécessaires à l'instance PDF
				$pdf->ot_object = $object;
				$pdf->ot_outputlangs = $outputlangs;
				$pdf->ot_parent = $this;
				
				// Ajout de l'en-tête
				$pdf->AddPage();
				$current_y = $this->_pagehead($pdf, $object, $outputlangs);
				
				// Contenu
				$heightforinfotot = 50; // Height reserved to output the info and total part and payment part
				$heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + (getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS') ? 12 : 22); // Height reserved to output the footer (value include bottom margin)

				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("PdfTitle"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
					$pdf->SetCompression(false);
				}

				// Ajout de l'en-tête
				$current_y = $this->_pagehead($pdf, $object, $outputlangs);
				$this->_pagefoot($pdf, $object, $outputlangs);

				// Set certificate
				$cert = empty($user->conf->CERTIFICATE_CRT) ? '' : $user->conf->CERTIFICATE_CRT;
				// If user has no certificate, we try to take the company one
				if (!$cert) {
					$cert = getDolGlobalString('CERTIFICATE_CRT');
				}
				// If a certificate is found
				if ($cert) {
					$info = array(
						'Name' => $this->emetteur->name,
						'Location' => getCountry($this->emetteur->country_code, 0),
						'Reason' => 'MYOBJECT',
						'ContactInfo' => $this->emetteur->email
					);
					$pdf->setSignature($cert, $cert, $this->emetteur->name, '', 2, $info);
				}

				$pdf->SetMargins($this->marge_gauche, 20, $this->marge_droite);

				// Supprimer l'ajout de page supplémentaire ici
				// $pdf->AddPage();
				// if (!empty($tplidx)) {
				//     $pdf->useTemplate($tplidx);
				// }
				// $pagenb++;

				//$top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$tab_top = 50 + $top_shift;
				$tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 42 + $top_shift : 10);
				$tab_height = 130 - $top_shift;
				$tab_height_newpage = 150;
				if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
					$tab_height_newpage -= $top_shift;
				}

				$nexY = $tab_top - 1;

				// Display notes
				$notetoshow = empty($object->note_public) ? '' : $object->note_public;
				// Extrafields in note
				$extranote = $this->getExtrafieldsInHtml($object, $outputlangs);
				if (!empty($extranote)) {
					$notetoshow = dol_concatdesc($notetoshow, $extranote);
				}

				$pagenb = $pdf->getPage();
				if ($notetoshow) {
					$tab_top -= 2;

					$tab_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
					$pageposbeforenote = $pagenb;

					$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
					$notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

					$pdf->startTransaction();

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
					// Description
					$pageposafternote = $pdf->getPage();
					$posyafter = $pdf->GetY();

					if ($pageposafternote > $pageposbeforenote) {
						$pdf->rollbackTransaction(true);

						// prepare pages to receive notes
						while ($pagenb < $pageposafternote) {
							$pdf->AddPage();
							$pagenb++;
							$current_y = $this->_pagehead($pdf, $object, $outputlangs);
							$this->_pagefoot($pdf, $object, $outputlangs);
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							// $this->_pagefoot($pdf,$object,$outputlangs,1);
							$pdf->setTopMargin($tab_top_newpage);
							// The only function to edit the bottom margin of current page to set it.
							$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
						}

						// back to start
						$pdf->setPage($pageposbeforenote);
						$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
						$pageposafternote = $pdf->getPage();

						$posyafter = $pdf->GetY();

						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) {	// There is no space left for total+free text
							$pdf->AddPage('', '', true);
							$pagenb++;
							$pageposafternote++;
							$pdf->setPage($pageposafternote);
							$current_y = $this->_pagehead($pdf, $object, $outputlangs);
							$this->_pagefoot($pdf, $object, $outputlangs);
							$pdf->setTopMargin($tab_top_newpage);
							// The only function to edit the bottom margin of current page to set it.
							$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
							//$posyafter = $tab_top_newpage;
						}


						// apply note frame to previous pages
						$i = $pageposbeforenote;
						while ($i < $pageposafternote) {
							$pdf->setPage($i);
							$current_y = $this->_pagehead($pdf, $object, $outputlangs);

							$pdf->SetDrawColor(128, 128, 128);
							// Draw note frame
							if ($i > $pageposbeforenote) {
								$height_note = $this->page_hauteur - ($tab_top_newpage + $heightforfooter);
								$pdf->Rect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1);
							} else {
								$height_note = $this->page_hauteur - ($tab_top + $heightforfooter);
								$pdf->Rect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1);
							}

							// Add footer
							$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
							$this->_pagefoot($pdf, $object, $outputlangs, 1);

							$i++;
						}

						// apply note frame to last page
						$pdf->setPage($pageposafternote);
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						$current_y = $this->_pagehead($pdf, $object, $outputlangs);
						$height_note = $posyafter - $tab_top_newpage;
						$pdf->Rect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1);
					} else // No pagebreak
					{
						$pdf->commitTransaction();
						$posyafter = $pdf->GetY();
						$height_note = $posyafter - $tab_top;
						$pdf->Rect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1);


						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) {
							// not enough space, need to add page
							$pdf->AddPage('', '', true);
							$pagenb++;
							$pageposafternote++;
							$pdf->setPage($pageposafternote);
							$current_y = $this->_pagehead($pdf, $object, $outputlangs);
							$this->_pagefoot($pdf, $object, $outputlangs);
							$pdf->setTopMargin($tab_top_newpage);
							// The only function to edit the bottom margin of current page to set it.
							$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
							//$posyafter = $tab_top_newpage;
						}
					}

					$tab_height = $tab_height - $height_note;
					$tab_top = $posyafter + 6;
				} else {
					$height_note = 0;
				}

//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

				$otId = $object->id;
				if (!$otId) {
					throw new Exception("L'ID OT est invalide.");
				}

				// Charger les cellules associées à cet OT
				$sql = "SELECT oc.rowid, oc.id_cellule, oc.x, oc.y, oc.type, oc.title 
						FROM " . MAIN_DB_PREFIX . "ot_ot_cellule AS oc
						WHERE oc.ot_id = $otId
						AND oc.type != 'cardprincipale'";
				$resql = $db->query($sql);
				if (!$resql) {
					dol_syslog("Erreur SQL : " . $db->lasterror(), LOG_ERR);
				}

				// Convertir le timestamp Unix en objet DateTime
				$dateAppli = new DateTime();
				$dateAppli->setTimestamp($object->date_applica_ot);

				// Formatter la date en "jour/mois/année"
				$formattedDateAppli = $dateAppli->format('d/m/Y');

				// Position verticale juste après le header RÉDUITE
				$current_y = 30; 

				// Récupération des références client des commandes
				$sql_commandes = "SELECT ref_client 
								FROM " . MAIN_DB_PREFIX . "commande 
								WHERE fk_projet = " . $object->fk_project . " 
								AND fk_statut = 1";
				$resql_commandes = $db->query($sql_commandes);
				$refs_commandes = array();
				if ($resql_commandes) {
					while ($obj = $db->fetch_object($resql_commandes)) {
						if (!empty($obj->ref_client)) {
							$refs_commandes[] = $obj->ref_client;
						}
					}
				}
				$refs_commandes_str = !empty($refs_commandes) ? implode(" & ", $refs_commandes) : "";

				// Affichage des commandes concernées (plus compact)
				$current_y += 8; // Réduit de 10 à 8
				$pdf->SetFont('', '', 9); // Réduit de 10 à 9
				$pdf->SetTextColor(0, 0, 0);
				$pdf->Text($this->marge_gauche, $current_y, "Commande concernée : ");
				$pdf->SetFont('', 'B', 9); // Réduit de 10 à 9
				$pdf->Text($this->marge_gauche + 45, $current_y, $refs_commandes_str); // Réduit de 48 à 45

				// Affichage de la date d'applicabilité (plus compact)
				$current_y += 6; // Réduit de 7 à 6
				$pdf->SetFont('', '', 9); // Réduit de 10 à 9
				$pdf->Text($this->marge_gauche, $current_y, "Date d'applicabilité de l'OT : ");
				$pdf->SetFont('', 'B', 9); // Réduit de 10 à 9
				$pdf->Text($this->marge_gauche + 45, $current_y, $formattedDateAppli); // Réduit de 48 à 45

				// Laisser un espace plus petit avant les cartes principales
				$current_y += 12; // Réduit de 15 à 12

				// Récupération des cartes principales (RA, Q3, PCR)
				$sql_cards = "SELECT c.rowid, c.title, c.type 
							FROM " . MAIN_DB_PREFIX . "ot_ot_cellule c 
							WHERE c.ot_id = " . $object->id . " 
							AND c.type = 'cardprincipale' 
							ORDER BY FIELD(c.title, 'RA', 'Q3', 'PCR')";
				$resql_cards = $db->query($sql_cards);

				// AFFICHAGE DES CARTES PRINCIPALES
				if ($resql_cards && $db->num_rows($resql_cards) > 0) {
					// Paramètres pour l'affichage des cartes principales
					$card_width = 50;
					$card_height = 25;
					$card_margin = 10;
					$cards_per_row = 3;
					$current_x = $this->marge_gauche;

					// Calcul de la position X pour centrer les cartes
					$total_width = ($card_width * $cards_per_row) + ($card_margin * ($cards_per_row - 1));
					$start_x = ($this->page_largeur - $total_width) / 2;

					while ($card = $db->fetch_object($resql_cards)) {
						// Récupération des données utilisateur pour cette carte
						$sql_user = "SELECT DISTINCT u.firstname, u.lastname, u.office_phone, cd.role 
									FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne cd 
									JOIN " . MAIN_DB_PREFIX . "user u ON cd.fk_user = u.rowid 
									WHERE cd.ot_cellule_id = " . $card->rowid . "
									ORDER BY cd.rowid DESC
									LIMIT 1";
						$resql_user = $db->query($sql_user);
						$user_data = $db->fetch_object($resql_user);

						if ($user_data) {
							// Dessiner uniquement les contours latéraux et inférieur
							$pdf->SetDrawColor(0, 0, 0);
							
							// Ligne du bas
							$pdf->Line($start_x, $current_y + $card_height, $start_x + $card_width, $current_y + $card_height);
							
							// Lignes latérales (qui s'arrêtent au niveau du nom/prénom)
							$side_line_start_y = $current_y + 12; // Commence après le titre
							$pdf->Line($start_x, $side_line_start_y, $start_x, $current_y + $card_height); // Ligne gauche
							$pdf->Line($start_x + $card_width, $side_line_start_y, $start_x + $card_width, $current_y + $card_height); // Ligne droite

							// Afficher le titre (RA, Q3, PCR)
							$pdf->SetFont('', 'B', 10);
							$title = '';
							switch ($card->title) {
								case 'RA':
									$title = 'Responsable Affaire';
									break;
								case 'Q3':
									$title = 'Responsable Q3SE';
									break;
								case 'PCR':
									$title = 'PCR Referent';
									break;
								default:
									$title = $card->title;
							}
							$title_width = $pdf->GetStringWidth($title);
							$title_x = $start_x + ($card_width - $title_width) / 2;
							$pdf->Text($title_x, $current_y + 5, $title);

							// Afficher le nom et prénom
							$pdf->SetFont('', '', 9);
							$name = $user_data->firstname . ' ' . $user_data->lastname;
							$name_width = $pdf->GetStringWidth($name);
							$name_x = $start_x + ($card_width - $name_width) / 2;
							$pdf->Text($name_x, $current_y + 12, $name);

							// Afficher le numéro de téléphone
							if (!empty($user_data->office_phone)) {
								$phone_width = $pdf->GetStringWidth($user_data->office_phone);
								$phone_x = $start_x + ($card_width - $phone_width) / 2;
								$pdf->Text($phone_x - 5, $current_y + 19, "Tel:"); // Utilisation de "T:" comme symbole téléphone
								$pdf->Text($phone_x + 2, $current_y + 19, $user_data->office_phone);
							}

							// Passer à la position suivante
							$start_x += $card_width + $card_margin;
						}
					}

					// Ajouter un espace après les cartes principales
					$current_y += $card_height + 10; // Ajout d'un espace supplémentaire
				}

				// Initialisation des tableaux pour les cartes dans la grille et en dessous de la grille
				$cardsData = [];
				$listeUniqueCards = [];  // Cartes à afficher en dessous de la grille

				// DEBUG : Ajouter des logs pour voir ce qui est récupéré
				dol_syslog("DEBUG: Début du traitement des cartes", LOG_DEBUG);

				while ($row = $db->fetch_object($resql)) {
					// DEBUG : Log pour chaque carte trouvée
					dol_syslog("DEBUG: Carte trouvée - ID: " . $row->rowid . ", Type: " . $row->type . ", Titre: " . $row->title, LOG_DEBUG);

					$cellData = [
						'id' => $row->rowid,
						'x' => $row->x,
						'y' => $row->y,
						'type' => $row->type,
						'title' => $row->title
					];

					// Vérifier si des utilisateurs sont associés à cette cellule
					$sqlUser = "SELECT fk_user FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne WHERE ot_cellule_id = " . $row->rowid;
					$resqlUser = $db->query($sqlUser);
					if ($resqlUser && $db->num_rows($resqlUser) > 0) {
						$userIds = [];
						$userNames = []; // Tableau pour stocker les noms des utilisateurs

						// Parcours de tous les utilisateurs associés
						while ($userRow = $db->fetch_object($resqlUser)) {
							$userIds[] = $userRow->fk_user;

							// Récupérer le prénom et le nom de famille de chaque utilisateur
							$sqlUserDetails = "SELECT 
									u.lastname, 
									u.firstname,
									u.office_phone,
									cct.type AS contrat
								FROM " . MAIN_DB_PREFIX . "user AS u
								LEFT JOIN " . MAIN_DB_PREFIX . "donneesrh_positionetcoefficient_extrafields AS drh 
									ON drh.fk_object = u.rowid
								LEFT JOIN " . MAIN_DB_PREFIX . "c_contrattravail AS cct 
									ON drh.contratdetravail = cct.rowid
								WHERE u.rowid = " . $userRow->fk_user;
							$resqlUserDetails = $db->query($sqlUserDetails);
							if ($resqlUserDetails && $db->num_rows($resqlUserDetails) > 0) {
								$userDetails = $db->fetch_object($resqlUserDetails);
								
								// Récupérer les fonctions et habilitations
								$fonctions = $this->getFonctions($userRow->fk_user, $object->fk_project, $db);
								$habilitations = $this->getHabilitations($userRow->fk_user, $db);
								
								// Créer une chaîne avec toutes les informations
								$userInfo = $userDetails->firstname . ' ' . $userDetails->lastname;
								if (!empty($userDetails->office_phone)) {
									$userInfo .= ' - Tel: ' . $userDetails->office_phone;
								}
								if (!empty($userDetails->contrat)) {
									$userInfo .= ' - Contrat: ' . $userDetails->contrat;
								}
								if (!empty($fonctions)) {
									$userInfo .= ' - Fonctions: ' . $fonctions;
								}
								if (!empty($habilitations)) {
									$userInfo .= ' - Habilitations: ' . $habilitations;
								}
								
								// Ajouter les informations complètes au tableau userNames
								$userNames[] = $userInfo;
							}
						}
						
						// Ajouter les utilisateurs associés à cellData
						$cellData['userIds'] = $userIds;
						$cellData['userNames'] = $userNames;

						// DEBUG : Log pour les utilisateurs trouvés
						dol_syslog("DEBUG: Carte " . $row->title . " avec " . count($userNames) . " utilisateurs", LOG_DEBUG);
					} else {
						// DEBUG : Log pour les cartes sans utilisateurs
						dol_syslog("DEBUG: Carte " . $row->title . " sans utilisateurs associés", LOG_DEBUG);
					}

					// Ajouter la carte dans le tableau approprié - MODIFICATION ICI
					if ($row->type === 'listeunique' || 
						$row->type === 'liste' || 
						strpos(strtolower($row->title), 'sous') !== false ||
						strpos(strtolower($row->title), 'traitant') !== false) {
						
						$listeUniqueCards[] = $cellData;  // Cartes de type liste à afficher en dessous de la grille
						dol_syslog("DEBUG: Carte " . $row->title . " ajoutée aux listes", LOG_DEBUG);
					} else {
						$cardsData[] = $cellData;  // Cartes normales à afficher dans la grille
						dol_syslog("DEBUG: Carte " . $row->title . " ajoutée aux cartes normales", LOG_DEBUG);
					}
				}

				// DEBUG : Log des totaux
				dol_syslog("DEBUG: Total cartes normales: " . count($cardsData), LOG_DEBUG);
				dol_syslog("DEBUG: Total listes: " . count($listeUniqueCards), LOG_DEBUG);

//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

				// AFFICHAGE DES CARTES NORMALES
				if (!empty($cardsData)) {
					// Vérifier si on a assez d'espace pour afficher au moins une carte
					$min_space_needed = 50; // Hauteur minimale nécessaire pour une carte
					if ($current_y + $min_space_needed > $this->page_hauteur - $this->marge_basse) {
						$pdf->AddPage();
						$current_y = $this->_pagehead($pdf, $object, $outputlangs);
						$this->_pagefoot($pdf, $object, $outputlangs);
					}

					// Paramètres pour la grille
					$grid_columns = 3;
					$base_card_width = 50;
					$card_height = 35;
					$card_margin = 10; 
					$card_user_margin = 5;
					$shift_left = 1;
					
					// Calculer les dimensions de la grille
					$max_x = 0;
					$max_y = 0; 
					foreach ($cardsData as $card) { 
						if ($card['x'] > $max_x) $card['x'] = $max_x;
						if ($card['y'] > $max_y) $card['y'] = $max_y;
					}
					
					// Calculer la largeur totale disponible
					$available_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
					
					// Position initiale
					$current_y = $current_y; // Utiliser la position Y actuelle
					
					// Parcourir chaque ligne (y)
					for ($y = 0; $y <= $max_y; $y++) {
						// Vérifier l'espace disponible pour cette ligne
						if ($current_y + $card_height > $this->page_hauteur - $this->marge_basse) {
							$pdf->AddPage();
							$current_y = $this->_pagehead($pdf, $object, $outputlangs);
							$this->_pagefoot($pdf, $object, $outputlangs);
						}

						// Récupérer toutes les cartes de cette ligne
						$cardsOnY = array_filter($cardsData, function($card) use ($y) {
							return $card['y'] == $y;
						});
						
						// Trier les cartes par position x
						usort($cardsOnY, function($a, $b) {
							return $a['x'] - $b['x'];
						});
						
						// Calculer la largeur disponible pour cette ligne
						$line_width = $available_width;
						
						// Compter le nombre de cartes normales et de listes
						$normal_cards = array_filter($cardsOnY, function($card) { return $card['type'] !== 'list'; });
						$list_cards = array_filter($cardsOnY, function($card) { return $card['type'] === 'list'; });
						
						// Calculer les largeurs
						$normal_card_width = $base_card_width;
						$list_card_width = 0;
						
						if (count($list_cards) > 0) {
							// Si on a des listes, elles se partagent l'espace restant
							$remaining_width = $line_width - (count($normal_cards) * ($normal_card_width + $card_margin));
							$list_card_width = ($remaining_width - ($card_margin * (count($list_cards) - 1))) / count($list_cards);
						}
						
						// Calculer la position X pour centrer les cartes
						$total_cards_width = 0;
						foreach ($cardsOnY as $card) {
							$card_width = ($card['type'] === 'list') ? $list_card_width : $normal_card_width;
							$total_cards_width += $card_width;
						}
						$total_cards_width += $card_margin * (count($cardsOnY) - 1);
						
						// Position initiale pour cette ligne
						$current_x = $this->marge_gauche + ($line_width - $total_cards_width) / 2;
						
						// Calculer la hauteur maximale pour cette ligne AVANT d'afficher les cartes
						$max_line_height = 0;
						$card_heights = array(); // Stocker la hauteur de chaque carte
						
						foreach ($cardsOnY as $card_index => $card) {
							$card_width = ($card['type'] === 'list') ? $list_card_width : $normal_card_width;
							$card_content_height = 15; // Hauteur pour le titre
							
							if (!empty($card['userNames'])) {
								if ($card['type'] === 'list') {
									// Calculer la hauteur RÉELLE pour les listes
									$total_users = count($card['userNames']);
									$is_alone = count($cardsOnY) === 1;
									
									// Recalculer la logique de division
									if ($is_alone) {
										$should_split_list = $total_users > 4;
										if ($should_split_list) {
											$first_half = array_slice($card['userNames'], 0, ceil($total_users / 2));
											$second_half = array_slice($card['userNames'], ceil($total_users / 2));
											$lists_to_display = array($first_half, $second_half);
										} else {
											$lists_to_display = array($card['userNames']);
										}
									} else {
										$should_split_list = false;
										$lists_to_display = array($card['userNames']);
									}
									
									$users_per_row = ($is_alone && $should_split_list) ? 2 : 1;
									
									// Calculer la hauteur exacte nécessaire pour les utilisateurs
									$actual_users_height = 0;
									foreach (array_chunk($card['userNames'], $users_per_row) as $row_users) {
										$max_lines_in_row = 1; // Au minimum 1 ligne par utilisateur
										foreach ($row_users as $userInfo) {
											$info_parts = explode(' - ', $userInfo);
											$fonction = '';
											$habilitation = '';
											
											foreach ($info_parts as $part) {
												if (strpos($part, 'Fonctions:') === 0) {
													$fonction = substr($part, 10);
												} elseif (strpos($part, 'Habilitations:') === 0) {
													$habilitation = substr($part, 14);
												}
											}
											
											// Calcul précis des lignes nécessaires
											$hab_line_count = 1;
											if (!empty($habilitation)) {
												$hab_words = explode('-', $habilitation);
												$hab_line_count = max(1, ceil(count($hab_words) / 3));
											}
											
											$fonc_line_count = 1;
											if (!empty($fonction)) {
												$fonc_words = explode('-', $fonction);
												$fonc_line_count = max(1, ceil(count($fonc_words) / 2));
											}
											
											$max_lines_in_row = max($max_lines_in_row, $hab_line_count, $fonc_line_count);
										}
										$actual_users_height += ($max_lines_in_row * 3) + 2; // 3px par ligne + 2px d'espacement
									}
									$list_height += $actual_users_height;
									$list_height += 3; // Marge finale réduite
									
									$card_content_height = $list_height;
								} else {
									// Calculer la hauteur pour les cartes normales - HAUTEUR EXACTE
									$actual_content_height = 15; // Hauteur pour le titre
									
									foreach ($card['userNames'] as $userInfo) {
										$info_parts = explode(' - ', $userInfo);
										$name = $info_parts[0];
											$habilitations = '';
										
										foreach ($info_parts as $part) {
											if (strpos($part, 'Habilitations:') === 0) {
												$habilitations = substr($part, 14);
											}
										}
										
										// Calculer les lignes exactes pour le nom
										$name_parts = explode(' ', $name);
										$max_width = $card_width - 25;
										$name_lines = 1; // Au minimum une ligne
										$current_line = '';
										
										foreach ($name_parts as $part) {
											$test_line = $current_line . ($current_line ? ' ' : '') . $part;
											if ($pdf->GetStringWidth($test_line) >= $max_width) {
												$name_lines++;
												$current_line = $part;
											} else {
												$current_line = $test_line;
											}
										}
										
										// Calculer les lignes exactes pour les habilitations
										$hab_lines = 0;
										if (!empty($habilitations)) {
											$hab_lines = 1; // Au minimum une ligne si habilitations existent
											$max_hab_width = $card_width - 30;
											$words = explode('-', $habilitations);
											$current_line = '';
											
											foreach ($words as $word) {
												$word = trim($word);
												$test_line = $current_line . ($current_line ? '-' : '') . $word;
												if ($pdf->GetStringWidth($test_line) >= $max_hab_width) {
													$hab_lines++;
													$current_line = $word;
												} else {
													$current_line = $test_line;
												}
											}
										}
										
										// Ajouter l'espace réel nécessaire
										$actual_content_height += 4; // "Nom/Prénom :" label
										$actual_content_height += ($name_lines * 4); // Lignes de nom
										$actual_content_height += 1; // Espacement
										
										if ($hab_lines > 0) {
											$actual_content_height += 4; // "Habilitation :" label
											$actual_content_height += ($hab_lines * 4); // Lignes d'habilitations
											$actual_content_height += 1; // Espacement
										}
										
										// Vérifier s'il y a un contrat
										$has_contrat = false;
										foreach ($info_parts as $part) {
											if (strpos($part, 'Contrat:') === 0) {
												$has_contrat = true;
												break;
											}
										}
										
										if ($has_contrat) {
											$actual_content_height += 5; // Ligne de contrat
										}
										
										$actual_content_height += 2; // Espacement entre utilisateurs
									}
									
									$actual_content_height += 2; // Marge finale minimale
									$card_content_height = $actual_content_height;
								}
							}
							
							$card_heights[$card_index] = $card_content_height;
							$max_line_height = max($max_line_height, $card_content_height);
						}
						
						// Vérifier si on a assez d'espace pour toute la ligne
						if ($current_y + $max_line_height > $this->page_hauteur - $this->marge_basse) {
							$pdf->AddPage();
							$current_y = $this->_pagehead($pdf, $object, $outputlangs);
							$this->_pagefoot($pdf, $object, $outputlangs);
							$current_x = $this->marge_gauche + ($line_width - $total_cards_width) / 2;
						}
						
						// Maintenant afficher chaque carte de la ligne
						$temp_current_x = $current_x; // Position X temporaire pour cette ligne
						$actual_line_heights = array(); // Stocker les hauteurs réelles de chaque carte
						
						foreach ($cardsOnY as $card_index => $card) {
							$card_width = ($card['type'] === 'list') ? $list_card_width : $normal_card_width;
							$content_height = $card_heights[$card_index];
							
							if (!empty($card['userNames'])) {
								if ($card['type'] === 'list') {
									// AFFICHAGE EN TABLEAU POUR LES LISTES
									$is_alone = count($cardsOnY) === 1;
									$total_users = count($card['userNames']);
									
									// Logique de division identique au calcul de hauteur
									if ($is_alone) {
										$should_split_list = $total_users > 4;
										if ($should_split_list) {
											$first_half = array_slice($card['userNames'], 0, ceil($total_users / 2));
											$second_half = array_slice($card['userNames'], ceil($total_users / 2));
											$lists_to_display = array($first_half, $second_half);
										} else {
											$lists_to_display = array($card['userNames']);
										}
									} else {
										$should_split_list = false;
										$lists_to_display = array($card['userNames']);
									}
									
									if ($is_alone && $should_split_list) {
										$col_widths = array(
											'nom' => ($card_width * 0.25) / 2,
											'habilitation' => ($card_width * 0.35) / 2,
											'fonction' => ($card_width * 0.25) / 2,
											'contrat' => ($card_width * 0.15) / 2
										);
										$users_per_row = 2;
									} else {
										$col_widths = array(
											'nom' => $card_width * 0.25,
											'habilitation' => $card_width * 0.35,
											'fonction' => $card_width * 0.25,
											'contrat' => $card_width * 0.15
										);
										$users_per_row = 1;
									}
									
										// Position de départ pour cette liste
									$list_start_y = $current_y;
									
									// Dessiner le contour GAUCHE ET DROIT UNIQUEMENT AU DÉBUT
									$pdf->SetDrawColor(0, 0, 0);
									$side_line_start_y = $current_y + 12;
									$pdf->Line($temp_current_x, $side_line_start_y, $temp_current_x, $side_line_start_y); // On dessine juste un point pour l'instant
									$pdf->Line($temp_current_x + $card_width, $side_line_start_y, $temp_current_x + $card_width, $side_line_start_y); // On dessine juste un point pour l'instant
									
									// Afficher le titre UNIQUEMENT UNE FOIS
									$pdf->SetFont('', 'B', 9);
									$list_title = $card['title'];
									$title_width = $pdf->GetStringWidth($list_title);
									$pdf->Text($temp_current_x + ($card_width - $title_width) / 2, $current_y + 5, $list_title);
									
									// Afficher la légende UNIQUEMENT UNE FOIS
									$legend_y = $current_y + 15;
									$current_x_legend = $temp_current_x + 2;
									
									$pdf->SetFont('', '', 5);
									$pdf->Text($current_x_legend, $legend_y, 'Nom');
									$current_x_legend += $col_widths['nom'];
									
									$habilitation_title = 'Habilitation';
									$habilitation_width = $pdf->GetStringWidth($habilitation_title);
									$habilitation_x = $current_x_legend + ($col_widths['habilitation'] - $habilitation_width) / 2;
									$pdf->Text($habilitation_x, $legend_y, $habilitation_title);
									$current_x_legend += $col_widths['habilitation'];
									
									$fonction_x = $current_x_legend;
									$pdf->Text($fonction_x, $legend_y, 'Fonction');
									$pdf->SetTextColor(128, 128, 128);
									$pdf->Text($fonction_x + $pdf->GetStringWidth('Fonction') + 1, $legend_y, '(1)');
									$pdf->SetTextColor(0, 0, 0);
									$current_x_legend += $col_widths['fonction'];
									
									$contrat_x = $current_x_legend;
									$pdf->Text($contrat_x, $legend_y, 'Contrat');
									$pdf->SetTextColor(128, 128, 128);
									$pdf->Text($contrat_x + $pdf->GetStringWidth('Contrat') + 1, $legend_y, '(2)');
									$pdf->SetTextColor(0, 0, 0);
									
									// Pour une liste divisée, ajouter les en-têtes de la deuxième colonne
									if ($is_alone && $should_split_list) {
										$current_x_legend += $col_widths['contrat'];
										
										$pdf->Text($current_x_legend, $legend_y, 'Nom');
										$current_x_legend += $col_widths['nom'];
										
										$habilitation_x = $current_x_legend + ($col_widths['habilitation'] - $habilitation_width) / 2;
										$pdf->Text($habilitation_x, $legend_y, $habilitation_title);
										$current_x_legend += $col_widths['habilitation'];
										
										$fonction_x = $current_x_legend;
										$pdf->Text($fonction_x, $legend_y, 'Fonction');
										$pdf->SetTextColor(128, 128, 128);
										$pdf->Text($fonction_x + $pdf->GetStringWidth('Fonction') + 1, $legend_y, '(1)');
										$pdf->SetTextColor(0, 0, 0);
										$current_x_legend += $col_widths['fonction'];
										
										$contrat_x = $current_x_legend;
										$pdf->Text($contrat_x, $legend_y, 'Contrat');
										$pdf->SetTextColor(128, 128, 128);
										$pdf->Text($contrat_x + $pdf->GetStringWidth('Contrat') + 1, $legend_y, '(2)');
										$pdf->SetTextColor(0, 0, 0);
									}

									// Ligne de séparation sous la légende
									$pdf->SetDrawColor(200, 200, 200);
									if ($is_alone && $should_split_list) {
										// Pour une liste divisée, ligne complète + séparation verticale
										$pdf->Line($temp_current_x + 2, $legend_y + 4, $temp_current_x + $card_width - 2, $legend_y + 4);
										$mid_x = $temp_current_x + ($card_width / 2);
										$pdf->Line($mid_x, $legend_y + 4, $mid_x, $legend_y + 4); // On étendra cette ligne plus tard
									} else {
										$pdf->Line($temp_current_x + 2, $legend_y + 4, $temp_current_x + $card_width - 2, $legend_y + 4);
									}
									
									// Afficher les utilisateurs UNE SEULE FOIS
									$y_offset = $legend_y + 7;
									$pdf->SetFont('', '', 7);
									
									// Utiliser TOUS les utilisateurs pour l'affichage
									$users_to_display = $card['userNames'];
									
									foreach (array_chunk($users_to_display, $users_per_row) as $row_users) {
										$max_lines_in_row = 0;
										$current_x_user = $temp_current_x + 2;
										
										foreach ($row_users as $user_index => $userInfo) {
											// Ajuster la position X pour la deuxième colonne si nécessaire
											if ($user_index > 0 && $is_alone && $should_split_list) {
												$current_x_user = $temp_current_x + ($card_width / 2) + 2;
											}
											
											$info_parts = explode(' - ', $userInfo);
											$lastname = $info_parts[0];
											$firstname = $info_parts[1];
											$name = $lastname . '.' . substr($firstname, 0, 1);
											
											$fonction = '';
											$contrat = '';
											$habilitation = '';
											
											foreach ($info_parts as $part) {
												if (strpos($part, 'Fonctions:') === 0) {
													$fonction = substr($part, 10);
												} elseif (strpos($part, 'Contrat:') === 0) {
													$contrat = substr($part, 9);
												} elseif (strpos($part, 'Habilitations:') === 0) {
													$habilitation = substr($part, 14);
												}
											}
											
											$pdf->Text($current_x_user, $y_offset, $name);
											$current_x_user += $col_widths['nom'];
											
											// Traitement habilitations
											$hab_lines = array();
											$words = explode('-', $habilitation);
											$current_line = '';
											foreach ($words as $word) {
												$word = trim($word);
												$test_line = $current_line . ($current_line ? '-' : '') . $word;
												if ($pdf->GetStringWidth($test_line) < $col_widths['habilitation'] - 2) {
													$current_line = $test_line;
												} else {
													if ($current_line) {
														$hab_lines[] = $current_line;
													}
													$current_line = $word;
												}
											}
											if ($current_line) {
												$hab_lines[] = $current_line;
											}
											
											// Traitement fonctions
											$fonc_lines = array();
											$words = explode('-', $fonction);
											$current_line = '';
											foreach ($words as $word) {
												$word = trim($word);
												$test_line = $current_line . ($current_line ? '-' : '') . $word;
												if ($pdf->GetStringWidth($test_line) < $col_widths['fonction'] - 2) {
													$current_line = $test_line;
												} else {
													if ($current_line) {
														$fonc_lines[] = $current_line;
													}
													$current_line = $word;
												}
											}
											if ($current_line) {
												$fonc_lines[] = $current_line;
											}
											
											$pdf->Text($current_x_user + $col_widths['habilitation'] + $col_widths['fonction'], $y_offset, $contrat);
											
											$max_lines = max(1, count($fonc_lines), count($hab_lines));
											$max_lines_in_row = max($max_lines_in_row, $max_lines);
											
											// Afficher habilitations
											$temp_y = $y_offset;
											foreach ($hab_lines as $line) {
												$pdf->Text($current_x_user, $temp_y, $line);
												$temp_y += 3;
											}
											
											// Afficher fonctions
											$temp_y = $y_offset;
											foreach ($fonc_lines as $line) {
												$pdf->Text($current_x_user + $col_widths['habilitation'], $temp_y, $line);
												$temp_y += 3;
											}
											
											$current_x_user += $col_widths['habilitation'] + $col_widths['fonction'] + $col_widths['contrat'];
										}
										
										$y_offset += ($max_lines_in_row * 3) + 2;
										
										// Ligne de séparation entre les lignes (sauf la dernière)
										if ($y_offset < $current_y + $content_height - 5) {
											$pdf->SetDrawColor(200, 200, 200);
											if ($is_alone && $should_split_list) {
												$mid_x = $temp_current_x + ($card_width / 2);
												$gap = 10;
												$pdf->Line($temp_current_x + 2, $y_offset - 1, $mid_x - ($gap/2), $y_offset - 1);
												$pdf->Line($mid_x + ($gap/2), $y_offset - 1, $temp_current_x + $card_width - 2, $y_offset - 1);
											} else {
												$pdf->Line($temp_current_x + 2, $y_offset - 1, $temp_current_x + $card_width - 2, $y_offset - 1);
											}
										}
										}
									
									// MAINTENANT DESSINER LES CONTOURS AVEC LA POSITION Y RÉELLE
									$actual_list_end_y = $y_offset; // Position réelle de fin
									$actual_line_heights[$card_index] = $actual_list_end_y - $list_start_y;
									
									// Dessiner le contour complet avec la hauteur réelle
									$pdf->SetDrawColor(0, 0, 0);
									$pdf->Line($temp_current_x, $side_line_start_y, $temp_current_x, $actual_list_end_y);
									$pdf->Line($temp_current_x + $card_width, $side_line_start_y, $temp_current_x + $card_width, $actual_list_end_y);
									$pdf->Line($temp_current_x, $actual_list_end_y, $temp_current_x + $card_width, $actual_list_end_y);
									
									// Étendre la ligne verticale du milieu si nécessaire
									if ($is_alone && $should_split_list) {
										$mid_x = $temp_current_x + ($card_width / 2);
										$pdf->Line($mid_x, $legend_y + 4, $mid_x, $actual_list_end_y);
									}
								} else {
									// AFFICHAGE STANDARD POUR LES CARTES NORMALES (inchangé)
									$pdf->SetDrawColor(0, 0, 0);
									
									// Calculer la hauteur RÉELLE du contenu pendant l'affichage
									$actual_y_end = $current_y + 12; // Position après le titre
									
									$pdf->SetFont('', 'B', 10);
									$pdf->SetTextColor(0, 0, 0);
									$title_width = $pdf->GetStringWidth($card['title']);
									$center_title_x = $temp_current_x + ($card_width - $title_width) / 2 - $shift_left;
									$pdf->Text($center_title_x, $current_y + 5, $card['title']);
									
									$y_offset = $current_y + 12;
									$pdf->SetFont('', '', 8);
									
									foreach ($card['userNames'] as $userInfo) {
										$info_parts = explode(' - ', $userInfo);
										$name = $info_parts[0];
										$contrat = '';
										$habilitations = '';
										
										foreach ($info_parts as $part) {
											if (strpos($part, 'Contrat:') === 0) {
												$contrat = substr($part, 9);
											} elseif (strpos($part, 'Habilitations:') === 0) {
												$habilitations = substr($part, 14);
											}
										}
										
										$pdf->SetFont('', '', 6);
										$pdf->Text($temp_current_x + 2, $y_offset, 'Nom/Prénom :');
										$pdf->SetFont('', '', 8);
										
										$max_width = $card_width - 25;
										$name_parts = explode(' ', $name);
										$current_line = '';
										$name_lines = array();
										
										foreach ($name_parts as $part) {
											$test_line = $current_line . ($current_line ? ' ' : '') . $part;
											if ($pdf->GetStringWidth($test_line) < $max_width) {
												$current_line = $test_line;
											} else {
												if ($current_line) {
													$name_lines[] = $current_line;
												}
												$current_line = $part;
											}
										}
										if ($current_line) {
											$name_lines[] = $current_line;
										}
										
										foreach ($name_lines as $line) {
											$pdf->Text($temp_current_x + 20, $y_offset, $line);
											$y_offset += 4;
										}
										
										$y_offset += 1;
										
										if (!empty($habilitations)) {
											$pdf->SetFont('', '', 6);
											$pdf->Text($temp_current_x + 2, $y_offset, 'Habilitation :');
											$pdf->SetFont('', '', 8);
											
											$max_width = $card_width - 30;
											$words = explode('-', $habilitations);
											$current_line = '';
											
											foreach ($words as $word) {
												$word = trim($word);
												$test_line = $current_line . ($current_line ? '-' : '') . $word;
												
												if ($pdf->GetStringWidth($test_line) < $max_width) {
													$current_line = $test_line;
												} else {
													if ($current_line) {
														$pdf->Text($temp_current_x + 20, $y_offset, $current_line);
														$y_offset += 4;
													}
													$current_line = $word;
												}
											}
											
											if ($current_line) {
												$pdf->Text($temp_current_x + 20, $y_offset, $current_line);
												$y_offset += 4; // Réduit de 5 à 4
											}
										}
										
										if (!empty($contrat)) {
											$pdf->SetFont('', '', 6);
											$pdf->Text($temp_current_x + 2, $y_offset, 'Contrat :');
											$pdf->SetFont('', 'B', 8);
											$pdf->Text($temp_current_x + 20, $y_offset, $contrat);
											$y_offset += 4; // Réduit de 5 à 4
										}
										
										$y_offset += 2;
									}
									
									// Utiliser la position Y réelle au lieu de content_height calculé
									$actual_y_end = $y_offset - 2; // Enlever le dernier espacement
									$actual_line_heights[$card_index] = $actual_y_end - $current_y;
									
									// Dessiner les lignes avec la hauteur réelle
									$pdf->Line($temp_current_x, $actual_y_end, $temp_current_x + $card_width, $actual_y_end);
									$side_line_start_y = $current_y + 12;
									$pdf->Line($temp_current_x, $side_line_start_y, $temp_current_x, $actual_y_end);
									$pdf->Line($temp_current_x + $card_width, $side_line_start_y, $temp_current_x + $card_width, $actual_y_end);
								}
							}
							
							// Passer à la position suivante
							$temp_current_x += $card_width + $card_margin;
						}
						
						// Passer à la ligne suivante en utilisant la hauteur RÉELLE maximale
						$real_max_height = max($actual_line_heights);
						$current_y += $real_max_height + 2;
					}
				}

				// AFFICHAGE DES LISTES
				if (!empty($listeUniqueCards)) {
					dol_syslog("DEBUG: Début affichage des listes - " . count($listeUniqueCards) . " liste(s)", LOG_DEBUG);
					
					foreach ($listeUniqueCards as $cardIndex => $card) {
						dol_syslog("DEBUG: Affichage liste " . ($cardIndex + 1) . ": " . $card['title'], LOG_DEBUG);
						
						// Vérifier si la carte a des utilisateurs
						if (empty($card['userNames'])) {
							dol_syslog("DEBUG: Liste " . $card['title'] . " vide, passage à la suivante", LOG_DEBUG);
							continue;
						}
						
						dol_syslog("DEBUG: Liste " . $card['title'] . " avec " . count($card['userNames']) . " utilisateurs", LOG_DEBUG);

						// Calculer la hauteur PRÉCISE de la liste
						$estimated_height = 30; // Header + en-têtes
						$users_count = count($card['userNames']);
						
						// Calculer la hauteur réelle nécessaire pour chaque ligne
						$actual_content_height = 0;
						foreach (array_chunk($card['userNames'], 2) as $row_users) {
							$max_lines_in_row = 1;
							foreach ($row_users as $userInfo) {
								$info_parts = explode(' - ', $userInfo);
								$habilitation = '';
								$fonction = '';
								
								foreach ($info_parts as $part) {
									if (strpos($part, 'Habilitations:') === 0) {
										$habilitation = substr($part, 14);
									} elseif (strpos($part, 'Fonctions:') === 0) {
										$fonction = substr($part, 10);
									}
								}
								
								// Calculer le nombre de lignes nécessaires
								$hab_lines = 1;
								if (!empty($habilitation)) {
									$hab_parts = explode('-', $habilitation);
									$hab_lines = max(1, ceil(count($hab_parts) / 3));
								}
								
								$fonc_lines = 1;
								if (!empty($fonction)) {
									$fonc_parts = explode('-', $fonction);
									$fonc_lines = max(1, ceil(count($fonc_parts) / 2));
								}
								
								$max_lines_in_row = max($max_lines_in_row, $hab_lines, $fonc_lines);
							}
							$actual_content_height += ($max_lines_in_row * 2.0) + 3;
						}
						
						$totalListHeight = $estimated_height + $actual_content_height;

						// Optimiser l'espace disponible - Utiliser plus d'espace en haut
						$available_space_top = $this->page_hauteur - $current_y - 60; // Marge augmentée pour signatures
						
						// Si la liste peut tenir sur la page actuelle, l'afficher
						if ($totalListHeight <= $available_space_top) {
							// Afficher la liste sur la page actuelle
							dol_syslog("DEBUG: Liste " . $card['title'] . " affichée sur la page actuelle", LOG_DEBUG);
						} else {
							// Forcer un saut de page seulement si vraiment nécessaire
							dol_syslog("DEBUG: Saut de page nécessaire pour la liste " . $card['title'], LOG_DEBUG);
							$pdf->AddPage();
							$current_y = $this->_pagehead($pdf, $object, $outputlangs);
							$this->_pagefoot($pdf, $object, $outputlangs);
						}

						// Afficher la liste avec des colonnes plus compactes
						$card_width = ($this->page_largeur - $this->marge_gauche - $this->marge_droite);
						
						// Ajustement des largeurs pour une liste seule (comme une carte normale seule)
						$col_widths = array(
							'nom' => $card_width * 0.20,        // Plus large pour une liste seule
							'habilitation' => $card_width * 0.40, // Plus large pour une liste seule
							'fonction' => $card_width * 0.20,    // Plus large pour une liste seule
							'contrat' => $card_width * 0.15      // Plus large pour une liste seule
						);

						// Pour une liste seule, pas de division en deux colonnes
						$separator_x = null; // Pas de séparateur pour une liste seule
						$col_positions = array(
							// Une seule colonne centrée
							'nom1' => $this->marge_gauche + ($card_width * 0.025), // Centré avec marge
							'hab1' => $this->marge_gauche + ($card_width * 0.025) + $col_widths['nom'],
							'fonc1' => $this->marge_gauche + ($card_width * 0.025) + $col_widths['nom'] + $col_widths['habilitation'],
							'cont1' => $this->marge_gauche + ($card_width * 0.025) + $col_widths['nom'] + $col_widths['habilitation'] + $col_widths['fonction']
						);

						$pdf->SetFont('', '', 7);
						$current_x = $this->marge_gauche;
						$y_offset = $current_y + 5;

						// Afficher le titre de la liste
						$pdf->SetFont('', 'B', 9);
						$title_width = $pdf->GetStringWidth($card['title']);
						$pdf->Text($current_x + ($card_width - $title_width) / 2, $y_offset, $card['title']);
						$y_offset += 8;

						// Afficher les en-têtes de colonnes avec centrage
						$pdf->SetFont('', 'B', 6);
						$pdf->SetTextColor(0, 0, 0);
						
						// En-têtes centrés dans leurs colonnes
						$nom_header_width = $pdf->GetStringWidth('Nom');
						$pdf->Text($col_positions['nom1'] + ($col_widths['nom'] - $nom_header_width) / 2, $y_offset, 'Nom');
						
						$hab_header_width = $pdf->GetStringWidth('Habilitations');
						$pdf->Text($col_positions['hab1'] + ($col_widths['habilitation'] - $hab_header_width) / 2, $y_offset, 'Habilitations');
						
						$fonc_header_width = $pdf->GetStringWidth('Fonction');
						$pdf->Text($col_positions['fonc1'] + ($col_widths['fonction'] - $fonc_header_width) / 2, $y_offset, 'Fonction');
						
						$cont_header_width = $pdf->GetStringWidth('Contrat');
						$pdf->Text($col_positions['cont1'] + ($col_widths['contrat'] - $cont_header_width) / 2, $y_offset, 'Contrat');
						
						$y_offset += 4;
						
						// Ligne de séparation sous les en-têtes
						$pdf->SetDrawColor(200, 200, 200);
						$pdf->Line($current_x + 2, $y_offset, $current_x + $card_width - 2, $y_offset);
						$y_offset += 3;

						// Retour à la police normale pour les données
						$pdf->SetFont('', '', 6);

						// Variable pour suivre la position Y réelle
						$actual_y_end = $y_offset;

						// Afficher un utilisateur par ligne (pas de division en 2 colonnes)
						foreach ($card['userNames'] as $userInfo) {
							// Séparer les informations
							$info_parts = explode(' - ', $userInfo);
							$name_parts = explode(' ', $info_parts[0]);
							$lastname = $name_parts[count($name_parts) - 1];
							$firstname = $name_parts[0];
							$name = $lastname . '.' . substr($firstname, 0, 1);
							
							$habilitation = '';
							$fonction = '';
							$contrat = '';

							foreach ($info_parts as $part) {
								if (strpos($part, 'Habilitations:') === 0) {
									$habilitation = substr($part, 14);
								} elseif (strpos($part, 'Fonctions:') === 0) {
									$fonction = substr($part, 10);
								} elseif (strpos($part, 'Contrat:') === 0) {
									$contrat = substr($part, 9);
								}
							 }

							// Calculer les limites pour les habilitations
							$max_hab_width = $col_widths['habilitation'] - 4;

							// Gestion des retours à la ligne pour les habilitations
							$habilitation_lines = [];
							if (!empty($habilitation)) {
								$habilitation_parts = explode('-', $habilitation);
								$current_line = '';
								$max_elements_per_line = 5; // Plus d'éléments par ligne car plus large

								foreach ($habilitation_parts as $part) {
									$part = trim($part);
									if (empty($part)) continue;
									
									$test_line = $current_line . ($current_line ? '-' : '') . $part;
									
									if ($pdf->GetStringWidth($test_line) > $max_hab_width || 
										(substr_count($current_line, '-') >= ($max_elements_per_line - 1) && !empty($current_line))) {
										
										if ($current_line) {
											$habilitation_lines[] = $current_line;
										}
										$current_line = $part;
										
										if ($pdf->GetStringWidth($current_line) > $max_hab_width) {
											while ($pdf->GetStringWidth($current_line) > $max_hab_width && strlen($current_line) > 0) {
												$current_line = substr($current_line, 0, -1);
											}
										}
									} else {
										$current_line = $test_line;
									}
								}
								
								if ($current_line) {
									$habilitation_lines[] = $current_line;
								}
								
								if (empty($habilitation_lines)) {
									$habilitation_lines[] = '';
								}
							}

							// Traitement similaire pour les fonctions
							$fonction_lines = [];
							if (!empty($fonction)) {
								$fonction_parts = explode('-', $fonction);
								$current_line = '';
								$max_fonc_width = $col_widths['fonction'] - 2;

								foreach ($fonction_parts as $part) {
									$part = trim($part);
									if (empty($part)) continue;
									
									$test_line = $current_line . ($current_line ? '-' : '') . $part;
									
									if ($pdf->GetStringWidth($test_line) > $max_fonc_width) {
										if ($current_line) {
											$fonction_lines[] = $current_line;
										}
										$current_line = $part;
										
										while ($pdf->GetStringWidth($current_line) > $max_fonc_width && strlen($current_line) > 0) {
											$current_line = substr($current_line, 0, -1);
										}
									} else {
										$current_line = $test_line;
									}
								}
								
								if ($current_line) {
									$fonction_lines[] = $current_line;
								}
								
								if (empty($fonction_lines)) {
									$fonction_lines[] = $fonction;
								}
							}

							// Calculer le nombre maximum de lignes pour cet utilisateur
							$max_lines_for_user = max(1, count($habilitation_lines), count($fonction_lines));

							// Afficher le nom (centré dans sa colonne)
							$name_width = $pdf->GetStringWidth($name);
							$centered_nom_x = $col_positions['nom1'] + ($col_widths['nom'] - $name_width) / 2;
							$pdf->Text($centered_nom_x, $actual_y_end, $name);

							// Afficher les habilitations sur plusieurs lignes (centrées dans leur colonne)
							$temp_y = $actual_y_end;
							foreach ($habilitation_lines as $line) {
								$line_width = $pdf->GetStringWidth($line);
								$centered_hab_x = $col_positions['hab1'] + ($col_widths['habilitation'] - $line_width) / 2;
								$pdf->Text($centered_hab_x, $temp_y, $line);
								$temp_y += 2.0;
							}

							// Afficher les fonctions sur plusieurs lignes (centrées dans leur colonne)
							$temp_y = $actual_y_end;
							foreach ($fonction_lines as $line) {
								$line_width = $pdf->GetStringWidth($line);
								$centered_fonc_x = $col_positions['fonc1'] + ($col_widths['fonction'] - $line_width) / 2;
								$pdf->Text($centered_fonc_x, $temp_y, $line);
								$temp_y += 2.0;
							}

							// Afficher le contrat (centré dans sa colonne)
							$contrat_display = $contrat;
							$max_cont_width = $col_widths['contrat'] - 2;
							while ($pdf->GetStringWidth($contrat_display) > $max_cont_width && strlen($contrat_display) > 0) {
								$contrat_display = substr($contrat_display, 0, -1);
							}
							$contrat_width = $pdf->GetStringWidth($contrat_display);
							$centered_cont_x = $col_positions['cont1'] + ($col_widths['contrat'] - $contrat_width) / 2;
							$pdf->Text($centered_cont_x, $actual_y_end, $contrat_display);

							// Passer à la ligne suivante
							$actual_y_end += ($max_lines_for_user * 2.0) + 3;

							// Ligne de séparation horizontale entre les utilisateurs
							$pdf->SetDrawColor(230, 230, 230);
							$pdf->Line($current_x + 2, $actual_y_end - 2, $current_x + $card_width - 2, $actual_y_end - 2);
						}

						// Dessiner le contour de la liste avec la position Y réelle
						$pdf->SetDrawColor(0, 0, 0);
						$pdf->Line($current_x, $current_y + 12, $current_x, $actual_y_end); // Ligne gauche
						$pdf->Line($current_x + $card_width, $current_y + 12, $current_x + $card_width, $actual_y_end); // Ligne droite
						$pdf->Line($current_x, $actual_y_end, $current_x + $card_width, $actual_y_end); // Ligne du bas

						// Mettre à jour la position Y actuelle avec la position réelle
						$current_y = $actual_y_end + 3;
					}
				}

				// AFFICHAGE DE LA LISTE DES SOUS-TRAITANTS
				// Récupérer les sous-traitants depuis la table dédiée
				$sql_soustraitants = "SELECT 
					ots.fk_socpeople,
					ots.fk_societe,
					ots.fonction,
					ots.contrat,
					ots.habilitation,
					sp.lastname,
					sp.firstname,
					s.nom
				FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants ots
				LEFT JOIN " . MAIN_DB_PREFIX . "socpeople sp ON ots.fk_socpeople = sp.rowid
				LEFT JOIN " . MAIN_DB_PREFIX . "societe s ON ots.fk_societe = s.rowid
				WHERE ots.ot_id = " . $object->id;

				$resql_soustraitants = $db->query($sql_soustraitants);
				$soustraitants_data = array();

				if ($resql_soustraitants && $db->num_rows($resql_soustraitants) > 0) {
					while ($soustraitant = $db->fetch_object($resql_soustraitants)) {
						// Formater les informations du sous-traitant
						$userInfo = $soustraitant->firstname . ' ' . $soustraitant->lastname;
						if (!empty($soustraitant->nom)) {
							$userInfo .= ' - Entreprise: ' . $soustraitant->nom;
						}
						if (!empty($soustraitant->contrat)) {
							$userInfo .= ' - Contrat: ' . $soustraitant->contrat;
						}
						if (!empty($soustraitant->fonction)) {
							$userInfo .= ' - Fonctions: ' . $soustraitant->fonction;
						}
						if (!empty($soustraitant->habilitation)) {
							$userInfo .= ' - Habilitations: ' . $soustraitant->habilitation;
						}
						
						$soustraitants_data[] = $userInfo;
					}
				}

				// Afficher la liste des sous-traitants si elle existe
				if (!empty($soustraitants_data)) {
					dol_syslog("DEBUG: Affichage liste sous-traitants avec " . count($soustraitants_data) . " entrées", LOG_DEBUG);

					// Calculer la hauteur nécessaire pour la liste des sous-traitants
					$estimated_height = 30;
					$actual_content_height = 0;
					foreach ($soustraitants_data as $userInfo) {
						$info_parts = explode(' - ', $userInfo);
						$habilitation = '';
						$fonction = '';
						
						foreach ($info_parts as $part) {
							if (strpos($part, 'Habilitations:') === 0) {
								$habilitation = substr($part, 14);
							} elseif (strpos($part, 'Fonctions:') === 0) {
								$fonction = substr($part, 10);
							}
						}
						
						$hab_lines = 1;
						if (!empty($habilitation)) {
							$hab_parts = explode('-', $habilitation);
							$hab_lines = max(1, ceil(count($hab_parts) / 5)); // Plus d'éléments par ligne
						}
						
						$fonc_lines = 1;
						if (!empty($fonction)) {
							$fonc_parts = explode('-', $fonction);
							$fonc_lines = max(1, ceil(count($fonc_parts) / 3)); // Plus d'éléments par ligne
						}
						
						$max_lines_for_user = max(1, $hab_lines, $fonc_lines);
						$actual_content_height += ($max_lines_for_user * 2.0) + 3;
					}
					
					$totalListHeight = $estimated_height + $actual_content_height;

					// Vérifier l'espace disponible avec marge pour signatures
					$available_space_top = $this->page_hauteur - $current_y - 60; // Marge augmentée pour signatures
					
					if ($totalListHeight > $available_space_top) {
						dol_syslog("DEBUG: Saut de page nécessaire pour la liste des sous-traitants", LOG_DEBUG);
						$pdf->AddPage();
						$current_y = $this->_pagehead($pdf, $object, $outputlangs);
						$this->_pagefoot($pdf, $object, $outputlangs);
					} else {
						dol_syslog("DEBUG: Liste des sous-traitants affichée sur la page actuelle", LOG_DEBUG);
					}

					// Configuration de la liste des sous-traitants avec même largeur qu'une liste normale
					$card_width = ($this->page_largeur - $this->marge_gauche - $this->marge_droite);
					
					// Même répartition que pour les listes normales
					$col_widths = array(
						'nom' => $card_width * 0.15,        // Nom/Prénom
						'entreprise' => $card_width * 0.20, // Entreprise
						'habilitation' => $card_width * 0.35, // Habilitations (plus large)
						'fonction' => $card_width * 0.20,   // Fonction
						'contrat' => $card_width * 0.10     // Contrat
					);

					// Positions pour une seule colonne large
					$col_positions = array(
						'nom1' => $this->marge_gauche + ($card_width * 0.025), // Centré avec marge
						'entr1' => $this->marge_gauche + ($card_width * 0.025) + $col_widths['nom'],
						'hab1' => $this->marge_gauche + ($card_width * 0.025) + $col_widths['nom'] + $col_widths['entreprise'],
						'fonc1' => $this->marge_gauche + ($card_width * 0.025) + $col_widths['nom'] + $col_widths['entreprise'] + $col_widths['habilitation'],
						'cont1' => $this->marge_gauche + ($card_width * 0.025) + $col_widths['nom'] + $col_widths['entreprise'] + $col_widths['habilitation'] + $col_widths['fonction']
					);

					$pdf->SetFont('', '', 7);
					$current_x = $this->marge_gauche;
					$y_offset = $current_y + 5;

					// Titre de la liste des sous-traitants
					$pdf->SetFont('', 'B', 9);
					$title_text = "Liste des Sous-traitants";
					$title_width = $pdf->GetStringWidth($title_text);
					$pdf->Text($current_x + ($card_width - $title_width) / 2, $y_offset, $title_text);
					$y_offset += 8;

					// En-têtes de colonnes
					$pdf->SetFont('', 'B', 6);
					$pdf->SetTextColor(0, 0, 0);
					
					// En-têtes centrés dans leurs colonnes
					$nom_header_width = $pdf->GetStringWidth('Nom');
					$pdf->Text($col_positions['nom1'] + ($col_widths['nom'] - $nom_header_width) / 2, $y_offset, 'Nom');
					
					$entr_header_width = $pdf->GetStringWidth('Entreprise');
					$pdf->Text($col_positions['entr1'] + ($col_widths['entreprise'] - $entr_header_width) / 2, $y_offset, 'Entreprise');
					
					$hab_header_width = $pdf->GetStringWidth('Habilitations');
					$pdf->Text($col_positions['hab1'] + ($col_widths['habilitation'] - $hab_header_width) / 2, $y_offset, 'Habilitations');
					
					$fonc_header_width = $pdf->GetStringWidth('Fonction');
					$pdf->Text($col_positions['fonc1'] + ($col_widths['fonction'] - $fonc_header_width) / 2, $y_offset, 'Fonction');
					
					$cont_header_width = $pdf->GetStringWidth('Contrat');
					$pdf->Text($col_positions['cont1'] + ($col_widths['contrat'] - $cont_header_width) / 2, $y_offset, 'Contrat');
					
					$y_offset += 4;
					
					// Ligne de séparation
					$pdf->SetDrawColor(200, 200, 200);
					$pdf->Line($current_x + 2, $y_offset, $current_x + $card_width - 2, $y_offset);
					$y_offset += 3;

					// Données des sous-traitants
					$pdf->SetFont('', '', 6);
					$actual_y_end = $y_offset;

					// Afficher un sous-traitant par ligne
					foreach ($soustraitants_data as $userInfo) {
						$info_parts = explode(' - ', $userInfo);
						$name_parts = explode(' ', $info_parts[0]);
						$lastname = $name_parts[count($name_parts) - 1];
						$firstname = $name_parts[0];
						
						// Format correct : NOM. P (première lettre du prénom)
						$name = strtoupper($lastname) . '. ' . strtoupper(substr($firstname, 0, 1));
						
						$habilitation = '';
						$fonction = '';
						$contrat = '';
						$entreprise = '';

						foreach ($info_parts as $part) {
							if (strpos($part, 'Habilitations:') === 0) {
								$habilitation = substr($part, 14);
							} elseif (strpos($part, 'Fonctions:') === 0) {
								$fonction = substr($part, 10);
							} elseif (strpos($part, 'Contrat:') === 0) {
								$contrat = substr($part, 9);
							} elseif (strpos($part, 'Entreprise:') === 0) {
								$entreprise = substr($part, 11);
							}
						}

						// Traitement des habilitations et fonctions
						$max_hab_width = $col_widths['habilitation'] - 4;
						$habilitation_lines = [];
						if (!empty($habilitation)) {
							$habilitation_parts = explode('-', $habilitation);
							$current_line = '';
							$max_elements_per_line = 5; // Plus d'éléments par ligne car plus large

							foreach ($habilitation_parts as $part) {
								$part = trim($part);
								if (empty($part)) continue;
								
								$test_line = $current_line . ($current_line ? '-' : '') . $part;
								
								if ($pdf->GetStringWidth($test_line) > $max_hab_width || 
									(substr_count($current_line, '-') >= ($max_elements_per_line - 1) && !empty($current_line))) {
									
									if ($current_line) {
										$habilitation_lines[] = $current_line;
									}
									$current_line = $part;
								} else {
									$current_line = $test_line;
								}
							}
							
							if ($current_line) {
								$habilitation_lines[] = $current_line;
							}
						}

						$fonction_lines = [];
						if (!empty($fonction)) {
							$fonction_parts = explode('-', $fonction);
							$current_line = '';
							$max_fonc_width = $col_widths['fonction'] - 2;

							foreach ($fonction_parts as $part) {
								$part = trim($part);
								if (empty($part)) continue;
								
								$test_line = $current_line . ($current_line ? '-' : '') . $part;
								
								if ($pdf->GetStringWidth($test_line) > $max_fonc_width) {
									if ($current_line) {
										$fonction_lines[] = $current_line;
									}
									$current_line = $part;
								} else {
									$current_line = $test_line;
								}
							}
							
							if ($current_line) {
								$fonction_lines[] = $current_line;
							}
						}

						// Calculer le nombre maximum de lignes pour cet utilisateur
						$max_lines_for_user = max(1, count($habilitation_lines), count($fonction_lines));

						// Afficher le nom (centré dans sa colonne)
						$name_width = $pdf->GetStringWidth($name);
						$centered_nom_x = $col_positions['nom1'] + ($col_widths['nom'] - $name_width) / 2;
						$pdf->Text($centered_nom_x, $actual_y_end, $name);

						// Afficher l'entreprise (centrée dans sa colonne)
						if (!empty($entreprise)) {
							$entreprise_width = $pdf->GetStringWidth($entreprise);
							$centered_entr_x = $col_positions['entr1'] + ($col_widths['entreprise'] - $entreprise_width) / 2;
							$pdf->Text($centered_entr_x, $actual_y_end, $entreprise);
						}

						// Afficher les habilitations sur plusieurs lignes (centrées dans leur colonne)
						$temp_y = $actual_y_end;
						foreach ($habilitation_lines as $line) {
							$line_width = $pdf->GetStringWidth($line);
							$centered_hab_x = $col_positions['hab1'] + ($col_widths['habilitation'] - $line_width) / 2;
							$pdf->Text($centered_hab_x, $temp_y, $line);
							$temp_y += 2.0;
						}

						// Afficher les fonctions sur plusieurs lignes (centrées dans leur colonne)
						$temp_y = $actual_y_end;
						foreach ($fonction_lines as $line) {
							$line_width = $pdf->GetStringWidth($line);
							$centered_fonc_x = $col_positions['fonc1'] + ($col_widths['fonction'] - $line_width) / 2;
							$pdf->Text($centered_fonc_x, $temp_y, $line);
							$temp_y += 2.0;
						}

						// Afficher le contrat (centré dans sa colonne)
						$contrat_display = $contrat;
						$max_cont_width = $col_widths['contrat'] - 2;
						while ($pdf->GetStringWidth($contrat_display) > $max_cont_width && strlen($contrat_display) > 0) {
							$contrat_display = substr($contrat_display, 0, -1);
						}
						$contrat_width = $pdf->GetStringWidth($contrat_display);
						$centered_cont_x = $col_positions['cont1'] + ($col_widths['contrat'] - $contrat_width) / 2;
						$pdf->Text($centered_cont_x, $actual_y_end, $contrat_display);

						// Passer à la ligne suivante
						$actual_y_end += ($max_lines_for_user * 2.0) + 3;

						// Ligne de séparation horizontale entre les utilisateurs
						$pdf->SetDrawColor(230, 230, 230);
						$pdf->Line($current_x + 2, $actual_y_end - 2, $current_x + $card_width - 2, $actual_y_end - 2);
					}

					// Contour de la liste des sous-traitants
					$pdf->SetDrawColor(0, 0, 0);
					$pdf->Line($current_x, $current_y + 12, $current_x, $actual_y_end);
					$pdf->Line($current_x + $card_width, $current_y + 12, $current_x + $card_width, $actual_y_end);
					$pdf->Line($current_x, $actual_y_end, $current_x + $card_width, $actual_y_end);

					$current_y = $actual_y_end + 3;
				}

//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

					// Calculer l'espace disponible pour les signatures RÉDUIT
					$signature_height = 18; // Réduit de 20 à 18
					$signature_spacing = 2; // Réduit de 3 à 2
					$signature_margin = 2; // Réduit de 3 à 2
					$min_space_needed = $signature_height + $signature_spacing + $signature_margin + 8; // Réduit de 10 à 8

					// Forcer la position Y pour les signatures (plus haut)
					$signature_y = $this->page_hauteur - 15 - $signature_height - 15; // Réduit les marges

					// Vérifier si les signatures se superposent avec le contenu
					dol_syslog("DEBUG: current_y=" . $current_y . ", signature_y=" . $signature_y, LOG_DEBUG);
					if ($current_y > $signature_y - 8) { // Réduit de 10 à 8
						// Ajouter une nouvelle page
						dol_syslog("DEBUG: Ajout d'une nouvelle page pour les signatures", LOG_DEBUG);
						$pdf->AddPage();
						$current_y = $this->_pagehead($pdf, $object, $outputlangs);
						$this->_pagefoot($pdf, $object, $outputlangs);
						// Recalculer signature_y pour la nouvelle page
						$signature_y = $this->page_hauteur - 15 - $signature_height - 15;
					} else {
						dol_syslog("DEBUG: Signatures affichées sur la page actuelle", LOG_DEBUG);
					}

					// Positionner le curseur pour les signatures
					$pdf->SetY($signature_y);

					// ZONES DE SIGNATURE
					$signature_width = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 20) / 2; // Largeur de chaque zone
					$signature_margin = 20; // Marge entre les zones

					// Zone Rédaction
					$redaction_x = $this->marge_gauche;
					$redaction_y = $signature_y;

					// Titre Rédaction (centré au-dessus de la ligne)
					$pdf->SetFont('', '', 10);
					$title_width = $pdf->GetStringWidth("Rédaction");
					$pdf->Text($redaction_x + ($signature_width - $title_width) / 2, $redaction_y - 8, "Rédaction");
					
					// Ajouter (*) à côté du titre Rédaction
					$pdf->SetFont('', '', 6);
					$pdf->SetTextColor(128, 128, 128); // Gris
					$pdf->Text($redaction_x + ($signature_width - $title_width) / 2 + $title_width + 2, $redaction_y - 8, "(*)");
					$pdf->SetTextColor(0, 0, 0); // Retour à la couleur noire

					// Récupérer les informations de création de l'OT
					$sql_creator = "SELECT u.firstname, u.lastname, o.date_creation 
									FROM " . MAIN_DB_PREFIX . "ot_ot o 
									JOIN " . MAIN_DB_PREFIX . "user u ON o.fk_user_creat = u.rowid 
									WHERE o.rowid = " . $object->id;
					$resql_creator = $db->query($sql_creator);
					$creator_info = $db->fetch_object($resql_creator);

					// Formater le nom du créateur (première lettre du prénom + nom)
					$creator_name = "";
					$creation_date = "";
					if ($creator_info) {
						$firstname = $creator_info->firstname;
						$lastname = $creator_info->lastname;
						$creator_name = substr($firstname, 0, 1) . "." . $lastname;
						
						// Formater la date de création
						$date_creation = new DateTime($creator_info->date_creation);
						$creation_date = $date_creation->format('d/m/Y');
					}

					// Dessiner le contour de la zone Rédaction
					$pdf->SetDrawColor(0, 0, 0);
					$pdf->Line($redaction_x, $redaction_y, $redaction_x + $signature_width, $redaction_y); // Ligne du haut
					$pdf->Line($redaction_x, $redaction_y + $signature_height, $redaction_x + $signature_width, $redaction_y + $signature_height); // Ligne du bas

					// Lignes pour les informations
					$pdf->SetFont('', '', 9);
					$pdf->Text($redaction_x + 5, $redaction_y + 3, "Nom :");
					$pdf->SetFont('', 'B', 9);
					$pdf->Text($redaction_x + 25, $redaction_y + 3, $creator_name);
					$pdf->SetFont('', '', 9);
					$pdf->Text($redaction_x + 5, $redaction_y + 8, "Date :");
					$pdf->SetFont('', 'B', 9);
					$pdf->Text($redaction_x + 25, $redaction_y + 8, $creation_date);
					$pdf->SetFont('', '', 9);
					$pdf->Text($redaction_x + 5, $redaction_y + 13, "Visa :");

					// Zone Validation RD
					$validation_x = $redaction_x + $signature_width + $signature_margin;
					$validation_y = $redaction_y;

					// Titre Validation RD (centré au-dessus de la ligne)
					$pdf->SetFont('', '', 10);
					$title_width = $pdf->GetStringWidth("Validation RD");
					$pdf->Text($validation_x + ($signature_width - $title_width) / 2, $validation_y - 8, "Validation RD");

					// Texte en gris et petite taille
					$pdf->SetFont('', '', 6);
					$pdf->SetTextColor(128, 128, 128); // Gris
					$note_text = "(si travaux en ZC et personnel intérimaire ou CDD)";
					$note_width = $pdf->GetStringWidth($note_text);
					$pdf->Text($validation_x + ($signature_width - $note_width) / 2, $validation_y - 4, $note_text);
					$pdf->SetTextColor(0, 0, 0); // Retour à la couleur noire

					// Dessiner le contour de la zone Validation RD
					$pdf->SetDrawColor(0, 0, 0);
					$pdf->Line($validation_x, $validation_y, $validation_x + $signature_width, $validation_y); // Ligne du haut
					$pdf->Line($validation_x, $validation_y + $signature_height, $validation_x + $signature_width, $validation_y + $signature_height); // Ligne du bas

					// Lignes pour les informations
					$pdf->SetFont('', '', 9);
					$pdf->Text($validation_x + 5, $validation_y + 3, "Nom :");
					$pdf->Text($validation_x + 5, $validation_y + 8, "Date :");
					$pdf->Text($validation_x + 5, $validation_y + 13, "Visa :");

					// Ajouter la note en bas
					$pdf->SetFont('', '', 6);
					$pdf->SetTextColor(128, 128, 128); // Gris
					$note_bottom = "(*) Vérifier la bonne transmission de la FOD aux nouveaux intervenants si risque radiologique sur l'affaire";
					$note_bottom_width = $pdf->GetStringWidth($note_bottom);
					$pdf->Text($redaction_x + ($signature_width * 2 + $signature_margin - $note_bottom_width) / 2, $validation_y + $signature_height + 5, $note_bottom);
					$pdf->SetTextColor(0, 0, 0); // Retour à la couleur noire

					// Pied de page
					$this->_pagefoot($pdf, $object, $outputlangs, true);
					
						// AJOUTER LE FILIGRANE SUR TOUTES LES PAGES À LA FIN
						$this->addWatermarkToAllPages($pdf, $object);

					// Fermer le PDF sans forcer de nouvelle page
					$pdf->Close();
					
					// Sauvegarder le fichier
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

					dolChmod($file);

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
	/**
	 *  Return list of active generation modules
	 *
	 *  @param	DoliDB	$db     			Database handler
	 *  @param  integer	$maxfilenamelength  Max length of value to show
	 *   	@param	TCPDF		$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	protected function _pagehead(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf, $langs, $db;

		// Position initiale pour l'en-tête RÉDUITE
		$header_y = 8; // Réduit de 10 à 8
		$header_height = 25; // Réduit de 30 à 25

		// Logo à gauche (plus compact)
		if (!getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO')) {
			if ($this->emetteur->logo) {
				$logodir = $conf->mycompany->dir_output;
				if (!empty($conf->mycompany->multidir_output[$object->entity])) {
					$logodir = $conf->mycompany->multidir_output[$object->entity];
				}
				if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO')) {
					$logo = $logodir.'/logos/thumbs/'.$this->emetteur->logo_small;
				} else {
					$logo = $logodir.'/logos/'.$this->emetteur->logo;
				}
				if (is_readable($logo)) {
					$height = pdf_getHeightForLogo($logo);
					$height = $height * 0.6; // Réduit de 0.7 à 0.6
					$logo_x = $this->marge_gauche + 8; // Réduit de 10 à 8
					$pdf->Image($logo, $logo_x, $header_y + 6, 0, $height); // Réduit de 8 à 6
				} else {
					$pdf->SetTextColor(200, 0, 0);
					$pdf->SetFont('', 'B', $default_font_size - 2);
					$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
					$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
				}
			} else {
				$text = $this->emetteur->name;
				$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, $ltrdirection);
			}
		}

		// Informations centrales (plus compactes)
		$pdf->SetFont('helvetica', '', 7); // Réduit de 8 à 7
		$center_x = $this->page_largeur / 2;

		// Récupérer les informations du projet et de la société
		$sql_project = "SELECT p.title, p.ref as project_ref, s.nom as site_name
			FROM " . MAIN_DB_PREFIX . "projet p
			LEFT JOIN " . MAIN_DB_PREFIX . "societe s ON p.fk_soc = s.rowid
			WHERE p.rowid = " . $object->fk_project;
		
		$resql_project = $db->query($sql_project);
		if (!$resql_project) {
			dol_syslog("Erreur SQL Project: " . $db->lasterror());
			$site = "Site non trouvé";
			$project_label = "Projet non trouvé";
			$project_ref = "";
		} else {
			$project_info = $db->fetch_object($resql_project);
			if (!$project_info) {
				dol_syslog("Aucun projet trouvé pour fk_project = " . $object->fk_project);
				$site = "Site non trouvé";
				$project_label = "Projet non trouvé";
				$project_ref = "";
			} else {
				$site = $project_info->site_name ? $project_info->site_name : "Site non défini";
				$project_label = $project_info->title ? $project_info->title : "Projet non défini";
				$project_ref = $project_info->project_ref ? $project_info->project_ref : "";
			}
		}
		
		// Site d'intervention
		$pdf->SetFont('helvetica', 'B', 7); // Réduit de 8 à 7
		$site_width = $pdf->GetStringWidth($site);
		$pdf->Text($center_x - ($site_width / 2), $header_y + 4, $site); // Réduit de 5 à 4

		// Libellé du projet
		$pdf->SetFont('helvetica', '', 7); // Réduit de 8 à 7
		$project_width = $pdf->GetStringWidth($project_label);
		$pdf->Text($center_x - ($project_width / 2), $header_y + 9, $project_label); // Réduit de 12 à 9

		// "Organigramme d'affaire"
		$pdf->SetFont('helvetica', 'B', 7); // Réduit de 8 à 7
		$title = "Organigramme d'affaire";
		$title_width = $pdf->GetStringWidth($title);
		$pdf->Text($center_x - ($title_width / 2), $header_y + 14, $title); // Réduit de 19 à 14

		// Informations à droite (plus compactes)
		$pdf->SetFont('helvetica', '', 6); // Réduit de 7 à 6
		$right_x = $this->page_largeur - $this->marge_droite - 35; // Réduit de 40 à 35
		
		// Référence OT
		$pdf->Text($right_x, $header_y + 4, "Réf. OT : "); // Réduit de 5 à 4
		$pdf->SetFont('helvetica', 'B', 6); // Réduit de 7 à 6
		$pdf->Text($right_x + 18, $header_y + 4, $object->ref); // Réduit de 20 à 18
		
		// Indice
		$pdf->SetFont('helvetica', '', 6); // Réduit de 7 à 6
		$pdf->Text($right_x, $header_y + 8, "Indice : "); // Réduit de 10 à 8
		$pdf->SetFont('helvetica', 'B', 6); // Réduit de 7 à 6
		$pdf->Text($right_x + 18, $header_y + 8, $object->indice); // Réduit de 20 à 18
		
		// Numéro d'affaire
		$pdf->SetFont('helvetica', '', 6); // Réduit de 7 à 6
		$pdf->Text($right_x, $header_y + 12, "Affaire : "); // Réduit de 15 à 12
		$pdf->SetFont('helvetica', 'B', 6); // Réduit de 7 à 6
		$pdf->Text($right_x + 18, $header_y + 12, $project_ref); // Réduit de 20 à 18

		// Numéro de page
		$pdf->SetFont('helvetica', '', 6); // Réduit de 7 à 6
		$pdf->Text($right_x, $header_y + 16, "Page : "); // Réduit de 20 à 16
		$pdf->SetFont('helvetica', 'B', 6); // Réduit de 7 à 6
		$pdf->Text($right_x + 18, $header_y + 16, $pdf->getPage() . " / " . $pdf->getAliasNbPages()); // Réduit de 20 à 18

		// Ligne noire horizontale
		$pdf->SetDrawColor(0, 0, 0);
		$pdf->Line($this->marge_gauche, $header_y + 22, $this->page_largeur - $this->marge_droite, $header_y + 22); // Réduit de 30 à 22

		return $header_y + 25; // Réduit de 35 à 25
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	TCPDF		$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0, $is_last_page = false)
	{
		global $conf, $user, $langs;
		
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		
		// Si on est sur la dernière page
		if ($is_last_page) {
			// Calculer l'espace nécessaire pour les signatures
			$signature_height = 30;
			$total_signature_height = $signature_height * $this->page_signatures;
			
			// Si l'espace restant est insuffisant
			if ($this->marge_basse - $total_signature_height < 20) {
				$pdf->AddPage();
				$this->page_signatures = 0;
			}
		}
		
		// Afficher les signatures
		if ($this->page_signatures > 0) {
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor(0, 0, 0);
			
			$signature_y = $this->marge_basse - ($this->page_signatures * 30);
			$pdf->SetY($signature_y);
			
			for ($i = 0; $i < $this->page_signatures; $i++) {
				$pdf->SetY($signature_y + ($i * 30));
				$pdf->Cell(0, 5, 'Signature', 0, 1, 'C');
			}
		}
		
		// Si c'est la dernière page et qu'il n'y a plus de signatures
		if ($is_last_page && $this->page_signatures == 0) {
			$pdf->SetY($this->marge_basse);
			// Ne pas appeler lastPage() pour éviter la page blanche
		}
		
		return $pdf;
	}

	/**
	 *  Define Array Column Field
	 *
	 *  @param	object			$object    		common object
	 *  @param	Translate		$outputlangs    langs
	 *  @param	int			   $hidedetails		Do not show line details
	 *  @param	int			   $hidedesc		Do not show desc
	 *  @param	int			   $hideref			Do not show ref
	 *  @return	void
	 */
	public function defineColumnField($object, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $conf, $hookmanager;

		// Default field style for content
		$this->defaultContentsFieldsStyle = array(
			'align' => 'R', // R,C,L
			'padding' => array(1, 0.5, 1, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		);

		// Default field style for content
		$this->defaultTitlesFieldsStyle = array(
			'align' => 'C', // R,C,L
			'padding' => array(0.5, 0, 0.5, 0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		);

		$rank = 0; // do not use negative rank
		$this->cols['desc'] = array(
			'rank' => $rank,
			'width' => false, // only for desc
			'status' => true,
			'title' => array(
				'textkey' => 'Designation', // use lang key is usefull in somme case with module
				'align' => 'L',
				'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'content' => array(
				'align' => 'L',
				'padding' => array(1, 0.5, 1, 1.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
		);

		// PHOTO
		$rank = $rank + 10;
		$this->cols['photo'] = array(
			'rank' => $rank,
			'width' => (!getDolGlobalInt('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH') ? 20 : getDolGlobalInt('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH')), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Photo',
				'label' => ' '
			),
			'content' => array(
				'padding' => array(0, 0, 0, 0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
		);

		if (!getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT') && !getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN')) {
			$this->cols['vat']['status'] = true;
		}

		$rank = $rank + 10;
		$this->cols['subprice'] = array(
			'rank' => $rank,
			'width' => 19, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'PriceUHT'
			),
			'border-left' => true, // add left line separator
		);

		$rank = $rank + 10;
		$this->cols['qty'] = array(
			'rank' => $rank,
			'width' => 16, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'Qty'
			),
			'border-left' => true, // add left line separator
		);

		$rank = $rank + 10;
		$this->cols['progress'] = array(
			'rank' => $rank,
			'width' => 19, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Progress'
			),
			'border-left' => true, // add left line separator
		);

		if ($this->situationinvoice) {
			$this->cols['progress']['status'] = true;
		}

		$rank = $rank + 10;
		$this->cols['unit'] = array(
			'rank' => $rank,
			'width' => 11, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Unit'
			),
			'border-left' => true, // add left line separator
		);
		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
			$this->cols['unit']['status'] = true;
		}

		$rank = $rank + 10;
		$this->cols['discount'] = array(
			'rank' => $rank,
			'width' => 13, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'ReductionShort'
			),
			'border-left' => true, // add left line separator
		);
		if ($this->atleastonediscount) {
			$this->cols['discount']['status'] = true;
		}

		$rank = $rank + 1000; // add a big offset to be sure is the last col because default extrafield rank is 100
		$this->cols['totalexcltax'] = array(
			'rank' => $rank,
			'width' => 26, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'TotalHT'
			),
			'border-left' => true, // add left line separator
		);

		// Add extrafields cols
		if (!empty($object->lines)) {
			$line = reset($object->lines);
			$this->defineColumnExtrafield($line, $outputlangs, $hidedetails);
		}

		$parameters = array(
			'object' => $object,
			'outputlangs' => $outputlangs,
			'hidedetails' => $hidedetails,
			'hidedesc' => $hidedesc,
			'hideref' => $hideref
		);

		$reshook = $hookmanager->executeHooks('defineColumnField', $parameters, $this); // Note that $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		} elseif (empty($reshook)) {
			$this->cols = array_replace($this->cols, $hookmanager->resArray); // array_replace is used to preserve keys
		} else {
			$this->cols = $hookmanager->resArray;
		}
	}

    /**
     * Récupère les fonctions d'un utilisateur pour un projet
     */
    private function getFonctions($userId, $projectId, $db) {
        // Mapping des labels complets vers les raccourcis
        $fonction_mapping = array(
            "Responsable d'Affaire" => "RA",
            "Responsable de Site" => "RS", 
            "Responsable de Suivi d'Intervention" => "RSI",
            "Responsable d'Équipes" => "RE",
            "Responsable d'Intervention" => "RI",
            "Chargé de Travaux" => "CdT",
            "Contrôleur Technique" => "CT",
            "Vérificateur" => "V",
            "Intervenant" => "INT",
            "Responsable du Suivi Radiologique" => "RSR",
            "Personne Techniquement Compétente" => "PTC",
            "Primo-Intervenant" => "PI",
            "Tuteur Primo-Intervenant" => "TPI",
            "Personnel en Compagnonnage" => "CO",
            "Sauveteur Secouriste du Travail" => "SST",
            // Ajoutez d'autres mappings selon vos besoins
        );

        $fonctions = [];

        $sql = "SELECT cf.label 
                FROM " . MAIN_DB_PREFIX . "element_contact_fonction ecf
                JOIN " . MAIN_DB_PREFIX . "contact_fonction cf ON ecf.function_id = cf.rowid
                WHERE ecf.contact_id = " . intval($userId) . "
                AND ecf.element_id = " . intval($projectId);

        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $label = $obj->label;
                
                // Vérifier si on a un mapping défini pour ce label
                if (isset($fonction_mapping[$label])) {
                    $fonctions[] = $fonction_mapping[$label];
                } else {
                    // Créer un raccourci automatique pour les nouvelles fonctions
                    $raccourci = $this->createFunctionShortcut($label);
                    $fonctions[] = $raccourci;
                }
            }
        }

        return !empty($fonctions) ? implode("-", $fonctions) : null;
    }

    /**
     * Crée un raccourci automatique pour une fonction non mappée
     */
    private function createFunctionShortcut($label) {
        // Supprimer les mots courants et créer un raccourci
        $mots_a_ignorer = array('de', 'du', 'des', 'le', 'la', 'les', 'et', 'en', 'pour', 'sur', 'avec', 'dans', 'par');
        
        // Diviser le label en mots
        $mots = explode(' ', $label);
        $raccourci = '';
        
        foreach ($mots as $mot) {
            $mot = trim($mot);
            // Ignorer les mots courants et les mots de moins de 3 caractères (sauf s'ils sont importants)
            if (!in_array(strtolower($mot), $mots_a_ignorer) && strlen($mot) > 0) {
                if (strlen($mot) <= 3) {
                    // Garder les mots courts tels quels
                    $raccourci .= strtoupper($mot);
                } else {
                    // Prendre les premières lettres des mots longs
                    $raccourci .= strtoupper(substr($mot, 0, 2));
                }
            }
        }
        
        // Si le raccourci est trop long, le limiter à 6 caractères maximum
        if (strlen($raccourci) > 6) {
            $raccourci = substr($raccourci, 0, 6);
        }
        
        // Si le raccourci est vide ou trop court, utiliser les premières lettres du label original
        if (strlen($raccourci) < 2) {
            $raccourci = strtoupper(substr(str_replace(' ', '', $label), 0, 4));
        }
        
        return $raccourci;
    }

    /**
     * Récupère les habilitations d'un utilisateur
     */
    private function getHabilitations($userId, $db) {
        $habilitationRefs = [];

        $sql = "SELECT fh.ref 
                FROM ".MAIN_DB_PREFIX."formationhabilitation_userhabilitation as fuh 
                JOIN ".MAIN_DB_PREFIX."formationhabilitation_habilitation as fh 
                    ON fuh.fk_habilitation = fh.rowid 
                WHERE fuh.fk_user = ".intval($userId);

        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $habilitationRefs[] = $obj->ref;
            }
        }

        return !empty($habilitationRefs) ? implode("-", $habilitationRefs) : null;
	}

	/**
	 * Vérifie si l'OT est périmé
	 *
	 * @param Object $object Objet OT
	 * @return bool True si périmé, False sinon
	 */
	private function isOTPerime($object)
	{
		// Vérifier si l'OT est au statut "archivé"
		// Utiliser la constante STATUS_ARCHIVED qui vaut 2
		if (isset($object->status) && $object->status == 2) {
			return true;
		}
		
		// Vérifier aussi par un champ spécifique si vous en avez un
		if (isset($object->archived) && $object->archived == 1) {
			return true;
		}
		
		return false;
		
		// Code de test commenté (pour forcer l'affichage sur tous les PDF)
		// return true;
	}

	/**
	 * Ajoute un filigrane "Périmé" sur le PDF
	 *
	 * @param TCPDF $pdf Instance PDF
	 * @param string $text Texte du filigrane
	 * @return void
	 */
	private function addWatermark(&$pdf, $text = 'PÉRIMÉ')
	{
		// Sauvegarder l'état actuel
		$pdf->startTransaction();
		
		// Récupérer les dimensions de la page
		$pageWidth = $pdf->getPageWidth();
		$pageHeight = $pdf->getPageHeight();
		
		// Calculer le centre de la page
		$centerX = $pageWidth / 2;
		$centerY = $pageHeight / 2;
		
		// Configurer la transparence
		$pdf->SetAlpha(0.3); // Transparence à 30%
		
		// Configurer la police pour le filigrane
		$pdf->SetFont('helvetica', 'B', 60);
		$pdf->SetTextColor(255, 0, 0); // Rouge
		
		// Calculer la largeur du texte
		$textWidth = $pdf->GetStringWidth($text);
		
		// Sauvegarder la rotation actuelle
		$pdf->StartTransform();
		
		// Rotation de 45 degrés au centre de la page
		$pdf->Rotate(45, $centerX, $centerY);
		
		// Positionner le texte au centre
		$x = $centerX - ($textWidth / 2);
		$y = $centerY;
		
		// Afficher le texte
		$pdf->Text($x, $y, $text);
		
		// Restaurer la rotation
		$pdf->StopTransform();
		
		// Restaurer la transparence
		$pdf->SetAlpha(1);
		
		// Restaurer les couleurs par défaut
		$pdf->SetTextColor(0, 0, 0);
		
		// Valider la transaction
		$pdf->commitTransaction();
	}

	/**
	 * Ajoute le filigrane sur toutes les pages du PDF
	 *
	 * @param TCPDF $pdf Instance PDF
	 * @param Object $object Objet OT
	 * @return void
	 */
	private function addWatermarkToAllPages(&$pdf, $object)
	{
		 // REMETTRE LA CONDITION - AFFICHER LE FILIGRANE SEULEMENT SI OT ARCHIVÉ
		if (!$this->isOTPerime($object)) {
			return;
		}

		// Sauvegarder la page actuelle
		$currentPage = $pdf->getPage();
		$totalPages = $pdf->getNumPages();
		
		// Parcourir toutes les pages
		for ($pageNum = 1; $pageNum <= $totalPages; $pageNum++) {
			$pdf->setPage($pageNum);
			$this->addWatermark($pdf);
		}
		
		// Retourner à la page courante
		$pdf->setPage($currentPage);
	}
}