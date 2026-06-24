<?php
/**
 * GentleTest — handler form prenotazione.
 * Riceve nome, telefono, zona e li invia via email al centro.
 * Deploy: Hostinger (hosting con PHP). Sta in /public quindi finisce nella docroot.
 * Hostinger supporta mail() di default.
 */

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /gentletest/');
    exit;
}

// Honeypot anti-spam: se compilato, scarta in silenzio.
if (!empty($_POST['website'])) {
    header('Location: /gentletest/grazie');
    exit;
}

$nome     = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
$zona     = isset($_POST['zona']) ? trim($_POST['zona']) : '';

// Validazione minima
if ($nome === '' || $telefono === '') {
    header('Location: /gentletest/#prenota');
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
$headers = "From: sito@gentlebeam.it\r\n"
         . "Reply-To: sito@gentlebeam.it\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n";

@mail($to, $subject, $body, $headers);

// Reindirizza alla pagina di ringraziamento
header('Location: /gentletest/grazie');
exit;
