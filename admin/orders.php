<?php
$page_title = "Customer Orders";
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

// Handle Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    $new_status = trim($_POST['order_status'] ?? '');
    $allowed_statuses = ['Placed', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

    if ($order_id > 0 && in_array($new_status, $allowed_statuses)) {
        $stmt = mysqli_prepare($conn, "UPDATE orders SET order_status = ? WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: orders.php?msg=updated");
            exit();
        }
    }
}

require_once __DIR__ . '/header.php';

$msg = $_GET['msg'] ?? '';
$filter_status = trim($_GET['status'] ?? '');

// Compute order statistics
$total_orders = 0;
$total_revenue = 0;
$pending_count = 0;
$delivered_count = 0;

$stat_res = mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN payment_status = 'Paid' THEN total_amount ELSE 0 END) as revenue,
    SUM(CASE WHEN order_status IN ('Placed', 'Pending Payment', 'Processing') THEN 1 ELSE 0 END) as pending_cnt,
    SUM(CASE WHEN order_status = 'Delivered' THEN 1 ELSE 0 END) as delivered_cnt
    FROM orders");

if ($stat_res && $st = mysqli_fetch_assoc($stat_res)) {
    $total_orders = (int)$st['total'];
    $total_revenue = (float)$st['revenue'];
    $pending_count = (int)$st['pending_cnt'];
    $delivered_count = (int)$st['delivered_cnt'];
}

// Build query with optional status filter
$sql = "SELECT * FROM orders";
if (!empty($filter_status)) {
    $safe_status = mysqli_real_escape_string($conn, $filter_status);
    $sql .= " WHERE order_status = '$safe_status'";
}
$sql .= " ORDER BY id DESC";

$res = mysqli_query($conn, $sql);
$orders = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $oid = (int)$row['id'];
        $item_res = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = $oid");
        $items = [];
        if ($item_res) {
            while ($item = mysqli_fetch_assoc($item_res)) {
                $items[] = $item;
            }
        }
        $row['items'] = $items;
        $orders[] = $row;
    }
}
?>

<div class="admin-page-header">
    <div>
        <h1>Customer Orders</h1>
        <p class="admin-page-subtitle">View and manage all customer purchases and fulfillment status.</p>
    </div>
    <div>
        <a href="orders.php" class="btn btn-secondary btn-sm <?php echo empty($filter_status) ? 'active' : ''; ?>">All Orders (<?php echo $total_orders; ?>)</a>
        <a href="orders.php?status=Placed" class="btn btn-primary btn-sm <?php echo $filter_status === 'Placed' ? 'active' : ''; ?>">Placed</a>
        <a href="orders.php?status=Processing" class="btn btn-warning btn-sm <?php echo $filter_status === 'Processing' ? 'active' : ''; ?>" style="color: #000;">Processing</a>
        <a href="orders.php?status=Delivered" class="btn btn-success btn-sm <?php echo $filter_status === 'Delivered' ? 'active' : ''; ?>">Delivered</a>
    </div>
</div>

<!-- Quick Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Orders</h3>
        <div class="stat-value"><?php echo $total_orders; ?></div>
        <span class="stat-sub">All-time customer orders</span>
    </div>

    <div class="stat-card card-green">
        <h3>Total Revenue (Paid)</h3>
        <div class="stat-value">₹<?php echo number_format($total_revenue, 2); ?></div>
        <span class="stat-sub">Successful payments</span>
    </div>

    <div class="stat-card card-amber">
        <h3>Active / In-Process</h3>
        <div class="stat-value"><?php echo $pending_count; ?></div>
        <span class="stat-sub">Requires dispatch/delivery</span>
    </div>

    <div class="stat-card card-purple">
        <h3>Delivered Orders</h3>
        <div class="stat-value"><?php echo $delivered_count; ?></div>
        <span class="stat-sub">Completed orders</span>
    </div>
</div>

<?php if ($msg === 'updated'): ?>
    <div class="alert alert-success">Order status updated successfully.</div>
<?php endif; ?>

