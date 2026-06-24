<?php
/**
 * GentleTest — handler form prenotazione.
 * Riceve nome, telefono, zona e li invia via email al centro.
 * Deploy: GoDaddy (hosting con PHP). Sta in /public quindi finisce nella docroot.
 *
 * TODO [DA CONFERMARE prima del lancio]:
 *  - $to: email reale del centro a cui arrivano i lead.
 *  - Verificare che l'hosting GoDaddy supporti mail() (cPanel/Linux: si').
 *    In alternativa: servizio form (Formspree) o SMTP.
 */

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /test');
    exit;
}

// Honeypot anti-spam: se compilato, scarta in silenzio.
if (!empty($_POST['website'])) {
    header('Location: /grazie');
    exit;
}

$nome     = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
$zona     = isset($_POST['zona']) ? trim($_POST['zona']) : '';

// Validazione minima
if ($nome === '' || $telefono === '') {
    header('Location: /test#prenota');
    exit;
}

// Pulizia per l'email
$nome     = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
$telefono = htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8');
$zona     = htmlspecialchars($zona, ENT_QUOTES, 'UTF-8');

$to      = 'info@gentlebeam.it';
$subject = 'Nuova richiesta GentleTest';
$body    = "Nuova richiesta di GentleTest dal sito:\n\n"
         . "Nome: {$nome}\n"
         . "Telefono: {$telefono}\n"
         . "Zona: {$zona}\n";
$headers = "From: sito@gentletest.it\r\n"
         . "Reply-To: sito@gentletest.it\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n";

@mail($to, $subject, $body, $headers);

// Reindirizza alla pagina di ringraziamento
header('Location: /grazie');
exit;
