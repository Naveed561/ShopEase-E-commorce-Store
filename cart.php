
<?php
session_start();


$cart = $_SESSION['cart'] ?? [];
$total = 0;
$orderFeedback = $_SESSION['order_feedback'] ?? null;
unset($_SESSION['order_feedback']);
$orderEmailFeedback = $_SESSION['order_email_feedback'] ?? null;
unset($_SESSION['order_email_feedback']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<?php
if (isset($_GET['success'], $_GET['order_id'])
    && $_GET['success'] == '1') {
?>
    <div class="alert alert-success">
        Order confirmed successfully!
        Order ID: <?php echo (int)$_GET['order_id']; ?>
    </div>
<?php } 
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Cart - ShopEase</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

<div class="container py-5">
    <h2 class="mb-4">My Shopping Cart</h2>
    <a href="index.php" class="btn btn-outline-primary mb-3">
        Continue Shopping
    </a>

    <?php if ($orderEmailFeedback !== null) { ?>
        <div class="alert alert-<?php echo strpos($orderEmailFeedback, 'Email successfully sent') !== false ? 'success' : 'warning'; ?>"
             role="status">
            <?php echo htmlspecialchars($orderEmailFeedback, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php } ?>

    <?php if (is_array($orderFeedback)) { ?>
        <div class="alert alert-<?php echo !empty($orderFeedback['is_error']) ? 'danger' : 'success'; ?>">
            <?php echo htmlspecialchars($orderFeedback['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php } ?>

    <?php if (empty($cart)) { ?>
        <div class="alert alert-info">Your cart is empty.</div>
    <?php } else { ?>

    <div class="table-responsive">
    <table class="table table-bordered bg-white align-middle">
        <thead class="table-dark">
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($cart as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
            $cartImage = (string)($item['image'] ?? '');
            $cartImageUrl = preg_match('/^https?:\/\//i', $cartImage) === 1
                ? $cartImage
                : 'uploads/' . $cartImage;
        ?>
            <tr>
                <td>
                    <img
                        src="<?php echo htmlspecialchars($cartImageUrl, ENT_QUOTES, 'UTF-8'); ?>"
                        width="55" height="55" style="object-fit:cover"
                        alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($item['name']); ?>
                </td>
                <td>Rs. <?php echo number_format($item['price'], 2); ?></td>
                <td>
                    <form action="update_cart.php" method="POST"
                          class="d-flex gap-2">
                        <input type="hidden" name="product_id"
                               value="<?php echo (int)$item['id']; ?>">
                        <input type="number" name="quantity"
                               value="<?php echo (int)$item['quantity']; ?>"
                               min="1" max="99" required
                               class="form-control" style="width:85px">
                        <button class="btn btn-primary btn-sm">Update</button>
                    </form>
                </td>
                <td>Rs. <?php echo number_format($subtotal, 2); ?></td>
                <td>
                    <form action="remove_from_cart.php" method="POST">
                        <input type="hidden" name="product_id"
                               value="<?php echo (int)$item['id']; ?>">
                        <button class="btn btn-danger btn-sm">Remove</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    </div>

    
<h4 class="text-end mb-3">
    Total Bill: Rs. <?php echo number_format($total, 2); ?>
</h4>

<div class="text-end">
    <form action="confirm_order.php" method="POST">

        <input type="hidden" name="csrf_token"
               value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit"
                class="btn btn-success btn-lg"
                <?php echo empty($cart) ? 'disabled' : ''; ?>>

            <i class="bi bi-check-circle"></i>
            Confirm Order
        </button>

    </form>
</div>


    <?php } ?>
</div>
</body>
</html>
