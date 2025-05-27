<?php
/* Copyright (C) 2020       Laurent Destailleur     <eldy@users.sourceforge.net>
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

/**
 *       \file       htdocs/public/recruitment/view.php
 *       \ingroup    recruitment
 *       \brief      Public file to show on job
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentjobposition.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "recruitment"));

// Get parameters
$action   = GETPOST('action', 'aZ09');
$cancel   = GETPOST('cancel', 'alpha');
$email    = GETPOST('email', 'alpha');
$backtopage = '';

$ref = GETPOST('ref', 'alpha');

if (GETPOST('btn_view')) {
	unset($_SESSION['email_customer']);
}
if (isset($_SESSION['email_customer'])) {
	$email = $_SESSION['email_customer'];
}

$object = new RecruitmentJobPosition($db);

if (!$action) {
	if (!$ref) {
		print $langs->trans('ErrorBadParameters')." - ref missing";
		exit;
	} else {
		$object->fetch('', $ref);
	}
}

// $username = GETPOST('username');

// // $logfile = __DIR__ . '/log_username.txt';
// $logfile = DOL_DATA_ROOT . '/log_username.txt';

// file_put_contents($logfile, date('Y-m-d H:i:s') . " - username: " . $username . "\n", FILE_APPEND);
// $username = trim(GETPOST('username', 'alpha'));
// if (empty($username)) {
//     print 'Invalid username';
//     exit;
// }
// $password = trim(GETPOST('password', 'none'));
// if (empty($password)) {
//     print 'Password is required';
//     exit;
// }

session_start();

$username = trim(GETPOST('username', 'alpha'));
if (empty($username)) {
    $_SESSION['login_error'] = "Nom d'utilisateur invalide";
     header('Location: /erp/public/recruitment/PlaningConges.php?ref=test');
    exit;
}

$password = trim(GETPOST('password', 'none'));
if (empty($password)) {
    $_SESSION['login_error'] = "Mot de passe requis";
    header('Location: /erp/public/recruitment/PlaningConges.php?ref=test');
    exit;
}



if (!empty($_GET['error'])) {
    if ($_GET['error'] == 'invalid_username') {
        echo '<div class="error">Nom d\'utilisateur invalide</div>';
    } elseif ($_GET['error'] == 'missing_password') {
        echo '<div class="error">Mot de passe requis</div>';
    }
}

// $logfile = DOL_DATA_ROOT . '/log_username.txt';
// file_put_contents($logfile, date('Y-m-d H:i:s') . " - username: " . $username . "\n", FILE_APPEND);

$logfile = DOL_DATA_ROOT . '/log_username.txt';

// Nettoyage du nom d'utilisateur pour éviter les injections
$safe_username = preg_replace('/[^a-zA-Z0-9_\-\.@]/', '', $username);

// Format de la ligne de log
$log_entry = date('Y-m-d H:i:s') . " - username: " . $safe_username . "\n";

// Écriture sécurisée (log append, verrouillage)
file_put_contents($logfile, $log_entry, FILE_APPEND | LOCK_EX);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Simulation sécurité</title>
  <style>
   html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      width: 100%;
      background-color: black;
      color: lime;
      font-family: "Courier New", Courier, monospace;
      font-size: 16px;
      overflow: hidden; 
    }
    #console {
      white-space: pre-wrap;
    }
    .red {
      color: red;
    }
  </style>
</head>
<body>
  <div id="console"></div>

  <script>
    const lines = [
      "Connexion au shell root...",
      "Analyse du système...",
      "Connexion au serveur distant...",
      "⚠️ Activité réseau suspecte détectée !",
      "Téléchargement de fichier : exploit.exe",
      "Extraction des données personnelles...",
      "Fichiers envoyés vers : http://hacked.unknown/upload",
      "Suppression des fichiers locaux...",
      "",
      "!!! SYSTEME COMPROMIS !!!",
      "!!! DONNÉES VOLÉES !!!"
    ];

    const consoleEl = document.getElementById('console');
    let index = 0;

    function printNextLine() {
      if (index < lines.length) {
        const line = lines[index];
        const delay = 300 + Math.random() * 200;

        let html = line;
        if (line.includes("⚠️") || line.includes("!!!")) {
          html = `<span class="red">${line}</span>`;
        }

        consoleEl.innerHTML += html + '\n';
        index++;
        setTimeout(printNextLine, delay);
      }
    }

    function showRealMessage() {
      // Remet un style normal
      document.body.style.backgroundColor = "#f0f0f0";
      document.body.style.color = "#222";
      document.body.style.fontFamily = "Arial, sans-serif";
      document.body.style.padding = "40px";

      // Supprime le contenu précédent
      consoleEl.remove();

      // Crée le message final
      const message = document.createElement('div');
      message.innerHTML = `
        <h1>🎓 Sensibilisation au phishing</h1>
        <p>Ce que vous venez de voir est une <strong>simulation pédagogique</strong>.</p>
        <p>Elle avait pour but de vous sensibiliser aux dangers des liens suspects, souvent reçus par email ou messagerie interne.</p>
        <h2>🛡️ Quelques conseils pour éviter les pièges :</h2>
        <ul>
          <li>Ne cliquez jamais sur un lien étrange ou inattendu.</li>
          <li>Vérifiez toujours l'adresse de l'expéditeur d'un email.</li>
          <li>Ne fournissez jamais vos mots de passe en ligne, même sous pression.</li>
          <li>Signalez toute suspicion à votre responsable ou au service informatique.</li>
        </ul>
        <p><strong>Merci pour votre vigilance !</strong></p>
  
       <p>Aucun utilisateur n'a été piégé. 🎉</p>
		<div><p>Équipe du système d'information à Bron</p></div>
		<div class="inline-block valignmiddle">
			<img style="max-height: 80px; max-width: 200px;" src="<?php echo DOL_URL_ROOT; ?>/custom/quiz/img/OPTIM-Industries_Logo_fondblanc_small.jpg" alt="Logo">
			</div>
      `;
      document.body.appendChild(message);
    }

    // Lancer la simulation
    setTimeout(printNextLine, 500);

    // Afficher le vrai message après 30 secondes
    setTimeout(showRealMessage, 15000);
  </script>

  	

<table>
    <thead>
        <tr>
            <th>Utilisateur</th>
            <th>Date/Heure</th>
            <th>URL suspecte</th>
        </tr>
    </thead>
    <tbody>
    <?php
        $username = trim($username ?? 'Inconnu');
        $datetime = trim(date('Y-m-d H:i:s') ?? 'Date inconnue');

        // URL piégée pour la démonstration
        $phishUrl = "https://preprod-dolibarr.optim-industries.fr/erp/public/recruitment/PlaningConges.php?ref=test";

        print "<tr>";
        print "<td class='danger'>" . htmlspecialchars($username) . "</td>";
        print "<td>" . htmlspecialchars($datetime) . "</td>";
        print "<td><a href='" . htmlspecialchars($phishUrl) . "'>" . htmlspecialchars($phishUrl) . "</a></td>";
        print "</tr>";
	
	?>
	
	
</body>
</html>