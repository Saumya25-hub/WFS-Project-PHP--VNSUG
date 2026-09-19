<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Calculate cart count
$nav_cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $c_item) {
        $nav_cart_count += intval($c_item['quantity'] ?? 1);
    }
}

// Calculate wishlist count
$nav_wishlist_count = 0;
if (isset($_SESSION['user_id']) && isset($conn)) {
    $uid = intval($_SESSION['user_id']);
    $w_res = mysqli_query($conn, "SELECT COUNT(*) FROM wishlist WHERE user_id = $uid");
    if ($w_res && $w_row = mysqli_fetch_array($w_res)) {
        $nav_wishlist_count = intval($w_row[0]);
    }
} elseif (isset($_SESSION['wishlist']) && is_array($_SESSION['wishlist'])) {
    $nav_wishlist_count = count($_SESSION['wishlist']);
}
// Determine current script for active navbar highlight
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$is_home      = in_array($current_page, ['index.php', '']);
$is_products  = in_array($current_page, ['products.php', 'brand.php', 'product-details.php']);
$is_wishlist  = ($current_page === 'wishlist.php');
$is_cart      = in_array($current_page, ['cart.php', 'checkout.php']);
$is_feedback  = ($current_page === 'feedback.php');
$is_login     = ($current_page === 'login.php');
$is_register  = ($current_page === 'register.php');
$is_account   = ($current_page === 'dashboard.php');
$is_admin     = ($current_page === 'admin_dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - WatchStore' : 'WatchStore — Online Watches Store'; ?></title>
    <link rel="stylesheet" href="css/style.css?v=14">
</head>
<body>

<header>
    <div class="navbar">
        <a href="index.php" class="brand-logo" title="WatchStore - Home">
            <span class="brand-icon">
                <svg viewBox="0 0 40 40" width="34" height="34" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <!-- Strap Lugs (Top & Bottom) -->
                    <path d="M14 5.5h12v4H14zM14 30.5h12v4H14z" fill="#C6A58D" rx="1.5" opacity="0.85"/>
                    <path d="M16 2.5h8v3h-8zM16 34.5h8v3h-8z" fill="#7A5645" rx="1" opacity="0.7"/>
                    <!-- Outer Round Watch Case -->
                    <circle cx="20" cy="20" r="14.5" fill="#1E1A18" stroke="#EFE3D6" stroke-width="1.8"/>
                    <!-- Refined Inner Bezel Ring -->
                    <circle cx="20" cy="20" r="11.8" stroke="#C6A58D" stroke-width="0.8" opacity="0.75"/>
                    <!-- Crown at 3 o'clock -->
                    <path d="M34.5 18h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2v-4z" fill="#C6A58D"/>
                    <!-- 4 Major Hour Markers (12, 3, 6, 9) -->
                    <line x1="20" y1="9.5" x2="20" y2="12" stroke="#C6A58D" stroke-width="1.6" stroke-linecap="round"/>
                    <line x1="20" y1="28" x2="20" y2="30.5" stroke="#C6A58D" stroke-width="1.6" stroke-linecap="round"/>
                    <line x1="9.5" y1="20" x2="12" y2="20" stroke="#C6A58D" stroke-width="1.6" stroke-linecap="round"/>
                    <line x1="28" y1="20" x2="30.5" y2="20" stroke="#C6A58D" stroke-width="1.6" stroke-linecap="round"/>
                    <!-- Minor Hour Dots -->
                    <circle cx="25.5" cy="12.5" r="0.65" fill="#C6A58D"/>
                    <circle cx="28.5" cy="15.5" r="0.65" fill="#C6A58D"/>
                    <circle cx="28.5" cy="24.5" r="0.65" fill="#C6A58D"/>
                    <circle cx="25.5" cy="27.5" r="0.65" fill="#C6A58D"/>
                    <circle cx="14.5" cy="27.5" r="0.65" fill="#C6A58D"/>
                    <circle cx="11.5" cy="24.5" r="0.65" fill="#C6A58D"/>
                    <circle cx="11.5" cy="15.5" r="0.65" fill="#C6A58D"/>
                    <circle cx="14.5" cy="12.5" r="0.65" fill="#C6A58D"/>
                    <!-- Classic 10:10 Luxury Watch Hands -->
                    <line x1="20" y1="20" x2="15.2" y2="14.2" stroke="#EFE3D6" stroke-width="1.8" stroke-linecap="round"/>
                    <line x1="20" y1="20" x2="25.5" y2="14.8" stroke="#EFE3D6" stroke-width="1.5" stroke-linecap="round"/>
                    <!-- Center Pinion Jewel -->
                    <circle cx="20" cy="20" r="1.5" fill="#C6A58D"/>
                </svg>
            </span>
            <span class="brand-text">
                <span class="brand-title">Watch<span class="brand-accent">Store</span></span>
                <span class="brand-subtitle">TIMEPIECES</span>
            </span>
        </a>
        <!-- Navbar Search (Capsule Component) -->
        <form action="products.php" method="GET" class="nav-search-form">
            <input type="text" name="search" class="nav-search-input" placeholder="Search watches..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit" class="nav-search-btn" title="Search">
                <svg viewBox="0 0 24 24" width="13" height="13" stroke="#FFFFFF" stroke-width="2.3" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"></circle>
                    <line x1="21" y1="21" x2="16" y2="16"></line>
                </svg>
            </button>
        </form>
        <ul class="nav-links">
            <li>
                <a href="index.php" class="<?php echo $is_home ? 'active' : ''; ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    Home
                </a>
            </li>
            <li>
                <a href="products.php" class="<?php echo $is_products ? 'active' : ''; ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="7"></circle><polyline points="12 9 12 12 13.5 13.5"></polyline><path d="M16.5 17.3l-.8 3.7a2 2 0 0 1-2 1.5h-3.4a2 2 0 0 1-2-1.5l-.8-3.7"></path><path d="M7.5 6.7l.8-3.7a2 2 0 0 1 2-1.5h3.4a2 2 0 0 1 2 1.5l.8 3.7"></path></svg>
                    Products
                </a>
            </li>
            <li>
                <a href="wishlist.php" class="<?php echo $is_wishlist ? 'active' : ''; ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                    Wishlist<?php if ($nav_wishlist_count > 0): ?><span class="nav-badge"><?php echo $nav_wishlist_count; ?></span><?php endif; ?>
                </a>
            </li>
            <li>
                <a href="cart.php" class="<?php echo $is_cart ? 'active' : ''; ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    Cart<?php if ($nav_cart_count > 0): ?><span class="nav-badge"><?php echo $nav_cart_count; ?></span><?php endif; ?>
                </a>
            </li>
            <li>
                <a href="feedback.php" class="<?php echo $is_feedback ? 'active' : ''; ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    Feedback
                </a>
            </li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li>
                    <a href="dashboard.php" class="<?php echo $is_account ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        My Account (<?php echo htmlspecialchars($_SESSION['user_name']); ?>)
                    </a>
                </li>
                <li>
                    <a href="logout.php" class="btn-nav">
                        <svg class="nav-icon" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        Logout
                    </a>
                </li>
            <?php else: ?>
                <li>
                    <a href="login.php" class="<?php echo $is_login ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        Login
                    </a>
                </li>
                <li>
                    <a href="register.php" class="btn-nav <?php echo $is_register ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                        Register
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</header>

