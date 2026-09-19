<?php
require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$product_id = intval($_GET['id'] ?? 0);
if ($product_id <= 0) {
    header("Location: products.php");
    exit();
}

// Handle review submission (POST)
$review_success = '';
$review_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $review_error = "Please login to submit a review.";
    } else {
        $user_id = intval($_SESSION['user_id']);
        $rating = intval($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $review_error = "Please select a rating from 1 to 5 stars.";
        } elseif (empty($comment)) {
            $review_error = "Please write a review comment.";
        } else {
            // Check if user has already reviewed this watch
            $chk_stmt = mysqli_prepare($conn, "SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
            mysqli_stmt_bind_param($chk_stmt, "ii", $user_id, $product_id);
            mysqli_stmt_execute($chk_stmt);
            $chk_res = mysqli_stmt_get_result($chk_stmt);
            if (mysqli_num_rows($chk_res) > 0) {
                $review_error = "You have already submitted a review for this watch.";
            } else {
                $ins_stmt = mysqli_prepare($conn, "INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($ins_stmt, "iiis", $product_id, $user_id, $rating, $comment);
                if (mysqli_stmt_execute($ins_stmt)) {
                    $review_success = "Thank you! Your review has been submitted.";
                } else {
                    $review_error = "Error saving review. Please try again.";
                }
                mysqli_stmt_close($ins_stmt);
            }
            mysqli_stmt_close($chk_stmt);
        }
    }
}

// Fetch single watch details from MySQL database
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$product) {
    $page_title = "Product Not Found";
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="info-card" style="text-align: center; padding: 50px;"><h2>Watch Not Found</h2><p>The requested watch does not exist.</p><a href="products.php" class="btn btn-primary" style="display: inline-block; width: auto; margin-top: 15px;">Back to Products</a></div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

// Check if logged-in user has already reviewed this product
$user_has_reviewed = false;
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $user_chk = mysqli_query($conn, "SELECT id FROM reviews WHERE user_id = $uid AND product_id = $product_id");
    if ($user_chk && mysqli_num_rows($user_chk) > 0) {
        $user_has_reviewed = true;
    }
}

// Fetch average rating and review count from database
$summary_query = "SELECT COUNT(*) AS total_reviews, AVG(rating) AS avg_rating FROM reviews WHERE product_id = ?";
$sum_stmt = mysqli_prepare($conn, $summary_query);
mysqli_stmt_bind_param($sum_stmt, "i", $product_id);
mysqli_stmt_execute($sum_stmt);
$sum_res = mysqli_stmt_get_result($sum_stmt);
$summary = mysqli_fetch_assoc($sum_res);
$total_reviews = intval($summary['total_reviews'] ?? 0);
$avg_rating = $total_reviews > 0 ? round(floatval($summary['avg_rating']), 1) : 0;
mysqli_stmt_close($sum_stmt);

// Fetch actual reviews belonging to this specific product
$rev_stmt = mysqli_prepare($conn, "SELECT r.*, u.full_name FROM reviews r JOIN users u ON r.user_id = u.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC, r.id DESC");
mysqli_stmt_bind_param($rev_stmt, "i", $product_id);
mysqli_stmt_execute($rev_stmt);
$reviews_result = mysqli_stmt_get_result($rev_stmt);
$reviews_list = [];
if ($reviews_result) {
    while ($r_row = mysqli_fetch_assoc($reviews_result)) {
        $reviews_list[] = $r_row;
    }
}
mysqli_stmt_close($rev_stmt);

// Star display helper function
if (!function_exists('renderStars')) {
    function renderStars($rating) {
        $rounded = round($rating);
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rounded) {
                $stars .= '★';
            } else {
                $stars .= '☆';
            }
        }
        return $stars;
    }
}

$page_title = $product['name'];
require_once __DIR__ . '/includes/header.php';
$msg = $_GET['msg'] ?? '';
?>

