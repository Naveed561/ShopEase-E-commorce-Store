<?php
session_start();
include "connection.php";

function redirectToCart(string $message, bool $isError = false): void
{
    $_SESSION['order_feedback'] = [
        'message' => $message,
        'is_error' => $isError
    ];

    header('Location: cart.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectToCart('Use the Confirm Order button to place your order.', true);
}

$submittedToken = $_POST['csrf_token'] ?? null;
if (!is_string($submittedToken) ||
    !hash_equals($_SESSION['csrf_token'] ?? '', $submittedToken)) {
    redirectToCart('Your session has expired. Please try confirming your order again.', true);
}

$cart = $_SESSION['cart'] ?? [];
if (!is_array($cart) || $cart === []) {
    redirectToCart('Your cart is empty.', true);
}

$productQuery = $conn->prepare('SELECT id, price FROM products WHERE id = ?');
if (!$productQuery) {
    error_log('Could not prepare confirm-order product query: ' . $conn->error);
    redirectToCart('Unable to confirm your order right now.', true);
}

$items = [];
$totalCents = 0;
foreach ($cart as $item) {
    if (!is_array($item) || !isset($item['id'], $item['quantity']) ||
        !is_numeric($item['id']) || !is_numeric($item['quantity'])) {
        $productQuery->close();
        redirectToCart('Your cart contains invalid items. Please review it and try again.', true);
    }

    $productId = (int)$item['id'];
    $quantity = (int)$item['quantity'];
    if ($productId < 1 || $quantity < 1 || $quantity > 99) {
        $productQuery->close();
        redirectToCart('A product or quantity in your cart is invalid.', true);
    }

    $productQuery->bind_param('i', $productId);
    if (!$productQuery->execute()) {
        error_log('Could not execute confirm-order product query: ' . $productQuery->error);
        $productQuery->close();
        redirectToCart('Unable to confirm your order right now.', true);
    }

    $product = $productQuery->get_result()->fetch_assoc();
    if (!$product) {
        $productQuery->close();
        redirectToCart('A product in your cart is no longer available.', true);
    }

    $priceCents = (int)round((float)$product['price'] * 100);
    $totalCents += $priceCents * $quantity;
    $items[] = [
        'product_id' => (int)$product['id'],
        'price' => number_format($priceCents / 100, 2, '.', ''),
        'quantity' => $quantity
    ];
}
$productQuery->close();

$totalBill = number_format($totalCents / 100, 2, '.', '');

try {
    if (!$conn->begin_transaction()) {
        throw new RuntimeException('Could not begin order transaction: ' . $conn->error);
    }

    if (isset($_SESSION['id'])) {
        $customerId = (int)$_SESSION['id'];
        if ($customerId < 1) {
            throw new RuntimeException('The signed-in user ID is invalid.');
        }
    } elseif (isset($_SESSION['cust_id'])) {
        $customerId = (int)$_SESSION['cust_id'];
    } else {
        $customerQuery = $conn->prepare(
            "INSERT INTO customers (customer_name) VALUES ('Guest Customer')"
        );
        if (!$customerQuery) {
            throw new RuntimeException('Could not prepare guest customer insert: ' . $conn->error);
        }
        if (!$customerQuery->execute()) {
            throw new RuntimeException('Could not create guest customer: ' . $customerQuery->error);
        }
        $customerId = $conn->insert_id;
        $customerQuery->close();
    }

    $orderQuery = $conn->prepare(
        'INSERT INTO orders (`date`, cust_id, total_bill) VALUES (CURRENT_TIMESTAMP, ?, ?)'
    );
    if (!$orderQuery) {
        throw new RuntimeException('Could not prepare order insert: ' . $conn->error);
    }

    $orderQuery->bind_param('id', $customerId, $totalBill);
    if (!$orderQuery->execute()) {
        throw new RuntimeException('Could not insert order: ' . $orderQuery->error);
    }
    $orderId = $conn->insert_id;
    $orderQuery->close();

    $detailQuery = $conn->prepare(
        'INSERT INTO order_detailed (order_id, product_id, price, quantity) VALUES (?, ?, ?, ?)'
    );
    if (!$detailQuery) {
        throw new RuntimeException('Could not prepare order detail insert: ' . $conn->error);
    }

    foreach ($items as $item) {
        $productId = $item['product_id'];
        $price = $item['price'];
        $quantity = $item['quantity'];
        $detailQuery->bind_param('iidi', $orderId, $productId, $price, $quantity);
        if (!$detailQuery->execute()) {
            throw new RuntimeException('Could not insert order detail: ' . $detailQuery->error);
        }
    }
    $detailQuery->close();

    if (!$conn->commit()) {
        throw new RuntimeException('Could not commit order: ' . $conn->error);
    }
} catch (mysqli_sql_exception | RuntimeException $error) {
    $conn->rollback();
    error_log('Order confirmation failed: ' . $error->getMessage());
    redirectToCart(
        'Order could not be saved. Import database.sql into the shopease database and verify existing table columns match it. Your cart has been kept.',
        true
    );
}

$orderEmailFeedback = 'Order confirmed. Sign in with an account to receive the order email.';
if (isset($_SESSION['id'])) {
    try {
        require_once __DIR__ . '/mail_helper.php';

        $userId = (int)$_SESSION['id'];
        $userQuery = $conn->prepare('SELECT name, email FROM users WHERE id = ?');
        if (!$userQuery) {
            throw new RuntimeException('Could not prepare order email recipient lookup: ' . $conn->error);
        }
        $userQuery->bind_param('i', $userId);
        if (!$userQuery->execute()) {
            throw new RuntimeException('Could not load order email recipient: ' . $userQuery->error);
        }
        $userQuery->bind_result($recipientName, $recipientEmail);
        if (!$userQuery->fetch()) {
            throw new RuntimeException('Could not find the signed-in order email recipient.');
        }
        $userQuery->close();

        $orderItemsQuery = $conn->prepare(
            "SELECT o.id AS order_id, o.`date`, o.total_bill,
                   od.price, od.quantity,
                   COALESCE(p.name, CONCAT('Product #', od.product_id)) AS product_name
             FROM orders o
             INNER JOIN order_detailed od ON od.order_id = o.id
             LEFT JOIN products p ON p.id = od.product_id
             WHERE o.id = ? AND o.cust_id = ?
             ORDER BY od.product_id"
        );
        if (!$orderItemsQuery) {
            throw new RuntimeException('Could not prepare order receipt email: ' . $conn->error);
        }
        $orderItemsQuery->bind_param('ii', $orderId, $userId);
        if (!$orderItemsQuery->execute()) {
            throw new RuntimeException('Could not load order receipt email: ' . $orderItemsQuery->error);
        }
        $orderItemsResult = $orderItemsQuery->get_result();
        $orderItems = [];
        while ($row = $orderItemsResult->fetch_assoc()) {
            $orderItems[] = $row;
        }
        $orderItemsQuery->close();
        if ($orderItems === []) {
            throw new RuntimeException('The confirmed order has no items to email.');
        }

        require_once __DIR__ . '/order_email_template.php';
        $emailContent = shopEaseBuildOrderConfirmationEmail(
            (string)$recipientName,
            $orderId,
            (string)$orderItems[0]['date'],
            $orderItems,
            (float)$orderItems[0]['total_bill']
        );
        shopEaseSendEmail(
            (string)$recipientEmail,
            (string)$recipientName,
            'ShopEase order confirmation #' . $orderId,
            $emailContent['html'],
            $emailContent['text']
        );
        $orderEmailFeedback = 'Order confirmed. Email successfully sent to '
            . (string)$recipientEmail . '.';
    } catch (PHPMailer\PHPMailer\Exception | mysqli_sql_exception | RuntimeException $emailError) {
        error_log('Order confirmation email could not be sent: ' . $emailError->getMessage());
        if ($emailError instanceof RuntimeException &&
            preg_match('/^Required mail setting is missing: (MAIL_USERNAME|MAIL_PASSWORD)$/', $emailError->getMessage(), $matches)) {
            $settingName = $matches[1] === 'MAIL_USERNAME'
                ? 'your Gmail address'
                : 'a Google App Password';
            $orderEmailFeedback = 'Order confirmed, but email was not sent. Configure '
                . $settingName . ' in mail.local.php or Apache/PHP, then try again. See MAIL_SETUP.md.';
        } elseif ($emailError instanceof RuntimeException &&
            preg_match('/^Required mail setting is missing: ([A-Z_]+)$/', $emailError->getMessage(), $matches)) {
            $orderEmailFeedback = 'Order confirmed, but email was not sent. Configure ' . $matches[1]
                . ' in mail.local.php or Apache/PHP, then try again. See MAIL_SETUP.md.';
        } elseif ($emailError instanceof RuntimeException &&
            $emailError->getMessage() === 'Mailer dependency is missing. Run composer install.') {
            $orderEmailFeedback = 'Order confirmed, but email was not sent because the mail dependency is missing. Run composer install.';
        } else {
            $orderEmailFeedback = 'Order confirmed, but email was not sent. Check the Apache/PHP error log for the SMTP error and verify the Gmail settings.';
        }
    }
}

$_SESSION['order_email_feedback'] = $orderEmailFeedback;
unset($_SESSION['cart']);
header('Location: cart.php?success=1&order_id=' . $orderId);
exit;
?>
