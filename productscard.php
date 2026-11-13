
<?php
$conditions = [];
$parameters = [];
$types = '';

if ($selectedCategory !== null) {
    $conditions[] = 'category_id = ?';
    $parameters[] = $selectedCategory;
    $types .= 'i';
}
if ($searchTerm !== '') {
    $conditions[] = 'name LIKE ?';
    $parameters[] = '%' . $searchTerm . '%';
    $types .= 's';
}

$sql = 'SELECT id, name, price, image FROM products';
if ($conditions !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY id DESC';
$productQuery = $conn->prepare($sql);
if (!$productQuery) {
    throw new RuntimeException('Could not prepare product catalog query: ' . $conn->error);
}
if ($parameters !== []) {
    $productQuery->bind_param($types, ...$parameters);
}
if (!$productQuery->execute()) {
    throw new RuntimeException('Could not load products: ' . $productQuery->error);
}
$result = $productQuery->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $showFeedback = isset($cartFeedback['product_id']) &&
            (int)$cartFeedback['product_id'] === (int)$row['id'];
        $isFeedbackError = $showFeedback && !empty($cartFeedback['is_error']);
        $image = (string)($row['image'] ?? '');
        $imageUrl = preg_match('/^https?:\/\//i', $image) === 1 ? $image : 'uploads/' . $image;
?>
<div class="col-12 col-sm-6 col-lg-4" id="product-<?php echo (int)$row['id']; ?>">
    <div class="card product-card shadow-sm">
        <?php if ($showFeedback) { ?>
            <div class="product-feedback is-visible<?php echo $isFeedbackError ? ' has-error' : ''; ?>"
                 role="status" aria-live="polite" aria-atomic="true">
                <i class="bi <?php echo $isFeedbackError ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill'; ?>"></i>
                <span><?php echo htmlspecialchars($cartFeedback['message']); ?></span>
            </div>
        <?php } ?>
        <img
            src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>"
            class="card-img-top"
            alt="<?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>">

        <div class="card-body">
            <h5 class="card-title">
                <?php echo htmlspecialchars($row['name']); ?>
            </h5>

            <p class="price">
                Rs. <?php echo number_format((float)$row['price'], 2); ?>
            </p>

            <form action="add_to_cart.php" method="POST">
                <input type="hidden" name="product_id"
                       value="<?php echo (int)$row['id']; ?>">
                <input type="hidden" name="search"
                       value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="category"
                       value="<?php echo $selectedCategory === null ? '' : $selectedCategory; ?>">

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-cart-plus"></i> Add to Cart
                </button>
            </form>
        </div>
    </div>
</div>
<?php
    }
} else {
    echo '<div class="col-12"><p class="text-muted">No products found.</p></div>';
}
$productQuery->close();
?>
