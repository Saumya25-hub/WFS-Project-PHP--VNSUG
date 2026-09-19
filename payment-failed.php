<?php
$page_title = "Payment Status - WatchStore";
require_once __DIR__ . '/config/db.php';

$status = trim($_GET['status'] ?? 'failed');
$order_number = trim($_GET['order'] ?? '');
$reason = trim($_GET['reason'] ?? '');

$is_cancelled = ($status === 'cancelled');

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 700px;">
    <div class="info-card" style="text-align: center; padding: 40px 20px; border-top: 4px solid <?php echo $is_cancelled ? '#f59e0b' : '#ef4444'; ?>; margin-top: 30px;">
        
        <?php if ($is_cancelled): ?>
            <div style="font-size: 48px; color: #f59e0b; margin-bottom: 15px;">&#9888;</div>
            <h1 style="font-size: 24px; color: #0f172a; margin-bottom: 8px;">Payment Cancelled</h1>
            <p style="color: #64748b; font-size: 15px; margin-bottom: 20px; line-height: 1.6;">
                The Razorpay payment window was closed before completing the transaction.<br>
                You can try again from checkout whenever you're ready. Your cart items are preserved.
            </p>
        <?php else: ?>
            <div style="font-size: 48px; color: #ef4444; margin-bottom: 15px;">&#10008;</div>
            <h1 style="font-size: 24px; color: #0f172a; margin-bottom: 8px;">Payment Failed</h1>
            <p style="color: #64748b; font-size: 15px; margin-bottom: 20px; line-height: 1.6;">
                Your payment could not be completed.<br>
                <?php if (!empty($reason)): ?>
                    <span style="color: #b91c1c; font-size: 14px;"><strong>Reason:</strong> <?php echo htmlspecialchars($reason); ?></span><br>
                <?php endif; ?>
                Please try again. Your cart items are safe.
            </p>
        <?php endif; ?>

        <?php if (!empty($order_number)): ?>
            <div style="background: #FAF7F4; border: 1px solid #E8DFD5; padding: 12px 18px; border-radius: 6px; display: inline-block; margin-bottom: 25px; text-align: left;">
                <span style="font-size: 12px; color: #7A5645; text-transform: uppercase; font-weight: 600;">Reference:</span>
                <span style="font-weight: 700; color: #4B2E2A; margin-left: 6px;"><?php echo htmlspecialchars($order_number); ?></span>
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <a href="checkout.php" class="btn btn-primary" style="width: auto; padding: 11px 24px; text-decoration: none;">
                Try Again from Checkout &rarr;
            </a>
            <a href="cart.php" class="btn btn-secondary" style="width: auto; padding: 11px 22px; text-decoration: none;">
                View Cart
            </a>
            <a href="products.php" class="btn" style="width: auto; padding: 11px 20px; text-decoration: none; background: #FAF7F4; color: #4B2E2A; border: 1px solid #E8DFD5;">
                Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
