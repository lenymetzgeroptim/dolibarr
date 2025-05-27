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

				// Create pdf instance
				$pdf = pdf_getInstanceCustomOt($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance

				// Configuration du PDF
				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false); // Désactiver le header automatique
					$pdf->setPrintFooter(false);
					$pdf->SetMargins(10, 10, 10); // Réduire la marge supérieure
					$pdf->SetAutoPageBreak(TRUE, 15);
				}

				// Passer les objets nécessaires à l'instance PDF
				$pdf->ot_object = $object;
				$pdf->ot_outputlangs = $outputlangs;
				$pdf->ot_parent = $this;

				$heightforinfotot = 50; // Height reserved to output the info and total part and payment part
				$heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + (getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS') ? 12 : 22); // Height reserved to output the footer (value include bottom margin)

				$pdf->Open();
				$pagenb = 0;
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
				$pdf->AddPage();
				$pagenb++;
				$current_y = $this->_pagehead($pdf, $object, $outputlangs);

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
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}

							$posyafter = $tab_top_newpage;
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

				// Position verticale juste après le header
				$current_y = 40; 

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

				// Affichage des commandes concernées
				$current_y += 10; // Augmenté de 5 à 10 pour plus d'espace après le header
				$pdf->SetFont('', '', 10);
				$pdf->SetTextColor(0, 0, 0);
				$pdf->Text($this->marge_gauche, $current_y, "Commande concernée : ");
				$pdf->SetFont('', 'B', 10);
				$pdf->Text($this->marge_gauche + 48, $current_y, $refs_commandes_str);

				// Affichage de la date d'applicabilité
				$current_y += 7; // Réduit de 5 à 7 pour un espacement plus petit entre les champs
				$pdf->SetFont('', '', 10);
				$pdf->Text($this->marge_gauche, $current_y, "Date d'applicabilité de l'OT : ");
				$pdf->SetFont('', 'B', 10);
				$pdf->Text($this->marge_gauche + 48, $current_y, $formattedDateAppli);

				// Laisser un espace plus important avant les cartes principales
				$current_y += 15; // Augmenté de 5 à 15 pour éviter la fusion avec les cartes

				// Récupération des cartes principales (RA, Q3, PCR)
				$sql_cards = "SELECT c.rowid, c.title, c.type 
							FROM " . MAIN_DB_PREFIX . "ot_ot_cellule c 
							WHERE c.ot_id = " . $object->id . " 
							AND c.type = 'cardprincipale' 
							ORDER BY FIELD(c.title, 'RA', 'Q3', 'PCR')";
				$resql_cards = $db->query($sql_cards);

				// AFFICHAGE DES CARTES PRINCIPALES
				if ($resql_cards) {
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
						$sql_user = "SELECT u.firstname, u.lastname, u.office_phone, cd.role 
									FROM " . MAIN_DB_PREFIX . "ot_ot_cellule_donne cd 
									JOIN " . MAIN_DB_PREFIX . "user u ON cd.fk_user = u.rowid 
									WHERE cd.ot_cellule_id = " . $card->rowid;
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
								$pdf->Text($phone_x, $current_y + 19, $user_data->office_phone);
							}

							// Passer à la position suivante
							$start_x += $card_width + $card_margin;
						}
					}

					// Ajouter un espace après les cartes principales
					$current_y += $card_height; // Suppression de l'espace supplémentaire
				}

				// Initialisation des tableaux pour les cartes dans la grille et en dessous de la grille
				$cardsData = [];
				$listeUniqueCards = [];  // Cartes à afficher en dessous de la grille

				while ($row = $db->fetch_object($resql)) {
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
					}

					// Ajouter la carte dans le tableau approprié
					if ($row->type === 'listeunique') {
						$listeUniqueCards[] = $cellData;  // Cartes 'listeunique' à afficher en dessous de la grille
					} else {
						$cardsData[] = $cellData;  // Cartes normales à afficher dans la grille
					}
				}

				// AFFICHAGE DES CARTES NORMALES
				if (!empty($cardsData)) {
					// Vérifier si on a assez d'espace pour afficher au moins une carte
					$min_space_needed = 50; // Hauteur minimale nécessaire pour une carte
					if ($current_y + $min_space_needed > $this->page_hauteur - $this->marge_basse) {
						$pdf->AddPage();
						$current_y = $this->_pagehead($pdf, $object, $outputlangs);
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
						if ($card['x'] > $max_x) $max_x = $card['x'];
						if ($card['y'] > $max_y) $max_y = $card['y'];
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
						
						// Afficher chaque carte de la ligne
						foreach ($cardsOnY as $card) {
							// Vérifier l'espace disponible pour cette carte
							if ($current_y + $card_height > $this->page_hauteur - $this->marge_basse) {
								$pdf->AddPage();
								$current_y = $this->_pagehead($pdf, $object, $outputlangs);
								$current_x = $this->marge_gauche + ($line_width - $total_cards_width) / 2;
							}

							// Déterminer la largeur de la carte
							$card_width = ($card['type'] === 'list') ? $list_card_width : $normal_card_width;
							
							// Calculer la hauteur nécessaire pour le contenu
							$content_height = 15; // Hauteur pour le titre
							
							if (!empty($card['userNames'])) {
								// Vérifier l'espace disponible pour le contenu
								if ($current_y + $content_height > $this->page_hauteur - $this->marge_basse) {
									$pdf->AddPage();
									$current_y = $this->_pagehead($pdf, $object, $outputlangs);
									$current_x = $this->marge_gauche + ($line_width - $total_cards_width) / 2;
								}

								// ... reste du code pour l'affichage des cartes ...
								if ($card['type'] === 'list') {
									// AFFICHAGE EN TABLEAU POUR LES LISTES
									// Définir les colonnes du tableau pour un utilisateur (la moitié de la largeur)
									$is_alone = count($cardsOnY) === 1;
									
									if ($is_alone) {
										// Double colonne si la carte est seule
										$col_widths = array(
											'nom' => ($card_width * 0.25) / 2,
											'habilitation' => ($card_width * 0.35) / 2,
											'fonction' => ($card_width * 0.25) / 2,
											'contrat' => ($card_width * 0.15) / 2
										);
										$users_per_row = 2;
									} else {
										// Colonne simple si la carte partage la ligne
										$col_widths = array(
											'nom' => $card_width * 0.25,
											'habilitation' => $card_width * 0.35,
											'fonction' => $card_width * 0.25,
											'contrat' => $card_width * 0.15
										);
										$users_per_row = 1;
									}
									
									// Calculer la hauteur nécessaire pour le contenu
									$content_height = 15; // Hauteur pour le titre
									$content_height += 10; // Hauteur pour la légende et sa ligne
									
									// Calculer la hauteur nécessaire pour les utilisateurs
									$total_users = count($card['userNames']);
									$total_rows = ceil($total_users / $users_per_row);
									
									foreach (array_chunk($card['userNames'], $users_per_row) as $row_users) {
										$max_lines_in_row = 0;
										
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
											
											// Calculer les lignes pour les fonctions
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
											
											// Calculer les lignes pour les habilitations
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
											
											$max_lines = max(1, count($fonc_lines), count($hab_lines));
											$max_lines_in_row = max($max_lines_in_row, $max_lines);
										}
										
										$content_height += ($max_lines_in_row * 3) + 2; // 3 pixels par ligne + 2 pixels d'espacement
									}
									
									// Ajouter une petite marge en bas
									$content_height += 2;
									
									// Dessiner le contour de la carte
						$pdf->SetDrawColor(0, 0, 0);
						$pdf->SetDrawColor(0, 0, 0);
							// Lignes verticales uniquement (pas de haut, pas de bas)
							$side_line_start_y = $current_y + 12; // Commence après le titre
							$pdf->Line($current_x, $side_line_start_y, $current_x, $current_y + $content_height); // gauche
							$pdf->Line($current_x + $card_width, $side_line_start_y, $current_x + $card_width, $current_y + $content_height); // droite
				
									// Afficher le titre de la liste
									$pdf->SetFont('', 'B', 9); // Réduit de 10 à 9
						$title_width = $pdf->GetStringWidth($card['title']);
									$pdf->Text($current_x + ($card_width - $title_width) / 2, $current_y + 5, $card['title']);
									
									// Afficher la légende
									$legend_y = $current_y + 15;
									$current_x_legend = $current_x + 2;
									
									// Afficher les titres des colonnes
									$pdf->SetFont('', '', 5); // Réduit de 6 à 5
									$pdf->Text($current_x_legend, $legend_y, 'Nom');
									$current_x_legend += $col_widths['nom'];
									
									// Centrer la légende "Habilitations"
									$habilitation_title = 'Habilitation';
									$habilitation_width = $pdf->GetStringWidth($habilitation_title);
									$habilitation_x = $current_x_legend + ($col_widths['habilitation'] - $habilitation_width) / 2;
									$pdf->Text($habilitation_x, $legend_y, $habilitation_title);
									$current_x_legend += $col_widths['habilitation'];
									
									$pdf->Text($current_x_legend, $legend_y, 'Fonction');
									$current_x_legend += $col_widths['fonction'];
									$pdf->Text($current_x_legend, $legend_y, 'Contrat');

									if ($is_alone) {
										// Afficher les titres des colonnes pour le deuxième utilisateur
										$current_x_legend += $col_widths['contrat'];
										$pdf->Text($current_x_legend, $legend_y, 'Nom');
										$current_x_legend += $col_widths['nom'];
										
										// Centrer la légende "Habilitations" pour le deuxième utilisateur
										$habilitation_x = $current_x_legend + ($col_widths['habilitation'] - $habilitation_width) / 2;
										$pdf->Text($habilitation_x, $legend_y, $habilitation_title);
										$current_x_legend += $col_widths['habilitation'];
										
										$pdf->Text($current_x_legend, $legend_y, 'Fonction');
										$current_x_legend += $col_widths['fonction'];
										$pdf->Text($current_x_legend, $legend_y, 'Contrat');
									}

									// Ligne de séparation sous la légende
									$pdf->SetDrawColor(200, 200, 200);
									if ($is_alone) {
										// Dessiner deux segments de ligne avec un espace au milieu
										$mid_x = $current_x + ($card_width / 2);
										$gap = 10; // Largeur de l'espace
										$pdf->Line($current_x + 2, $legend_y + 4, $mid_x - ($gap/2), $legend_y + 4);
										$pdf->Line($mid_x + ($gap/2), $legend_y + 4, $current_x + $card_width - 2, $legend_y + 4);
									} else {
										$pdf->Line($current_x + 2, $legend_y + 4, $current_x + $card_width - 2, $legend_y + 4);
									}
									
									// Passer à la ligne suivante pour les données
									$y_offset = $legend_y + 7;
									$pdf->SetFont('', '', 7); // Réduit de 8 à 7
									
									// Afficher les utilisateurs par paires
									foreach (array_chunk($card['userNames'], $users_per_row) as $row_users) {
										$max_lines_in_row = 0;
										$current_x_user = $current_x + 2;
										
										foreach ($row_users as $userInfo) {
											// Séparer les informations
											$info_parts = explode(' - ', $userInfo);
											$name_parts = explode(' ', $info_parts[0]);
											$lastname = $name_parts[count($name_parts) - 1];
											$firstname = $name_parts[0];
											$name = $lastname . ' ' . substr($firstname, 0, 1) . '.';
											
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
											
											// Afficher le nom
											$pdf->Text($current_x_user, $y_offset, $name);
											$current_x_user += $col_widths['nom'];
											
											// Afficher les habilitations
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
											
											// Afficher les fonctions
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
											
											// Afficher le contrat
											$pdf->Text($current_x_user + $col_widths['habilitation'] + $col_widths['fonction'], $y_offset, $contrat);
											
											// Calculer le nombre maximum de lignes pour cette entrée
											$max_lines = max(1, count($fonc_lines), count($hab_lines));
											$max_lines_in_row = max($max_lines_in_row, $max_lines);
											
											// Afficher les habilitations sur plusieurs lignes si nécessaire
											$temp_y = $y_offset;
											foreach ($hab_lines as $line) {
												$pdf->Text($current_x_user, $temp_y, $line);
												$temp_y += 3;
											}
											
											// Afficher les fonctions sur plusieurs lignes si nécessaire
											$temp_y = $y_offset;
											foreach ($fonc_lines as $line) {
												$pdf->Text($current_x_user + $col_widths['habilitation'], $temp_y, $line);
												$temp_y += 3;
											}
											
											$current_x_user += $col_widths['habilitation'] + $col_widths['fonction'] + $col_widths['contrat'];
										}
										
										// Dessiner la ligne verticale de séparation après avoir calculé la hauteur
										if ($is_alone && count($row_users) === 2) {
											$separator_x = $current_x + ($card_width / 2);
											$pdf->SetDrawColor(200, 200, 200);
											$pdf->Line($separator_x, $legend_y + 4, $separator_x, $y_offset + ($max_lines_in_row * 3) + 2);
										}
										
										// Mettre à jour la position Y pour la prochaine ligne
										$y_offset += ($max_lines_in_row * 3) + 2;
										
										// Ajouter une ligne de séparation entre les lignes d'utilisateurs
										if ($y_offset < $current_y + $content_height - 2) {
											$pdf->SetDrawColor(200, 200, 200);
											if ($is_alone) {
												// Dessiner deux segments de ligne avec un espace au milieu
												$mid_x = $current_x + ($card_width / 2);
												$gap = 10; // Largeur de l'espace
												$pdf->Line($current_x + 2, $y_offset - 1, $mid_x - ($gap/2), $y_offset - 1);
												$pdf->Line($mid_x + ($gap/2), $y_offset - 1, $current_x + $card_width - 2, $y_offset - 1);
											} else {
												// Ligne continue si ce n'est pas une double colonne
												$pdf->Line($current_x + 2, $y_offset - 1, $current_x + $card_width - 2, $y_offset - 1);
											}
										}
									}
								} else {
									// AFFICHAGE STANDARD POUR LES CARTES
									$y_offset = $current_y + 12; // Augmenter l'espace initial de 8 à 12
									$pdf->SetFont('', '', 8);
									
									// Calculer la hauteur nécessaire pour le contenu
									$content_height = 15; // Hauteur pour le titre
									
									foreach ($card['userNames'] as $userInfo) {
										// Séparer les informations
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
										
										// Calculer la hauteur pour les habilitations
										$hab_lines = 1; // Au moins une ligne
										if (!empty($habilitations)) {
											$max_width = $card_width - 30;
											$words = explode('-', $habilitations);
											$current_line = '';
											$hab_lines = 0;
											
											foreach ($words as $word) {
												$word = trim($word);
												$test_line = $current_line . ($current_line ? '-' : '') . $word;
												
												if ($pdf->GetStringWidth($test_line) < $max_width) {
													$current_line = $test_line;
												} else {
													if ($current_line) {
														$hab_lines++;
													}
													$current_line = $word;
												}
											}
											if ($current_line) {
												$hab_lines++;
											}
										}
										
										// Ajouter la hauteur nécessaire pour cet utilisateur
										$content_height += 5; // Espace pour le nom
										$content_height += ($hab_lines * 4); // 4 pixels par ligne d'habilitation
										if (!empty($contrat)) {
											$content_height += 5; // Espace pour le contrat
										}
										$content_height += 3; // Espacement entre les utilisateurs
									}
									
									// Ajouter une marge en bas de la carte
									$content_height += 2;
									
									// Vérifier si la carte dépasse la page
									if ($current_y + $content_height > $this->page_hauteur - $this->marge_basse) {
										$pdf->AddPage();
										$current_y = $this->_pagehead($pdf, $object, $outputlangs);
										$pdf->SetY($current_y);
									}
									
									// Dessiner les lignes de la carte au lieu du rectangle
									$pdf->SetDrawColor(0, 0, 0);
									// Ligne du bas
									$pdf->Line($current_x, $current_y + $content_height, $current_x + $card_width, $current_y + $content_height);
									// Lignes latérales (qui s'arrêtent au niveau du nom/prénom)
									$side_line_start_y = $current_y + 12; // Commence après le titre
									$pdf->Line($current_x, $side_line_start_y, $current_x, $current_y + $content_height); // Ligne gauche
									$pdf->Line($current_x + $card_width, $side_line_start_y, $current_x + $card_width, $current_y + $content_height); // Ligne droite

									// Réinitialiser la position Y pour le contenu
									$y_offset = $current_y + 12; // Augmenter l'espace initial de 8 à 12
									
									// Afficher le titre
					$pdf->SetFont('', 'B', 10);
					$pdf->SetTextColor(0, 0, 0);
					$title_width = $pdf->GetStringWidth($card['title']);
									$center_title_x = $current_x + ($card_width - $title_width) / 2 - $shift_left;
									$pdf->Text($center_title_x, $current_y + 5, $card['title']);
									
									// Afficher le contenu
									$pdf->SetFont('', '', 8);
									
									foreach ($card['userNames'] as $userInfo) {
										// Séparer les informations
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
										
										// Affichage avec labels et gestion des retours à la ligne
										$pdf->SetFont('', '', 6);
										$pdf->Text($current_x + 2, $y_offset, 'Nom/Prénom :');
										$pdf->SetFont('', '', 8);
										$pdf->Text($current_x + 20, $y_offset, $name);
										$y_offset += 5; // Espace entre nom et habilitation
										
										if (!empty($habilitations)) {
											$pdf->SetFont('', '', 6);
											$pdf->Text($current_x + 2, $y_offset, 'Habilitation :');
											$pdf->SetFont('', '', 8);
											
											// Gestion des retours à la ligne pour les habilitations
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
														$pdf->Text($current_x + 20, $y_offset, $current_line);
														$y_offset += 4; // Espacement normal pour les retours à la ligne des habilitations
													}
													$current_line = $word;
												}
											}
											
											if ($current_line) {
												$pdf->Text($current_x + 20, $y_offset, $current_line);
												$y_offset += 5; // Espace entre habilitation et contrat
											}
										}
										
										if (!empty($contrat)) {
											$pdf->SetFont('', '', 6);
											$pdf->Text($current_x + 2, $y_offset, 'Contrat :');
											$pdf->SetFont('', 'B', 8); // Mettre en gras les données de contrat
											$pdf->Text($current_x + 20, $y_offset, $contrat);
											$y_offset += 5; // Espace après le contrat
										}
										
										$y_offset += 2; // Espace entre les utilisateurs
									}
								}
							}
							
							// Ajouter une marge en bas de la carte
							$content_height += 2; // Réduire la marge en bas de la carte de 5 à 2
				
							// Vérifier si la carte dépasse la page en Y
							if ($current_y + $content_height > $this->page_hauteur - $this->marge_basse) {
								$pdf->AddPage();
								$current_y = $this->_pagehead($pdf, $object, $outputlangs);
								$pdf->SetY($current_y);
							}
				
							// Passer à la position suivante
							$current_x += $card_width + $card_margin;
						}
						
						// Passer à la ligne suivante
						$current_y += $content_height + 2; // Réduit de 5 à 2
					}
				}

				// AFFICHAGE DES LISTES
				if (!empty($listeUniqueCards)) {
					$current_y += 0; // Suppression de l'espace supplémentaire

					foreach ($listeUniqueCards as $card) {
						// Calculer la largeur de la carte (largeur maximale)
						$card_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
						
						// Définir les colonnes du tableau pour un utilisateur (la moitié de la largeur)
						$col_widths = array(
							'nom' => ($card_width * 0.25) / 2,
							'habilitation' => ($card_width * 0.35) / 2,
							'fonction' => ($card_width * 0.25) / 2,
							'contrat' => ($card_width * 0.15) / 2
						);
						
						// Vérifier si le tableau dépasse la page
						if ($current_y + 50 > $this->page_hauteur - $this->marge_basse) {
							$pdf->AddPage();
							$current_y = $this->_pagehead($pdf, $object, $outputlangs);
							$pdf->SetY($current_y);
						}
						
						// Calculer la position X pour centrer la carte
						$card_x = $this->marge_gauche;
						
						// Afficher le titre de la liste avec plus d'espace
						$pdf->SetFont('', 'B', 9);
						$title_width = $pdf->GetStringWidth($card['title']);
						$title_y = $current_y + 5; // Réduire l'espace au-dessus du titre
						$pdf->Text($card_x + ($card_width - $title_width) / 2, $title_y, $card['title']); // Centrer le titre
						
						// Afficher la légende
						$legend_y = $title_y + 10;
						$current_x_legend = $card_x + 2;
						
						// Afficher les titres des colonnes
						$pdf->SetFont('', '', 5);
						$pdf->Text($current_x_legend, $legend_y, 'Nom');
						$current_x_legend += $col_widths['nom'];
						
						// Centrer la légende "Habilitations"
						$habilitation_title = 'Habilitation';
						$habilitation_width = $pdf->GetStringWidth($habilitation_title);
						$habilitation_x = $current_x_legend + ($col_widths['habilitation'] - $habilitation_width) / 2;
						$pdf->Text($habilitation_x, $legend_y, $habilitation_title);
						$current_x_legend += $col_widths['habilitation'];
						
						$pdf->Text($current_x_legend, $legend_y, 'Fonction');
						$current_x_legend += $col_widths['fonction'];
						$pdf->Text($current_x_legend, $legend_y, 'Contrat');
						
						// Afficher les titres des colonnes pour le deuxième utilisateur
						$current_x_legend += $col_widths['contrat'];
						$pdf->Text($current_x_legend, $legend_y, 'Nom');
						$current_x_legend += $col_widths['nom'];
						
						// Centrer la légende "Habilitations" pour le deuxième utilisateur
						$habilitation_x = $current_x_legend + ($col_widths['habilitation'] - $habilitation_width) / 2;
						$pdf->Text($habilitation_x, $legend_y, $habilitation_title);
						$current_x_legend += $col_widths['habilitation'];
						
						$pdf->Text($current_x_legend, $legend_y, 'Fonction');
						$current_x_legend += $col_widths['fonction'];
						$pdf->Text($current_x_legend, $legend_y, 'Contrat');

						// Ligne de séparation sous la légende
						$pdf->SetDrawColor(200, 200, 200);
						// Dessiner deux segments de ligne avec un espace au milieu
						$mid_x = $card_x + ($card_width / 2);
						$gap = 10; // Largeur de l'espace
						$pdf->Line($card_x + 2, $legend_y + 4, $mid_x - ($gap/2), $legend_y + 4);
						$pdf->Line($mid_x + ($gap/2), $legend_y + 4, $card_x + $card_width - 2, $legend_y + 4);
						
						// Passer à la ligne suivante pour les données
						$y_offset = $legend_y + 7;
						$pdf->SetFont('', '', 7);
						
						// Afficher les utilisateurs par paires
						foreach (array_chunk($card['userNames'], 2) as $row_users) {
							$max_lines_in_row = 0;
							$current_x_user = $card_x + 2;
							
							foreach ($row_users as $userInfo) {
								// Séparer les informations
								$info_parts = explode(' - ', $userInfo);
								$name_parts = explode(' ', $info_parts[0]);
								$lastname = $name_parts[count($name_parts) - 1];
								$firstname = $name_parts[0];
								$name = $lastname . ' ' . substr($firstname, 0, 1) . '.';
								
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
								
								// Afficher le nom
								$pdf->Text($current_x_user, $y_offset, $name);
								$current_x_user += $col_widths['nom'];
								
								// Afficher les habilitations
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
								
								// Afficher les fonctions
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
								
								// Afficher le contrat
								$pdf->Text($current_x_user + $col_widths['habilitation'] + $col_widths['fonction'], $y_offset, $contrat);
								
								// Calculer le nombre maximum de lignes pour cette entrée
								$max_lines = max(1, count($fonc_lines), count($hab_lines));
								$max_lines_in_row = max($max_lines_in_row, $max_lines);
								
								// Afficher les habilitations sur plusieurs lignes si nécessaire
								$temp_y = $y_offset;
								foreach ($hab_lines as $line) {
									$pdf->Text($current_x_user, $temp_y, $line);
									$temp_y += 3;
								}
								
								// Afficher les fonctions sur plusieurs lignes si nécessaire
								$temp_y = $y_offset;
								foreach ($fonc_lines as $line) {
									$pdf->Text($current_x_user + $col_widths['habilitation'], $temp_y, $line);
									$temp_y += 3;
								}
								
								$current_x_user += $col_widths['habilitation'] + $col_widths['fonction'] + $col_widths['contrat'];
							}
							
							// Dessiner la ligne verticale de séparation
							$separator_x = $card_x + ($card_width / 2);
							$pdf->SetDrawColor(200, 200, 200);
							$pdf->Line($separator_x, $legend_y + 4, $separator_x, $y_offset + ($max_lines_in_row * 3) + 2);
							
							// Mettre à jour la position Y pour la prochaine ligne
							$y_offset += ($max_lines_in_row * 3) + 2;
							
							// Ajouter une ligne de séparation entre les lignes d'utilisateurs
							if ($y_offset < $current_y + $content_height - 2) {
								$pdf->SetDrawColor(200, 200, 200);
								// Dessiner deux segments de ligne avec un espace au milieu
								$mid_x = $card_x + ($card_width / 2);
								$gap = 10; // Largeur de l'espace
								$pdf->Line($card_x + 2, $y_offset - 1, $mid_x - ($gap/2), $y_offset - 1);
								$pdf->Line($mid_x + ($gap/2), $y_offset - 1, $card_x + $card_width - 2, $y_offset - 1);
							}
						}
						
						// Dessiner le contour de la carte
						$pdf->SetDrawColor(0, 0, 0);
						$pdf->Line($card_x, $current_y + 12, $card_x, $y_offset + 2); // Ligne gauche
						$pdf->Line($card_x + $card_width, $current_y + 12, $card_x + $card_width, $y_offset + 2); // Ligne droite
						
						$current_y = $y_offset + 2; // Réduit de 5 à 2
					}
				}

				// AFFICHAGE DES SOUS-TRAITANTS
				$current_y += 0; // Suppression de l'espace supplémentaire

				// Récupérer les sous-traitants
				$sql_sous_traitants = "SELECT 
					u.firstname, u.lastname,
					s.nom as entreprise,
					st.fonction,
					st.contrat,
					st.habilitation
					FROM " . MAIN_DB_PREFIX . "ot_ot_sous_traitants st
					JOIN " . MAIN_DB_PREFIX . "user u ON st.fk_socpeople = u.rowid
					JOIN " . MAIN_DB_PREFIX . "societe s ON st.fk_societe = s.rowid
					WHERE st.ot_id = " . $object->id;

				$resql_sous_traitants = $db->query($sql_sous_traitants);
				if ($resql_sous_traitants && $db->num_rows($resql_sous_traitants) > 0) {
					// Calculer la largeur de la carte (largeur maximale)
					$card_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
					
					// Définir les colonnes du tableau
					$col_widths = array(
						'nom' => $card_width * 0.20,
						'entreprise' => $card_width * 0.20,
						'fonction' => $card_width * 0.20,
						'contrat' => $card_width * 0.15,
						'habilitation' => $card_width * 0.25
					);

					// Vérifier si le tableau dépasse la page
					if ($current_y + 50 > $this->page_hauteur - $this->marge_basse) {
						$pdf->AddPage();
						$current_y = $this->_pagehead($pdf, $object, $outputlangs);
						$pdf->SetY($current_y);
					}

					// Afficher le titre
					$pdf->SetFont('', 'B', 9);
					$title = "Sous-traitants";
					$title_width = $pdf->GetStringWidth($title);
					$title_y = $current_y + 5;
					$pdf->Text($this->marge_gauche + ($card_width - $title_width) / 2, $title_y, $title);

					// Afficher la légende
					$legend_y = $title_y + 10;
					$current_x_legend = $this->marge_gauche + 2;

					// Afficher les titres des colonnes
					$pdf->SetFont('', '', 5);
					$pdf->Text($current_x_legend, $legend_y, 'Nom/Prénom');
					$current_x_legend += $col_widths['nom'];

					$pdf->Text($current_x_legend, $legend_y, 'Entreprise');
					$current_x_legend += $col_widths['entreprise'];

					$pdf->Text($current_x_legend, $legend_y, 'Fonction');
					$current_x_legend += $col_widths['fonction'];

					$pdf->Text($current_x_legend, $legend_y, 'Contrat');
					$current_x_legend += $col_widths['contrat'];

					$pdf->Text($current_x_legend, $legend_y, 'Habilitations');

					// Ligne de séparation sous la légende
					$pdf->SetDrawColor(200, 200, 200);
					$pdf->Line($this->marge_gauche + 2, $legend_y + 4, $this->marge_gauche + $card_width - 2, $legend_y + 4);

					// Passer à la ligne suivante pour les données
					$y_offset = $legend_y + 7;
					$pdf->SetFont('', '', 7);

					// Afficher les sous-traitants
					while ($sous_traitant = $db->fetch_object($resql_sous_traitants)) {
						$current_x = $this->marge_gauche + 2;

						// Nom/Prénom
						$name = $sous_traitant->lastname . ' ' . $sous_traitant->firstname;
						$pdf->Text($current_x, $y_offset, $name);
						$current_x += $col_widths['nom'];

						// Entreprise
						$pdf->Text($current_x, $y_offset, $sous_traitant->entreprise);
						$current_x += $col_widths['entreprise'];

						// Fonction
						$pdf->Text($current_x, $y_offset, $sous_traitant->fonction);
						$current_x += $col_widths['fonction'];

						// Contrat
						$pdf->Text($current_x, $y_offset, $sous_traitant->contrat);
						$current_x += $col_widths['contrat'];

						// Habilitations
						$pdf->Text($current_x, $y_offset, $sous_traitant->habilitation);

						// Ligne de séparation entre les sous-traitants
						$y_offset += 7;
						$pdf->SetDrawColor(200, 200, 200);
						$pdf->Line($this->marge_gauche + 2, $y_offset - 1, $this->marge_gauche + $card_width - 2, $y_offset - 1);
					}

					// Dessiner le contour de la carte
					$pdf->SetDrawColor(0, 0, 0);
					// Ligne du bas uniquement
$pdf->SetDrawColor(0, 0, 0);
$pdf->Line($this->marge_gauche, $y_offset + 2, $this->marge_gauche + $card_width, $y_offset + 2); // Ligne du bas
$pdf->Line($this->marge_gauche, $current_y + 12, $this->marge_gauche, $y_offset + 2); // Ligne gauche
$pdf->Line($this->marge_gauche + $card_width, $current_y + 12, $this->marge_gauche + $card_width, $y_offset + 2); // Ligne droite

					$current_y = $y_offset + 2; // Réduit de 5 à 2
				}

				// Calculer la hauteur totale nécessaire pour les signatures
				$signature_height = 35;
				$signature_spacing = 5;
				$signature_margin = 5;

				// Calculer la position Y pour les signatures (en bas de page)
				$signature_y = $this->page_hauteur - $this->marge_basse - $signature_height - $signature_margin - 15; // Ajout de -15 pour remonter les signatures

				// Vérifier si on a assez d'espace pour les signatures sur la page actuelle
				if ($current_y + $signature_height > $signature_y) {
					// Si pas assez d'espace, on ajoute une nouvelle page
					$pdf->AddPage();
					$current_y = $this->_pagehead($pdf, $object, $outputlangs);
					$signature_y = $this->page_hauteur - $this->marge_basse - $signature_height - $signature_margin - 15; // Même ajustement ici
				}

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
				$pdf->Text($redaction_x + 5, $redaction_y + 8, "Nom :");
				$pdf->SetFont('', 'B', 9);
				$pdf->Text($redaction_x + 25, $redaction_y + 8, $creator_name);
				$pdf->SetFont('', '', 9);
				$pdf->Text($redaction_x + 5, $redaction_y + 15, "Date :");
				$pdf->SetFont('', 'B', 9);
				$pdf->Text($redaction_x + 25, $redaction_y + 15, $creation_date);
				$pdf->SetFont('', '', 9);
				$pdf->Text($redaction_x + 5, $redaction_y + 22, "Visa :");

				// Zone Validation RD
				$validation_x = $redaction_x + $signature_width + $signature_margin;
				$validation_y = $signature_y;

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
				$pdf->Text($validation_x + 5, $validation_y + 8, "Nom :");
				$pdf->Text($validation_x + 5, $validation_y + 15, "Date :");
				$pdf->Text($validation_x + 5, $validation_y + 22, "Visa :");

				// Ajouter la note en bas
				$pdf->SetFont('', '', 6);
				$pdf->SetTextColor(128, 128, 128); // Gris
				$note_bottom = "(*) Vérifier la bonne transmission de la FOD aux nouveaux intervenants si risque radiologique sur l'affaire";
				$note_bottom_width = $pdf->GetStringWidth($note_bottom);
				$pdf->Text($redaction_x + ($signature_width * 2 + $signature_margin - $note_bottom_width) / 2, $validation_y + $signature_height + 5, $note_bottom);
				$pdf->SetTextColor(0, 0, 0); // Retour à la couleur noire

