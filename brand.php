<?php
require_once __DIR__ . '/config/db.php';

// Get selected brand from URL
$brand = trim($_GET['brand'] ?? '');
if (empty($brand)) {
    header("Location: products.php");
    exit();
}

$page_title = htmlspecialchars($brand) . " Watches";
require_once __DIR__ . '/includes/header.php';

// Category filter for this brand
$category_filter = trim($_GET['category'] ?? '');

// Fetch products belonging ONLY to this brand
$sql = "SELECT * FROM products WHERE brand = ?";
$params = [$brand];
$types  = "s";

if (!empty($category_filter)) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

$sql .= " ORDER BY id ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_products = $result ? mysqli_num_rows($result) : 0;

// Fetch distinct categories that exist ONLY for this specific brand
$cat_stmt = mysqli_prepare($conn, "SELECT DISTINCT category FROM products WHERE brand = ? ORDER BY category ASC");
mysqli_stmt_bind_param($cat_stmt, "s", $brand);
mysqli_stmt_execute($cat_stmt);
$cat_res = mysqli_stmt_get_result($cat_stmt);
$brand_categories = [];
if ($cat_res) {
    while ($c_row = mysqli_fetch_assoc($cat_res)) {
        $brand_categories[] = $c_row['category'];
    }
    mysqli_stmt_close($cat_stmt);
}

// Curated brand watch background framing positions for optimal watermark placement
$brand_key = strtolower(trim($brand));
$brand_bg_positions = [
    'battuta'           => 'center 76%',
    'seiko'             => 'center 62%',
    'samsung'           => 'center 62%',
    'timex'             => 'center 52%',
];
$brand_bg_pos = $brand_bg_positions[$brand_key] ?? 'center center';

// Fetch a real watch image of this brand dynamically from database
$bg_stmt = mysqli_prepare($conn, "SELECT image FROM products WHERE brand = ? AND image IS NOT NULL AND image != '' LIMIT 1");
mysqli_stmt_bind_param($bg_stmt, "s", $brand);
mysqli_stmt_execute($bg_stmt);
$bg_res = mysqli_stmt_get_result($bg_stmt);
$brand_bg_image = '';
if ($bg_row = mysqli_fetch_assoc($bg_res)) {
    $brand_bg_image = $bg_row['image'];
}
mysqli_stmt_close($bg_stmt);

// Logo helper (queries brands table or fallback image)
if (!function_exists('getBrandLogo')) {
    function getBrandLogo($brand_name) {
        global $conn;
        if (isset($conn)) {
            $b_stmt = mysqli_prepare($conn, "SELECT logo FROM brands WHERE name = ? LIMIT 1");
            if ($b_stmt) {
                mysqli_stmt_bind_param($b_stmt, "s", $brand_name);
                mysqli_stmt_execute($b_stmt);
                $b_res = mysqli_stmt_get_result($b_stmt);
                if ($b_row = mysqli_fetch_assoc($b_res)) {
                    mysqli_stmt_close($b_stmt);
                    if (!empty($b_row['logo']) && file_exists(__DIR__ . '/' . $b_row['logo'])) {
                        return $b_row['logo'];
                    }
                } else {
                    mysqli_stmt_close($b_stmt);
                }
            }
        }
        $clean = strtolower(trim($brand_name));
        $clean = str_replace([' ', '&', '.'], ['-', '', ''], $clean);
        $path = 'images/brands/' . $clean . '.png';
        if (file_exists(__DIR__ . '/' . $path)) {
            return $path;
        }
        return 'images/brands/all-brands.png';
    }
}

$brand_logo = getBrandLogo($brand);
$msg = $_GET['msg'] ?? '';
?>

<style>
/* Dedicated Brand Page Hero Header with Visible Soft Watch Background */
.brand-hero-box {
    position: relative;
    background-color: #ffffff;
    border: 1px solid #E8DFD5;
    border-radius: 10px;
    padding: 34px 20px;
    margin-bottom: 25px;
    text-align: center;
    box-shadow: 0 2px 6px rgba(75, 46, 42, 0.04);
    overflow: hidden;
}

.brand-hero-bg {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-size: cover;
    background-repeat: no-repeat;
    opacity: 0.28;
    filter: blur(1px);
    transform: scale(1.03);
}

.brand-hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(180deg, rgba(250, 247, 242, 0.82) 0%, rgba(250, 247, 242, 0.94) 100%);
}

.brand-hero-content {
    position: relative;
    z-index: 2;
}

.brand-hero-logo {
    max-width: 180px;
    max-height: 70px;
    object-fit: contain;
    margin-bottom: 10px;
    filter: drop-shadow(0 1px 3px rgba(255, 255, 255, 0.9));
}

.brand-hero-subtitle {
    font-size: 15px;
    color: #73645A;
    font-weight: 500;
    margin: 0;
}

.brand-cat-row {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px solid #F0E8DF;
}

.brand-cat-label {
    font-size: 13px;
    font-weight: 600;
    color: #73645A;
    margin-right: 4px;
}

.brand-cat-btn {
    padding: 6px 15px;
    background-color: #ffffff;
    color: #332B27;
    border: 1px solid #E8DFD5;
    border-radius: 20px;
    font-size: 13px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
}

.brand-cat-btn:hover,
.brand-cat-btn.active {
    background-color: #4B2E2A;
    color: #ffffff;
    border-color: #4B2E2A;
}

