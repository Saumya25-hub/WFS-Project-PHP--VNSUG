<?php
$page_title = "My Account";
require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure only logged-in users can access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$user_data = null;

$stmt = mysqli_prepare($conn, "SELECT user_id, full_name, email, mobile, created_at FROM users WHERE user_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Fetch user orders
$orders_res = mysqli_query($conn, "SELECT * FROM orders WHERE user_id = $user_id ORDER BY id DESC");

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="hero-box">
        <span class="badge-week" style="background-color: #dcfce7; color: #166534;">Account Active</span>
        <h1>Welcome back, <?php echo htmlspecialchars($user_data['full_name'] ?? $_SESSION['user_name']); ?>!</h1>
        <p>Manage your account profile, track your watch orders, and check your saved wishlist.</p>
        
        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 15px;">
            <a href="products.php" class="btn btn-primary" style="width: auto; padding: 8px 16px; font-size: 14px; text-decoration: none;">
                Browse Watches
            </a>
            <a href="wishlist.php" class="btn btn-secondary" style="width: auto; padding: 8px 16px; font-size: 14px; text-decoration: none;">
                My Wishlist
            </a>
            <a href="cart.php" class="btn btn-secondary" style="width: auto; padding: 8px 16px; font-size: 14px; text-decoration: none;">
                Shopping Cart
            </a>
            <a href="feedback.php" class="btn btn-secondary" style="width: auto; padding: 8px 16px; font-size: 14px; text-decoration: none;">
                Leave Feedback
            </a>
        </div>
    </div>

    <!-- User Profile Details Card -->
    <div class="info-card">
        <h3>User Profile Information</h3>
        
        <?php if ($user_data): ?>
            <table class="user-info-table">
                <tr>
                    <th>User ID:</th>
                    <td>#<?php echo htmlspecialchars($user_data['user_id']); ?></td>
                </tr>
                <tr>
                    <th>Full Name:</th>
                    <td><?php echo htmlspecialchars($user_data['full_name']); ?></td>
                </tr>
                <tr>
                    <th>Email Address:</th>
                    <td><?php echo htmlspecialchars($user_data['email']); ?></td>
                </tr>
                <tr>
                    <th>Mobile Number:</th>
                    <td>+91 <?php echo htmlspecialchars($user_data['mobile']); ?></td>
                </tr>
                <tr>
                    <th>Member Since:</th>
                    <td><?php echo date('d M Y', strtotime($user_data['created_at'])); ?></td>
                </tr>
            </table>
        <?php endif; ?>
    </div>

    <!-- User Order History Card -->
    <div class="info-card">
        <h3>My Order History</h3>

        <?php if ($orders_res && mysqli_num_rows($orders_res) > 0): ?>
            <div class="cart-table-wrapper" style="margin-bottom: 0;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Order No</th>
                            <th>Date</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($ord = mysqli_fetch_assoc($orders_res)): ?>
                            <tr>
                                <td style="font-weight: 600; color: #7A5645;">
                                    <?php echo htmlspecialchars($ord['order_number']); ?>
                                </td>
                                <td style="color: #64748b; font-size: 13px;">
                                    <?php echo date('d M Y, h:i A', strtotime($ord['created_at'])); ?>
                                </td>
                                <td style="font-size: 13px;">
                                    <?php echo htmlspecialchars($ord['payment_method']); ?>
                                </td>
                                <td>
                                    <span class="badge-week" style="margin-bottom: 0; font-size: 11px; padding: 2px 8px; background-color: #dcfce7; color: #166534;">
                                        <?php echo htmlspecialchars($ord['order_status']); ?>
                                    </span>
                                </td>
                                <td style="font-weight: 700; color: #4B2E2A;">
                                    &#8377;<?php echo number_format($ord['total_amount'], 2); ?>
                                </td>
                                <td>
                                    <a href="payment-success.php?order=<?php echo urlencode($ord['order_number']); ?>" style="color: #7A5645; text-decoration: none; font-weight: 600; font-size: 13px;">
                                        View Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: #64748b; margin: 0;">You have not placed any watch orders yet. <a href="products.php" style="color: #7A5645; font-weight: 600;">Shop now</a></p>
        <?php endif; ?>

        <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #f1f5f9;">
            <a href="logout.php" class="btn btn-danger" style="width: auto; padding: 8px 18px; font-size: 14px; text-decoration: none; display: inline-block;">
                Logout Account
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

