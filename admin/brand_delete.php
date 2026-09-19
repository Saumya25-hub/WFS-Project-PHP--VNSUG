<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = mysqli_prepare($conn, "DELETE FROM brands WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: brands.php?msg=deleted");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

header("Location: brands.php?err=Could+not+delete+brand");
exit();
