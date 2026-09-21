<?php
$page_title = "Order Details";
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$order_id = intval($_GET['id'] ?? 0);
if ($order_id <= 0) {
    header("Location: orders.php");
    exit();
}

// Handle Order Status Update from Details Page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $new_status = trim($_POST['order_status'] ?? '');
    $allowed_statuses = ['Placed', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

    if (in_array($new_status, $allowed_statuses)) {
        $stmt = mysqli_prepare($conn, "UPDATE orders SET order_status = ? WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: order_details.php?id=" . $order_id . "&msg=updated");
            exit();
        }
    }
}

// Fetch order
$stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$order) {
    header("Location: orders.php?err=Order+not+found");
    exit();
}

// Fetch order items with product images if available
$items = [];
$item_sql = "SELECT oi.*, p.image, p.brand FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
$item_stmt = mysqli_prepare($conn, $item_sql);
if ($item_stmt) {
    mysqli_stmt_bind_param($item_stmt, "i", $order_id);
    mysqli_stmt_execute($item_stmt);
    $item_res = mysqli_stmt_get_result($item_stmt);
    while ($it = mysqli_fetch_assoc($item_res)) {
        $items[] = $it;
    }
    mysqli_stmt_close($item_stmt);
}

require_once __DIR__ . '/header.php';
$msg = $_GET['msg'] ?? '';
?>

<div class="admin-page-header">
    <div>
        <h1>Order Details: <?php echo htmlspecialchars($order['order_number']); ?></h1>
        <p class="admin-page-subtitle">Placed on <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></p>
    </div>
    <div>
        <a href="orders.php" class="btn btn-secondary">&larr; Back to Orders</a>
    </div>
</div>

<?php if ($msg === 'updated'): ?>
    <div class="alert alert-success">Order status updated successfully.</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 25px;">
    <!-- Customer & Delivery Box -->
    <div class="form-card" style="margin: 0; max-width: 100%;">
        <h3 style="font-size: 16px; margin-bottom: 12px; color: #212529; border-bottom: 1px solid #dee2e6; padding-bottom: 8px;">
            Shipping & Customer Details
        </h3>
        <table style="width: 100%; font-size: 14px; border-collapse: collapse;">
            <tr>
                <td style="padding: 6px 0; color: #6c757d; width: 140px;">Customer Name:</td>
                <td style="padding: 6px 0; font-weight: 600; color: #212529;"><?php echo htmlspecialchars($order['customer_name']); ?></td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #6c757d;">Phone Number:</td>
                <td style="padding: 6px 0; font-weight: 600; color: #212529;"><?php echo htmlspecialchars($order['customer_phone']); ?></td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #6c757d;">Email Address:</td>
                <td style="padding: 6px 0; color: #212529;"><?php echo htmlspecialchars($order['customer_email']); ?></td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #6c757d;">Delivery Address:</td>
                <td style="padding: 6px 0; color: #212529;">
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?><br>
                    <strong><?php echo htmlspecialchars($order['city']); ?> - <?php echo htmlspecialchars($order['pincode']); ?></strong>
                </td>
            </tr>
        </table>
    </div>

    <!-- Order Status & Payment Box -->
    <div class="form-card" style="margin: 0; max-width: 100%;">
        <h3 style="font-size: 16px; margin-bottom: 12px; color: #212529; border-bottom: 1px solid #dee2e6; padding-bottom: 8px;">
            Order Status & Payment
        </h3>
        <table style="width: 100%; font-size: 14px; border-collapse: collapse; margin-bottom: 15px;">
            <tr>
                <td style="padding: 6px 0; color: #6c757d; width: 120px;">Payment Status:</td>
                <td style="padding: 6px 0;">
                    <?php if ($order['payment_status'] === 'Paid'): ?>
                        <span class="badge badge-success">Paid</span>
                    <?php else: ?>
                        <span class="badge badge-warning"><?php echo htmlspecialchars($order['payment_status']); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #6c757d;">Payment Mode:</td>
                <td style="padding: 6px 0; font-weight: 600; color: #212529;"><?php echo htmlspecialchars($order['payment_method']); ?></td>
            </tr>
            <?php if (!empty($order['razorpay_payment_id'])): ?>
            <tr>
                <td style="padding: 6px 0; color: #6c757d;">Transaction ID:</td>
                <td style="padding: 6px 0; font-family: monospace; font-size: 12px; color: #0284c7;"><?php echo htmlspecialchars($order['razorpay_payment_id']); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td style="padding: 6px 0; color: #6c757d;">Current Status:</td>
                <td style="padding: 6px 0;">
                    <span class="badge badge-primary"><?php echo htmlspecialchars($order['order_status']); ?></span>
                </td>
            </tr>
        </table>

        <!-- Quick Status Update Form -->
        <form method="POST" action="order_details.php?id=<?php echo $order_id; ?>" style="background: #f8fafc; padding: 10px; border-radius: 4px; border: 1px solid #e2e8f0;">
            <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px;">Update Status:</label>
            <div style="display: flex; gap: 6px;">
                <select name="order_status" class="form-control" style="font-size: 13px; padding: 5px;">
                    <option value="Placed" <?php echo $order['order_status'] === 'Placed' ? 'selected' : ''; ?>>Placed</option>
                    <option value="Processing" <?php echo $order['order_status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="Shipped" <?php echo $order['order_status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="Delivered" <?php echo $order['order_status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="Cancelled" <?php echo $order['order_status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit" name="update_order_status" class="btn btn-primary btn-sm">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Ordered Items Table -->
<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 70px;">Item</th>
                <th>Watch Name</th>
                <th>Brand</th>
                <th style="text-align: right; width: 120px;">Unit Price</th>
                <th style="text-align: center; width: 80px;">Quantity</th>
                <th style="text-align: right; width: 140px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td>
                        <?php if (!empty($it['image'])): ?>
                            <img src="../<?php echo htmlspecialchars($it['image']); ?>" alt="watch" class="thumb" onerror="this.onerror=null; this.src='https://via.placeholder.com/50';">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/50" alt="watch" class="thumb">
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($it['product_name']); ?></strong>
                    </td>
                    <td>
                        <span style="color: #0284c7; font-weight: 600;"><?php echo htmlspecialchars($it['brand'] ?? 'Watch'); ?></span>
                    </td>
                    <td style="text-align: right;">
                        ₹<?php echo number_format($it['price'], 2); ?>
                    </td>
                    <td style="text-align: center; font-weight: bold;">
                        <?php echo $it['quantity']; ?>
                    </td>
                    <td style="text-align: right; font-weight: 600; color: #16a34a;">
                        ₹<?php echo number_format($it['subtotal'], 2); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td colspan="5" style="text-align: right; font-size: 15px; padding-top: 15px; padding-bottom: 15px;">
                    Grand Total Payable:
                </td>
                <td style="text-align: right; font-size: 17px; color: #16a34a; padding-top: 15px; padding-bottom: 15px;">
                    ₹<?php echo number_format($order['total_amount'], 2); ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
