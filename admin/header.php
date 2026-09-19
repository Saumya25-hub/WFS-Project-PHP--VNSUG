<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$active_admin_page = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - WatchStore Admin' : 'Admin Panel - WatchStore'; ?></title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<nav class="admin-navbar">
    <a href="dashboard.php" class="admin-brand">
        WatchStore Admin
    </a>
    <ul class="admin-nav-links">
        <li>
            <a href="dashboard.php" class="<?php echo ($active_admin_page === 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a>
        </li>
        <li>
            <a href="products.php" class="<?php echo (strpos($active_admin_page, 'product') !== false) ? 'active' : ''; ?>">Products</a>
        </li>
        <li>
            <a href="brands.php" class="<?php echo (strpos($active_admin_page, 'brand') !== false) ? 'active' : ''; ?>">Brands</a>
        </li>
        <li>
            <a href="reviews.php" class="<?php echo (strpos($active_admin_page, 'review') !== false) ? 'active' : ''; ?>">Reviews</a>
        </li>
        <li>
            <a href="../index.php" target="_blank" class="btn-client">View Store</a>
        </li>
        <li>
            <span class="admin-user-text">admin</span>
        </li>
        <li>
            <a href="logout.php" class="btn-logout" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
        </li>
    </ul>
</nav>

<div class="admin-container">
