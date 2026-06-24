<?php
/**
 * GentleTest — handler form prenotazione.
 *  1) Notifica immediata a info@gentlebeam.it + pillitterigregorio@gmail.com
 *  2) Iscrive il lead a Kit (subscriber + campi telefono/zona + tag "Lead - GentleTest")
 *
 * La chiave Kit NON sta nel repo (pubblico): viene iniettata in kit_key.php
 * durante il deploy (GitHub Actions secret). PHP server-side: la chiave non
 * raggiunge mai il browser.
 *
 * Deploy: Hostinger (PHP + mail()). Sta in /gentletest sul server.
 */

// --- config non segreta ---
$NOTIFY = ['info@gentlebeam.it', 'pillitterigregorio@gmail.com'];
$KIT_TAG_ID = 20606763; // "Lead - GentleTest"
$KIT_SEQ_ID = 2805175; // "GentleTest - Benvenuto" (incentive/welcome mail immediata)

// --- chiave Kit (iniettata in deploy, mai nel repo) ---
$KIT_KEY = '';
$keyFile = __DIR__ . '/kit_key.php';
if (is_readable($keyFile)) { $KIT_KEY = (string) (include $keyFile); }

// --- solo POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /gentletest/');
    exit;
}

// --- honeypot anti-spam ---
if (!empty($_POST['website'])) {
    header('Location: /gentletest/grazie');
    exit;
}

$nome     = trim($_POST['nome'] ?? '');
$email    = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$zona     = trim($_POST['zona'] ?? '');

// --- validazione minima ---
if ($nome === '' || $telefono === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /gentletest/#prenota');
    exit;
}

// --- 1) Notifica immediata al centro ---
$nomeS = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
$emailS = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$telS = htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8');
$zonaS = htmlspecialchars($zona, ENT_QUOTES, 'UTF-8');

$subject = 'Nuova richiesta GentleTest';
$body    = "Nuova richiesta di GentleTest dal sito:\n\n"
         . "Nome: {$nomeS}\n"
         . "Email: {$emailS}\n"
         . "Telefono: {$telS}\n"
         . "Zona: {$zonaS}\n\n"
         . "Richiamala il prima possibile.\n";
$headers = "From: GentleTest <sito@gentlebeam.it>\r\n"
         . "Reply-To: {$emailS}\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n";

@mail(implode(',', $NOTIFY), $subject, $body, $headers);

// --- 2) Iscrizione a Kit (best-effort, non blocca il redirect) ---
if ($KIT_KEY !== '') {
    // crea/aggiorna subscriber con campi custom
    kit_post('https://api.kit.com/v4/subscribers', $KIT_KEY, [
        'email_address' => $email,
        'first_name'    => $nome,
        'fields'        => ['telefono' => $telefono, 'zona' => $zona],
    ]);
    // applica il tag lead
    kit_post('https://api.kit.com/v4/tags/' . $KIT_TAG_ID . '/subscribers', $KIT_KEY, [
        'email_address' => $email,
    ]);
    // iscrive alla sequenza di benvenuto (incentive mail) se configurata
    if ($KIT_SEQ_ID > 0) {
        kit_post('https://api.kit.com/v4/sequences/' . $KIT_SEQ_ID . '/subscribers', $KIT_KEY, [
            'email_address' => $email,
        ]);
    }
}

header('Location: /gentletest/grazie');
exit;

function kit_post($url, $key, $payload) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Kit-Api-Key: ' . $key,
        ],
    ]);
    curl_exec($ch);
    curl_close($ch);
}
