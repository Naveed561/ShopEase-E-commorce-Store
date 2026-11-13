<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once 'mail_config.php';

try {
    $email = shopEaseRequiredEnv('MAIL_TEST_RECIPIENT');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('MAIL_TEST_RECIPIENT must be a valid email address.');
    }

    if (!sendOrderEmail($email, 'ShopEase SMTP test')) {
        fwrite(STDERR, "Email failed. Check the PHP error log.\n");
        exit(1);
    }

    fwrite(STDOUT, "Test email sent successfully.\n");
} catch (RuntimeException $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}