<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 140px;">Order Number</th>
                <th style="width: 170px;">Customer Info</th>
                <th style="width: 180px;">Shipping Details</th>
                <th>Ordered Items</th>
                <th style="width: 110px;">Total Amount</th>
                <th style="width: 120px;">Payment</th>
                <th style="width: 170px;">Order Status</th>
                <th style="width: 80px; text-align: center;">Details</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <!-- Order # & Date -->
                        <td>
                            <strong style="color: #0d6efd; font-size: 13px;"><?php echo htmlspecialchars($o['order_number']); ?></strong>
                            <div style="font-size: 11px; color: #6c757d; margin-top: 4px;">
                                <?php echo date('d M Y, h:i A', strtotime($o['created_at'])); ?>
                            </div>
                        </td>

                        <!-- Customer Details -->
                        <td>
                            <strong style="color: #212529;"><?php echo htmlspecialchars($o['customer_name']); ?></strong>
                            <div style="font-size: 12px; color: #495057; margin-top: 2px;">
                                <?php echo htmlspecialchars($o['customer_phone']); ?>
                            </div>
                            <div style="font-size: 11px; color: #6c757d;">
                                <?php echo htmlspecialchars($o['customer_email']); ?>
                            </div>
                        </td>

                        <!-- Shipping Address -->
                        <td>
                            <div style="font-size: 13px; color: #334155;">
                                <?php echo htmlspecialchars($o['shipping_address']); ?>
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                <?php echo htmlspecialchars($o['city']); ?> - <?php echo htmlspecialchars($o['pincode']); ?>
                            </div>
                        </td>

                        <!-- Items List -->
                        <td>
                            <?php if (!empty($o['items'])): ?>
                                <ul style="list-style: none; padding-left: 0; margin: 0; font-size: 12px;">
                                    <?php foreach ($o['items'] as $it): ?>
                                        <li style="padding: 2px 0; border-bottom: 1px dashed #e2e8f0;">
                                            <strong><?php echo htmlspecialchars($it['product_name']); ?></strong> 
                                            <span style="color: #64748b;">(Qty: <?php echo $it['quantity']; ?> × ₹<?php echo number_format($it['price'], 2); ?>)</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span style="color: #94a3b8; font-size: 12px;">No items recorded</span>
                            <?php endif; ?>
                        </td>

                        <!-- Total Amount -->
                        <td>
                            <strong style="font-size: 15px; color: #16a34a;">
                                ₹<?php echo number_format($o['total_amount'], 2); ?>
                            </strong>
                        </td>

                        <!-- Payment Method & Status -->
                        <td>
                            <div style="margin-bottom: 4px;">
                                <?php if ($o['payment_status'] === 'Paid'): ?>
                                    <span class="badge badge-success">Paid</span>
                                <?php elseif ($o['payment_status'] === 'Pending'): ?>
                                    <span class="badge badge-warning"><?php echo htmlspecialchars($o['payment_status']); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-secondary"><?php echo htmlspecialchars($o['payment_status']); ?></span>
                                <?php endif; ?>
                            </div>
                            <span style="font-size: 11px; color: #6c757d;">
                                <?php echo htmlspecialchars($o['payment_method']); ?>
                            </span>
                            <?php if (!empty($o['razorpay_payment_id'])): ?>
                                <div style="font-size: 10px; color: #0284c7; font-family: monospace;">
                                    <?php echo htmlspecialchars($o['razorpay_payment_id']); ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Status Update Form -->
                        <td>
                            <form method="POST" action="orders.php<?php echo !empty($filter_status) ? '?status=' . urlencode($filter_status) : ''; ?>" style="display: flex; gap: 4px; align-items: center;">
                                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                <select name="order_status" class="form-control" style="font-size: 12px; padding: 4px 6px; width: auto;">
                                    <option value="Placed" <?php echo $o['order_status'] === 'Placed' ? 'selected' : ''; ?>>Placed</option>
                                    <option value="Processing" <?php echo $o['order_status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="Shipped" <?php echo $o['order_status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="Delivered" <?php echo $o['order_status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="Cancelled" <?php echo $o['order_status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_order_status" class="btn btn-primary btn-sm" title="Save status">Save</button>
                            </form>
                        </td>

                        <!-- View Details / Invoice -->
                        <td style="text-align: center;">
                            <a href="order_details.php?id=<?php echo $o['id']; ?>" class="btn btn-secondary btn-sm" title="View Order Details">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b; padding: 40px;">
                        No orders found <?php echo !empty($filter_status) ? 'with status "' . htmlspecialchars($filter_status) . '"' : 'in the database.'; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
