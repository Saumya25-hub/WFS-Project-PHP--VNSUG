<?php
$page_title = "Edit Product";
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header("Location: products.php?err=Invalid+Product+ID");
    exit();
}

// Fetch current product details
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$product) {
    header("Location: products.php?err=Product+not+found");
    exit();
}

// Fetch available brands
$brands = [];
$b_res = mysqli_query($conn, "SELECT name FROM brands ORDER BY name ASC");
if ($b_res) {
    while ($b_row = mysqli_fetch_assoc($b_res)) {
        $brands[] = $b_row['name'];
    }
}

// Predefined categories
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
    $quantity    = intval($_POST['quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $image_path  = trim($_POST['image_path'] ?? $product['image']);

    if (empty($name) || empty($brand) || empty($category) || $price <= 0) {
        $error_msg = "Please fill in all required fields (Name, Brand, Category, and valid Price).";
    } else {
        // Handle file upload if new image provided
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

        // Update database record
        $update_sql = "UPDATE products SET name = ?, brand = ?, price = ?, image = ?, category = ?, description = ?, quantity = ? WHERE id = ?";
        if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
            mysqli_stmt_bind_param($update_stmt, "ssdsssii", $name, $brand, $price, $image_path, $category, $description, $quantity, $id);
            if (mysqli_stmt_execute($update_stmt)) {
                mysqli_stmt_close($update_stmt);
                header("Location: products.php?msg=updated");
                exit();
            } else {
                $error_msg = "Failed to update product: " . mysqli_stmt_error($update_stmt);
            }
            mysqli_stmt_close($update_stmt);
        } else {
            $error_msg = "Database error: " . mysqli_error($conn);
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <div>
        <h1>Edit Product #<?php echo $product['id']; ?></h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Update information for "<?php echo htmlspecialchars($product['name']); ?>"</p>
    </div>
    <a href="products.php" class="btn btn-secondary">&larr; Back to Products</a>
</div>

<div class="form-card">
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <form method="POST" action="product_edit.php?id=<?php echo $product['id']; ?>" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">

        <div class="form-group">
            <label for="name">Product Name *</label>
            <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? $product['name']); ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="brand">Brand *</label>
                <select name="brand" id="brand" class="form-control" required>
                    <option value="">-- Select Brand --</option>
                    <?php 
                    $curr_brand = $_POST['brand'] ?? $product['brand'];
                    foreach ($brands as $b): 
                    ?>
                        <option value="<?php echo htmlspecialchars($b); ?>" <?php echo ($curr_brand === $b) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="category">Category *</label>
                <select name="category" id="category" class="form-control" required>
                    <option value="">-- Select Category --</option>
                    <?php 
                    $curr_cat = $_POST['category'] ?? $product['category'];
                    foreach ($categories as $cat): 
                    ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($curr_cat === $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="price">Price (₹ INR) *</label>
                <input type="number" step="0.01" name="price" id="price" class="form-control" value="<?php echo htmlspecialchars($_POST['price'] ?? $product['price']); ?>" required>
            </div>

            <div class="form-group">
                <label for="quantity">Available Stock *</label>
                <input type="number" name="quantity" id="quantity" class="form-control" value="<?php echo htmlspecialchars($_POST['quantity'] ?? $product['quantity']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Current Image Preview</label>
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 8px;">
                <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="Current Watch" style="width: 70px; height: 70px; object-fit: contain; border: 1px solid #cbd5e1; border-radius: 6px; padding: 3px; background: #fff;" onerror="this.onerror=null; this.src='https://via.placeholder.com/70';">
                <span style="font-size: 13px; color: #64748b;"><code><?php echo htmlspecialchars($product['image']); ?></code></span>
            </div>
        </div>

        <div class="form-group">
            <label for="product_image">Upload New Image (Optional)</label>
            <input type="file" name="product_image" id="product_image" class="form-control" accept="image/*">
            <small style="color: #64748b; font-size: 12px;">Leave empty to keep current image.</small>
        </div>

        <div class="form-group">
            <label for="image_path">Or Change Image Path</label>
            <input type="text" name="image_path" id="image_path" class="form-control" value="<?php echo htmlspecialchars($_POST['image_path'] ?? $product['image']); ?>">
        </div>

        <div class="form-group">
            <label for="description">Product Description</label>
            <textarea name="description" id="description" class="form-control"><?php echo htmlspecialchars($_POST['description'] ?? $product['description']); ?></textarea>
        </div>

        <div style="margin-top: 25px; display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">Update Product</button>
            <a href="products.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
