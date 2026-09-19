<?php
$page_title = "Add New Product";
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

// Fetch all available brands for dropdown
$brands = [];
$b_res = mysqli_query($conn, "SELECT name FROM brands ORDER BY name ASC");
if ($b_res) {
    while ($b_row = mysqli_fetch_assoc($b_res)) {
        $brands[] = $b_row['name'];
    }
}

// Predefined watch categories
$categories = [
    'Luxury Watches',
    'Chronograph Watches',
    'Smart Watches',
    'Casual & Minimalist',
    'Formal & Classic'
];

$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name        = trim($_POST['name'] ?? '');
    $brand       = trim($_POST['brand'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $price       = floatval($_POST['price'] ?? 0);
    $quantity    = intval($_POST['quantity'] ?? 10);
    $description = trim($_POST['description'] ?? '');
    $image_path  = trim($_POST['image_path'] ?? '');

    if (empty($name) || empty($brand) || empty($category) || $price <= 0) {
        $error_msg = "Please fill in all required fields (Name, Brand, Category, and valid Price).";
    } else {
        // Handle image file upload if provided
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['product_image']['tmp_name'];
            $orig_name = basename($_FILES['product_image']['name']);
            $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed_exts)) {
                $upload_dir = __DIR__ . '/../images/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $new_filename = 'watch_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $dest_path = $upload_dir . $new_filename;

                if (move_uploaded_file($tmp_name, $dest_path)) {
                    $image_path = 'images/products/' . $new_filename;
                }
            }
        }

        // Fallback image if none provided
        if (empty($image_path)) {
            $image_path = 'images/watch1.jpg';
        }

        // Insert into products table
        $sql = "INSERT INTO products (name, brand, price, image, category, description, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssdsssi", $name, $brand, $price, $image_path, $category, $description, $quantity);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: products.php?msg=added");
                exit();
            } else {
                $error_msg = "Failed to save product: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_msg = "Database error: " . mysqli_error($conn);
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <div>
        <h1>Add New Watch Product</h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Fill in the product details below to add it to the website catalog.</p>
    </div>
    <a href="products.php" class="btn btn-secondary">&larr; Back to Products</a>
</div>

<div class="form-card">
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <form method="POST" action="product_add.php" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Product Name *</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Titan Contemporary Quartz Watch" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required autofocus>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="brand">Brand *</label>
                <select name="brand" id="brand" class="form-control" required>
                    <option value="">-- Select Brand --</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?php echo htmlspecialchars($b); ?>" <?php echo (isset($_POST['brand']) && $_POST['brand'] === $b) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: #64748b; font-size: 12px;">Need a new brand? <a href="brand_add.php" target="_blank" style="color: #0284c7;">Add Brand first</a></small>
            </div>

            <div class="form-group">
                <label for="category">Category *</label>
                <select name="category" id="category" class="form-control" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($_POST['category']) && $_POST['category'] === $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="price">Price (₹ INR) *</label>
                <input type="number" step="0.01" name="price" id="price" class="form-control" placeholder="e.g. 12499.00" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="quantity">Available Quantity / Stock *</label>
                <input type="number" name="quantity" id="quantity" class="form-control" placeholder="e.g. 15" value="<?php echo htmlspecialchars($_POST['quantity'] ?? '10'); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="product_image">Product Image (Upload file)</label>
            <input type="file" name="product_image" id="product_image" class="form-control" accept="image/*">
            <small style="color: #64748b; font-size: 12px;">Supported: JPG, PNG, WEBP.</small>
        </div>

        <div class="form-group">
            <label for="image_path">Or Existing Image Path</label>
            <input type="text" name="image_path" id="image_path" class="form-control" placeholder="e.g. images/watch1.jpg" value="<?php echo htmlspecialchars($_POST['image_path'] ?? ''); ?>">
            <small style="color: #64748b; font-size: 12px;">Leave blank if you uploaded an image above, or enter path like <code>images/watch1.jpg</code>.</small>
        </div>

        <div class="form-group">
            <label for="description">Product Description</label>
            <textarea name="description" id="description" class="form-control" placeholder="Enter detailed description of watch specifications, movement, water resistance, etc."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>

        <div style="margin-top: 25px; display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">Save & Publish Product</button>
            <a href="products.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
