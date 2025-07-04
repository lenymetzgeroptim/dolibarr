<?php
/* Copyright (C)2025 Soufiane Fadel <s.fadel@optim-industries.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file       htdocs/core/lib/email.lib.php
 *  \brief      Ensemble de fonctions de base pour le module ecm
 *  \ingroup    ecm
 */

 /**
  * @param array $senders
  * @param string $subject
  * @param string $messageTemplate
  * @param array $arr_file
  *
  * @return array $emailLogs 
  */
// function send_bulk_emails($senders, $foldername, $filenames, $attachments, $lastfiles = []) {
//     global $db, $conf, $user, $mysoc, $dolibarr_main_url_root;

//     $emailLogs = [];
//     $senders = array_unique($senders, SORT_REGULAR);
//     $lastfiles = array_unique($lastfiles);
//     $paths = DOL_DATA_ROOT . '/ecmcustom/' . $folder . '/' . $filenames;
   
//     $urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
//     $urlwithroot = $urlwithouturlroot . DOL_URL_ROOT;
//     $logo = $urlwithouturlroot.DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&file=logos%2Fthumbs%2F' . urlencode($mysoc->logo_small);
//     $editor = dol_escape_htmltag($user->getFullName($langs));
// 	$link = '<a href="'.$urlwithroot.'/custom/ecmcustom/index.php?uploadform=1"> ici.</a>';
	
//     // Insertion dans la file
//     $values = [];
   
//     foreach ($senders as $email => $name) {
//         $subject = '[OPTIM Industries] Notification automatique (nouveaux documents dans ' . $foldername . ')';
//        	$trackid = 'mem'.$section;
// 		$msgishtml = '<html><body>';
//         $msgishtml .= '<br>Bonjour ' . htmlspecialchars($name) . ',<br><br>';
//         $msgishtml .= 'Des nouveaux fichiers (ajoutés par ' . htmlspecialchars($editor) . ') sont disponibles dans le répertoire « ' . htmlspecialchars($foldername) . ' » :';
//         $msgishtml .= '<br><ul><li>' . implode("<br><li>", array_unique(array_filter($lastfiles))) . '</li></ul>';
//         $msgishtml .= '
// 					<p>Pour les consulter sur l\'ERP, veuillez cliquer 
// 					<a href="'.$urlwithroot.'/custom/ecmcustom/index.php?uploadform=1" style="color:#0066cc;">ici</a>.
// 					</p>';
//         $msgishtml .= '<br>Cordialement,<br><br><div><img src="' . $logo . '" style="max-width:150px; height:auto;"></div>';
//         $msgishtml .= '</body></html>';
//         $message = strip_tags($msgishtml);

//         $values[] = "('".$db->escape($email)."','".$db->escape($name)."','".$db->escape($subject)."','".$db->escape($msgishtml)."','".$db->escape($attachments)."','".$db->escape(implode(',', $filenames))."', '".$db->escape($foldername)."')";
//     }

 

//     if (!empty($values)) {
//         $sql = "INSERT INTO ".MAIN_DB_PREFIX."ecm_email_queue (email, name, subject, message, attachments, filename, foldername) VALUES ".implode(',', $values);
//         $db->query($sql);
//     }

//     // Traitement des envois
//     $replyto = 'erp@optim-industries.fr';
//     $resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."ecm_email_queue WHERE sent = 0 AND filename IN('".implode($filenames)."') AND foldername = '".$db->escape($foldername)."' ORDER BY rowid ASC");
//     while ($obj = $db->fetch_object($resql)) {
//         $email = filter_var(trim($obj->email), FILTER_VALIDATE_EMAIL);
//         if (!$email) continue;

//         try {
//             $mailfile = new CMailFile(
// 				$obj->subject,
//                 $email,
//                 $replyto,
//                 $obj->message,      
//                 $arr_file,
//                 $arr_mime,
//                 $arr_name,
//                 $cc,
//                 $ccc,
//                 $deliveryreceipt,
//                 1,    // HTML version
//                 $errors_to,
//                 $css,
//                 $trackid,
//                 $moreinheader,
//                 $sendcontext                   
// 			);
			
//             $success = $mailfile->sendfile();
//             date_default_timezone_set('Europe/Paris');
//             $db->query("UPDATE ".MAIN_DB_PREFIX."ecm_email_queue SET sent = ".((int)$success).", received = ".((int)$success).", sent_at = '".$db->idate(dol_now())."', error = ".($success ? "NULL" : "'".$db->escape($mailfile->error)."'")." WHERE rowid = ".((int)$obj->rowid));
          
