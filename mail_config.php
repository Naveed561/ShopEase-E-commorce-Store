<?php

require_once 'mail_helper.php';

function sendOrderEmail(string $customerEmail, string $customerName): bool
{
    try {
        $safeName = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
        $html = '<h2>Order Confirmed!</h2><p>Hello ' . $safeName . ',</p>'
            . '<p>Thank you for shopping with ShopEase.</p>'
            . '<p>Your order has been confirmed successfully.</p>';
        $text = "Hello {$customerName},\n\n"
            . "Thank you for shopping with ShopEase.\n"
            . 'Your order has been confirmed successfully.';

        shopEaseSendEmail(
            $customerEmail,
            $customerName,
            'ShopEase order confirmation',
            $html,
            $text
        );
        return true;
    } catch (PHPMailer\PHPMailer\Exception | RuntimeException $error) {
        error_log('Order email error: ' . $error->getMessage());
        return false;
    }
}