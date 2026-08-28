<?php
/**
 * send_rsvp.php
 * Riceve i dati del form di conferma presenza (RSVP) e invia una email
 * di notifica agli sposi. Risponde in JSON per l'AJAX del frontend.
 *
 * CONFIGURAZIONE: modifica solo le costanti qui sotto se necessario.
 */

header('Content-Type: application/json; charset=utf-8');

// ---------------------------------------------------------------
// CONFIGURAZIONE
// ---------------------------------------------------------------
const TO_EMAIL      = 'goemontero@gmail.com'; // indirizzo che riceve le conferme
const EMAIL_SUBJECT = 'Nuova conferma RSVP - Matrimonio Simona & Marco';
const SPOSI_NAMES    = 'Simona & Marco';

// ---------------------------------------------------------------
// UTILITY
// ---------------------------------------------------------------

function respond(bool $success, string $message): void
{
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

function clean(string $value): string
{
    $value = trim($value);
    $value = str_replace(["\r", "\n"], ' ', $value); // anti header-injection
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------------
// SOLO RICHIESTE POST
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Metodo non consentito.');
}

// ---------------------------------------------------------------
// LETTURA E VALIDAZIONE CAMPI
// ---------------------------------------------------------------
$fullname   = clean($_POST['fullname'] ?? '');
$guests     = (int)($_POST['guests'] ?? 0);
$children   = (int)($_POST['children'] ?? 0);
$attendance = clean($_POST['attendance'] ?? '');
$allergie   = clean($_POST['allergie'] ?? '');
$message    = clean($_POST['message'] ?? '');

// Restrizioni alimentari (checkbox multipli)
$dietRaw = $_POST['diet'] ?? [];
$dietLabels = [
    'vegetariano'     => 'Vegetariano',
    'vegano'          => 'Vegano',
    'senza_glutine'   => 'Senza glutine',
    'senza_lattosio'  => 'Senza lattosio',
];
$diet = [];
if (is_array($dietRaw)) {
    foreach ($dietRaw as $d) {
        $d = clean($d);
        if (isset($dietLabels[$d])) {
            $diet[] = $dietLabels[$d];
        }
    }
}
$dietText = count($diet) > 0 ? implode(', ', $diet) : 'Nessuna';

$attendanceLabels = [
    'partecipo'     => 'Partecipa',
    'non_partecipo' => 'Non partecipa',
    'forse'         => 'Non può ancora confermare',
];
$attendanceText = $attendanceLabels[$attendance] ?? 'Non specificato';

// Validazione obbligatori
if ($fullname === '') {
    respond(false, 'Il nome e cognome sono obbligatori.');
}
if ($guests < 1) {
    respond(false, 'Indica in quante persone sarete.');
}
if (!array_key_exists($attendance, $attendanceLabels)) {
    respond(false, 'Seleziona una risposta di partecipazione valida.');
}

// ---------------------------------------------------------------
// COSTRUZIONE MESSAGGIO EMAIL
// ---------------------------------------------------------------
$body  = "Nuova conferma di partecipazione al matrimonio di " . SPOSI_NAMES . "\n";
$body .= str_repeat('-', 50) . "\n\n";
$body .= "Nome e Cognome: {$fullname}\n";
$body .= "Numero totale ospiti: {$guests}\n";
$body .= "Di cui bambini: {$children}\n";
$body .= "Partecipazione: {$attendanceText}\n";
$body .= "Restrizioni alimentari: {$dietText}\n";
$body .= "Altre allergie/note: " . ($allergie !== '' ? $allergie : 'Nessuna') . "\n\n";
$body .= "Messaggio per gli sposi:\n";
$body .= ($message !== '' ? $message : '(nessun messaggio)') . "\n\n";
$body .= str_repeat('-', 50) . "\n";
$body .= 'Inviato il ' . date('d/m/Y H:i:s') . "\n";

// ---------------------------------------------------------------
// INVIO EMAIL
// ---------------------------------------------------------------
$headers   = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'From: Sito Matrimonio <noreply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . '>';

$subjectEncoded = '=?UTF-8?B?' . base64_encode(EMAIL_SUBJECT) . '?=';

$sent = @mail(TO_EMAIL, $subjectEncoded, $body, implode("\r\n", $headers));

if ($sent) {
    respond(true, 'Grazie ' . $fullname . '! La tua conferma è stata inviata con successo.');
} else {
    // Fallback: salva comunque la richiesta su file locale così non si perde nulla
    $logLine = date('Y-m-d H:i:s') . " | {$fullname} | {$guests} ospiti | {$children} bambini | {$attendanceText} | Diete: {$dietText} | Allergie: {$allergie} | Messaggio: {$message}" . PHP_EOL;
    @file_put_contents(__DIR__ . '/rsvp_log.txt', $logLine, FILE_APPEND | LOCK_EX);

    respond(true, 'Grazie ' . $fullname . '! La tua conferma è stata registrata.');
}
