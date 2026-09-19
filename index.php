<?php
$page_title = "Home";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// Fetch brands from database for the brand logo carousel (brands table or products fallback)
$home_brands = [];
$b_tbl = mysqli_query($conn, "SELECT name, logo FROM brands ORDER BY name ASC");
if ($b_tbl && mysqli_num_rows($b_tbl) > 0) {
    while ($b_row = mysqli_fetch_assoc($b_tbl)) {
        $logo_path = $b_row['logo'];
        if (!file_exists(__DIR__ . '/' . $logo_path)) {
            $logo_path = 'images/brands/all-brands.png';
        }
        $home_brands[] = [
            'name' => $b_row['name'],
            'logo' => $logo_path
        ];
    }
} else {
    $brand_query = "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC";
    $brand_res = mysqli_query($conn, $brand_query);
    if ($brand_res) {
        while ($b_row = mysqli_fetch_assoc($brand_res)) {
            $clean = strtolower(trim($b_row['brand']));
            $clean = str_replace([' ', '&', '.'], ['-', '', ''], $clean);
            $logo_path = 'images/brands/' . $clean . '.png';
            if (!file_exists(__DIR__ . '/' . $logo_path)) {
                $logo_path = 'images/brands/all-brands.png';
            }
            $home_brands[] = [
                'name' => $b_row['brand'],
                'logo' => $logo_path
            ];
        }
    }
}

// Fetch 6 featured watches from MySQL database
$featured_query = "SELECT * FROM products ORDER BY id ASC LIMIT 6";
$featured_result = mysqli_query($conn, $featured_query);
?>

<style>
/* Continuous Horizontal Brand Logo Carousel */
.brand-carousel-wrapper {
    width: 100%;
    overflow: hidden;
    padding: 10px 0 15px 0;
    position: relative;
    mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
    -webkit-mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
}

.brand-carousel-track {
    display: flex;
    gap: 22px;
    width: max-content;
    animation: scrollBrands 28s linear infinite;
}

.brand-carousel-track:hover {
    animation-play-state: paused;
}

@keyframes scrollBrands {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

.brand-logo-circle {
    width: 120px;
    height: 120px;
    min-width: 120px;
    min-height: 120px;
    border-radius: 50%;
    background-color: #ffffff;
    border: 1px solid #E5DACF;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    box-shadow: 0 3px 12px rgba(75, 46, 42, 0.05);
    text-decoration: none;
    transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
    cursor: pointer;
    flex-shrink: 0;
}

.brand-logo-circle:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 8px 22px rgba(75, 46, 42, 0.10);
    border-color: #C6A58D;
}

.brand-logo-circle img {
    max-width: 85px;
    max-height: 38px;
    width: auto;
    height: auto;
    object-fit: contain;
    transition: transform 0.25s ease;
}

.brand-logo-circle:hover img {
    transform: scale(1.06);
}

@media (max-width: 768px) {
    .brand-logo-circle {
        width: 100px;
        height: 100px;
        min-width: 100px;
        min-height: 100px;
        padding: 12px;
    }
    .brand-logo-circle img {
        max-width: 70px;
        max-height: 32px;
    }
    .brand-carousel-track {
        gap: 16px;
        animation-duration: 22s;
    }
}
</style>

