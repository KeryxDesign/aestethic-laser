<?php
/**
 * GentleTest — handler form prenotazione.
 *  1) Notifica immediata a info@gentlebeam.it + pillitterigregorio@gmail.com
 *  2) Iscrive il lead a Kit (subscriber + campi telefono/zona + tag + sequenza welcome)
 * Chiave Kit iniettata in kit_key.php durante il deploy (mai nel repo).
 */

$NOTIFY = ['info@gentlebeam.it', 'pillitterigregorio@gmail.com'];
$KIT_TAG_ID = 20606763;   // "Lead - GentleTest"
$KIT_SEQ_ID = 2805175;    // "GentleTest - Benvenuto" (incentive/welcome)

$KIT_KEY = '';
$keyFile = __DIR__ . '/kit_key.php';
if (is_readable($keyFile)) { $KIT_KEY = trim((string) (include $keyFile)); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /gentletest/'); exit; }
if (!empty($_POST['website'])) { header('Location: /gentletest/grazie'); exit; }

$nome     = trim($_POST['nome'] ?? '');
$email    = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$zona     = trim($_POST['zona'] ?? '');

if ($nome === '' || $telefono === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /gentletest/#prenota');
    exit;
}

// --- 1) Notifica al centro ---
$body = "Nuova richiesta di GentleTest dal sito:\n\n"
      . "Nome: " . htmlspecialchars($nome) . "\n"
      . "Email: " . htmlspecialchars($email) . "\n"
      . "Telefono: " . htmlspecialchars($telefono) . "\n"
      . "Zona: " . htmlspecialchars($zona) . "\n";
$headers = "From: GentleTest <sito@gentlebeam.it>\r\n"
         . "Reply-To: " . htmlspecialchars($email) . "\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n";
@mail(implode(',', $NOTIFY), 'Nuova richiesta GentleTest', $body, $headers);

// --- 2) Kit: subscriber + tag + sequenza welcome ---
if ($KIT_KEY !== '') {
    kit_post('https://api.kit.com/v4/subscribers', $KIT_KEY,
        ['email_address' => $email, 'first_name' => $nome, 'fields' => ['telefono' => $telefono, 'zona' => $zona]]);
    kit_post('https://api.kit.com/v4/tags/' . $KIT_TAG_ID . '/subscribers', $KIT_KEY,
        ['email_address' => $email]);
    if ($KIT_SEQ_ID > 0) {
        kit_post('https://api.kit.com/v4/sequences/' . $KIT_SEQ_ID . '/subscribers', $KIT_KEY,
            ['email_address' => $email]);
    }
}

header('Location: /gentletest/grazie');
exit;

/** POST JSON a Kit. Usa curl, con fallback file_get_contents. Best-effort. */
function kit_post($url, $key, $payload) {
    $json = json_encode($payload);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'X-Kit-Api-Key: ' . $key],
        ]);
        curl_exec($ch);
        curl_close($ch);
        return;
    }
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAccept: application/json\r\nX-Kit-Api-Key: " . $key . "\r\n",
        'content' => $json,
        'timeout' => 10,
        'ignore_errors' => true,
    ]]);
    @file_get_contents($url, false, $ctx);
}