//             $emailLogs[$email] = [
//                 'email'   => $email,
//                 'editor'  => $editor,
//                 'subject' => $subject,
//                 'folder' => $foldername,
//                 'file' => implode(',', $filenames),
//                 'attachment' => $attachments,
//                 'path' => $path,
//                 'status' => $success ? 'Envoyé' : 'Échec',
//                 'sentAt' => $success ? dol_print_date(dol_now(), 'dayhour') : '',
//                 'error'   => $success ? '' : $mailfile->error,
//             ];
//         } catch (Exception $e) {
//             error_log("Erreur envoi à $email: ".$e->getMessage());
//         }
//     }

//     return $emailLogs;
// }

// function send_bulk_emails($senders, $foldername, $filenames, $attachments, $lastfiles = []) {
//     set_time_limit(300);
// 	global $db, $conf, $user, $mysoc, $dolibarr_main_url_root, $langs;

//     // $senders = array_unique($senders, SORT_REGULAR);
//     $lastfiles = array_unique($lastfiles);
//     $filenameStr = implode(',', $filenames);
//     $path = DOL_DATA_ROOT . '/ecmcustom/' . $foldername . '/' . $filenameStr;

//     $urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
//     $urlwithroot = $urlwithouturlroot . DOL_URL_ROOT;
//     $logo = $urlwithroot . '/viewimage.php?modulepart=mycompany&file=logos%2Fthumbs%2F' . urlencode($mysoc->logo_small);
//     $editor = dol_escape_htmltag($user->getFullName($langs));
//     $trackid = 'mem' . $foldername;

//     // Insertion en file d’attente
//     $values = [];
//     foreach ($senders as $email => $name) {
// 		var_dump($email);
//         $subject = '[OPTIM Industries] Notification automatique (nouveaux documents dans ' . $foldername . ')';

//         $msgishtml = '<html><body>';
//         $msgishtml .= '<br>Bonjour ' . htmlspecialchars($name) . ',<br><br>';
//         $msgishtml .= 'Des nouveaux fichiers (ajoutés par ' . htmlspecialchars($editor) . ') sont disponibles dans le répertoire « ' . htmlspecialchars($foldername) . ' » :';
//         $msgishtml .= '<br><ul><li>' . implode("<br><li>", array_filter($lastfiles)) . '</li></ul>';
//         $msgishtml .= '<p>Pour les consulter sur l\'ERP, cliquez <a href="' . $urlwithroot . '/custom/ecmcustom/index.php?uploadform=1" style="color:#0066cc;">ici</a>.</p>';
//         $msgishtml .= '<br>Cordialement,<br><br><div><img src="' . $logo . '" style="max-width:150px; height:auto;"></div>';
//         $msgishtml .= '</body></html>';

//         $sql_check = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "ecm_email_queue 
//                       WHERE email = '" . $db->escape($email) . "' 
//                       AND foldername = '" . $db->escape($foldername) . "' 
//                       AND filename = '" . $db->escape($filenameStr) . "'";
//         $res_check = $db->query($sql_check);
//         $obj_check = $db->fetch_object($res_check);
//         if ($obj_check->nb == 0) {
//             $values[] = "("
//                 . "'" . $db->escape($email) . "',"
//                 . "'" . $db->escape($name) . "',"
//                 . "'" . $db->escape($subject) . "',"
//                 . "'" . $db->escape($msgishtml) . "',"
//                 . "'" . $db->escape($attachments) . "',"
//                 . "'" . $db->escape($filenameStr) . "',"
//                 . "'" . $db->escape($foldername) . "',"
//                 . "0" . ")";
//         }
//     }

//     if (!empty($values)) {
//         $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ecm_email_queue 
//                 (email, name, subject, message, attachments, filename, foldername, try_count) 
//                 VALUES " . implode(',', $values);
//         $db->query($sql);
//     }

//     // Envoi par lots
//     $maxTries = 1;
//     $maxBatch = 10;

//     do {
//         $resql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "ecm_email_queue 
//             WHERE sent = 0 AND try_count < $maxTries 
//             AND filename = '" . $db->escape($filenameStr) . "' 
//             AND foldername = '" . $db->escape($foldername) . "' 
//             ORDER BY rowid ASC LIMIT $maxBatch");

//         if (!$resql || $db->num_rows($resql) == 0) break;

