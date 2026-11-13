<?php
use PHPMailer\PHPMailer\PHPMailer;

function shopEaseLocalMailSettings(): array
{
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }

    $settingsFile = __DIR__ . '/mail.local.php';
    if (!is_file($settingsFile)) {
        $settings = [];
        return $settings;
    }

    $loadedSettings = require $settingsFile;
    if (!is_array($loadedSettings)) {
        throw new RuntimeException('mail.local.php must return an array of mail settings.');
    }

    foreach ($loadedSettings as $name => $value) {
        if (!is_string($name) || !is_string($value)) {
            throw new RuntimeException('Every mail.local.php setting must have a string name and value.');
        }
    }

    $settings = $loadedSettings;
    return $settings;
}

function shopEaseRequiredEnv(string $name): string
{
    $value = shopEaseOptionalEnv($name);
    if ($value === false || trim($value) === '') {
        throw new RuntimeException('Required mail setting is missing: ' . $name);
    }

    return trim($value);
}

function shopEaseOptionalEnv(string $name): string|false
{
    $value = getenv($name);
    if ($value !== false && trim($value) !== '') {
        return trim($value);
    }

    if (isset($_SERVER[$name]) && is_string($_SERVER[$name]) && trim($_SERVER[$name]) !== '') {
        return trim($_SERVER[$name]);
    }

    $settings = shopEaseLocalMailSettings();
    if (isset($settings[$name]) && trim($settings[$name]) !== '') {
        return trim($settings[$name]);
    }

    return false;
}

function shopEaseSendEmail(string $to, string $recipientName, string $subject, string $html, string $text): void
{
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Mailer dependency is missing. Run composer install.');
    }
    require_once $autoload;

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $hostSetting = shopEaseOptionalEnv('MAIL_HOST');
    $mail->Host = $hostSetting === false || $hostSetting === '' ? 'smtp.gmail.com' : $hostSetting;
    $portSetting = shopEaseOptionalEnv('MAIL_PORT');
    $port = filter_var(
        $portSetting === false || $portSetting === '' ? '587' : $portSetting,
        FILTER_VALIDATE_INT
    );
    if ($port !== 465 && $port !== 587) {
        throw new RuntimeException('MAIL_PORT must be 465 or 587.');
    }
    $mail->Port = $port;
    $mail->SMTPAuth = true;
    $mail->Username = shopEaseRequiredEnv('MAIL_USERNAME');
    $mail->Password = shopEaseRequiredEnv('MAIL_PASSWORD');
    if (strcasecmp($mail->Host, 'smtp.gmail.com') === 0) {
        $mail->Password = str_replace(' ', '', $mail->Password);
    }
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 15;

    if ($mail->Port === 465) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    $fromAddress = shopEaseOptionalEnv('MAIL_FROM_ADDRESS');
    if (($fromAddress === false || trim($fromAddress) === '') &&
        isset($_SERVER['MAIL_FROM_ADDRESS']) && is_string($_SERVER['MAIL_FROM_ADDRESS'])) {
        $fromAddress = $_SERVER['MAIL_FROM_ADDRESS'];
    }
    if ($fromAddress === false || trim($fromAddress) === '') {
        $fromAddress = $mail->Username;
    }
    $fromName = shopEaseOptionalEnv('MAIL_FROM_NAME');
    if (($fromName === false || trim($fromName) === '') &&
        isset($_SERVER['MAIL_FROM_NAME']) && is_string($_SERVER['MAIL_FROM_NAME'])) {
        $fromName = $_SERVER['MAIL_FROM_NAME'];
    }
    $mail->setFrom(trim($fromAddress), $fromName === false || trim($fromName) === '' ? 'ShopEase' : trim($fromName));
    $mail->addAddress($to, $recipientName);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $html;
    $mail->AltBody = $text;

    if (!$mail->send()) {
        throw new RuntimeException('The mail server did not accept the email.');
    }
}
