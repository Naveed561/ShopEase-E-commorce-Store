<?php
session_start();

if (!isset($_SESSION['id']) || (int)$_SESSION['id'] < 1) {
    header('Location: login.php');
    exit;
}

include "connection.php";

$userId = (int)$_SESSION['id'];
$ordersQuery = $conn->prepare(
    'SELECT o.id AS order_id, o.`date`, o.total_bill, od.product_id, od.price, od.quantity, p.name
     FROM orders o
     INNER JOIN order_detailed od ON od.order_id = o.id
     INNER JOIN products p ON p.id = od.product_id
     WHERE o.cust_id = ?
     ORDER BY o.`date` DESC, o.id DESC, od.product_id'
);
if (!$ordersQuery) {
    throw new RuntimeException('Could not prepare order history query: ' . $conn->error);
}

$ordersQuery->bind_param('i', $userId);
if (!$ordersQuery->execute()) {
    throw new RuntimeException('Could not load order history: ' . $ordersQuery->error);
}

$ordersResult = $ordersQuery->get_result();
$orders = [];
while ($row = $ordersResult->fetch_assoc()) {
    $orderId = (int)$row['order_id'];
    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [
            'date' => $row['date'],
            'total_bill' => $row['total_bill'],
            'items' => []
        ];
    }

    $orders[$orderId]['items'][] = [
        'name' => $row['name'],
        'price' => $row['price'],
        'quantity' => $row['quantity']
    ];
}
$ordersQuery->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Orders - ShopEase</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5">
    <h1 class="h2 mb-4">My Orders</h1>
    <a href="index.php" class="btn btn-outline-primary mb-4">Continue Shopping</a>

    <?php if ($orders === []) { ?>
        <div class="alert alert-info">You have not placed any orders yet.</div>
    <?php } else { ?>
        <?php foreach ($orders as $orderId => $order) { ?>
            <section class="card mb-4">
                <div class="card-header d-flex flex-wrap justify-content-between gap-2">
                    <strong>Order #<?php echo $orderId; ?></strong>
                    <span>
                        <?php echo htmlspecialchars((string)$order['date'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item) {
                                $price = (float)$item['price'];
                                $quantity = (int)$item['quantity'];
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>Rs. <?php echo number_format($price, 2); ?></td>
                                    <td><?php echo $quantity; ?></td>
                                    <td>Rs. <?php echo number_format($price * $quantity, 2); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Order total</th>
                                <th>Rs. <?php echo number_format((float)$order['total_bill'], 2); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        <?php } ?>
    <?php } ?>
</main>
</body>
</html>
