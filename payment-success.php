<?php
$page_title = "Payment Successful - WatchStore";
require_once __DIR__ . '/config/db.php';

$order_number = trim($_GET['order'] ?? '');
if (empty($order_number)) {
    header("Location: index.php");
    exit();
}

// Fetch order details from MySQL database
$order_stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE order_number = ?");
mysqli_stmt_bind_param($order_stmt, "s", $order_number);
mysqli_stmt_execute($order_stmt);
$order_res = mysqli_stmt_get_result($order_stmt);
$order = mysqli_fetch_assoc($order_res);
mysqli_stmt_close($order_stmt);

if (!$order) {
    header("Location: index.php");
    exit();
}

// Fetch order line items
$items_stmt = mysqli_prepare($conn, "SELECT * FROM order_items WHERE order_id = ?");
mysqli_stmt_bind_param($items_stmt, "i", $order['id']);
mysqli_stmt_execute($items_stmt);
$items_res = mysqli_stmt_get_result($items_stmt);
$order_items = [];
while ($it = mysqli_fetch_assoc($items_res)) {
    $order_items[] = $it;
}
mysqli_stmt_close($items_stmt);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 820px;">
    <!-- Success Banner Card -->
    <div class="info-card" style="text-align: center; padding: 35px 20px; border-top: 4px solid #16a34a; margin-bottom: 25px;">
        <div style="font-size: 48px; color: #16a34a; margin-bottom: 12px;">&#10004;</div>
        <h1 style="font-size: 24px; color: #0f172a; margin-bottom: 6px;">Payment Successful</h1>
        <p style="color: #64748b; font-size: 15px; margin-bottom: 20px;">Thank you for your order! Your payment has been verified and confirmed.</p>

        <!-- Key IDs Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; max-width: 680px; margin: 0 auto 20px auto; text-align: left;">
            <div style="background: #FAF7F4; border: 1px solid #E8DFD5; padding: 12px 16px; border-radius: 6px;">
                <div style="font-size: 11px; text-transform: uppercase; color: #7A5645; font-weight: 600; margin-bottom: 4px;">Order ID</div>
                <div style="font-weight: 700; color: #4B2E2A; font-size: 15px;"><?php echo htmlspecialchars($order['order_number']); ?></div>
            </div>

            <?php if (!empty($order['razorpay_payment_id'])): ?>
                <div style="background: #FAF7F4; border: 1px solid #E8DFD5; padding: 12px 16px; border-radius: 6px;">
                    <div style="font-size: 11px; text-transform: uppercase; color: #7A5645; font-weight: 600; margin-bottom: 4px;">Razorpay Payment ID</div>
                    <div style="font-weight: 700; color: #0f172a; font-size: 14px; font-family: monospace;"><?php echo htmlspecialchars($order['razorpay_payment_id']); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($order['razorpay_order_id'])): ?>
                <div style="background: #FAF7F4; border: 1px solid #E8DFD5; padding: 12px 16px; border-radius: 6px;">
                    <div style="font-size: 11px; text-transform: uppercase; color: #7A5645; font-weight: 600; margin-bottom: 4px;">Razorpay Order ID</div>
                    <div style="font-weight: 700; color: #0f172a; font-size: 14px; font-family: monospace;"><?php echo htmlspecialchars($order['razorpay_order_id']); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div style="display: inline-flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
            <span class="badge-week" style="background-color: #dcfce7; color: #166534; font-size: 13px; margin: 0;">
                Order Status: <?php echo htmlspecialchars($order['order_status']); ?>
            </span>
            <span class="badge-week" style="background-color: #F5EFEB; color: #4B2E2A; font-size: 13px; margin: 0; border: 1px solid #E8DFD5;">
                Payment: <?php echo htmlspecialchars($order['payment_status']); ?>
            </span>
        </div>
    </div>

    <!-- Order Details & Receipt -->
    <div class="info-card">
        <h3 style="margin-top: 0; margin-bottom: 15px;">Order Summary & Delivery Details</h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; font-size: 14px;">
            <div>
                <h4 style="font-size: 13px; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Customer Details:</h4>
                <p style="margin: 0; font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                <p style="margin: 2px 0; color: #475569;"><?php echo htmlspecialchars($order['customer_email']); ?></p>
                <p style="margin: 0; color: #475569;">+91 <?php echo htmlspecialchars($order['customer_phone']); ?></p>
            </div>
            <div>
                <h4 style="font-size: 13px; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Shipping Address:</h4>
                <p style="margin: 0; color: #475569;"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                <p style="margin: 2px 0; color: #475569;"><?php echo htmlspecialchars($order['city']); ?> - <?php echo htmlspecialchars($order['pincode']); ?></p>
                <p style="margin: 2px 0; font-size: 13px; color: #64748b;">Payment Method: <strong><?php echo htmlspecialchars($order['payment_method']); ?></strong></p>
            </div>
        </div>

        <h4 style="font-size: 14px; color: #0f172a; margin-bottom: 10px; border-top: 1px solid #f1f5f9; padding-top: 15px;">Ordered Watches:</h4>
        
        <table class="custom-table" style="margin-bottom: 15px;">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td style="font-weight: 600; color: #0f172a;">
                            <?php echo htmlspecialchars($item['product_name']); ?>
                        </td>
                        <td style="color: #475569;">
                            &#8377;<?php echo number_format($item['price'], 2); ?>
                        </td>
                        <td style="text-align: center; color: #475569;">
                            <?php echo intval($item['quantity']); ?>
                        </td>
                        <td style="text-align: right; font-weight: 600; color: #0f172a;">
                            &#8377;<?php echo number_format($item['subtotal'], 2); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold; padding-top: 15px;">Grand Total Paid:</td>
                    <td style="text-align: right; font-weight: bold; font-size: 16px; color: #4B2E2A; padding-top: 15px;">
                        &#8377;<?php echo number_format($order['total_amount'], 2); ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div style="display: flex; gap: 15px; justify-content: center; margin-top: 25px;">
            <a href="products.php" class="btn btn-primary" style="width: auto; padding: 10px 24px; text-decoration: none;">
                Continue Shopping
            </a>
            <a href="index.php" class="btn btn-secondary" style="width: auto; padding: 10px 20px; text-decoration: none;">
                Back to Home
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
