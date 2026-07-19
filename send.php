<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/php/PHPMailer/Exception.php';
require __DIR__ . '/php/PHPMailer/PHPMailer.php';
require __DIR__ . '/php/PHPMailer/SMTP.php';

$config = require __DIR__ . '/php/mail-config.php';

$lang = (($_POST['lang'] ?? '') === 'en') ? 'en' : 'hr';
$back = $lang === 'en' ? 'en.html' : 'index.html';
$anchor = $lang === 'en' ? '#contact' : '#kontakt';

function redirect_back(string $back, string $status, string $anchor): void
{
    header('Location: ' . $back . '?sent=' . $status . $anchor);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . $back . $anchor);
    exit;
}

// Honeypot: humans never see this field. If filled, pretend success.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    redirect_back($back, '1', $anchor);
}

// One message per 30 seconds per session.
session_start();
if (isset($_SESSION['last_send']) && time() - (int) $_SESSION['last_send'] < 30) {
    redirect_back($back, '0', $anchor);
}

// Single-line fields: trim, strip CR/LF (header-injection guard), cap length.
function field(string $key, int $max): string
{
    $v = trim((string) ($_POST[$key] ?? ''));
    $v = str_replace(["\r", "\n"], ' ', $v);
    return mb_substr($v, 0, $max);
}

$name      = field('name', 100);
$email     = field('email', 200);
$phone     = field('phone', 40);
$arrival   = field('arrival', 20);
$departure = field('departure', 20);
$guests    = field('guests', 5);
$message   = mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 5000);

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_back($back, '0', $anchor);
}

$lines = [
    'Ime:     ' . $name,
    'E-mail:  ' . $email,
];
if ($phone !== '') {
    $lines[] = 'Telefon: ' . $phone;
}
if ($arrival !== '') {
    $lines[] = 'Dolazak: ' . $arrival;
}
if ($departure !== '') {
    $lines[] = 'Odlazak: ' . $departure;
}
if ($guests !== '') {
    $lines[] = 'Osoba:   ' . $guests;
}
$lines[] = '';
$lines[] = $message;

$mail = new PHPMailer(true);

try {
    if (!empty($config['smtp_host'])) {
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->Port       = (int) $config['smtp_port'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_user'];
        $mail->Password   = $config['smtp_pass'];
        $mail->SMTPSecure = $config['smtp_secure'] === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->CharSet = 'UTF-8';
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['to_email']);
    $mail->addReplyTo($email, $name);
    $mail->Subject = ($lang === 'en' ? 'Website inquiry — ' : 'Upit s weba — ') . $name;
    $mail->Body    = implode("\n", $lines);

    $mail->send();
    $_SESSION['last_send'] = time();
    redirect_back($back, '1', $anchor);
} catch (Exception $e) {
    redirect_back($back, '0', $anchor);
}