<div class="container">
    <div style="margin-bottom: 15px;">
        <a href="products.php" style="color: #7A5645; text-decoration: none; font-size: 14px; font-weight: 500;">
            &larr; Back to Products
        </a>
    </div>

    <?php if ($msg === 'cart_added'): ?>
        <div class="alert alert-success">Watch added to cart! <a href="cart.php" style="font-weight: bold; text-decoration: underline;">View Cart</a></div>
    <?php elseif ($msg === 'wishlist_added'): ?>
        <div class="alert alert-success">Watch saved to wishlist! <a href="wishlist.php" style="font-weight: bold; text-decoration: underline;">View Wishlist</a></div>
    <?php endif; ?>

    <div class="product-details-wrapper">
        <!-- Left: Large Watch Image -->
        <div class="product-gallery">
            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.onerror=null; this.src='https://via.placeholder.com/400x400?text=Watch';">
        </div>

        <!-- Right: Product Information & Purchase Form -->
        <div class="product-info-panel">
            <div class="product-meta-row">
                <span class="badge-week" style="margin-bottom: 0; background-color: #F5EFEB; color: #4B2E2A; border: 1px solid #E8DFD5;">
                    Category: <strong><?php echo htmlspecialchars($product['category']); ?></strong>
                </span>
                <?php if (!empty($product['brand'])): ?>
                    <span class="badge-week" style="margin-bottom: 0; background-color: #F5EFEB; color: #4B2E2A; border: 1px solid #E8DFD5;">
                        Brand: <strong><?php echo htmlspecialchars($product['brand']); ?></strong>
                    </span>
                <?php endif; ?>
            </div>

            <h1><?php echo htmlspecialchars($product['name']); ?></h1>

            <div style="font-size: 1.8rem; font-weight: 700; color: #4B2E2A;">
                &#8377;<?php echo number_format($product['price'], 2); ?>
            </div>

            <div class="product-desc">
                <h4 style="font-size: 14px; color: #1E1A18; margin-bottom: 6px; font-weight: 600;">Description:</h4>
                <p style="margin: 0;"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <!-- Form: Add to Cart with Quantity -->
            <form method="POST" action="cart.php">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">

                <div class="product-form-group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" class="qty-input" value="1" min="1" required>
                </div>

                <div class="product-action-buttons">
                    <button type="submit" class="btn" style="width: auto; padding: 10px 24px; font-size: 14px;">
                        Add to Cart
                    </button>
                    <a href="wishlist.php?action=add&id=<?php echo $product['id']; ?>" class="btn btn-secondary" style="width: auto; padding: 10px 20px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        <span>&#9829;</span> Add to Wishlist
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Customer Reviews & Star Ratings Section -->
    <div class="reviews-section">
        <div class="reviews-header">
            <h2 class="reviews-title">Customer Reviews</h2>
            <div class="reviews-summary">
                <?php if ($total_reviews > 0): ?>
                    <span class="reviews-stars-avg"><?php echo renderStars($avg_rating); ?></span>
                    <span class="reviews-score"><?php echo number_format($avg_rating, 1); ?> / 5</span>
                    <span class="reviews-count">(<?php echo $total_reviews; ?> <?php echo $total_reviews === 1 ? 'Review' : 'Reviews'; ?>)</span>
                <?php else: ?>
                    <span class="reviews-count" style="font-size: 14px; color: #73645A;">No reviews yet. Be the first to review this watch!</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($review_success)): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">
                ✅ <?php echo htmlspecialchars($review_success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($review_error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px; background-color: #fde8e8; color: #9b1c1c; padding: 12px 15px; border-radius: 6px; border: 1px solid #f8b4b4;">
                ⚠️ <?php echo htmlspecialchars($review_error); ?>
            </div>
        <?php endif; ?>

        <!-- Write a Review Form -->
        <div class="review-form-card">
            <h3 class="review-form-title">Write a Review</h3>

            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($user_has_reviewed): ?>
                    <p style="margin: 0; color: #4B2E2A; font-size: 14px; font-weight: 500;">
                        ✓ You have already submitted your review for this watch. Thank you!
                    </p>
                <?php else: ?>
                    <form method="POST" action="product-details.php?id=<?php echo $product['id']; ?>">
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 13.5px; font-weight: 600; color: #1E1A18; margin-bottom: 6px;">Your Rating:</label>
                            <div class="star-rating-select">
                                <input type="radio" id="star5" name="rating" value="5" required>
                                <label for="star5" title="5 Stars">★</label>
                                <input type="radio" id="star4" name="rating" value="4">
                                <label for="star4" title="4 Stars">★</label>
                                <input type="radio" id="star3" name="rating" value="3">
                                <label for="star3" title="3 Stars">★</label>
                                <input type="radio" id="star2" name="rating" value="2">
                                <label for="star2" title="2 Stars">★</label>
                                <input type="radio" id="star1" name="rating" value="1">
                                <label for="star1" title="1 Star">★</label>
                            </div>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label for="review_comment" style="display: block; font-size: 13.5px; font-weight: 600; color: #1E1A18; margin-bottom: 6px;">Your Review:</label>
                            <textarea name="comment" id="review_comment" rows="3" class="form-control" placeholder="Write your feedback about this watch..." required style="resize: vertical;"></textarea>
                        </div>

                        <button type="submit" name="submit_review" class="btn" style="width: auto; padding: 9px 24px; font-size: 14px;">
                            Submit Review
                        </button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p style="margin: 0; font-size: 14px; color: #73645A;">
                    Please <a href="login.php" style="color: #7A5645; font-weight: 700; text-decoration: underline;">login</a> to submit a review.
                </p>
            <?php endif; ?>
        </div>

        <!-- Existing Reviews List -->
        <?php if (!empty($reviews_list)): ?>
            <div class="review-list">
                <?php foreach ($reviews_list as $rev): ?>
                    <div class="review-item">
                        <div class="review-author-row">
                            <span class="review-author"><?php echo htmlspecialchars($rev['full_name']); ?></span>
                            <span class="review-date"><?php echo date('d M Y', strtotime($rev['created_at'])); ?></span>
                        </div>
                        <div class="review-stars">
                            <?php echo renderStars($rev['rating']); ?>
                        </div>
                        <div class="review-comment">
                            "<?php echo nl2br(htmlspecialchars($rev['comment'])); ?>"
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
