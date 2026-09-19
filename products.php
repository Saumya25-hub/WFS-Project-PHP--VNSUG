<?php
$page_title = "Products";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// Handle product search query
$search = trim($_GET['search'] ?? '');

// Fetch watches: filtered if search keyword is provided, otherwise all
if ($search !== '') {
    $sql = "SELECT * FROM products WHERE name LIKE ? OR brand LIKE ? ORDER BY id ASC";
    $stmt = mysqli_prepare($conn, $sql);
    $search_param = '%' . $search . '%';
    mysqli_stmt_bind_param($stmt, "ss", $search_param, $search_param);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $sql = "SELECT * FROM products ORDER BY id ASC";
    $result = mysqli_query($conn, $sql);
}
$total_products = $result ? mysqli_num_rows($result) : 0;

// Fetch actual brands from database (brands table or products fallback)
$brands = [];
$b_tbl = mysqli_query($conn, "SELECT name FROM brands ORDER BY name ASC");
if ($b_tbl && mysqli_num_rows($b_tbl) > 0) {
    while ($b_row = mysqli_fetch_assoc($b_tbl)) {
        $brands[] = $b_row['name'];
    }
} else {
    $brand_query = "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC";
    $brand_res = mysqli_query($conn, $brand_query);
    if ($brand_res) {
        while ($b_row = mysqli_fetch_assoc($brand_res)) {
            $brands[] = $b_row['brand'];
        }
    }
}

// Brand logo helper (queries brands table or fallback images)
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

$msg = $_GET['msg'] ?? '';
?>

<style>
/* Shop by Brand Section */
.brand-section {
    margin-bottom: 35px;
}

.brand-section-title {
    font-size: 15px;
    font-weight: 700;
    color: #1E1A18;
    margin-bottom: 15px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}

.brand-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 14px;
}

.brand-card {
    background-color: #ffffff;
    border: 1px solid #E8DFD5;
    border-radius: 8px;
    padding: 16px 10px 14px 10px;
    text-align: center;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 115px;
    box-shadow: 0 2px 6px rgba(75, 46, 42, 0.04);
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    cursor: pointer;
}

.brand-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 16px rgba(75, 46, 42, 0.09);
    border-color: #7A5645;
}

.brand-card-logo-wrap {
    width: 100%;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
}

.brand-card-logo-wrap img {
    max-width: 110px;
    max-height: 44px;
    object-fit: contain;
    transition: transform 0.2s ease;
}

.brand-card:hover .brand-card-logo-wrap img {
    transform: scale(1.05);
}

.brand-card-action {
    font-size: 12px;
    font-weight: 600;
    color: #7A5645;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    transition: color 0.2s ease;
}

.brand-card:hover .brand-card-action {
    color: #4B2E2A;
}

/* Products Section Header */
.products-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 2px solid #E8DFD5;
    padding-bottom: 10px;
}

/* Clean 3-Column Product Grid */
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
    <!-- Hero Box -->
    <div class="hero-box" style="padding: 20px 25px; margin-bottom: 25px;">
        <h1 style="font-size: 22px; margin-bottom: 5px;">Our Watches</h1>
        <p style="margin-bottom: 0; font-size: 14px;">Browse our collection of quality watches.</p>
    </div>

    <?php if ($msg === 'cart_added'): ?>
        <div class="alert alert-success">Watch added to cart! <a href="cart.php" style="font-weight: bold; text-decoration: underline;">View Cart</a></div>
    <?php elseif ($msg === 'wishlist_added'): ?>
        <div class="alert alert-success">Watch saved to wishlist! <a href="wishlist.php" style="font-weight: bold; text-decoration: underline;">View Wishlist</a></div>
    <?php endif; ?>

    <!-- Shop by Brand Section (Real Cards with Company Logos) -->
    <div class="brand-section">
        <div class="brand-section-title">Shop by Brand</div>

        <div class="brand-grid">
            <!-- Dynamic Brand Cards from Database -->
            <?php foreach ($brands as $b): ?>
                <?php $logo_img = getBrandLogo($b); ?>
                <a href="brand.php?brand=<?php echo urlencode($b); ?>" class="brand-card" title="Explore <?php echo htmlspecialchars($b); ?> Watches">
                    <div class="brand-card-logo-wrap">
                        <img src="<?php echo htmlspecialchars($logo_img); ?>" alt="<?php echo htmlspecialchars($b); ?> Logo">
                    </div>
                    <span class="brand-card-action">View Brand &rarr;</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>


    <!-- Product Catalog Header -->
    <div class="products-section-header">
        <h2 style="font-size: 18px; color: #1E1A18; margin: 0; font-weight: 700;">
            <?php if (!empty($search)): ?>
                Search Results for "<?php echo htmlspecialchars($search); ?>"
            <?php else: ?>
                All Watches
            <?php endif; ?>
            <span style="font-size: 13px; font-weight: normal; color: #73645A;">(<?php echo $total_products; ?> <?php echo $total_products === 1 ? 'watch' : 'watches'; ?>)</span>
        </h2>
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
        <div class="info-card" style="text-align: center; padding: 40px; background: #ffffff; border: 1px solid #E8DFD5; border-radius: 8px;">
            <p style="color: #73645A; font-size: 16px; margin: 0; font-weight: 600;">No products found.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
