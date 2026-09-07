<?php

declare(strict_types=1);

ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const MAX_BODY_BYTES = 20000;
const ALLOWED_SERVICES = ['Google Ads', 'Remarketing', 'Not sure yet'];

function reply(int $code, array $body): never
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function public_error(int $code, string $message): never
{
    reply($code, ['success' => false, 'error' => $message]);
}

function clean_text(string $value): string
{
    $value = str_replace(["\r\n", "\r"], "\n", trim($value));
    return preg_replace('/[ \t]+/', ' ', $value) ?? $value;
}

function has_header_injection(string $value): bool
{
    return preg_match('/[\r\n\x00]/', $value) === 1;
}

function env_value(string $key): string
{
    $value = getenv($key);
    return is_string($value) ? trim($value) : '';
}

function load_site_config(): array
{
    $source = @file_get_contents(__DIR__ . '/config/config.js');
    if (!is_string($source) || $source === '') {
        public_error(503, 'Contact settings are unavailable.');
    }

    if (!preg_match('/\A\s*window\.SiteConfig\s*=\s*(\{.*\})\s*;?\s*\z/s', $source, $matches)) {
        public_error(503, 'Contact settings are unavailable.');
    }

    $config = json_decode($matches[1], true);
    if (!is_array($config)) {
        public_error(503, 'Contact settings are unavailable.');
    }

    return $config;
}

function load_smtp_config(string $recipient): array
{
    $config = [];
    $path = env_value('CONTACT_SMTP_CONFIG_PATH');
    if ($path === '') {
        $path = dirname(__DIR__) . '/forma-smtp.php';
    }

    if (is_file($path)) {
        $loaded = require $path;
        if (is_array($loaded)) {
            $config = $loaded;
        }
    }

    $domain = substr(strrchr($recipient, '@') ?: '', 1);
    $envConfig = [
        'host' => env_value('CONTACT_SMTP_HOST'),
        'port' => env_value('CONTACT_SMTP_PORT'),
        'username' => env_value('CONTACT_SMTP_USERNAME'),
        'password' => env_value('CONTACT_SMTP_PASSWORD'),
        'encryption' => env_value('CONTACT_SMTP_ENCRYPTION'),
        'from_email' => env_value('CONTACT_SMTP_FROM_EMAIL'),
        'from_name' => env_value('CONTACT_SMTP_FROM_NAME'),
        'timeout' => env_value('CONTACT_SMTP_TIMEOUT'),
    ];

    foreach ($envConfig as $key => $value) {
        if ($value !== '') {
            $config[$key] = $value;
        }
    }

    $config['port'] = isset($config['port']) ? (int)$config['port'] : 587;
    $config['timeout'] = isset($config['timeout']) ? (int)$config['timeout'] : 20;
    $config['encryption'] = strtolower((string)($config['encryption'] ?? 'tls'));
    $config['from_email'] = (string)($config['from_email'] ?? ('website@' . $domain));
    $config['from_name'] = (string)($config['from_name'] ?? 'Website enquiry');

    if (
        empty($config['host'])
        || empty($config['username'])
        || empty($config['password'])
        || !filter_var($config['from_email'], FILTER_VALIDATE_EMAIL)
        || has_header_injection($config['from_email'])
        || has_header_injection($config['from_name'])
        || !in_array($config['encryption'], ['tls', 'ssl', 'none'], true)
        || $config['port'] < 1
        || $config['port'] > 65535
    ) {
        public_error(503, 'Email delivery is not configured yet.');
    }

    return $config;
}

function smtp_expect($socket, array $codes): string
{
    $response = '';
    do {
        $line = fgets($socket, 515);
        if ($line === false) {
            throw new RuntimeException('No SMTP response.');
        }
        $response .= $line;
    } while (isset($line[3]) && $line[3] === '-');

    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $codes, true)) {
        throw new RuntimeException('Unexpected SMTP response: ' . trim($response));
    }

    return $response;
}

function smtp_command($socket, string $command, array $codes): string
{
    fwrite($socket, $command . "\r\n");
    return smtp_expect($socket, $codes);
}

function smtp_address(string $email): string
{
    return '<' . str_replace(['<', '>', "\r", "\n"], '', $email) . '>';
}

