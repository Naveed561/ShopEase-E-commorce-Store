<?php

function shopEaseBuildOrderConfirmationEmail(
    string $customerName,
    int $orderId,
    string $orderDate,
    array $items,
    float $total
): array {
    $safeCustomerName = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
    $safeOrderDate = htmlspecialchars($orderDate, ENT_QUOTES, 'UTF-8');
    $html = '<!doctype html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>ShopEase order confirmation</title></head>'
        . '<body style="margin:0;padding:24px 12px;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#243247;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;">'
        . '<tr><td style="padding:28px 32px;background:#1455c0;color:#ffffff;">'
        . '<div style="font-size:24px;font-weight:bold;letter-spacing:.3px;">ShopEase</div>'
        . '<div style="margin-top:7px;font-size:13px;color:#dce8ff;">ORDER CONFIRMATION</div>'
        . '</td></tr>'
        . '<tr><td style="padding:32px;">'
        . '<div style="display:inline-block;padding:7px 12px;border-radius:20px;background:#e8f7ef;color:#18794e;font-size:13px;font-weight:bold;">ORDER CONFIRMED</div>'
        . '<h1 style="margin:18px 0 8px;font-size:25px;line-height:1.3;color:#172b4d;">Thanks for your order, '
        . $safeCustomerName . '!</h1>'
        . '<p style="margin:0 0 24px;color:#5d6b82;font-size:15px;line-height:1.6;">We have received your order and are getting it ready.</p>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:25px;background:#f5f8fc;border-radius:10px;">'
        . '<tr><td style="padding:16px 18px;color:#5d6b82;font-size:13px;">ORDER NUMBER<br><strong style="display:inline-block;margin-top:5px;color:#172b4d;font-size:16px;">#'
        . $orderId . '</strong></td>'
        . '<td style="padding:16px 18px;text-align:right;color:#5d6b82;font-size:13px;">ORDER DATE<br><strong style="display:inline-block;margin-top:5px;color:#172b4d;font-size:14px;">'
        . $safeOrderDate . '</strong></td></tr></table>'
        . '<h2 style="margin:0 0 12px;font-size:18px;color:#172b4d;">Your order</h2>'
        . '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;">'
        . '<thead><tr style="background:#edf3fb;color:#52637c;text-align:left;">'
        . '<th style="padding:12px 10px;">Item</th><th style="padding:12px 10px;text-align:center;">Qty</th>'
        . '<th style="padding:12px 10px;text-align:right;">Price</th><th style="padding:12px 10px;text-align:right;">Subtotal</th>'
        . '</tr></thead><tbody>';

    $text = "ShopEase - Order confirmed\n"
        . "Thanks for your order, {$customerName}!\n\n"
        . "Order number: #{$orderId}\n"
        . "Order date: {$orderDate}\n\n"
        . "YOUR ORDER\n";

    foreach ($items as $item) {
        $productName = (string)$item['product_name'];
        $safeProductName = htmlspecialchars($productName, ENT_QUOTES, 'UTF-8');
        $price = (float)$item['price'];
        $quantity = (int)$item['quantity'];
        $subtotal = $price * $quantity;

        $html .= '<tr><td style="padding:13px 10px;border-bottom:1px solid #e8edf4;color:#243247;">'
            . $safeProductName . '</td><td style="padding:13px 10px;border-bottom:1px solid #e8edf4;text-align:center;">'
            . $quantity . '</td><td style="padding:13px 10px;border-bottom:1px solid #e8edf4;text-align:right;white-space:nowrap;">Rs. '
            . number_format($price, 2) . '</td><td style="padding:13px 10px;border-bottom:1px solid #e8edf4;text-align:right;white-space:nowrap;">Rs. '
            . number_format($subtotal, 2) . '</td></tr>';
        $text .= '- ' . $productName . ' | Qty: ' . $quantity . ' | Rs. '
            . number_format($price, 2) . ' each | Subtotal: Rs. '
            . number_format($subtotal, 2) . "\n";
    }

    $formattedTotal = number_format($total, 2);
    $html .= '</tbody><tfoot><tr><td colspan="3" style="padding:17px 10px;text-align:right;font-size:15px;font-weight:bold;color:#243247;">Order total</td>'
        . '<td style="padding:17px 10px;text-align:right;white-space:nowrap;font-size:17px;font-weight:bold;color:#1455c0;">Rs. '
        . $formattedTotal . '</td></tr></tfoot></table>'
        . '<p style="margin:24px 0 0;padding:17px;border-left:4px solid #1455c0;background:#f5f8fc;color:#52637c;font-size:14px;line-height:1.6;">'
        . 'Thank you for choosing ShopEase. We appreciate your business!</p>'
        . '</td></tr><tr><td style="padding:20px 32px;background:#f5f8fc;text-align:center;color:#738198;font-size:12px;">'
        . 'This is an automatic order confirmation from ShopEase.</td></tr></table>'
        . '</body></html>';

    $text .= "\nOrder total: Rs. {$formattedTotal}\n\n"
        . "Thank you for choosing ShopEase. We appreciate your business!\n";

    return ['html' => $html, 'text' => $text];
}
