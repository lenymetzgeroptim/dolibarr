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
				$pdf->SetAutoPageBreak(1, 0);

				$heightforinfotot = 50; // Height reserved to output the info and total part and payment part
				$heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + (getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS') ? 12 : 22); // Height reserved to output the footer (value include bottom margin)

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(true);
					$pdf->setPrintFooter(false);
					$pdf->SetMargins(10, 35, 10); 
					$pdf->SetAutoPageBreak(TRUE, 15);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));

				// Set path to the background PDF File
				if (getDolGlobalString('MAIN_ADD_PDF_BACKGROUND')) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->multidir_output[$object->entity].'/'.getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
					$tplidx = $pdf->importPage(1);
				}

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

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}
				$pagenb++;

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
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
								//$this->_pagehead($pdf, $object, 0, $outputlangs);
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
							$pdf->setTopMargin($tab_top_newpage);
							// The only function to edit the bottom margin of current page to set it.
							$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
							//$posyafter = $tab_top_newpage;
						}


						// apply note frame to previous pages
						$i = $pageposbeforenote;
						while ($i < $pageposafternote) {
							$pdf->setPage($i);


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
						if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
							//$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
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
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
								//$this->_pagehead($pdf, $object, 0, $outputlangs);
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

				// Charger les données principales pour l'OT
				$sql = "SELECT rowid, ref, indice, fk_project, date_creation FROM " . MAIN_DB_PREFIX . "ot_ot WHERE rowid = $otId";
				$resql = $db->query($sql);
				if ($resql && $db->num_rows($resql) > 0) {
					$otData = $db->fetch_object($resql);
				} else {
					throw new Exception("Aucune donnée trouvée pour l'OT avec l'ID $otId.");
				}

				// Charger les cellules associées à cet OT
				$sql = "SELECT oc.rowid, oc.id_cellule, oc.x, oc.y, oc.type, oc.title 
						FROM " . MAIN_DB_PREFIX . "ot_ot_cellule AS oc
						WHERE oc.ot_id = $otId";
				$resql = $db->query($sql);
				if (!$resql) {
					dol_syslog("Erreur SQL : " . $db->lasterror(), LOG_ERR);
				}

				// Convertir le timestamp Unix en objet DateTime
				$dateAppli = new DateTime();
				$dateAppli->setTimestamp($object->date_applica_ot);

				// Formatter la date en "jour/mois/année"
				$formattedDateAppli = $dateAppli->format('d/m/Y');

				// Affichage de la date d'applicabilité en haut, juste sous le header
				$pdf->SetFont('', 'B', 10);
				$pdf->SetTextColor(0, 0, 0);

				// Position verticale juste après le header (exemple : 15 pixels sous le haut)
				$current_y = 40; 
				$pdf->Text($this->marge_gauche, $current_y, "Date d'applicabilité de l'OT : ");
				$pdf->SetFont('', '', 10);
				$pdf->Text($this->marge_gauche + 48, $current_y, $formattedDateAppli);

				// Laisser un petit espace pour les prochains contenus
				$current_y += 10; 

				// Charger les rôles et leurs données
				$sqlRoles = "
				SELECT 
					u.firstname, 
					u.lastname, 
					u.office_phone, 
					ctc.libelle AS role_label
				FROM 
					".MAIN_DB_PREFIX."element_contact AS sp
				JOIN 
					".MAIN_DB_PREFIX."user AS u ON sp.fk_socpeople = u.rowid
				JOIN 
					".MAIN_DB_PREFIX."c_type_contact AS ctc ON sp.fk_c_type_contact = ctc.rowid
				WHERE 
					sp.element_id = $object->fk_project
					AND sp.statut = 4
					AND sp.fk_c_type_contact IN ('1031120', '1031131', '1031132')
				";
				$resqlRoles = $db->query($sqlRoles);

				$rolesData = [];
				while ($row = $db->fetch_object($resqlRoles)) {
				$rolesData[] = [
					'firstname' => $row->firstname,
					'lastname' => $row->lastname,
					'phone' => $row->office_phone,
					'role' => $row->role_label
				];
				}

				// Titre de la section
				$pdf->SetFont('', 'B', 12);
				$pdf->SetTextColor(0, 0, 0);
				$current_y += 10;

				// Répartition des rôles en trois colonnes
				$columns = 3;
				$col_counter = 0;
				$current_x = $this->marge_gauche + 10;
				$card_width = 50;
				$card_height = 25;
				$card_margin = 10;

				// Décalage horizontal vers la gauche (petit ajustement global)
				$shift_left = 1; // Ajustez cette valeur pour augmenter ou réduire le décalage
				
				// Parcours des rôles pour les afficher dans les colonnes
				foreach ($rolesData as $role) {
					if ($col_counter >= $columns) {
						$col_counter = 0;
						$current_y += $card_height + $card_margin; // Passe à la ligne suivante
						$current_x = $this->marge_gauche; // Reviens au début de la ligne
					}

					// Dessiner le contour de la carte
					$pdf->SetDrawColor(0, 0, 0);
					$pdf->Rect($current_x, $current_y, $card_width, $card_height);

					// Ajustement de centrage horizontal avec décalage vers la gauche
					$center_x = $current_x + $card_width / 2 - $shift_left; // Centrage ajusté vers la gauche

					// Afficher le titre du rôle
					$pdf->SetFont('', 'B', 9);
					$role_title_width = $pdf->GetStringWidth($role['role']);
					$role_title_x = $center_x - $role_title_width / 2; // Centrage ajusté
					$role_title_y = $current_y + 5; // Position verticale
					$pdf->Text($role_title_x, $role_title_y, $role['role']);

					// Afficher le nom de l'utilisateur
					$pdf->SetFont('', 'B', 10);
					$name = $role['firstname'] . ' ' . $role['lastname'];
					$name_width = $pdf->GetStringWidth($name);
					$name_x = $center_x - $name_width / 2; // Centrage ajusté
					$name_y = $current_y + 12; // Position verticale
					$pdf->Text($name_x, $name_y, $name);

					// Afficher le numéro de téléphone (s'il existe)
					if (!empty($role['phone'])) {
						$phone_width = $pdf->GetStringWidth($role['phone']);
						$phone_x = $center_x - $phone_width / 2; // Centrage ajusté
						$phone_y = $current_y + 19; // Position verticale
						$pdf->Text($phone_x, $phone_y, $role['phone']);
					}

					// Passer à la colonne suivante
					$current_x += $card_width + $card_margin;
					$col_counter++;
				}

				// Ajouter un espace vertical après les cartes des responsables
				$current_y += $card_height +   $card_margin;

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
							$sqlUserDetails = "SELECT lastname, firstname FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . $userRow->fk_user;
							$resqlUserDetails = $db->query($sqlUserDetails);
							if ($resqlUserDetails && $db->num_rows($resqlUserDetails) > 0) {
								$userDetails = $db->fetch_object($resqlUserDetails);
								// Ajouter le nom complet (prénom et nom) au tableau userNames
								$userNames[] = $userDetails->firstname . ' ' . $userDetails->lastname;
							}
						}
						
						// Ajouter les utilisateurs associés à cellData
						$cellData['userIds'] = $userIds;
						$cellData['userNames'] = $userNames;  // Ajouter les noms complets des utilisateurs
					}

					// Ajouter la carte dans le tableau approprié
					if ($row->type === 'listeunique') {
						$listeUniqueCards[] = $cellData;  // Cartes 'listeunique' à afficher en dessous de la grille
					} else {
						$cardsData[] = $cellData;  // Cartes normales à afficher dans la grille
					}
				}

				// Stocker les données dans la variable globale
				$GLOBALS['otData'] = [
					'otId' => $otData->rowid,
					'title' => $otData->ref,
					'description' => $otData->indice,
					'cardsData' => $cardsData,
					'listeUniqueCards' => $listeUniqueCards
				];

				// Paramètres pour la grille
				$grid_columns = 3;
				$card_width = 50;
				$card_height = 25;
				$card_margin = 10; 
				$card_user_margin = 5;
				$shift_left = 1;
				
				// Calcul du Y max à partir des données
				$max_y = 0; 
				foreach ($cardsData as $card) { 
					if ($card['y'] > $max_y) {
						$max_y = $card['y']; // Mettre à jour le Y maximum
					}
				}

				// Regrouper les cartes par `Y` et les trier par `X`
				$groupedByY = [];
				foreach ($cardsData as $card) {
					$groupedByY[$card['y']][] = $card;
				}

				// Positionner les cartes sur les bonnes lignes et colonnes
				$current_y = 100; // Position verticale de départ
				$center_x = ($this->page_largeur - $card_width) / 2; // Position horizontale de départ

				// Trier les clés de Y pour afficher les lignes dans l'ordre croissant
				ksort($groupedByY);

				foreach ($groupedByY as $y => $cardsOnY) {
					// Calculer la largeur totale occupée par les cartes sur cette ligne
					$line_width = count($cardsOnY) * $card_width + (count($cardsOnY) - 1) * $card_margin;
					
					// Appliquer un décalage manuel léger pour ajuster vers la droite
					$manual_offset = 0; // Ajustement léger, à moduler si nécessaire
					$current_x = ($this->page_largeur - $line_width) / 2 + $manual_offset;
				
					foreach ($cardsOnY as $card) {
						// Calculer la hauteur dynamique
						$userCount = isset($card['userNames']) ? count($card['userNames']) : 0;
						$dynamic_card_height = $card_height + ($userCount * $card_user_margin) - 5;
				
						// Vérifier si la carte dépasse la page en Y
						if ($current_y + $dynamic_card_height > $this->page_hauteur - $this->marge_basse) {
							$pdf->AddPage(); // Ajout d'une nouvelle page
							$current_y = $tab_top ?? 20; // Réinitialiser la position en Y
						}
				
						// Dessiner la carte
						$pdf->SetDrawColor(0, 0, 0);
						$pdf->Rect($current_x, $current_y, $card_width, $dynamic_card_height); // Rectangle pour la carte
				
						$pdf->SetFont('', 'B', 10);
						$pdf->SetTextColor(0, 0, 0);
						$title_width = $pdf->GetStringWidth($card['title']);
						$center_title_x = $current_x + ($card_width - $title_width) / 2 - $shift_left; // Ajustement horizontal
						$pdf->Text($center_title_x, $current_y + 5, $card['title']); // Titre centré
				
						// Affichage des utilisateurs
						if (!empty($card['userNames'])) {
							$y_offset = $current_y + 15; // Position initiale sous le titre
							$pdf->SetFont('', '', 8); // Police pour les utilisateurs
							foreach ($card['userNames'] as $userName) {
								$pdf->Text($current_x + 2, $y_offset, $userName);
								$y_offset += $card_user_margin; // Décalage vertical pour le prochain utilisateur
							}
						}
				
						// Mise à jour de current_x pour la prochaine carte sur cette ligne
						$current_x += $card_width + $card_margin;
					}
				
					// Mise à jour de current_y pour passer à la ligne suivante
					$current_y += $dynamic_card_height + $card_margin;
				}
				

				// Position pour les cartes de liste unique
				$below_y = $current_y + $card_margin;

				// Traitement des cartes listeunique
				foreach ($listeUniqueCards as $card) {
					// Calculer la hauteur dynamique pour la carte en fonction du nombre d'utilisateurs
					$userCount = count($card['userNames']);
					$dynamic_card_height = $card_height + ($userCount * $card_user_margin); // Ajouter une marge par utilisateur

					// La carte listeunique prend 3 fois la largeur d'une carte classique
					$listunique_width = $card_width * 3;

					// Calculer la position X pour centrer la carte listeunique par rapport à la page
					$center_x = ($this->page_largeur - $listunique_width) / 2;

					// Vérifier si la carte dépasse la page en Y
					if ($below_y + $dynamic_card_height > $this->page_hauteur - $this->marge_basse) {
						$pdf->AddPage(); // Ajout d'une nouvelle page
						$below_y = $tab_top ?? 20; // Réinitialiser la position en Y
					}

					// Dessiner la carte listeunique (largeur augmentée)
					$pdf->SetDrawColor(0, 0, 0);
					$pdf->Rect($center_x, $below_y, $listunique_width, $dynamic_card_height); // Rectangle pour la carte
					$pdf->SetFont('', 'B', 10);
					$pdf->SetTextColor(0, 0, 0);
					$title_width = $pdf->GetStringWidth($card['title']);
					$pdf->Text($center_x + ($listunique_width - $title_width) / 2, $below_y + 5, $card['title']); // Titre centré

					// Affichage des utilisateurs dans la carte listeunique
					if (!empty($card['userNames'])) {
						$y_offset = $below_y + 18; // Décalage vertical sous le titre
						$pdf->SetFont('', '', 8); // Police plus petite pour les utilisateurs
						foreach ($card['userNames'] as $userName) {
							$pdf->Text($center_x + 2, $y_offset, $userName); // Affichage du nom
							$y_offset += $card_user_margin; // Décalage pour le prochain utilisateur
						}
					}

					// Mise à jour de la position pour la prochaine carte
					$below_y += $dynamic_card_height + $card_margin;
				}

					// Définir la hauteur totale nécessaire pour afficher les zones de signature
					$signature_height = 40; // Hauteur estimée pour une zone de signature (incluant lignes, texte, marges)
					$header_margin = 40; // Espace sous le header (ou ajustez selon vos besoins)

					// Vérifier si l'espace restant est suffisant pour les deux zones
					if ($below_y + $signature_height > $this->page_hauteur - $this->marge_basse) {
						$pdf->AddPage(); // Ajouter une nouvelle page
						$below_y = $this->marge_haute + $header_margin; // Réinitialiser la position avec un espace sous le header
					}

					// Position pour les zones de signature
					$signature_width = ($this->page_largeur - $this->marge_gauche - $this->marge_droite) / 2 - 10; // Largeur de chaque zone
					$line_thickness = 0.2; // Épaisseur des lignes horizontales
					$line_spacing = 5; // Espacement entre les lignes pour nom, date, visa

					// Position X pour les deux zones
					$left_x = $this->marge_gauche;
					$right_x = $left_x + $signature_width + 20; // Ajout d'un espace entre les deux zones

					// Texte des zones
					$signature_labels = ['Rédaction', 'Validation RD'];

					// Ajouter la zone de signature "Rédaction"
					$pdf->SetFont('', 'B', 10);
					$pdf->Text($left_x, $below_y, $signature_labels[0]); // Ajouter le titre "Rédaction"

					// Calculer la largeur du texte "Rédaction" pour positionner l'astérisque à côté
					$width_of_redaction = $pdf->GetStringWidth($signature_labels[0]);

					// Ajouter un astérisque juste à côté du titre "Rédaction"
					$asterisk_font_size = 6; // Taille de la police pour l'astérisque
					$pdf->SetFont('', '', $asterisk_font_size); // Police plus petite pour l'astérisque
					$pdf->SetTextColor(150, 150, 150); // Couleur claire (gris) pour l'astérisque
					$asterisk_x = $left_x + $width_of_redaction + 2; // Position X pour l'astérisque juste après "Rédaction"
					$pdf->Text($asterisk_x, $below_y, '(*)'); // Ajouter l'astérisque

					// Réinitialiser la couleur du texte à noir pour le reste du PDF
					$pdf->SetTextColor(0, 0, 0); // Couleur noire pour le texte suivant

					$current_y = $below_y + 5; // Décaler sous le titre

					// Ligne horizontale supérieure pour "Rédaction"
					$pdf->SetLineWidth($line_thickness);
					$pdf->Line($left_x, $current_y, $left_x + $signature_width, $current_y);
					$current_y += $line_spacing; // Ajouter un espace sous la ligne

					// Lignes pour nom, date et visa pour "Rédaction"
					$pdf->SetFont('', '', 8);
					$pdf->Text($left_x, $current_y, 'Nom :');
					$current_y += $line_spacing;
					$pdf->Text($left_x, $current_y, 'Date :');
					$current_y += $line_spacing;
					$pdf->Text($left_x, $current_y, 'Visa :');
					$current_y += $line_spacing;

					// Ligne horizontale inférieure pour "Rédaction"
					$pdf->Line($left_x, $current_y, $left_x + $signature_width, $current_y);

					// Ajouter la zone de signature "Validation RD"
					$validation_y = $below_y; // Aligner avec le titre "Rédaction"
					$pdf->SetFont('', 'B', 10);
					$pdf->Text($right_x, $validation_y, $signature_labels[1]); // Ajouter le titre "Validation RD"

					// Calculer la largeur du texte "Validation RD" pour positionner la nouvelle phrase à côté
					$width_of_validation_rd = $pdf->GetStringWidth($signature_labels[1]);

					// Ajouter le texte "(si travaux en ZC et personnel intérimaire ou CDD)" juste à côté de "Validation RD"
					$additional_text = "(si travaux en ZC et personnel intérimaire ou CDD)";
					$additional_text_x = $right_x + $width_of_validation_rd + 2; // Position X pour le texte à côté de "Validation RD"
					$pdf->SetFont('', '', 6); // Police plus petite pour l'addition
					$pdf->SetTextColor(150, 150, 150); // Couleur claire (gris) pour le texte additionnel
					$pdf->Text($additional_text_x, $validation_y, $additional_text); // Ajouter le texte supplémentaire

					// Réinitialiser la couleur à noir pour la suite
					$pdf->SetTextColor(0, 0, 0); 

					// Ligne horizontale supérieure pour "Validation RD"
					$validation_y += 5; // Décaler sous le titre
					$pdf->SetLineWidth($line_thickness);
					$pdf->Line($right_x, $validation_y, $right_x + $signature_width, $validation_y);
					$validation_y += $line_spacing;

					// Lignes pour nom, date et visa pour "Validation RD"
					$pdf->SetFont('', '', 8);
					$pdf->Text($right_x, $validation_y, 'Nom :');
					$validation_y += $line_spacing;
					$pdf->Text($right_x, $validation_y, 'Date :');
					$validation_y += $line_spacing;
					$pdf->Text($right_x, $validation_y, 'Visa :');
					$validation_y += $line_spacing;

					// Ligne horizontale inférieure pour "Validation RD"
					$pdf->Line($right_x, $validation_y, $right_x + $signature_width, $validation_y);

					// Ajouter la phrase explicative sous l'espace de signature
					$explanation_text = "(*) Vérifier la bonne transmission de la FOD ou EDP aux nouveaux intervenants si risque radiologique sur l'affaire.";
					$explanation_y = $validation_y + 10; // Position sous la dernière ligne de signature
					$pdf->SetFont('', '', 6); // Utiliser la même police que pour l'astérisque
					$pdf->SetTextColor(150, 150, 150); // Même couleur que l'astérisque
					$pdf->Text($left_x, $explanation_y, $explanation_text); // Placer sous la dernière ligne de signature



//-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------


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
}