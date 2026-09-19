<?php
/**
 * Server-Side Razorpay Payment Verification
 * Validates HMAC SHA256 signature with Razorpay Test Secret
 * For WatchStore Project (Test Mode Only)
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/razorpay.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only accept POST requests from Checkout handler
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit();
}

$razorpay_payment_id = trim($_POST['razorpay_payment_id'] ?? '');
$razorpay_order_id   = trim($_POST['razorpay_order_id'] ?? '');
$razorpay_signature  = trim($_POST['razorpay_signature'] ?? '');
$order_number        = trim($_POST['order_number'] ?? '');

// Ensure all parameters are present
if (empty($razorpay_payment_id) || empty($razorpay_order_id) || empty($razorpay_signature) || empty($order_number)) {
    header("Location: payment-failed.php?status=missing_params");
    exit();
}

// Fetch order from database
$stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE order_number = ? LIMIT 1");
if (!$stmt) {
    header("Location: payment-failed.php?status=db_error");
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $order_number);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$order) {
    header("Location: payment-failed.php?status=order_not_found");
    exit();
}

// Verify that the razorpay_order_id matches the one generated and saved on the server
if (!empty($order['razorpay_order_id']) && $order['razorpay_order_id'] !== $razorpay_order_id) {
    header("Location: payment-failed.php?status=order_mismatch&order=" . urlencode($order_number));
    exit();
}

// Perform strict server-side HMAC-SHA256 signature verification
$is_signature_valid = verifyRazorpaySignature($razorpay_order_id, $razorpay_payment_id, $razorpay_signature);

if ($is_signature_valid) {
    // Idempotency safety: Check if order is already marked Paid (e.g. from repeat submission)
    if ($order['payment_status'] !== 'Paid') {
        // Mark payment as Paid and order as Placed
        $update_sql = "UPDATE orders SET payment_status = 'Paid', order_status = 'Placed', razorpay_payment_id = ?, razorpay_signature = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "ssi", $razorpay_payment_id, $razorpay_signature, $order['id']);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
        }

        // Deduct inventory stock for each ordered item
        $items_stmt = mysqli_prepare($conn, "SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        if ($items_stmt) {
            mysqli_stmt_bind_param($items_stmt, "i", $order['id']);
            mysqli_stmt_execute($items_stmt);
            $items_res = mysqli_stmt_get_result($items_stmt);
            while ($item = mysqli_fetch_assoc($items_res)) {
                $pid = intval($item['product_id']);
                $qty = intval($item['quantity']);
                mysqli_query($conn, "UPDATE products SET quantity = GREATEST(0, quantity - $qty) WHERE id = $pid");
            }
            mysqli_stmt_close($items_stmt);
        }

        // Clear user's active shopping cart now that payment is confirmed
        $_SESSION['cart'] = [];
        $_SESSION['last_order_number'] = $order_number;
    }

    // Redirect to payment success receipt
    header("Location: payment-success.php?order=" . urlencode($order_number));
    exit();

} else {
    // Signature verification failed! Never mark as Paid
    $fail_sql = "UPDATE orders SET payment_status = 'Failed', order_status = 'Payment Failed' WHERE id = ?";
    $fail_stmt = mysqli_prepare($conn, $fail_sql);
    if ($fail_stmt) {
        mysqli_stmt_bind_param($fail_stmt, "i", $order['id']);
        mysqli_stmt_execute($fail_stmt);
        mysqli_stmt_close($fail_stmt);
    }

    header("Location: payment-failed.php?status=signature_failed&order=" . urlencode($order_number));
    exit();
}