//         while ($obj = $db->fetch_object($resql)) {
//             $email = filter_var(trim($obj->email), FILTER_VALIDATE_EMAIL);
//             if (!$email) continue;

//             try {
//                 $mailfile = new CMailFile(
//                     $obj->subject,
//                     $email,
//                     'erp@optim-industries.fr',
//                     $obj->message,
//                     $arr_file, $arr_mime, $arr_name, $cc, $ccc, $deliveryreceipt,
//                     1, $errors_to, $css, $trackid, $moreinheader, $sendcontext
//                 );

//                 $success = $mailfile->sendfile();
//                 $now = $db->idate(dol_now());

//                 $db->query("UPDATE " . MAIN_DB_PREFIX . "ecm_email_queue 
//                     SET try_count = try_count + 1,
//                         sent = " . ((int)$success) . ",
//                         received = " . ((int)$success) . ",
//                         sent_at = '" . $now . "',
//                         error = " . ($success ? "NULL" : "'" . $db->escape($mailfile->error) . "'") . "
//                     WHERE rowid = " . ((int)$obj->rowid));

//             } catch (Exception $e) {
//                 error_log("Erreur envoi à $email : " . $e->getMessage());
//             }
//         }
//     } while (true);

//     // Construction du tableau $emailLogs global pour le PDF
//     $emailLogs = [];
//     $reslog = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "ecm_email_queue 
//         WHERE filename = '" . $db->escape($filenameStr) . "' 
//         AND foldername = '" . $db->escape($foldername) . "'");

//     while ($row = $db->fetch_object($reslog)) {
//         $emailLogs[] = [
//             'email' => $row->email,
//             'filename' => $row->filename,
//             'foldername' => $row->foldername,
//             'editor' => $editor,
//             'subject' => $row->subject,
//             'attachment' => $attachments,
//             'path' => $path,
//             'try_count' => (int)$row->try_count,
//             'sentAt' => $row->sent_at ? dol_print_date($row->sent_at, 'dayhour') : '',
//             'status' => $row->sent ? 'Envoyé' : 'Échec',
//             'error' => $row->error,
//             'statut_final' => (!$row->sent && (int)$row->try_count >= $maxTries) ? 'Échec définitif' : ''
//         ];
//     }

//     return $emailLogs;
// }