.brand-products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 2px solid #E8DFD5;
    padding-bottom: 10px;
}

/* 3-Column Product Grid */
.watches-grid-3col {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

@media (max-width: 900px) {
    .watches-grid-3col {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .watches-grid-3col {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container">
    <!-- Back to Products Link -->
    <div style="margin-bottom: 15px;">
        <a href="products.php" style="color: #7A5645; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
            &larr; Back to All Products
        </a>
    </div>

    <?php if ($msg === 'cart_added'): ?>
        <div class="alert alert-success">Watch added to cart! <a href="cart.php" style="font-weight: bold; text-decoration: underline;">View Cart</a></div>
    <?php elseif ($msg === 'wishlist_added'): ?>
        <div class="alert alert-success">Watch saved to wishlist! <a href="wishlist.php" style="font-weight: bold; text-decoration: underline;">View Wishlist</a></div>
    <?php endif; ?>

    <!-- Brand Header Section with Subtle Brand Watch Background -->
    <div class="brand-hero-box">
        <?php if (!empty($brand_bg_image)): ?>
            <div class="brand-hero-bg" style="background-image: url('<?php echo htmlspecialchars($brand_bg_image); ?>'); background-position: <?php echo htmlspecialchars($brand_bg_pos); ?>;"></div>
        <?php endif; ?>
        <div class="brand-hero-overlay"></div>

        <div class="brand-hero-content">
            <img src="<?php echo htmlspecialchars($brand_logo); ?>" alt="<?php echo htmlspecialchars($brand); ?> Logo" class="brand-hero-logo">
            <p class="brand-hero-subtitle">Explore <?php echo htmlspecialchars($brand); ?> Watches</p>

            <!-- Dynamic Categories for THIS Brand Only -->
            <?php if (!empty($brand_categories) && count($brand_categories) > 0): ?>
                <div class="brand-cat-row">
                    <span class="brand-cat-label">Categories:</span>
                    <a href="brand.php?brand=<?php echo urlencode($brand); ?>" class="brand-cat-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">
                        All
                    </a>
                    <?php foreach ($brand_categories as $c): ?>
                        <a href="brand.php?brand=<?php echo urlencode($brand); ?>&category=<?php echo urlencode($c); ?>" class="brand-cat-btn <?php echo $category_filter === $c ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($c); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section Title -->
    <div class="brand-products-header">
        <h2 style="font-size: 18px; color: #0f172a; margin: 0; font-weight: 700;">
            <?php echo htmlspecialchars($brand); ?> Watches
            <span style="font-size: 13px; font-weight: normal; color: #64748b;">
                (<?php echo $total_products; ?> <?php echo $total_products === 1 ? 'watch' : 'watches'; ?>)
            </span>
        </h2>
        <?php if (!empty($category_filter)): ?>
            <a href="brand.php?brand=<?php echo urlencode($brand); ?>" style="font-size: 12px; color: #dc2626; text-decoration: none; font-weight: 600; padding: 4px 10px; background-color: #fee2e2; border-radius: 4px;">
                &times; Clear Category
            </a>
        <?php endif; ?>
    </div>

    <!-- Product Cards Grid (3 Columns, Exact Existing Cards) -->
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <div class="watches-grid-3col">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="simple-watch-card">
                    <div class="simple-watch-img">
                        <span class="category-tag"><?php echo htmlspecialchars($row['category']); ?></span>
                        <a href="product-details.php?id=<?php echo $row['id']; ?>" style="display: block; width: 100%; height: 100%;">
                            <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" onerror="this.onerror=null; this.src='https://via.placeholder.com/300x250?text=Watch';">
                        </a>
                    </div>
                    <div class="simple-watch-body">
                        <h3 class="simple-watch-title">
                            <a href="product-details.php?id=<?php echo $row['id']; ?>" style="color: inherit; text-decoration: none;">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </a>
                        </h3>
                        <p class="simple-watch-price">&#8377;<?php echo number_format($row['price'], 2); ?></p>
                        
                        <?php $r_data = getProductRatingData($row['id']); ?>
                        <div class="card-actions">
                            <a href="product-details.php?id=<?php echo $row['id']; ?>" class="card-rating-badge" title="Rating: <?php echo $r_data['rating']; ?> / 5 (<?php echo $r_data['count']; ?> reviews)">
                                <span class="card-rating-star">★</span> <?php echo number_format($r_data['rating'], 1); ?>
                            </a>
                            <a href="cart.php?action=add&id=<?php echo $row['id']; ?>" class="btn-sm btn-cart">Add to Cart</a>
                            <a href="wishlist.php?action=add&id=<?php echo $row['id']; ?>" class="btn-sm btn-wishlist" title="Save to Wishlist">&#9829;</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="info-card" style="text-align: center; padding: 40px;">
            <h3>No Watches Found</h3>
            <p style="color: #64748b; margin-bottom: 20px;">No watches found for this category in <?php echo htmlspecialchars($brand); ?>.</p>
            <a href="brand.php?brand=<?php echo urlencode($brand); ?>" class="btn btn-primary" style="display: inline-block; width: auto; padding: 8px 20px;">
                View All <?php echo htmlspecialchars($brand); ?> Watches
            </a>
        </div>
    <?php endif; ?>
</div>

<?php 
if (isset($stmt)) {
    mysqli_stmt_close($stmt);
}
require_once __DIR__ . '/includes/footer.php'; 
?>
