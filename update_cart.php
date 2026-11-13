
<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['product_id'], $_POST['quantity'])) {

    $id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if (isset($_SESSION['cart'][$id])) {
        if ($quantity >= 1 && $quantity <= 99) {
            $_SESSION['cart'][$id]['quantity'] = $quantity;
        }
    }
}

header("Location: cart.php");
exit;
?>