function encode_header(string $value): string
{
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function format_mailbox(string $email, string $name = ''): string
{
    if ($name === '') {
        return $email;
    }

    return encode_header($name) . ' <' . $email . '>';
}

function smtp_send(array $smtp, string $recipient, string $subject, string $body, string $replyToEmail, string $replyToName): void
{
    $remote = ($smtp['encryption'] === 'ssl' ? 'ssl://' : '') . $smtp['host'] . ':' . $smtp['port'];
    $socket = @stream_socket_client($remote, $errno, $errstr, $smtp['timeout'], STREAM_CLIENT_CONNECT);
    if (!$socket) {
        throw new RuntimeException('SMTP connection failed: ' . $errstr);
    }

    try {
        stream_set_timeout($socket, $smtp['timeout']);
        smtp_expect($socket, [220]);

        $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
        smtp_command($socket, 'EHLO ' . preg_replace('/[^A-Za-z0-9.-]/', '', $serverName), [250]);

        if ($smtp['encryption'] === 'tls') {
            smtp_command($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Unable to start SMTP TLS.');
            }
            smtp_command($socket, 'EHLO ' . preg_replace('/[^A-Za-z0-9.-]/', '', $serverName), [250]);
        }

        smtp_command($socket, 'AUTH LOGIN', [334]);
        smtp_command($socket, base64_encode((string)$smtp['username']), [334]);
        smtp_command($socket, base64_encode((string)$smtp['password']), [235]);
        smtp_command($socket, 'MAIL FROM:' . smtp_address($smtp['from_email']), [250]);
        smtp_command($socket, 'RCPT TO:' . smtp_address($recipient), [250, 251]);
        smtp_command($socket, 'DATA', [354]);

        $headers = [
            'From: ' . format_mailbox($smtp['from_email'], (string)$smtp['from_name']),
            'To: ' . $recipient,
            'Reply-To: ' . format_mailbox($replyToEmail, $replyToName),
            'Subject: ' . encode_header($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'X-Mailer: Forma contact form',
        ];

        $message = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n", "\r\n", $body);
        $message = preg_replace('/^\./m', '..', $message) ?? $message;
        fwrite($socket, $message . "\r\n.\r\n");
        smtp_expect($socket, [250]);
        fwrite($socket, "QUIT\r\n");
    } finally {
        fclose($socket);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    public_error(405, 'Method not allowed.');
}

if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > MAX_BODY_BYTES) {
    public_error(413, 'Message is too large.');
}

foreach (['name', 'email', 'message', 'service', 'company', 'privacy_consent'] as $key) {
    if (isset($_POST[$key]) && !is_string($_POST[$key])) {
        public_error(400, 'Invalid input.');
    }
}

if (trim((string)($_POST['company'] ?? '')) !== '') {
    public_error(400, 'Unable to send this request.');
}

$name = clean_text((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$message = clean_text((string)($_POST['message'] ?? ''));
$service = clean_text((string)($_POST['service'] ?? ''));
$privacyConsent = (string)($_POST['privacy_consent'] ?? '');

if (strlen($name) < 2 || strlen($name) > 120 || has_header_injection($name)) {
    public_error(422, 'Please enter your name.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || has_header_injection($email)) {
    public_error(422, 'Please enter a valid email address.');
}

if (!in_array($service, ALLOWED_SERVICES, true) || has_header_injection($service)) {
    public_error(422, 'Please choose what we can help with.');
}

if (strlen($message) < 10 || strlen($message) > 3000 || strpos($message, "\0") !== false) {
    public_error(422, 'Please add a message between 10 and 3000 characters.');
}

if ($privacyConsent !== '1') {
    public_error(422, 'Please agree to the Privacy Policy before sending.');
}

$siteConfig = load_site_config();
$recipient = trim((string)($siteConfig['email'] ?? ''));

if (
    $recipient === ''
    || str_ends_with(strtolower($recipient), '.example')
    || !filter_var($recipient, FILTER_VALIDATE_EMAIL)
    || has_header_injection($recipient)
) {
    public_error(503, 'Email delivery is not configured yet.');
}

$smtp = load_smtp_config($recipient);
$subject = 'Website enquiry - ' . $service;
$body = implode("\n", [
    'Name: ' . $name,
    'Email: ' . $email,
    'Service: ' . $service,
    'Privacy consent: yes',
    '',
    $message,
]);

try {
    smtp_send($smtp, $recipient, $subject, $body, $email, $name);
} catch (Throwable $error) {
    error_log('[contact.php] ' . $error->getMessage());
    public_error(502, 'Unable to send your message right now. Please try again later.');
}

reply(200, ['success' => true]);