function send_bulk_emails($senders, $foldername, $filenames, $attachments, $lastfiles = []) {
    set_time_limit(600);
	date_default_timezone_set('Europe/Paris');
    global $db, $conf, $user, $mysoc, $dolibarr_main_url_root, $langs;

    $lastfiles = array_unique($lastfiles);
    $filenameStr = implode(',', $filenames);
    $path = DOL_DATA_ROOT . '/ecmcustom/' . $foldername . '/' . $filenameStr;

    $urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
    $urlwithroot = $urlwithouturlroot . DOL_URL_ROOT;
    $logo = $urlwithroot . '/viewimage.php?modulepart=mycompany&file=logos%2Fthumbs%2F' . urlencode($mysoc->logo_small);
    $editor = dol_escape_htmltag($user->getFullName($langs));
    $trackid = 'mem' . $foldername;

    // Insertion en file d’attente
    $values = [];
    foreach ($senders as $email => $name) {
        $subject = '[OPTIM Industries] Notification automatique (nouveaux documents dans ' . $foldername . ')';

        $msgishtml = '<html><body>';
        $msgishtml .= '<br>Bonjour ' . htmlspecialchars($name) . ',<br><br>';
        $msgishtml .= 'Des nouveaux fichiers (ajoutés par ' . htmlspecialchars($editor) . ') sont disponibles dans le répertoire « ' . htmlspecialchars($foldername) . ' » :';
        $msgishtml .= '<br><ul><li>' . implode("<br><li>", array_filter($lastfiles)) . '</li></ul>';
        $msgishtml .= '<p>Pour les consulter sur l\'ERP, cliquez <a href="' . $urlwithroot . '/custom/ecmcustom/index.php?uploadform=1" style="color:#0066cc;">ici</a>.</p>';
        $msgishtml .= '<br>Cordialement,<br><br><div><img src="' . $logo . '" style="max-width:150px; height:auto;"></div>';
        $msgishtml .= '</body></html>';

        $sql_check = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "ecm_email_queue 
                      WHERE email = '" . $db->escape($email) . "' 
                      AND foldername = '" . $db->escape($foldername) . "' 
                      AND filename = '" . $db->escape($filenameStr) . "'";
        $res_check = $db->query($sql_check);
        $obj_check = $db->fetch_object($res_check);
        if ($obj_check->nb == 0) {
            $values[] = "("
                . "'" . $db->escape($email) . "',"
                . "'" . $db->escape($name) . "',"
                . "'" . $db->escape($subject) . "',"
                . "'" . $db->escape($msgishtml) . "',"
                . "'" . $db->escape($attachments) . "',"
                . "'" . $db->escape($filenameStr) . "',"
                . "'" . $db->escape($foldername) . "',"
                . "0" . ")";
        }
    }

    if (!empty($values)) {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ecm_email_queue 
                (email, name, subject, message, attachments, filename, foldername, try_count) 
                VALUES " . implode(',', $values);
        $db->query($sql);
    }

    // Envoi par lots
    $maxTries = 1;
    $maxBatch = 10;

    do {
        $resql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "ecm_email_queue 
            WHERE sent = 0 AND try_count < $maxTries 
            AND filename = '" . $db->escape($filenameStr) . "' 
            AND foldername = '" . $db->escape($foldername) . "' 
            ORDER BY rowid ASC LIMIT $maxBatch");

        if (!$resql || $db->num_rows($resql) == 0) break;

        while ($obj = $db->fetch_object($resql)) {
            $email = filter_var(trim($obj->email), FILTER_VALIDATE_EMAIL);
            if (!$email) continue;

            try {
                $mailfile = new CMailFile(
                    $obj->subject,
                    $email,
                    'erp@optim-industries.fr',
                    $obj->message,
                    $arr_file, $arr_mime, $arr_name, $cc, $ccc, $deliveryreceipt,
                    1, $errors_to, $css, $trackid, $moreinheader, $sendcontext
                );

                $success = $mailfile->sendfile();
                sleep(1); // Pause de 2 secondes entre chaque envoi

				date_default_timezone_set('Europe/Paris');
                $now = $db->idate(dol_now());

                $db->query("UPDATE " . MAIN_DB_PREFIX . "ecm_email_queue 
                    SET try_count = try_count + 1,
                        sent = " . ((int)$success) . ",
                        received = " . ((int)$success) . ",
                        sent_at = '" . $now . "',
                        error = " . ($success ? "NULL" : "'" . $db->escape($mailfile->error) . "'") . "
                    WHERE rowid = " . ((int)$obj->rowid));

            } catch (Exception $e) {
                error_log("Erreur envoi à $email : " . $e->getMessage());
            }
        }
    } while (true);

    // Construction du tableau $emailLogs global pour le PDF
    $emailLogs = [];
    $reslog = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "ecm_email_queue 
        WHERE filename = '" . $db->escape($filenameStr) . "' 
        AND foldername = '" . $db->escape($foldername) . "'");
	
    while ($row = $db->fetch_object($reslog)) {
		var_dump($row->sent_at);
        $emailLogs[] = [
            'email' => $row->email,
            'filename' => $row->filename,
            'foldername' => $row->foldername,
            'editor' => $editor,
            'subject' => $row->subject,
            'attachment' => $attachments,
            'path' => $path,
            'try_count' => (int)$row->try_count,
            'sentAt' => $row->sent_at ? $row->sent_at : '',
            'status' => $row->sent ? 'Envoyé' : 'Échec',
            'error' => $row->error,
            'statut_final' => (!$row->sent && (int)$row->try_count >= $maxTries) ? 'Échec définitif' : ''
        ];
    }

    return $emailLogs;
}


require_once DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/config/tcpdf_config.php';
require_once DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/tcpdf.php';


function generateEmailReportPDF($upload_dir, $relativepath, $filename, $logsForThisFile, $user, $langs, $mysoc, $conf) {
	global $dolibarr_main_url_root, $mysoc;

	$info = pathinfo($filename);
	$filenameWithExt = $info['basename'];

	$pdfname = $filenameWithExt . '_rapport_emails_' . date('Ymd_His') . '.pdf';
	$pdfpath = $upload_dir . '/' . $pdfname;

	// Choix du logo
	$width = 30;
	$logoPath = DOL_DOCUMENT_ROOT.'/theme/common/login_logo.png';
	if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small)) {
		$logoPath = $conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small;
	} 

	// Init PDF
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	$pdf->SetCreator('Dolibarr');
	$pdf->SetAuthor($user->getFullName($langs));
	$pdf->SetTitle('Rapport Email - ' . $filename);
	$pdf->SetSubject('Rapport des envois');
	$pdf->SetMargins(10, 45, 10);
	$pdf->SetAutoPageBreak(TRUE, 15);
	$pdf->AddPage();
	$pdf->SetFont('dejavusans', '', 10);

	// Logo
	if (is_readable($logoPath)) {
		$pdf->Image($logoPath, 10, 10, $width);
	}

	// Entête
	$editor = dol_escape_htmltag($user->getFullName($langs));
	$date = dol_print_date(dol_now(), 'dayhourtext');
	$html_header = '
	<style>
		.title { font-size: 16px; font-weight: bold; color: #003366; text-align: center; margin-top: 20px; margin-bottom: 10px; }
		.info-table td { padding: 4px; font-size: 10px; }
	</style>
	<div class="title">Rapport d\'envoi d\'emails (GED)</div>
	<table class="info-table" width="100%">
		<tr>
			<td width="50%"><b>Dossier :</b> ' . dol_escape_htmltag($relativepath) . '</td>
			<td width="50%" align="right"><b>Date :</b> ' . $date . '</td>
		</tr>
		<tr>
			<td><b>Fichier :</b> ' . dol_escape_htmltag($filename) . '</td>
			<td align="right"><b>Éditeur :</b> ' . $editor . '</td>
		</tr>
	</table>
	<hr style="margin-top:10px; margin-bottom:10px;">';

	$pdf->writeHTML($html_header, true, false, true, false, '');

	// Tableau stylisé
	ob_start(); ?>
	<style>
		.table-style {
			border-collapse: collapse;
			font-size: 9pt;
		}
		.table-style th {
			background-color: #eaeaea;
			color: #003366;
			font-weight: bold;
			border: 1px solid #ccc;
			padding: 5px;
			text-align: center;
		}
		.table-style td {
			border: 1px solid #ddd;
			padding: 4px;
			text-align: center;
		}
	</style>
	<table class="table-style" width="100%">
		<thead>
			<tr>
				<th width="30%">Email</th>
				<th width="10%">Statut</th>
				<th width="20%">Date d’envoi</th>
				<th width="10%">Nb tentatives</th>
				<th width="30%">Erreur</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($logsForThisFile as $log): ?>
			<tr>
				<td width="30%"><?= dol_escape_htmltag($log['email']) ?></td>
				<td width="10%"><?= dol_escape_htmltag($log['status']) ?></td>
				<td width="20%"><?= dol_escape_htmltag($log['sentAt']) ?></td>
				<td width="10%"><?= dol_escape_htmltag($log['try_count']) ?> fois</td>
				<td width="30%"><?= dol_escape_htmltag($log['error']) ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php
	$html_table = ob_get_clean();
	$pdf->writeHTML($html_table, true, false, true, false, '');

	// Sauvegarde
	$pdf->Output($pdfpath, 'F');

	return [
		'name' => $pdfname,
		'path' => $pdfpath,
		'link' => $dolibarr_main_url_root . DOL_URL_ROOT . '/document.php?modulepart=ecmcustom&attachment=1&file=' . rawurlencode($relativepath . '/' . $pdfname)
	];
}

