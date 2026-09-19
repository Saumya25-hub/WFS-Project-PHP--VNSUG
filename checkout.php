<?php
$page_title = "Checkout - WatchStore";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/razorpay.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in before checkout
if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_redirect'] = 'checkout.php';
    $_SESSION['login_notice'] = 'Please log in to proceed with checkout and place your order.';
    header("Location: login.php");
    exit();
}

// Redirect if cart is empty
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header("Location: products.php");
    exit();
}

$error_msg = "";
$user_id = intval($_SESSION['user_id']);

// Auto-fill values if logged in
$default_name   = $_SESSION['user_name'] ?? '';
$default_email  = $_SESSION['user_email'] ?? '';
$default_mobile = $_SESSION['user_mobile'] ?? '';

// Calculate grand total strictly on server
$grand_total = 0;
$total_items = 0;
foreach ($_SESSION['cart'] as $c_item) {
    $grand_total += $c_item['price'] * $c_item['quantity'];
    $total_items += $c_item['quantity'];
}

$trigger_razorpay = false;
$razorpay_options = [];
$order_number     = '';

// Handle Order Submission & Razorpay Order Creation
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $customer_name    = trim($_POST['customer_name'] ?? '');
    $customer_email   = trim($_POST['customer_email'] ?? '');
    $customer_phone   = trim($_POST['customer_phone'] ?? '');
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $city             = trim($_POST['city'] ?? '');
    $pincode          = trim($_POST['pincode'] ?? '');

    if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($shipping_address) || empty($city) || empty($pincode)) {
        $error_msg = "Please fill in all shipping and customer details.";
    } elseif (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } elseif (strlen(preg_replace('/[^0-9]/', '', $customer_phone)) < 10) {
        $error_msg = "Please enter a valid 10-digit mobile number.";
    } else {
        // Generate unique internal order number
        $order_number = 'ORD-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
        
        // Amount strictly calculated server-side in paise (₹1 = 100 paise)
        $amount_in_paise = (int)round($grand_total * 100);

        // Create Razorpay Order via Server-Side cURL API
        $rzp_order = createRazorpayOrder($amount_in_paise, $order_number);

        if (!$rzp_order['success']) {
            $error_msg = "Razorpay Error: " . $rzp_order['error'];
        } else {
            $razorpay_order_id = $rzp_order['order_id'];
            $payment_method = 'Razorpay';
            $payment_status = 'Pending';
            $order_status   = 'Pending Payment';

            // Insert initial pending order into orders table
            $order_sql = "INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone, shipping_address, city, pincode, payment_method, payment_status, total_amount, order_status, razorpay_order_id) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($conn, $order_sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sissssssssiss", 
                    $order_number, 
                    $user_id, 
                    $customer_name, 
                    $customer_email, 
                    $customer_phone, 
                    $shipping_address, 
                    $city, 
                    $pincode, 
                    $payment_method, 
                    $payment_status, 
                    $grand_total, 
                    $order_status,
                    $razorpay_order_id
                );
                
                if (mysqli_stmt_execute($stmt)) {
                    $db_order_id = mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);

                    // Insert line items
                    $item_stmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($item_stmt) {
                        $i_pid = 0;
                        $i_name = '';
                        $i_price = 0.0;
                        $i_qty = 0;
                        $i_subtotal = 0.0;

                        mysqli_stmt_bind_param($item_stmt, "iisdid", 
                            $db_order_id, 
                            $i_pid, 
                            $i_name, 
                            $i_price, 
                            $i_qty, 
                            $i_subtotal
                        );

                        foreach ($_SESSION['cart'] as $p_id => $item) {
                            $i_pid = intval($p_id);
                            $i_name = strval($item['name']);
                            $i_price = floatval($item['price']);
                            $i_qty = intval($item['quantity']);
                            $i_subtotal = floatval($i_price * $i_qty);

                            mysqli_stmt_execute($item_stmt);
                        }
                        mysqli_stmt_close($item_stmt);
                    }

                    // Prepare Razorpay Checkout options for frontend modal
                    // Note: Only the public Test Key ID is sent to the client. The Secret is NEVER exposed!
                    $razorpay_options = [
                        'key'         => RAZORPAY_KEY_ID,
                        'amount'      => $amount_in_paise,
                        'currency'    => RAZORPAY_CURRENCY,
                        'name'        => 'WatchStore',
                        'description' => 'Order ' . $order_number,
                        'order_id'    => $razorpay_order_id,
                        'prefill'     => [
                            'name'    => $customer_name,
                            'email'   => $customer_email,
                            'contact' => $customer_phone
                        ],
                        'notes'       => [
                            'internal_order_number' => $order_number
                        ],
                        'theme'       => [
                            'color'   => '#4B2E2A' // Mocha Silk brand theme
                        ]
                    ];

                    $trigger_razorpay = true;

                } else {
                    $error_msg = "Order creation failed: " . mysqli_error($conn);
                    mysqli_stmt_close($stmt);
                }
            } else {
                $error_msg = "Database error. Please try again.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="hero-box" style="padding: 20px 25px; margin-bottom: 25px;">
        <h2 style="margin-top: 0; margin-bottom: 8px;">Checkout</h2>
        <p style="margin-bottom: 0; font-size: 14px;">Review your items, provide delivery details, and complete your order.</p>
    </div>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <?php if ($trigger_razorpay): ?>
        <!-- Razorpay Modal Trigger State -->
        <div class="info-card" style="text-align: center; padding: 40px 20px; max-width: 600px; margin: 0 auto 30px auto;">
            <div style="font-size: 44px; color: #4B2E2A; margin-bottom: 15px;">&#128179;</div>
            <h2 style="font-size: 20px; color: #0f172a; margin-bottom: 10px;">Connecting to Razorpay Secure Checkout...</h2>
            <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">
                Order Reference: <strong style="color: #4B2E2A;"><?php echo htmlspecialchars($order_number); ?></strong><br>
                Payable Amount: <strong style="color: #4B2E2A;">&#8377;<?php echo number_format($grand_total, 2); ?></strong>
            </p>

            <button id="rzp-manual-button" class="btn btn-primary" style="padding: 12px 30px; font-size: 15px; margin-bottom: 15px;">
                Open Razorpay Payment Popup
            </button>

            <div>
                <a href="payment-failed.php?status=cancelled&order=<?php echo urlencode($order_number); ?>" style="font-size: 13px; color: #64748b; text-decoration: underline;">
                    Cancel Payment and Return
                </a>
            </div>
        </div>

        <!-- Hidden form to submit Razorpay response to verify-payment.php -->
        <form id="razorpay_verification_form" method="POST" action="verify-payment.php" style="display: none;">
            <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
            <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
            <input type="hidden" name="razorpay_signature" id="razorpay_signature">
            <input type="hidden" name="order_number" value="<?php echo htmlspecialchars($order_number); ?>">
        </form>

        <!-- Official Razorpay Checkout Script -->
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
            var options = <?php echo json_encode($razorpay_options, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

            // Success callback handler: Send payment details to server for signature verification
            options.handler = function (response) {
                document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                document.getElementById('razorpay_order_id').value   = response.razorpay_order_id;
                document.getElementById('razorpay_signature').value  = response.razorpay_signature;
                document.getElementById('razorpay_verification_form').submit();
            };

            // Handle modal dismissal/cancellation by user
            options.modal = {
                ondismiss: function() {
                    window.location.href = 'payment-failed.php?status=cancelled&order=' + encodeURIComponent('<?php echo $order_number; ?>');
                }
            };

            var rzp1 = new Razorpay(options);

            // Handle payment failure event inside Razorpay
            rzp1.on('payment.failed', function (response) {
                var reason = (response.error && response.error.description) ? response.error.description : 'Payment Failed';
                window.location.href = 'payment-failed.php?status=failed&order=' + encodeURIComponent('<?php echo $order_number; ?>') + '&reason=' + encodeURIComponent(reason);
            });

            // Automatically launch Razorpay Checkout
            window.onload = function() {
                rzp1.open();
            };

            // Manual button in case auto-open is blocked by browser
            document.getElementById('rzp-manual-button').onclick = function(e) {
                e.preventDefault();
                rzp1.open();
            };
        </script>

    <?php else: ?>
        <!-- Standard Checkout Form -->
        <form method="POST" action="checkout.php">
            <div class="checkout-grid">
                <!-- Left: Shipping Details & Razorpay Information -->
                <div>
                    <!-- Delivery Address Card -->
                    <div class="info-card" style="margin-bottom: 20px;">
                        <h3 style="margin-top: 0; margin-bottom: 18px;">1. Shipping & Customer Details</h3>

                        <div class="form-group">
                            <label for="customer_name">Full Name *</label>
                            <input type="text" name="customer_name" id="customer_name" class="form-control" placeholder="e.g. Rahul Sharma" value="<?php echo htmlspecialchars($_POST['customer_name'] ?? $default_name); ?>" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="customer_email">Email Address *</label>
                                <input type="email" name="customer_email" id="customer_email" class="form-control" placeholder="e.g. rahul@example.com" value="<?php echo htmlspecialchars($_POST['customer_email'] ?? $default_email); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="customer_phone">Mobile Number *</label>
                                <input type="tel" name="customer_phone" id="customer_phone" class="form-control" placeholder="e.g. 9876543210" value="<?php echo htmlspecialchars($_POST['customer_phone'] ?? $default_mobile); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="shipping_address">Delivery Address *</label>
                            <textarea name="shipping_address" id="shipping_address" rows="2" class="form-control" placeholder="House/Flat No, Building, Street, Area" required><?php echo htmlspecialchars($_POST['shipping_address'] ?? ''); ?></textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="city">City *</label>
                                <input type="text" name="city" id="city" class="form-control" placeholder="e.g. Surat" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="pincode">Pincode *</label>
                                <input type="text" name="pincode" id="pincode" class="form-control" placeholder="e.g. 395007" value="<?php echo htmlspecialchars($_POST['pincode'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information Card -->
                    <div class="info-card" style="margin-bottom: 0;">
                        <h3 style="margin-top: 0; margin-bottom: 12px;">2. Payment Method</h3>
                        
                        <div style="border: 1px solid #E8DFD5; background: #FAF7F4; border-radius: 8px; padding: 14px 16px; display: flex; align-items: center; gap: 12px;">
                            <input type="radio" id="razorpay" name="payment_method" value="Razorpay" checked style="accent-color: #4B2E2A; width: 18px; height: 18px; cursor: pointer;">
                            <label for="razorpay" style="cursor: pointer; margin: 0; display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 18px;">&#128179;</span>
                                <span style="font-weight: 600; color: #4B2E2A; font-size: 15px;">Razorpay (UPI, Cards, NetBanking, Wallets)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Right: Order Summary -->
                <div>
                    <div class="cart-summary-box">
                        <h3>Order Summary (<?php echo $total_items; ?> items)</h3>

                        <div style="max-height: 250px; overflow-y: auto; margin-bottom: 15px;">
                            <?php foreach ($_SESSION['cart'] as $item): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-size: 13px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" style="width: 35px; height: 35px; object-fit: contain; border-radius: 4px; border: 1px solid #e2e8f0;">
                                        <div>
                                            <div style="font-weight: 600; color: #0f172a; max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </div>
                                            <span style="color: #64748b;">Qty: <?php echo intval($item['quantity']); ?></span>
                                        </div>
                                    </div>
                                    <span style="font-weight: 600; color: #334155;">
                                        &#8377;<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="summary-row">
                            <span>Items Subtotal:</span>
                            <span>&#8377;<?php echo number_format($grand_total, 2); ?></span>
                        </div>

                        <div class="summary-row">
                            <span>Standard Delivery:</span>
                            <span style="color: #16a34a; font-weight: 600;">FREE</span>
                        </div>

                        <div class="summary-total">
                            <span>Total Payable:</span>
                            <span style="color: #4B2E2A;">&#8377;<?php echo number_format($grand_total, 2); ?></span>
                        </div>

                        <div style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary" style="padding: 13px; font-size: 15px; width: 100%;">
                                Pay with Razorpay &bull; &#8377;<?php echo number_format($grand_total, 2); ?> &rarr;
                            </button>
                        </div>

                        <div style="margin-top: 15px; text-align: center;">
                            <a href="cart.php" style="font-size: 13px; color: #7A5645; text-decoration: none; font-weight: 500;">
                                &larr; Return to Shopping Cart
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
