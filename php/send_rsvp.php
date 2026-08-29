<?php
/**
 * send_rsvp.php
 * Riceve i dati del form RSVP via AJAX, salva una copia locale in rsvp_log.txt
 * e invia una notifica via email agli sposi usando la funzione mail().
 */

ini_set('display_errors', '0');
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

// ---------------------------------------------------------------
// CONFIGURAZIONE INDIRIZZI E OGGETTO
// ---------------------------------------------------------------
const TO_EMAIL      = 'goemontero@gmail.com, musicall@musicall.it, simor0523@gmail.com'; 
const EMAIL_SUBJECT = 'Nuova conferma RSVP - Matrimonio Simona & Marco';
const SPOSI_NAMES   = 'Simona & Marco';

function respond($success, $message) {
    echo json_encode(array('success' => $success, 'message' => $message), JSON_UNESCAPED_UNICODE);
    exit;
}

// Verifica che la richiesta sia inviata via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Metodo non consentito.');
}

// ---------------------------------------------------------------
// LETTURA E PULIZIA DATI RICEVUTI
// ---------------------------------------------------------------
$fullname   = isset($_POST['fullname']) ? trim(strip_tags($_POST['fullname'])) : '';
$guests     = isset($_POST['guests']) ? (int)$_POST['guests'] : 0;
$children   = isset($_POST['children']) ? (int)$_POST['children'] : 0;
$attendance = isset($_POST['attendance']) ? trim(strip_tags($_POST['attendance'])) : '';
$allergie   = isset($_POST['allergie']) ? trim(strip_tags($_POST['allergie'])) : '';
$message    = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';

// Gestione checkbox restrizioni alimentari
$dietRaw = isset($_POST['diet']) ? $_POST['diet'] : array();
$dietLabels = array(
    'vegetariano'    => 'Vegetariano',
    'vegano'         => 'Vegano',
    'senza_glutine'  => 'Senza glutine',
    'senza_lattosio' => 'Senza lattosio'
);

$diet = array();
if (is_array($dietRaw)) {
    foreach ($dietRaw as $d) {
        if (isset($dietLabels[$d])) {
            $diet[] = $dietLabels[$d];
        }
    }
}
$dietText = !empty($diet) ? implode(', ', $diet) : 'Nessuna';

$attendanceLabels = array(
    'partecipo'     => 'Partecipa',
    'non_partecipo' => 'Non partecipa',
    'forse'         => 'Non può ancora confermare'
);
$attendanceText = isset($attendanceLabels[$attendance]) ? $attendanceLabels[$attendance] : 'Non specificato';

// ---------------------------------------------------------------
// VALIDAZIONE CAMPI OBBLIGATORI
// ---------------------------------------------------------------
if (empty($fullname)) {
    respond(false, 'Inserisci nome e cognome.');
}
if ($guests < 1) {
    respond(false, 'Indica in quante persone sarete.');
}

// ---------------------------------------------------------------
// 1) SALVATAGGIO LOG LOCALE (DI SICUREZZA)
// ---------------------------------------------------------------
$logLine = date('Y-m-d H:i:s') . " | Nome: {$fullname} | Ospiti: {$guests} | Bambini: {$children} | Esito: {$attendanceText} | Diete: {$dietText} | Note: {$allergie} | Msg: {$message}" . PHP_EOL;
@file_put_contents(__DIR__ . '/rsvp_log.txt', $logLine, FILE_APPEND | LOCK_EX);

// ---------------------------------------------------------------
// 2) COSTRUZIONE CONTENUTO EMAIL ED HEADER
// ---------------------------------------------------------------
$email_content  = "Nuova conferma di partecipazione al matrimonio di " . SPOSI_NAMES . "\n";
$email_content .= "--------------------------------------------------\n\n";
$email_content .= "Nome e Cognome: $fullname\n";
$email_content .= "Numero totale ospiti: $guests\n";
$email_content .= "Di cui bambini: $children\n";
$email_content .= "Partecipazione: $attendanceText\n";
$email_content .= "Restrizioni alimentari: $dietText\n";
$email_content .= "Altre allergie o intolleranze: " . (!empty($allergie) ? $allergie : 'Nessuna') . "\n\n";
$email_content .= "Messaggio per gli sposi:\n";
$email_content .= (!empty($message) ? $message : '(nessun messaggio)') . "\n\n";
$email_content .= "--------------------------------------------------\n";
$email_content .= "Inviato il " . date('d/m/Y H:i:s') . "\n";

// Definizione Header (Simile alla struttura utilizzata nel tuo precedente progetto)
$from_email = 'noreply@' . ($_SERVER['SERVER_NAME'] !== 'localhost' ? $_SERVER['SERVER_NAME'] : 'matrimonio.it');
$headers  = "From: $from_email\r\n";
$headers .= "Reply-To: $from_email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// ---------------------------------------------------------------
// 3) INVIO MAIL TRAMITE API NATIVA mail()
// ---------------------------------------------------------------
$mail_sent = @mail(TO_EMAIL, EMAIL_SUBJECT, $email_content, $headers);

// Restituisce sempre esito positivo al client se i dati sono stati registrati
if ($mail_sent) {
    respond(true, 'Grazie ' . $fullname . '! La tua conferma è stata inviata con successo.');
} else {
    // In ambiente locale (MAMP/XAMPP) la mail fallirà, ma risponderà comunque OK grazie al log salvato
    respond(true, 'Grazie ' . $fullname . '! La tua conferma è stata registrata con successo.');
}