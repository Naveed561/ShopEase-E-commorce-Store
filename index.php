
<?php
session_start();
include "connection.php";

$cartFeedback = $_SESSION['cart_feedback'] ?? null;
unset($_SESSION['cart_feedback']);

$cartCount = 0;
foreach ($_SESSION['cart'] ?? [] as $item) {
    $cartCount += $item['quantity'];
}

$searchTerm = isset($_GET['search']) && is_string($_GET['search'])
    ? trim($_GET['search'])
    : '';
$categoryInput = $_GET['category'] ?? null;
$requestedCategory = is_string($categoryInput) || is_int($categoryInput)
    ? filter_var($categoryInput, FILTER_VALIDATE_INT)
    : false;
$selectedCategory = $requestedCategory !== false && $requestedCategory !== null && $requestedCategory > 0
    ? $requestedCategory
    : null;
$categoriesResult = $conn->query('SELECT id, name FROM categories ORDER BY name');
if (!$categoriesResult) {
    throw new RuntimeException('Could not load product categories: ' . $conn->error);
}
$categories = $categoriesResult->fetch_all(MYSQLI_ASSOC);
$selectedCategoryName = null;
foreach ($categories as $category) {
    if ((int)$category['id'] === $selectedCategory) {
        $selectedCategoryName = (string)$category['name'];
        break;
    }
}

