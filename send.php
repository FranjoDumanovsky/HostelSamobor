<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

// This script answers with a redirect or a one-line JSON body, so no stray
// output is allowed: a single notice reaching the output buffer makes header()
// fail and corrupts the JSON. Log everything, display nothing.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// TEMP DIAGNOSTIC — pin the error log to a known path. Remove once working.
// php/.htaccess denies HTTP access to this directory, so the log is not public.
ini_set('error_log', __DIR__ . '/php/diag.log');

require __DIR__ . '/php/PHPMailer/Exception.php';
require __DIR__ . '/php/PHPMailer/PHPMailer.php';
require __DIR__ . '/php/PHPMailer/SMTP.php';

$config = require __DIR__ . '/php/mail-config.php';

$lang = (($_POST['lang'] ?? '') === 'en') ? 'en' : 'hr';
$back = $lang === 'en' ? 'en.html' : 'index.html';
$anchor = $lang === 'en' ? '#contact' : '#kontakt';

// The page submits with fetch() and asks for JSON, so it can show the result
// without reloading. A no-JS submit sends the usual Accept: text/html and still
// gets the redirect, so the form keeps working either way.
define('WANTS_JSON', strpos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false);

function redirect_back(string $back, string $status, string $anchor): void
{
    if (WANTS_JSON) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $status === '1']);
        exit;
    }

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
    error_log('MAILFAIL: rate limited'); // TEMP DIAGNOSTIC
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
    error_log('MAILFAIL: validation'); // TEMP DIAGNOSTIC
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

$subject = ($lang === 'en' ? 'Website inquiry — ' : 'Upit s weba — ') . $name;
$body    = implode("\n", $lines);

/**
 * Send over Resend's HTTPS API instead of SMTP.
 *
 * This host firewalls outbound SMTP — ports 25 and 587 are redirected to its
 * own Exim and 465 is refused outright (verified 2026-07-31), so no external
 * mail server is reachable. Port 443 is obviously open, so the transactional
 * API works where SMTP cannot.
 *
 * Returns [success, detail-for-the-log].
 */
function send_via_api(array $config, string $subject, string $body, string $replyEmail, string $replyName): array
{
    // Resend wants From as a single RFC 5322 string. Quote the display name so
    // a comma or diacritic in it cannot break address parsing.
    $payload = json_encode([
        'from'     => '"' . str_replace('"', '', $config['from_name']) . '" <' . $config['from_email'] . '>',
        'to'       => [$config['to_email']],
        'reply_to' => $replyEmail,
        'subject'  => $subject,
        'text'     => $body,
    ], JSON_UNESCAPED_UNICODE);

    $url     = 'https://api.resend.com/emails';
    $headers = [
        'Authorization: Bearer ' . $config['api_key'],
        'Content-Type: application/json',
    ];

    // cURL is not enabled on every shared host, so fall back to a stream POST,
    // which only needs allow_url_fopen and openssl.
    if (function_exists('curl_init')) {
        [$status, $response, $err] = post_via_curl($url, $headers, $payload);
    } elseif (ini_get('allow_url_fopen')) {
        [$status, $response, $err] = post_via_stream($url, $headers, $payload);
    } else {
        return [false, 'no HTTP transport: curl missing and allow_url_fopen disabled'];
    }

    // Resend answers 200 with {"id":"..."} on success.
    $ok = $status >= 200 && $status < 300;

    return [$ok, 'HTTP ' . $status . ($err !== '' ? ' err: ' . $err : '') . ' body: ' . mb_substr($response, 0, 300)];
}

/** Returns [status, body, error]. */
function post_via_curl(string $url, array $headers, string $payload): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => $payload,
    ]);

    $response = (string) curl_exec($ch);
    $status   = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $err      = curl_error($ch);
    // No curl_close(): it has been a no-op since PHP 8.0 and is deprecated in
    // 8.5. Calling it emits a notice, and any output breaks the redirect below.

    return [$status, $response, $err];
}

/** Returns [status, body, error]. */
function post_via_stream(string $url, array $headers, string $payload): array
{
    $context = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => implode("\r\n", $headers),
            'content'       => $payload,
            'timeout'       => 15,
            // Without this, a 4xx makes file_get_contents return false and the
            // API's error message — the useful part — is thrown away.
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $last = error_get_last();
        return [0, '', $last['message'] ?? 'stream request failed'];
    }

    // $http_response_header is populated by the stream wrapper.
    $status = 0;
    foreach ($http_response_header ?? [] as $line) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
            $status = (int) $m[1];
        }
    }

    return [$status, $response, ''];
}

// API is the primary path. With no key configured, fall through to PHPMailer,
// which hands the message to the server's local Exim via mail().
if (!empty($config['api_key'])) {
    [$ok, $detail] = send_via_api($config, $subject, $body, $email, $name);

    if ($ok) {
        $_SESSION['last_send'] = time();
        redirect_back($back, '1', $anchor);
    }

    error_log('MAILFAIL: api — ' . $detail); // TEMP DIAGNOSTIC
    redirect_back($back, '0', $anchor);
}

$mail = new PHPMailer(true);

// TEMP DIAGNOSTIC — remove once the form is confirmed working.
$mail->SMTPDebug   = 2;
$mail->Debugoutput = static function (string $str, int $level): void {
    error_log('SMTPDEBUG: ' . trim($str));
};

try {
    if (!empty($config['smtp_host'])) {
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->Port       = (int) $config['smtp_port'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_user'];
        $mail->Password   = $config['smtp_pass'];
        // 'none' is for localhost only: this host's TLS cert is issued for its
        // own hostname, so STARTTLS to 'localhost' always fails CN validation.
        // Loopback traffic never leaves the machine, so plaintext is fine there.
        if ($config['smtp_secure'] === 'none') {
            $mail->SMTPSecure  = '';
            $mail->SMTPAutoTLS = false;
        } else {
            $mail->SMTPSecure = $config['smtp_secure'] === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
        }
    }

    $mail->CharSet = 'UTF-8';
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['to_email']);
    $mail->addReplyTo($email, $name);
    $mail->Subject = $subject;
    $mail->Body    = $body;

    $mail->send();
    $_SESSION['last_send'] = time();
    redirect_back($back, '1', $anchor);
} catch (Exception $e) {
    // TEMP DIAGNOSTIC — remove once the form is confirmed working.
    error_log('MAILFAIL: ' . $e->getMessage() . ' | ErrorInfo: ' . $mail->ErrorInfo);
    redirect_back($back, '0', $anchor);
}