function formatLocalDate($timestamp) {
    if (!$timestamp) return '';
    $date = new DateTime('@' . $timestamp);
    $date->setTimezone(new DateTimeZone('Europe/Paris'));
    return $date->format('Y-m-d H:i:s');
}


/**
 * Suppression des fichiers physiques et les entrées non envoyées dans ecm_email_queue.
 *
 * @param array  $filenames   Liste des noms de fichiers à supprimer
 * @param string $foldername  Nom du dossier (ex. "emailsent")
 * @return int                Nombre de suppressions effectuées
 */
function delete_unsent_email_files($filenames, $foldername)
{
    global $conf, $db;

    if (empty($filenames) || empty($foldername)) return 0;

    $deleted = 0;

    foreach ($filenames as $filename) {
        $fullpath = $conf->ecmcustom->dir_output . '/' . $foldername . '/' . $filename;

        // Suppression de l'entrée en base
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ecm_email_queue";
        $sql .= " WHERE 1 = 1";
        $sql .= " AND filename = '" . $db->escape($filename) . "'";
        $sql .= " AND foldername = '" . $db->escape($foldername) . "'";

        $res = $db->query($sql);
        if ($res) {
            $deleted += $db->affected_rows($res);
        } else {
            dol_syslog(__METHOD__ . " SQL error: " . $db->lasterror(), LOG_ERR);
        }
    }

    return $deleted;
}