$userName = null;
if (isset($_SESSION['id'])) {
    $userQuery = $conn->prepare('SELECT name FROM users WHERE id = ?');
    if (!$userQuery) {
        throw new RuntimeException('Could not prepare user lookup: ' . $conn->error);
    }

    $userId = (int)$_SESSION['id'];
    $userQuery->bind_param('i', $userId);
    if (!$userQuery->execute()) {
        throw new RuntimeException('Could not load user: ' . $userQuery->error);
    }

    $userQuery->bind_result($userName);
    if (!$userQuery->fetch()) {
        $userName = null;
        unset($_SESSION['id'], $_SESSION['user_name']);
    } else {
        $_SESSION['user_name'] = $userName;
    }
    $userQuery->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ShopEase - Online Shopping</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background:#f8f9fa; font-family:Arial,sans-serif; }
.navbar-brand { color:#0d6efd; font-size:27px; font-weight:bold; }
.hero .carousel-item { position:relative; height:460px; background:#172b4d; }
.hero-img { width:100%; height:100%; object-fit:cover; filter:brightness(48%); }
.hero-caption { position:absolute; top:50%; left:10%; right:10%; color:#fff; text-align:center; transform:translateY(-50%); }
.hero-caption h1 { font-size:clamp(2rem,5vw,3.7rem); font-weight:700; }
.hero-caption p { font-size:clamp(1rem,2vw,1.25rem); }
.category-card { min-width:150px; flex:1 1 150px; border:1px solid #e8edf4; border-radius:14px; color:#243247; text-decoration:none; transition:transform .2s,box-shadow .2s,border-color .2s; }
.category-card:hover,.category-card.is-active { border-color:#0d6efd; box-shadow:0 8px 22px #0d6efd1a; transform:translateY(-3px); }
.category-card-icon { display:grid; place-items:center; width:48px; height:48px; margin:0 auto .65rem; border-radius:14px; background:#edf4ff; color:#0d6efd; font-size:1.35rem; }
.site-nav { display:flex; align-items:center; gap:1rem; flex-wrap:wrap; }
.site-nav-links { display:flex; align-items:center; gap:1rem; flex-wrap:wrap; margin:0 auto; padding:0; list-style:none; }
.site-account { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
.product-card { border:0; border-radius:12px; overflow:hidden; height:100%; position:relative; }
.product-card img { height:220px; object-fit:cover; }
.product-card:hover { box-shadow:0 8px 24px #0002; }
.price { color:#0d6efd; font-size:20px; font-weight:bold; }
.product-feedback {
    position:absolute;
    top:50%;
    left:50%;
    z-index:2;
    display:flex;
    align-items:center;
    gap:.65rem;
    max-width:calc(100% - 2rem);
    padding:.85rem 1rem;
    border-radius:14px;
    background:#fff;
    color:#198754;
    box-shadow:0 10px 32px #0003;
    font-weight:600;
    opacity:0;
    visibility:hidden;
    transform:translate(-50%, calc(-50% + 8px)) scale(.96);
    pointer-events:none;
}
.product-feedback i { font-size:1.35rem; }
.product-feedback span { overflow-wrap:anywhere; }
.product-feedback.is-visible {
    animation:product-feedback-show 3s ease forwards;
}
.product-feedback.has-error { color:#dc3545; }
@keyframes product-feedback-show {
    0% { opacity:0; visibility:hidden; transform:translate(-50%, calc(-50% + 8px)) scale(.96); }
    10%, 82% { opacity:1; visibility:visible; transform:translate(-50%, -50%) scale(1); }
    100% { opacity:0; visibility:hidden; transform:translate(-50%, calc(-50% - 8px)) scale(.98); }
}
@media (prefers-reduced-motion: reduce) {
    .product-feedback.is-visible { animation:none; opacity:1; visibility:visible; transform:translate(-50%, -50%); }
}
</style>
</head>
<body>

<nav class="navbar bg-white sticky-top shadow-sm">
<div class="container">
<div class="site-nav w-100">
<a class="navbar-brand" href="index.php">ShopEase</a>
<ul class="site-nav-links">
<li><a class="nav-link" href="#home">Home</a></li>
<li><a class="nav-link" href="#trending">Trending</a></li>
<li><a class="nav-link" href="#categories">Categories</a></li>
</ul>
<form class="d-flex" action="#trending" method="GET">
<input class="form-control" name="search" placeholder="Search products..."
value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>">
<button class="btn btn-primary ms-2"><i class="bi bi-search"></i></button>
</form>
<a href="cart.php" class="btn btn-outline-dark">
<i class="bi bi-cart3"></i> Cart
<span class="badge bg-primary cart-count"><?php echo $cartCount; ?></span>
</a>
<?php if ($userName !== null) { ?>
<div class="site-account">
<span class="text-nowrap"><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
<a class="btn btn-outline-primary" href="orders.php">Order List</a>
<a class="btn btn-outline-secondary" href="logout.php">Logout</a>
</div>
<?php } else { ?>
<div class="site-account">
<a class="btn btn-outline-primary" href="login.php">Login</a>
<a class="btn btn-primary" href="register.php">Register</a>
</div>
<?php } ?>
</div>
</div>
</nav>

<section id="home" class="hero carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
<div class="carousel-indicators">
<button type="button" data-bs-target="#home" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
<button type="button" data-bs-target="#home" data-bs-slide-to="1" aria-label="Slide 2"></button>
<button type="button" data-bs-target="#home" data-bs-slide-to="2" aria-label="Slide 3"></button>
</div>
<div class="carousel-inner">
<div class="carousel-item active">
<img class="d-block w-100 hero-img" src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&amp;fit=crop&amp;w=1600&amp;q=85" alt="Modern clothing store">
<div class="hero-caption"><h1>Discover Your Style</h1><p>Fresh looks and everyday essentials, all in one place.</p><a href="#trending" class="btn btn-primary btn-lg">Shop Now</a></div>
</div>
<div class="carousel-item">
<img class="d-block w-100 hero-img" src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&amp;fit=crop&amp;w=1600&amp;q=85" alt="A collection of fashionable outfits">
<div class="hero-caption"><h1>Find Your Everyday Look</h1><p>Explore new arrivals made for your style.</p><a href="#categories" class="btn btn-light btn-lg">Explore Fashion</a></div>
</div>
<div class="carousel-item">
<img class="d-block w-100 hero-img" src="https://images.unsplash.com/photo-1555529669-e69e7aa0ba9a?auto=format&amp;fit=crop&amp;w=1600&amp;q=85" alt="Shop a wide selection of products">
<div class="hero-caption"><h1>More to Love</h1><p>Shop clothing, accessories, electronics and more.</p><a href="#trending" class="btn btn-primary btn-lg">Browse Products</a></div>
</div>
</div>
<button class="carousel-control-prev" type="button" data-bs-target="#home" data-bs-slide="prev" aria-label="Previous slide">
<span class="carousel-control-prev-icon" aria-hidden="true"></span>
</button>
<button class="carousel-control-next" type="button" data-bs-target="#home" data-bs-slide="next" aria-label="Next slide">
<span class="carousel-control-next-icon" aria-hidden="true"></span>
</button>
</section>

<section id="categories" class="container py-4">
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
<div><h2 class="fw-bold mb-1">Shop by Category</h2><p class="text-muted mb-0">Find something you'll love.</p></div>
</div>
<div class="d-flex gap-3 flex-wrap">
<?php
$categoryIcons = [
    'Accessories' => 'bi-handbag',
    'Electronics' => 'bi-headphones',
    'Fashion' => 'bi-stars',
    'Home & Living' => 'bi-house-heart',
    'Shoes' => 'bi-sunglasses',
    'Sports & Outdoors' => 'bi-bicycle'
];
$allProductsUrl = $searchTerm === '' ? 'index.php#trending' : 'index.php?' . http_build_query(['search' => $searchTerm]) . '#trending';
?>
<a class="category-card p-3 text-center<?php echo $selectedCategory === null ? ' is-active' : ''; ?>"
   href="<?php echo htmlspecialchars($allProductsUrl, ENT_QUOTES, 'UTF-8'); ?>">
<span class="category-card-icon"><i class="bi bi-grid"></i></span>
<span class="fw-semibold">All Products</span>
</a>
<?php foreach ($categories as $category) {
    $categoryId = (int)$category['id'];
    $categoryName = (string)$category['name'];
    $categoryParams = ['category' => $categoryId];
    if ($searchTerm !== '') {
        $categoryParams['search'] = $searchTerm;
    }
    $categoryUrl = 'index.php?' . http_build_query($categoryParams) . '#trending';
?>
<a class="category-card p-3 text-center<?php echo $selectedCategory === $categoryId ? ' is-active' : ''; ?>"
   href="<?php echo htmlspecialchars($categoryUrl, ENT_QUOTES, 'UTF-8'); ?>">
<span class="category-card-icon"><i class="bi <?php echo htmlspecialchars($categoryIcons[$categoryName] ?? 'bi-tag', ENT_QUOTES, 'UTF-8'); ?>"></i></span>
<span class="fw-semibold"><?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?></span>
</a>
<?php } ?>
</div>
</section>

<section id="trending" class="container py-4">
<h2 class="fw-bold"><?php echo $selectedCategory === null ? 'Trending Products' : 'Explore ' . htmlspecialchars($selectedCategoryName ?? 'Products', ENT_QUOTES, 'UTF-8'); ?></h2>
<p class="text-muted">Our popular picks, just for you.</p>
<div class="row g-4" id="productList">
<?php include "productscard.php"; ?>
</div>
</section>

<footer class="bg-dark text-white text-center p-4 mt-4">
<p class="mb-0">© 2026 ShopEase. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
