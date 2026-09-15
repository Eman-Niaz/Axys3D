
<?php
/**
 * contact-handler.php
 *
 * Receives the contact form POST from Contact.astro, validates it on the
 * server, checks for spam, sends the email via PHPMailer/Hostinger SMTP,
 * and logs the enquiry to a file if the email fails to send so nothing
 * is lost.
 *
 * This file is NOT built by Astro — upload it directly to your Hostinger
 * public_html (or wherever your static site lives), alongside mail-config.php
 * and the PHPMailer folder.
 */

// ---------- Basic setup ----------
header('Access-Control-Allow-Origin: http://localhost:4321');
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = require __DIR__ . '/mail-config.php';

// ---------- Helper: respond with JSON and stop ----------

function respond(bool $success, string $message): void
{
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// ---------- 1. Honeypot check (silent bot trap) ----------
// The form has a hidden field named "website" that real users never fill in.
// If it has a value, silently pretend success so the bot thinks it worked,
// but do nothing further.

if (!empty($_POST['website'])) {
    respond(true, 'Thank you. Your enquiry has been received.');
}

// ---------- 2. Basic rate limiting (same IP, 60 seconds) ----------

$rateLimitFile = __DIR__ . '/logs/rate-limit.json';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

$rateData = [];
if (file_exists($rateLimitFile)) {
    $rateData = json_decode(file_get_contents($rateLimitFile), true) ?: [];
}

$now = time();
// Clean up old entries older than 5 minutes so the file doesn't grow forever
foreach ($rateData as $storedIp => $timestamp) {
    if ($now - $timestamp > 300) {
        unset($rateData[$storedIp]);
    }
}

if (isset($rateData[$ip]) && ($now - $rateData[$ip]) < 60) {
    respond(false, 'Please wait a moment before submitting again.');
}

$rateData[$ip] = $now;
file_put_contents($rateLimitFile, json_encode($rateData));

// ---------- 3. Collect and validate fields ----------

function cleanInput(string $value): string
{
    return trim(strip_tags($value));
}

$name = cleanInput($_POST['name'] ?? '');
$workEmail = cleanInput($_POST['workEmail'] ?? '');
$company = cleanInput($_POST['company'] ?? '');
$projectType = cleanInput($_POST['projectType'] ?? '');
$projectStage = cleanInput($_POST['projectStage'] ?? '');
$disciplines = cleanInput($_POST['disciplines'] ?? '');
$sourceInformation = cleanInput($_POST['sourceInformation'] ?? '');
$projectBrief = cleanInput($_POST['projectBrief'] ?? '');
$deliverables = cleanInput($_POST['deliverables'] ?? '');
$targetDate = cleanInput($_POST['targetDate'] ?? '');
$additionalInformation = cleanInput($_POST['additionalInformation'] ?? '');

$errors = [];

if ($name === '' || mb_strlen($name) > 120) {
    $errors[] = 'Please provide a valid name.';
}

if ($workEmail === '' || !filter_var($workEmail, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid work email address.';
}

if ($company === '' || mb_strlen($company) > 150) {
    $errors[] = 'Please provide a company or organization name.';
}

if ($projectType === '') {
    $errors[] = 'Please select a project type.';
}

if ($projectStage === '') {
    $errors[] = 'Please select a project stage.';
}

if ($disciplines === '') {
    $errors[] = 'Please select a discipline.';
}

if ($sourceInformation === '') {
    $errors[] = 'Please select the available source information.';
}

if ($projectBrief === '' || mb_strlen($projectBrief) < 20 || mb_strlen($projectBrief) > 4000) {
    $errors[] = 'Please provide a project brief between 20 and 4000 characters.';
}

if ($deliverables === '') {
    $errors[] = 'Please select a required deliverable.';
}

if ($targetDate === '') {
    $errors[] = 'Please provide a target date.';
}

if (mb_strlen($additionalInformation) > 4000) {
    $errors[] = 'Additional information is too long.';
}

if (!empty($errors)) {
    respond(false, implode(' ', $errors));
}

// ---------- 4. Build the email ----------

$mail = new PHPMailer(true);

$emailBody = "
New project enquiry from the Axys3D website

Name: {$name}
Work email: {$workEmail}
Company: {$company}
Project type: {$projectType}
Project stage: {$projectStage}
Discipline: {$disciplines}
Available source information: {$sourceInformation}
Required deliverable: {$deliverables}
Target date: {$targetDate}

Project brief:
{$projectBrief}

Additional information:
{$additionalInformation}
";

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_username'];
    $mail->Password = $config['smtp_password'];
    $mail->SMTPSecure = $config['smtp_secure'];
    $mail->Port = $config['smtp_port'];

    // Recipients
    $mail->setFrom($config['smtp_username'], $config['from_name']);
    $mail->addAddress($config['recipient_email'], $config['recipient_name']);
    $mail->addReplyTo($workEmail, $name);

    // Content
    $mail->isHTML(false);
    $mail->Subject = "New project enquiry — {$company}";
    $mail->Body = $emailBody;

    $mail->send();

    respond(true, 'Thank you. Your enquiry has been received — we will be in touch shortly.');
} catch (Exception $e) {
    // ---------- 5. Email failed — log it so the enquiry isn't lost ----------

    $logFile = __DIR__ . '/logs/contact-failures.log';
    $logEntry = '[' . date('Y-m-d H:i:s') . "] Mail failed: {$mail->ErrorInfo}\n" . $emailBody . "\n-----\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);

    respond(false, 'Something went wrong sending your enquiry. Please try again or email us directly.');
}