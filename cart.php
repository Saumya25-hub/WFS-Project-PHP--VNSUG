<?php
$page_title = "Shopping Cart";
require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart in session if not set
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$product_id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
$quantity = max(1, intval($_POST['quantity'] ?? $_GET['quantity'] ?? 1));

// Handle Add to Cart
if ($action === 'add' && $product_id > 0) {
    // Check if user is logged in before adding to cart
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['login_redirect'] = 'cart.php?action=add&id=' . $product_id . '&quantity=' . $quantity;
        $_SESSION['login_notice'] = 'Please log in to add items to your cart.';
        header("Location: login.php");
        exit();
    }

    // Fetch product details from DB
    $stmt = mysqli_prepare($conn, "SELECT id, name, price, image, category, quantity FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($prod = mysqli_fetch_assoc($res)) {
        $max_stock = intval($prod['quantity']);
        if ($max_stock > 0) {
            if (isset($_SESSION['cart'][$product_id])) {
                $new_qty = min($max_stock, $_SESSION['cart'][$product_id]['quantity'] + $quantity);
                $_SESSION['cart'][$product_id]['quantity'] = $new_qty;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'id'       => $prod['id'],
                    'name'     => $prod['name'],
                    'price'    => floatval($prod['price']),
                    'image'    => $prod['image'],
                    'category' => $prod['category'],
                    'quantity' => min($max_stock, $quantity),
                    'max_stock'=> $max_stock
                ];
            }
        }
    }
    mysqli_stmt_close($stmt);

    // Redirect to cart or back with message
    header("Location: cart.php");
    exit();
}

// Handle Remove Item
if ($action === 'remove' && $product_id > 0) {
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
    header("Location: cart.php");
    exit();
}

// Handle Update Quantities
if ($action === 'update' && isset($_POST['quantities']) && is_array($_POST['quantities'])) {
    foreach ($_POST['quantities'] as $p_id => $p_qty) {
        $p_id = intval($p_id);
        $p_qty = intval($p_qty);
        if (isset($_SESSION['cart'][$p_id])) {
            if ($p_qty <= 0) {
                unset($_SESSION['cart'][$p_id]);
            } else {
                $max_stock = $_SESSION['cart'][$p_id]['max_stock'] ?? 99;
                $_SESSION['cart'][$p_id]['quantity'] = min($max_stock, $p_qty);
            }
        }
    }
    header("Location: cart.php");
    exit();
}

// Handle Clear Cart
if ($action === 'clear') {
    $_SESSION['cart'] = [];
    header("Location: cart.php");
    exit();
}

// Calculate totals
$grand_total = 0;
$total_items = 0;
foreach ($_SESSION['cart'] as $item) {
    $grand_total += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="hero-box" style="padding: 20px 25px; margin-bottom: 20px;">
        <h1 style="font-size: 22px; margin-bottom: 5px;">Your Shopping Cart</h1>
        <p style="margin-bottom: 0; font-size: 14px;">Review your selected watches, adjust quantities, and proceed to checkout.</p>
    </div>

    <?php if (!empty($_SESSION['cart'])): ?>
        <div class="cart-layout">
            <!-- Left: Cart Items Table -->
            <div>
                <form method="POST" action="cart.php">
                    <input type="hidden" name="action" value="update">

                    <div class="cart-table-wrapper">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Watch</th>
                                    <th>Price</th>
                                    <th style="width: 110px;">Quantity</th>
                                    <th>Subtotal</th>
                                    <th style="text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['cart'] as $id => $item): 
                                    $item_subtotal = $item['price'] * $item['quantity'];
                                ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-img" onerror="this.onerror=null; this.src='https://via.placeholder.com/60x60?text=Watch';">
                                                <div>
                                                    <a href="product-details.php?id=<?php echo $id; ?>" style="font-weight: 600; color: #0f172a; text-decoration: none; font-size: 14px;">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </a>
                                                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                                        Category: <?php echo htmlspecialchars($item['category']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="font-weight: 600; color: #334155;">
                                            &#8377;<?php echo number_format($item['price'], 2); ?>
                                        </td>
                                        <td>
                                            <input type="number" name="quantities[<?php echo $id; ?>]" value="<?php echo intval($item['quantity']); ?>" min="1" max="<?php echo intval($item['max_stock'] ?? 50); ?>" class="qty-input" style="width: 65px; padding: 6px;">
                                        </td>
                                        <td style="font-weight: 700; color: #4B2E2A;">
                                            &#8377;<?php echo number_format($item_subtotal, 2); ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <a href="cart.php?action=remove&id=<?php echo $id; ?>" onclick="return confirm('Remove this watch from cart?');" style="color: #9E3838; text-decoration: none; font-size: 13px; font-weight: 600; padding: 4px 8px; background-color: #FBEBEB; border-radius: 4px; border: 1px solid #EFC8C8;">
                                                Remove
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <div>
                            <a href="products.php" class="btn btn-secondary" style="display: inline-block; width: auto; padding: 8px 16px; font-size: 14px; text-decoration: none;">
                                &larr; Continue Shopping
                            </a>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn" style="width: auto; padding: 8px 16px; font-size: 14px; background-color: #7A5645;">
                                Update Cart
                            </button>
                            <a href="cart.php?action=clear" onclick="return confirm('Are you sure you want to clear your entire cart?');" class="btn btn-danger" style="width: auto; padding: 8px 16px; font-size: 14px; text-decoration: none;">
                                Clear Cart
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right: Cart Summary Box -->
            <div>
                <div class="cart-summary-box">
                    <h3>Order Summary</h3>
                    
                    <div class="summary-row">
                        <span>Total Items:</span>
                        <strong><?php echo $total_items; ?> units</strong>
                    </div>
                    <div class="summary-row">
                        <span>Items Subtotal:</span>
                        <span>&#8377;<?php echo number_format($grand_total, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Estimated Shipping:</span>
                        <span style="color: #16a34a; font-weight: 600;">FREE</span>
                    </div>
                    <div class="summary-row">
                        <span>Estimated Tax (GST):</span>
                        <span>Included</span>
                    </div>

                    <div class="summary-total">
                        <span>Grand Total:</span>
                        <span style="color: #4B2E2A;">&#8377;<?php echo number_format($grand_total, 2); ?></span>
                    </div>

                    <div style="margin-top: 20px;">
                        <a href="checkout.php" class="btn" style="display: block; width: 100%; padding: 12px; font-size: 16px; text-decoration: none; text-align: center;">
                            Proceed to Checkout &rarr;
                        </a>
                    </div>

                    <div style="margin-top: 15px; font-size: 12px; color: #64748b; text-align: center;">
                        &#128274; 100% Secure Checkout
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="info-card" style="text-align: center; padding: 50px 20px;">
            <div style="font-size: 40px; margin-bottom: 10px;">&#128722;</div>
            <h2 style="font-size: 20px; color: #0f172a; margin-bottom: 8px;">Your Shopping Cart is Empty</h2>
            <p style="color: #64748b; margin-bottom: 25px; font-size: 14px;">Looks like you haven't added any watches to your cart yet.</p>
            <a href="products.php" class="btn btn-primary" style="display: inline-block; width: auto; padding: 10px 25px; font-size: 14px; text-decoration: none;">
                Explore Watch Collection
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
