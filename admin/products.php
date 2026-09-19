<?php
$page_title = "Manage Products";
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/header.php';

// Handle flash messages
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Fetch all products
$products = [];
$query = "SELECT * FROM products ORDER BY id DESC";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}
?>

<div class="admin-page-header">
    <div>
        <h1>Products Management</h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">View, add, edit, or delete watch products from the store catalog.</p>
    </div>
    <a href="product_add.php" class="btn btn-primary">+ Add New Product</a>
</div>

<?php if ($msg === 'added'): ?>
    <div class="alert alert-success">✅ Product added successfully! It is now visible on the client store.</div>
<?php elseif ($msg === 'updated'): ?>
    <div class="alert alert-success">✅ Product details updated successfully!</div>
<?php elseif ($msg === 'deleted'): ?>
    <div class="alert alert-success">✅ Product has been deleted from catalog.</div>
<?php endif; ?>

<?php if (!empty($err)): ?>
    <div class="alert alert-danger">⚠️ <?php echo htmlspecialchars($err); ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th style="width: 70px;">Image</th>
                <th>Product Name</th>
                <th>Brand</th>
                <th>Category</th>
                <th>Price (₹)</th>
                <th>Stock</th>
                <th style="width: 140px; text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>#<?php echo $p['id']; ?></td>
                        <td>
                            <img src="../<?php echo htmlspecialchars($p['image']); ?>" alt="product" class="thumb" onerror="this.onerror=null; this.src='https://via.placeholder.com/50';">
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                            <div style="font-size: 12px; color: #64748b; margin-top: 3px;">
                                <?php echo htmlspecialchars(mb_strimwidth($p['description'], 0, 60, '...')); ?>
                            </div>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: #0284c7;"><?php echo htmlspecialchars($p['brand']); ?></span>
                        </td>
                        <td>
                            <span style="background: #e2e8f0; padding: 3px 8px; border-radius: 4px; font-size: 12px;"><?php echo htmlspecialchars($p['category']); ?></span>
                        </td>
                        <td><strong>₹<?php echo number_format($p['price'], 2); ?></strong></td>
                        <td>
                            <span style="font-weight: 600; color: <?php echo $p['quantity'] > 0 ? '#16a34a' : '#dc2626'; ?>;">
                                <?php echo $p['quantity']; ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 6px; justify-content: center;">
                                <a href="product_edit.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                <a href="product_delete.php?id=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete \'<?php echo addslashes($p['name']); ?>\'?');">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b; padding: 35px;">No products found in the catalog.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
