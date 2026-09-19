<?php
$page_title = "Dashboard";
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/header.php';

// Fetch statistics
$product_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as total FROM products");
if ($res && $r = mysqli_fetch_assoc($res)) $product_count = $r['total'];

$brand_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as total FROM brands");
if ($res && $r = mysqli_fetch_assoc($res)) $brand_count = $r['total'];

$user_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
if ($res && $r = mysqli_fetch_assoc($res)) $user_count = $r['total'];

$order_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as total FROM orders");
if ($res && $r = mysqli_fetch_assoc($res)) $order_count = $r['total'];

// Fetch latest 5 products
$recent_products = [];
$res = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC LIMIT 5");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $recent_products[] = $row;
    }
}
?>

<div class="admin-page-header">
    <div>
        <h1>Admin Dashboard</h1>
        <p class="admin-page-subtitle">Manage your store from here.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="product_add.php" class="btn btn-primary">+ Add New Product</a>
        <a href="brand_add.php" class="btn btn-success">+ Add New Brand</a>
    </div>
</div>

<!-- Key Performance / Counter Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Products</h3>
        <div class="stat-value"><?php echo $product_count; ?></div>
        <a href="products.php" class="stat-link">View Products &rarr;</a>
    </div>

    <div class="stat-card card-green">
        <h3>Active Brands</h3>
        <div class="stat-value"><?php echo $brand_count; ?></div>
        <a href="brands.php" class="stat-link">View Brands &rarr;</a>
    </div>

    <div class="stat-card card-purple">
        <h3>Registered Users</h3>
        <div class="stat-value"><?php echo $user_count; ?></div>
        <span class="stat-sub">Total Users</span>
    </div>

    <div class="stat-card card-amber">
        <h3>Customer Orders</h3>
        <div class="stat-value"><?php echo $order_count; ?></div>
        <span class="stat-sub">Total Orders</span>
    </div>
</div>

<!-- Recent Products Section -->
<div class="admin-page-header" style="margin-top: 30px;">
    <h2>Recently Added Products</h2>
    <a href="products.php" class="btn btn-secondary btn-sm">View All Products</a>
</div>

<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 70px;">Image</th>
                <th>Product Name</th>
                <th>Brand</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th style="width: 130px; text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($recent_products)): ?>
                <?php foreach ($recent_products as $p): ?>
                    <tr>
                        <td>
                            <img src="../<?php echo htmlspecialchars($p['image']); ?>" alt="img" class="thumb" onerror="this.onerror=null; this.src='https://via.placeholder.com/50';">
                        </td>
                        <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($p['brand']); ?></td>
                        <td><span style="background: #e2e8f0; padding: 3px 8px; border-radius: 4px; font-size: 12px;"><?php echo htmlspecialchars($p['category']); ?></span></td>
                        <td>₹<?php echo number_format($p['price'], 2); ?></td>
                        <td>
                            <span style="font-weight: 600; color: <?php echo $p['quantity'] > 0 ? '#16a34a' : '#dc2626'; ?>;">
                                <?php echo $p['quantity']; ?> in stock
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 6px; justify-content: center;">
                                <a href="product_edit.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                <a href="product_delete.php?id=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b; padding: 30px;">No products found in the catalog.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