//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------


				// Use new auto column system
				$this->prepareArrayColumnField($object, $outputlangs, $hidedetails, $hidedesc, $hideref);

				// Table simulation to know the height of the title line
				$pdf->startTransaction();
				$this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);
				$pdf->rollbackTransaction(true);

				$nexY = $tab_top + $this->tabTitleHeight;

				// Loop on each lines
				$pageposbeforeprintlines = $pdf->getPage();
				$pagenb = $pageposbeforeprintlines;

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
	 *  @return	array						List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		return parent::liste_modeles($db, $maxfilenamelength); // TODO: Change the autogenerated stub
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   	Show header of page. Need this->emetteur object
	 *
	 *   	@param	TCPDF		$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	protected function _pagehead(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf, $langs, $db;

		// Position initiale pour l'en-tête
		$header_y = 10;
		$header_height = 30;

		// Logo à gauche
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
					$height = $height * 0.4; // Réduire la hauteur de 60%
					// Positionner le logo à gauche avec un léger décalage vers la droite
					$logo_x = $this->marge_gauche + 15; // Ajout de 15mm de décalage
					$pdf->Image($logo, $logo_x, $header_y + 12, 0, $height); // width=0 (auto) et aligné avec la deuxième ligne
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

		// Informations centrales
		$pdf->SetFont('', '', 8); // Réduit de 10 à 8
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
		$pdf->SetFont('', 'B', 8); // Réduit de 10 à 8
		$site_width = $pdf->GetStringWidth($site);
		$pdf->Text($center_x - ($site_width / 2), $header_y + 5, $site);

		// Libellé du projet
		$pdf->SetFont('', '', 8); // Réduit de 10 à 8
		$project_width = $pdf->GetStringWidth($project_label);
		$pdf->Text($center_x - ($project_width / 2), $header_y + 12, $project_label);

		// "Organigramme d'affaire"
		$pdf->SetFont('', 'B', 8); // Réduit de 10 à 8
		$title = "Organigramme d'affaire";
		$title_width = $pdf->GetStringWidth($title);
		$pdf->Text($center_x - ($title_width / 2), $header_y + 19, $title);

		// Informations à droite
		$pdf->SetFont('', '', 7); // Réduit de 10 à 7
		$right_x = $this->page_largeur - $this->marge_droite - 40;
		
		// Référence OT
		$pdf->Text($right_x, $header_y + 5, "Réf. OT : ");
		$pdf->SetFont('', 'B', 7); // Réduit de 10 à 7
		$pdf->Text($right_x + 20, $header_y + 5, $object->ref);
		
		// Indice
		$pdf->SetFont('', '', 7); // Réduit de 10 à 7
		$pdf->Text($right_x, $header_y + 10, "Indice : "); // Réduit de 12 à 10
		$pdf->SetFont('', 'B', 7); // Réduit de 10 à 7
		$pdf->Text($right_x + 20, $header_y + 10, $object->indice); // Réduit de 12 à 10
		
		// Numéro d'affaire
		$pdf->SetFont('', '', 7); // Réduit de 10 à 7
		$pdf->Text($right_x, $header_y + 15, "Affaire : "); // Réduit de 19 à 15
		$pdf->SetFont('', 'B', 7); // Réduit de 10 à 7
		$pdf->Text($right_x + 20, $header_y + 15, $project_ref); // Réduit de 19 à 15

		// Numéro de page
		$pdf->SetFont('', '', 7); // Réduit de 10 à 7
		$pdf->Text($right_x, $header_y + 20, "Page : "); // Réduit de 26 à 20
		$pdf->SetFont('', 'B', 7); // Réduit de 10 à 7
		$pdf->Text($right_x + 20, $header_y + 20, $pdf->getPage() . " / " . $pdf->getAliasNbPages()); // Réduit de 26 à 20

		// Ligne noire horizontale
		$pdf->SetDrawColor(0, 0, 0);
		$pdf->Line($this->marge_gauche, $header_y + 30, $this->page_largeur - $this->marge_droite, $header_y + 30); // Réduit de 35 à 30

		return $header_y + 35; // Retourne la position Y après l'en-tête
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
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = !getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS') ? 0 : getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS');
		return pdf_pagefoot($pdf, $outputlangs, 'INVOICE_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
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

		/*
		 * For exemple
		$this->cols['theColKey'] = array(
			'rank' => $rank, // int : use for ordering columns
			'width' => 20, // the column width in mm
			'title' => array(
				'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				'label' => ' ', // the final label : used fore final generated text
				'align' => 'L', // text alignement :  R,C,L
				'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'content' => array(
				'align' => 'L', // text alignement :  R,C,L
				'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
		);
		*/

		$rank = 0; // do not use negative rank
		$this->cols['desc'] = array(
			'rank' => $rank,
			'width' => false, // only for desc
			'status' => true,
			'title' => array(
				'textkey' => 'Designation', // use lang key is usefull in somme case with module
				'align' => 'L',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
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
			'border-left' => false, // remove left line separator
		);

		if (getDolGlobalInt('MAIN_GENERATE_INVOICES_WITH_PICTURE') && !empty($this->atleastonephoto)) {
			$this->cols['photo']['status'] = true;
		}


		$rank = $rank + 10;
		$this->cols['vat'] = array(
			'rank' => $rank,
			'status' => false,
			'width' => 16, // in mm
			'title' => array(
				'textkey' => 'VAT'
			),
			'border-left' => true, // add left line separator
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
        $fonction_map = [
            160 => "RA",
            1031113 => "RI",
            161 => "INT",
            1031119 => "CT",
            1032001 => "PCRREF",
            1031139 => "CONS"
        ];

        $fonctions = [];

        $sql = "SELECT sp.fk_c_type_contact 
                FROM ".MAIN_DB_PREFIX."element_contact as sp
                JOIN ".MAIN_DB_PREFIX."c_type_contact as ctc ON sp.fk_c_type_contact = ctc.rowid
                WHERE sp.fk_socpeople = ".intval($userId)."
                AND sp.element_id = ".intval($projectId)."
                AND ctc.element = 'project'
                AND sp.statut = 4";

        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                if (isset($fonction_map[$obj->fk_c_type_contact])) {
                    $fonctions[] = $fonction_map[$obj->fk_c_type_contact];
                }
            }
        }

        return !empty($fonctions) ? implode("-", $fonctions) : null;
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
}