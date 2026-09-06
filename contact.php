<?php
// Copy this file beside index.html on a PHP-enabled host.
declare(strict_types=1);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function reply(int $code, array $body): never
{
    http_response_code($code);
    echo json_encode($body);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(405, ['success' => false, 'error' => 'Method not allowed.']);
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 20000) reply(413, ['success' => false, 'error' => 'Message is too large.']);
foreach (['name', 'email', 'message', 'service', 'company', 'privacy_consent'] as $key) if (isset($_POST[$key]) && !is_string($_POST[$key])) reply(400, ['success' => false, 'error' => 'Invalid input.']);
if (trim($_POST['company'] ?? '') !== '') reply(400, ['success' => false, 'error' => 'Unable to send this request.']);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');
$service = trim($_POST['service'] ?? '');
if (strlen($name) < 2 || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email) || strlen($message) < 10 || strlen($message) > 8000 || ($_POST['privacy_consent'] ?? '') !== '1' || !in_array($service, ['Google Ads', 'Remarketing', 'Not sure yet'], true)) reply(422, ['success' => false, 'error' => 'Please check the fields and your privacy consent.']);
// This accepts a strictly JSON object in a fixed JS assignment, never executes JS.
$source = @file_get_contents(__DIR__ . '/config/config.js');
if (!$source || !preg_match('/\A\s*window\.SiteConfig\s*=\s*(\{.*\})\s*;?\s*\z/s', $source, $matches)) reply(503, ['success' => false, 'error' => 'Contact settings are unavailable.']);
$config = json_decode($matches[1], true);
$recipient = $config['email'] ?? '';
if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || str_ends_with($recipient, '.example') || preg_match('/[\r\n]/', $recipient)) reply(503, ['success' => false, 'error' => 'Email delivery has not been configured.']);
$domain = substr(strrchr($recipient, '@'), 1);
$body = "Name: $name\nEmail: $email\nService: $service\nPrivacy consent: yes\n\n$message";
$headers = ['From' => 'website@' . $domain, 'Reply-To' => $email, 'Content-Type' => 'text/plain; charset=UTF-8'];
if (!@mail($recipient, 'Website enquiry — ' . $service, $body, $headers)) reply(502, ['success' => false, 'error' => 'Unable to send your message. Please try again later.']);
reply(200, ['success' => true]);