<!-- Hero Banner with VIDEO AD 2.MP4 (Clearly Visible, Cinematic Luxury) -->
<div class="simple-hero">
    <video class="hero-bg-video" autoplay muted loop playsinline preload="auto">
        <source src="VIDEO AD 2.mp4" type="video/mp4">
        <source src="VIDEO/AD 2.mp4" type="video/mp4">
    </video>
    <div class="hero-overlay">
        <div class="container" style="text-align: center; padding: 0 20px;">
            <h1 style="font-size: 2.5rem; color: #FAF7F2; margin-bottom: 14px; font-weight: 700; text-shadow: 0 2px 10px rgba(0,0,0,0.7), 0 1px 3px rgba(0,0,0,0.9); letter-spacing: 0.5px;">
                Find Your Perfect Watch
            </h1>
            <p style="font-size: 1.1rem; color: #EFE3D6; max-width: 600px; margin: 0 auto 26px auto; line-height: 1.6; text-shadow: 0 2px 6px rgba(0,0,0,0.8);">
                Explore our curated collection of luxury, chronograph, smart, and classic timepieces at the best prices.
            </p>
            <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                <a href="products.php" class="btn btn-primary" style="display: inline-block; width: auto; padding: 12px 28px; font-size: 0.96rem; border: 1px solid #C6A58D; box-shadow: 0 4px 14px rgba(0,0,0,0.35);">
                    Browse All Watches
                </a>
                <a href="feedback.php" class="btn btn-secondary" style="display: inline-block; width: auto; padding: 12px 26px; font-size: 0.96rem; background-color: rgba(30, 26, 24, 0.55); border: 1px solid #C6A58D; color: #EFE3D6; box-shadow: 0 4px 14px rgba(0,0,0,0.3);">
                    Customer Feedback
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Shop by Brand Carousel Section (Curated Band with Soft Warm Tone) -->
<?php if (!empty($home_brands)): ?>
<div class="brand-section-full" style="width: 100%; margin-bottom: 50px; background: linear-gradient(180deg, #F5EFEB 0%, #FAF7F2 100%); border-top: 1px solid #EAE1D6; border-bottom: 1px solid #EAE1D6; padding: 34px 0 28px 0; overflow: hidden;">
    <div class="container" style="margin-bottom: 18px; margin-top: 0;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #E5DACF; padding-bottom: 10px;">
            <div>
                <span style="font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #7A5645; display: block; margin-bottom: 3px;">CURATED HOROLOGY</span>
                <h2 style="font-size: 1.4rem; color: #1E1A18; margin: 0 0 4px 0; font-weight: 700; font-family: Georgia, 'Times New Roman', serif;">
                    Shop by Brand
                </h2>
                <p style="margin: 0; font-size: 13.5px; color: #73645A;">
                    Explore timepieces from world-renowned watchmakers.
                </p>
            </div>
            <a href="products.php" style="color: #7A5645; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; transition: color 0.2s ease;">
                All Brands &rarr;
            </a>
        </div>
    </div>

    <div class="brand-carousel-wrapper" style="width: 100%; padding: 12px 0 20px 0;">
        <div class="brand-carousel-track">
            <?php 
            // Quadruple brands list for seamless infinite scroll on any wide screen or zoom level
            $carousel_brands = array_merge($home_brands, $home_brands, $home_brands, $home_brands);
            foreach ($carousel_brands as $b): 
            ?>
                <a href="brand.php?brand=<?php echo urlencode($b['name']); ?>" class="brand-logo-circle" title="Explore <?php echo htmlspecialchars($b['name']); ?> Watches">
                    <img src="<?php echo htmlspecialchars($b['logo']); ?>" alt="<?php echo htmlspecialchars($b['name']); ?> Logo">
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Featured Watches Section -->
<div class="container" id="featured-watches" style="padding-bottom: 60px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px; border-bottom: 2px solid #E5DACF; padding-bottom: 10px;">
        <div>
            <span style="font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #7A5645; display: block; margin-bottom: 3px;">POPULAR SELECTION</span>
            <h2 style="font-size: 1.4rem; color: #1E1A18; margin: 0; font-weight: 700; font-family: Georgia, 'Times New Roman', serif;">
                Featured Watches
            </h2>
        </div>
        <a href="products.php" style="color: #7A5645; text-decoration: none; font-size: 13.5px; font-weight: 600;">
            View All &rarr;
        </a>
    </div>

    <div class="simple-watches-grid">
        <?php while ($watch = mysqli_fetch_assoc($featured_result)): ?>
            <div class="simple-watch-card">
                <div class="simple-watch-img">
                    <span class="category-tag"><?php echo htmlspecialchars($watch['category']); ?></span>
                    <img src="<?php echo htmlspecialchars($watch['image']); ?>" alt="<?php echo htmlspecialchars($watch['name']); ?>" onerror="this.onerror=null; this.src='https://via.placeholder.com/300x250?text=Watch';">
                </div>
                <div class="simple-watch-body">
                    <h3 class="simple-watch-title"><?php echo htmlspecialchars($watch['name']); ?></h3>
                    <p class="simple-watch-price">&#8377;<?php echo number_format($watch['price'], 2); ?></p>
                    
                    <?php $r_data = getProductRatingData($watch['id']); ?>
                    <div class="card-actions">
                        <a href="product-details.php?id=<?php echo $watch['id']; ?>" class="card-rating-badge" title="Rating: <?php echo $r_data['rating']; ?> / 5 (<?php echo $r_data['count']; ?> reviews)">
                            <span class="card-rating-star">★</span> <?php echo number_format($r_data['rating'], 1); ?>
                        </a>
                        <a href="cart.php?action=add&id=<?php echo $watch['id']; ?>" class="btn-sm btn-cart">Add to Cart</a>
                        <a href="wishlist.php?action=add&id=<?php echo $watch['id']; ?>" class="btn-sm btn-wishlist" title="Save to Wishlist">&#9829;</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
