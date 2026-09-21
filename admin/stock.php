<?php
$page_title = "Stock Management";
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

// Handle Stock Operations
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Direct Stock Quantity Update
    if ($action === 'set_stock') {
        $product_id = intval($_POST['product_id'] ?? 0);
        $new_qty = intval($_POST['quantity'] ?? 0);
        if ($new_qty < 0) $new_qty = 0;

        if ($product_id > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE products SET quantity = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $new_qty, $product_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                header("Location: stock.php?msg=updated");
                exit();
            }
        }
    }

    // 2. Add Additional Stock to Product
    if ($action === 'add_stock') {
        $product_id = intval($_POST['product_id'] ?? 0);
        $add_qty = intval($_POST['add_qty'] ?? 0);

        if ($product_id > 0 && $add_qty > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE products SET quantity = quantity + ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $add_qty, $product_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                header("Location: stock.php?msg=added");
                exit();
            }
        } else {
            $err = "Please select a valid product and enter a positive quantity.";
        }
    }
}

require_once __DIR__ . '/header.php';
$flash_msg = $_GET['msg'] ?? '';

// Fetch products & calculate stock metrics
$products = [];
$total_products = 0;
$available_cnt = 0;
$out_stock_cnt = 0;

$res = mysqli_query($conn, "SELECT * FROM products ORDER BY name ASC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $products[] = $row;
        $total_products++;
        $qty = (int)$row['quantity'];
        if ($qty <= 0) {
            $out_stock_cnt++;
        } else {
            $available_cnt++;
        }
    }
}
?>

<div class="admin-page-header">
    <div>
        <h1>Stock Management</h1>
        <p class="admin-page-subtitle">Manage watch stock quantity and availability.</p>
    </div>
    <a href="products.php" class="btn btn-secondary">View Product Catalog</a>
</div>

<!-- Stock Overview Counter Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Products</h3>
        <div class="stat-value"><?php echo $total_products; ?></div>
        <span class="stat-sub">Watches in store</span>
    </div>

    <div class="stat-card card-green">
        <h3>Available Stock</h3>
        <div class="stat-value"><?php echo $available_cnt; ?></div>
        <span class="stat-sub">Products in stock</span>
    </div>

    <div class="stat-card card-purple" style="border-left-color: #dc3545;">
        <h3>Out of Stock</h3>
        <div class="stat-value" style="color: #dc3545;"><?php echo $out_stock_cnt; ?></div>
        <span class="stat-sub">0 quantity</span>
    </div>
</div>

<?php if ($flash_msg === 'updated'): ?>
    <div class="alert alert-success">Stock quantity updated successfully.</div>
<?php elseif ($flash_msg === 'added'): ?>
    <div class="alert alert-success">Stock units added successfully.</div>
<?php endif; ?>

<?php if (!empty($err)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
<?php endif; ?>

<!-- Quick Add Stock Form Box -->
<div class="form-card" style="margin-bottom: 25px; max-width: 100%;">
    <h3 style="font-size: 15px; margin-bottom: 12px; color: #212529;">
        Add Stock to Product
    </h3>
    <form method="POST" action="stock.php" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="action" value="add_stock">

        <div style="flex: 2; min-width: 260px;">
            <label style="font-size: 13px; font-weight: 600; color: #495057; display: block; margin-bottom: 5px;">Select Product:</label>
            <select name="product_id" class="form-control" required>
                <option value="">-- Choose Watch Model --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>">
                        <?php echo htmlspecialchars($p['name']); ?> (Current Stock: <?php echo $p['quantity']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex: 1; min-width: 140px;">
            <label style="font-size: 13px; font-weight: 600; color: #495057; display: block; margin-bottom: 5px;">Units to Add:</label>
            <input type="number" name="add_qty" value="10" min="1" max="1000" class="form-control" required>
        </div>

        <div>
            <button type="submit" class="btn btn-success" style="height: 38px;">Add Stock</button>
        </div>
    </form>
</div>

<!-- Stock Inventory Table -->
<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th style="width: 70px;">Image</th>
                <th>Watch Name</th>
                <th>Brand</th>
                <th>Category</th>
                <th style="width: 110px;">Price (₹)</th>
                <th style="width: 130px; text-align: center;">Stock Status</th>
                <th style="width: 220px; text-align: center;">Set Exact Stock</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $p): ?>
                    <?php $qty = (int)$p['quantity']; ?>
                    <tr>
                        <td>#<?php echo $p['id']; ?></td>
                        <td>
                            <img src="../<?php echo htmlspecialchars($p['image']); ?>" alt="watch" class="thumb" onerror="this.onerror=null; this.src='https://via.placeholder.com/50';">
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: #0284c7;"><?php echo htmlspecialchars($p['brand']); ?></span>
                        </td>
                        <td>
                            <span style="background: #e2e8f0; padding: 2px 7px; border-radius: 4px; font-size: 12px;"><?php echo htmlspecialchars($p['category']); ?></span>
                        </td>
                        <td>
                            <strong>₹<?php echo number_format($p['price'], 2); ?></strong>
                        </td>
                        
                        <!-- Stock Status Badge -->
                        <td style="text-align: center;">
                            <?php if ($qty <= 0): ?>
                                <span class="badge badge-danger">Out of Stock</span>
                            <?php else: ?>
                                <span class="badge badge-success"><?php echo $qty; ?> in stock</span>
                            <?php endif; ?>
                        </td>

                        <!-- Inline Update Form -->
                        <td style="text-align: center;">
                            <form method="POST" action="stock.php" style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                                <input type="hidden" name="action" value="set_stock">
                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $qty; ?>" min="0" max="9999" class="form-control" style="width: 80px; text-align: center; padding: 4px 6px; font-size: 13px; font-weight: 600;">
                                <button type="submit" class="btn btn-primary btn-sm" title="Save Stock">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b; padding: 35px;">No products found in catalog.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
