<?php
$page_title = "My Wishlist";
require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$action = $_GET['action'] ?? '';
$product_id = intval($_GET['id'] ?? 0);

// Initialize guest session wishlist
if (!isset($_SESSION['wishlist']) || !is_array($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

// Handle Add to Wishlist
if ($action === 'add' && $product_id > 0) {
    if ($user_id) {
        $chk = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id = $user_id AND product_id = $product_id");
        if (mysqli_num_rows($chk) == 0) {
            $ins = mysqli_prepare($conn, "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($ins, "ii", $user_id, $product_id);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
        }
    } else {
        if (!in_array($product_id, $_SESSION['wishlist'])) {
            $_SESSION['wishlist'][] = $product_id;
        }
    }
    header("Location: wishlist.php?msg=added");
    exit();
}

// Handle Remove from Wishlist
if ($action === 'remove' && $product_id > 0) {
    if ($user_id) {
        mysqli_query($conn, "DELETE FROM wishlist WHERE user_id = $user_id AND product_id = $product_id");
    } else {
        if (($key = array_search($product_id, $_SESSION['wishlist'])) !== false) {
            unset($_SESSION['wishlist'][$key]);
        }
    }
    header("Location: wishlist.php?msg=removed");
    exit();
}

// Fetch all wishlist products
$wishlist_items = [];
if ($user_id) {
    $w_sql = "SELECT p.*, w.id AS wishlist_id, w.created_at AS saved_at 
              FROM wishlist w 
              JOIN products p ON w.product_id = p.id 
              WHERE w.user_id = $user_id 
              ORDER BY w.id DESC";
    $w_res = mysqli_query($conn, $w_sql);
    while ($row = mysqli_fetch_assoc($w_res)) {
        $wishlist_items[] = $row;
    }
} else {
    if (!empty($_SESSION['wishlist'])) {
        $ids_str = implode(',', array_map('intval', $_SESSION['wishlist']));
        $w_res = mysqli_query($conn, "SELECT * FROM products WHERE id IN ($ids_str)");
        while ($row = mysqli_fetch_assoc($w_res)) {
            $wishlist_items[] = $row;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
$msg = $_GET['msg'] ?? '';
?>

<div class="container">
    <div class="hero-box" style="padding: 20px 25px; margin-bottom: 20px;">
        <h1 style="font-size: 22px; margin-bottom: 5px;">My Wishlist</h1>
        <p style="margin-bottom: 0; font-size: 14px;">Your saved watches.</p>
    </div>

    <?php if ($msg === 'added'): ?>
        <div class="alert alert-success">Watch added to wishlist!</div>
    <?php elseif ($msg === 'removed'): ?>
        <div class="alert alert-success">Watch removed from wishlist.</div>
    <?php endif; ?>

    <?php if (!empty($wishlist_items)): ?>
        <div class="cart-table-wrapper">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Watch</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wishlist_items as $item): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-img" onerror="this.onerror=null; this.src='https://via.placeholder.com/60x60?text=Watch';">
                                    <div>
                                        <a href="product-details.php?id=<?php echo $item['id']; ?>" style="font-weight: 600; color: #1E1A18; text-decoration: none; font-size: 14px;">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge-week" style="margin-bottom: 0; font-size: 12px; padding: 2px 8px;">
                                    <?php echo htmlspecialchars($item['category']); ?>
                                </span>
                            </td>
                            <td style="font-weight: 700; color: #4B2E2A;">
                                &#8377;<?php echo number_format($item['price'], 2); ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; justify-content: center; gap: 8px;">
                                    <a href="cart.php?action=add&id=<?php echo $item['id']; ?>" class="btn-sm btn-cart" style="text-decoration: none; padding: 6px 12px; font-size: 13px;">
                                        Add to Cart
                                    </a>
                                    <a href="wishlist.php?action=remove&id=<?php echo $item['id']; ?>" onclick="return confirm('Remove from wishlist?');" style="color: #9E3838; text-decoration: none; font-size: 13px; font-weight: 600; padding: 6px 10px; background-color: #FBEBEB; border-radius: 4px; border: 1px solid #EFC8C8;">
                                        Remove
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px; display: flex; justify-content: space-between;">
            <a href="products.php" class="btn btn-secondary" style="display: inline-block; width: auto; padding: 8px 16px; font-size: 14px; text-decoration: none;">
                &larr; Explore More Watches
            </a>
            <a href="cart.php" class="btn btn-primary" style="display: inline-block; width: auto; padding: 8px 16px; font-size: 14px; text-decoration: none;">
                View Shopping Cart &rarr;
            </a>
        </div>
    <?php else: ?>
        <div class="info-card" style="text-align: center; padding: 50px 20px;">
            <div style="font-size: 40px; margin-bottom: 10px; color: #e11d48;">&#9825;</div>
            <h2 style="font-size: 20px; color: #0f172a; margin-bottom: 8px;">Your Wishlist is Empty</h2>
            <p style="color: #64748b; margin-bottom: 25px; font-size: 14px;">You haven't saved any watches yet.</p>
            <a href="products.php" class="btn btn-primary" style="display: inline-block; width: auto; padding: 10px 25px; font-size: 14px; text-decoration: none;">
                Browse Watches
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
