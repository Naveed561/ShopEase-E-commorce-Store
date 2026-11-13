
<?php
session_start();
include "connection.php";

function redirectToProducts(string $message, ?int $productId = null, bool $isError = false): void
{
    $_SESSION['cart_feedback'] = [
        'message' => $message,
        'product_id' => $productId,
        'is_error' => $isError
    ];

    $url = 'index.php';
    if ($productId !== null) {
        $url .= '#product-' . $productId;
    } else {
        $url .= '#trending';
    }

    header('Location: ' . $url);
    exit;
}

$productId = isset($_POST['product_id']) && is_string($_POST['product_id']) &&
    ctype_digit($_POST['product_id'])
    ? (int)$_POST['product_id']
    : null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectToProducts('Use the Add to Cart button to add a product.', $productId, true);
}

if ($productId === null || $productId < 1) {
    redirectToProducts('A valid product is required.', null, true);
}

$search = isset($_POST['search']) && is_string($_POST['search'])
    ? $_POST['search']
    : '';
$category = isset($_POST['category']) && is_string($_POST['category']) &&
    ctype_digit($_POST['category']) && (int)$_POST['category'] > 0
    ? (int)$_POST['category']
    : null;
$stmt = $conn->prepare('SELECT id, name, price, image FROM products WHERE id = ?');
if (!$stmt) {
    error_log('Could not prepare add-to-cart product query: ' . $conn->error);
    redirectToProducts('Unable to add this product right now.', $productId, true);
}

$stmt->bind_param('i', $productId);

if (!$stmt->execute()) {
    error_log('Could not execute add-to-cart product query: ' . $stmt->error);
    $stmt->close();
    redirectToProducts('Unable to add this product right now.', $productId, true);
}

$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    redirectToProducts('This product could not be found.', $productId, true);
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['cart'][$productId])) {
    $_SESSION['cart'][$productId]['quantity']++;
} else {
    $_SESSION['cart'][$productId] = [
        'id' => (int)$product['id'],
        'name' => $product['name'],
        'price' => (float)$product['price'],
        'image' => $product['image'],
        'quantity' => 1
    ];
}

$message = $product['name'] . ' added to your cart.';
$feedback = [
    'message' => $message,
    'product_id' => $productId,
    'is_error' => false
];
if ($search !== '') {
    $_SESSION['cart_feedback'] = $feedback;
    $query = ['search' => $search];
    if ($category !== null) {
        $query['category'] = $category;
    }
    header('Location: index.php?' . http_build_query($query) . '#product-' . $productId);
    exit;
}

if ($category !== null) {
    $_SESSION['cart_feedback'] = $feedback;
    header('Location: index.php?category=' . $category . '#product-' . $productId);
    exit;
}

redirectToProducts($message, $productId);
?>
