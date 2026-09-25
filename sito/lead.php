<?php
/**
 * GentleTest — handler form prenotazione.
 *  1) Notifica immediata a info@gentlebeam.it + pillitterigregorio@gmail.com
 *  2) Iscrive il lead a Kit (subscriber + campo telefono + tag + sequenza welcome)
 *  3) Se 1) o 2) falliscono, scrive il contatto nel registro di riserva per il
 *     ricontatto a mano. Il visitatore vede sempre la pagina di ringraziamento.
 * Chiave Kit iniettata in kit_key.php durante il deploy (mai nel repo).
 */

// Nessun errore a schermo: un warning stampato qui perderebbe il contatto.
@ini_set('display_errors', '0');
@ini_set('log_errors', '0');

$NOTIFY = ['info@gentlebeam.it', 'pillitterigregorio@gmail.com'];
$KIT_TAG_ID = 20606763;   // "Lead - GentleTest"
$KIT_SEQ_ID = 2805175;    // "GentleTest - Benvenuto" (incentive/welcome)

// Registro di riserva: stessa cartella, non raggiungibile via HTTP (vedi .htaccess).
$LOG_FILE = __DIR__ . '/lead_fallback.log.php';

$KIT_KEY = '';
$keyFile = __DIR__ . '/kit_key.php';
if (is_readable($keyFile)) { $KIT_KEY = trim((string) (include $keyFile)); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /gentletest/'); exit; }
if (!empty($_POST['website'])) { header('Location: /gentletest/grazie'); exit; }

$nome     = trim($_POST['nome'] ?? '');
$email    = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$consenso = ((($_POST['marketing'] ?? '')) === 'si'); // consenso marketing facoltativo

// Spunta privacy obbligatoria: la chiedono i moduli che la mostrano, con ?privacy=richiesta
// nell'action (oggi la landing /gentletest/epilazione-laser-palermo). Il modulo di
// /gentletest/ ha solo l'informativa, senza spunta: per lui la regola non scatta.
$privacyRichiesta = ((($_GET['privacy'] ?? '')) === 'richiesta');
$privacy = ((($_POST['privacy'] ?? '')) === 'si');
$ritorno = $privacyRichiesta ? '/gentletest/epilazione-laser-palermo#prenota' : '/gentletest/#prenota';

if ($nome === '' || $telefono === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || ($privacyRichiesta && !$privacy)) {
    header('Location: ' . $ritorno);
    exit;
}

$falliti = []; // ['passo' => 'errore leggibile']

// --- 1) Notifica al centro ---
$body = "Nuova richiesta di GentleTest dal sito:\n\n"
      . "Nome: " . htmlspecialchars($nome) . "\n"
      . "Email: " . htmlspecialchars($email) . "\n"
      . "Telefono: " . htmlspecialchars($telefono) . "\n"
      . "Consenso marketing: " . ($consenso ? 'si' : 'no') . "\n"
      . ($privacyRichiesta ? "Spunta privacy: si\n" : '');
$headers = "From: GentleTest <sito@gentlebeam.it>\r\n"
         . "Reply-To: " . htmlspecialchars($email) . "\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n";
// La chiocciola zittisce il warning a schermo, il valore di ritorno si legge.
$mailOk = @mail(implode(',', $NOTIFY), 'Nuova richiesta GentleTest', $body, $headers);
if ($mailOk !== true) { $falliti['mail'] = 'mail() ha restituito false'; }

// --- 2) Kit: SOLO con consenso marketing esplicito (GDPR). Il ricontatto per
//        l'appuntamento avviene comunque via la notifica email qui sopra. ---
if ($KIT_KEY !== '' && $consenso) {
    $r = kit_post('https://api.kit.com/v4/subscribers', $KIT_KEY,
        ['email_address' => $email, 'first_name' => $nome,
         'fields' => ['telefono' => $telefono,
                      'consenso_marketing' => 'si - ' . date('Y-m-d H:i') . ' - ' . ($_SERVER['REMOTE_ADDR'] ?? '')]]);
    if (!$r['ok']) { $falliti['kit_subscriber'] = kit_errore($r); }

    $r = kit_post('https://api.kit.com/v4/tags/' . $KIT_TAG_ID . '/subscribers', $KIT_KEY,
        ['email_address' => $email]);
    if (!$r['ok']) { $falliti['kit_tag'] = kit_errore($r); }

    if ($KIT_SEQ_ID > 0) {
        $r = kit_post('https://api.kit.com/v4/sequences/' . $KIT_SEQ_ID . '/subscribers', $KIT_KEY,
            ['email_address' => $email]);
        if (!$r['ok']) { $falliti['kit_sequenza'] = kit_errore($r); }
    }
} elseif ($consenso && $KIT_KEY === '') {
    $falliti['kit'] = 'chiave Kit assente sul server';
}

// --- 3) Registro di riserva: solo se qualcosa e' fallito ---
if ($falliti) {
    registra_fallimento($LOG_FILE, $falliti, $nome, $email, $telefono, $consenso, $KIT_KEY);
}

header('Location: /gentletest/grazie');
exit;

/** Errore Kit in forma leggibile e corta (mai la chiave: qui non passa). */
function kit_errore($r) {
    $msg = 'HTTP ' . $r['status'];
    if ($r['error'] !== '') { $msg .= ' - ' . $r['error']; }
    if ($r['body'] !== '')  { $msg .= ' - ' . $r['body']; }
    return $msg;
}

/**
 * Registro di riserva. Contiene il minimo per ricontattare a mano la persona:
 * quando, quale passo e' fallito, l'errore, e i campi del modulo. Nient'altro.
 * Il file nasce con un tappo PHP: anche se il blocco <Files> saltasse, servirlo
 * via HTTP non stampa nulla.
 */
function registra_fallimento($file, $falliti, $nome, $email, $telefono, $consenso, $key) {
    $riga = [
        'ts'       => date('c'),
        'falliti'  => $falliti,
        'nome'     => $nome,
        'email'    => $email,
        'telefono' => $telefono,
        'consenso_marketing' => $consenso ? 'si' : 'no',
    ];
    $testo = json_encode($riga, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($testo === false) { return; }
    // Cintura: la chiave non deve comparire nel registro in nessun ramo.
    if ($key !== '' && strpos($testo, $key) !== false) {
        $testo = str_replace($key, '[chiave-rimossa]', $testo);
    }
    $nuovo = !file_exists($file);
    // Tappo PHP in testa al file appena creato.
    $testa = $nuovo ? "<?php http_response_code(404); exit; ?>\n" : '';
    $ok = @file_put_contents($file, $testa . $testo . "\n", FILE_APPEND | LOCK_EX);
    if ($nuovo && $ok !== false) { @chmod($file, 0600); }
}

/**
 * POST JSON a Kit. Torna ['ok'=>bool, 'status'=>int, 'error'=>string, 'body'=>string].
 * Il body e' troncato a 200 caratteri: serve a capire il perche', non a fare un dump.
 */
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
        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);
        return [
            'ok'     => ($err === '' && $status >= 200 && $status < 300),
            'status' => $status,
            'error'  => $err,
            'body'   => ($status >= 200 && $status < 300) ? '' : substr((string) $body, 0, 200),
        ];
    }
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAccept: application/json\r\nX-Kit-Api-Key: " . $key . "\r\n",
        'content' => $json,
        'timeout' => 10,
        'ignore_errors' => true,
    ]]);
    $body   = @file_get_contents($url, false, $ctx);
    $status = 0;
    // PHP >= 8.4 espone la funzione; sotto resta la variabile locale (accesso
    // dinamico: il nome diretto e' deprecato nelle versioni nuove).
    $hdrs = function_exists('http_get_last_response_headers')
        ? http_get_last_response_headers()
        : (get_defined_vars()['http_response_header'] ?? null);
    if (is_array($hdrs)) {
        foreach ($hdrs as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) { $status = (int) $m[1]; }
        }
    }
    return [
        'ok'     => ($body !== false && $status >= 200 && $status < 300),
        'status' => $status,
        'error'  => ($body === false ? 'richiesta HTTP fallita' : ''),
        'body'   => ($status >= 200 && $status < 300) ? '' : substr((string) $body, 0, 200),
    ];
}
