<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id > 0) {
    // Delete product from database
    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: products.php?msg=deleted");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

header("Location: products.php?err=Could+not+delete+product");
exit();
