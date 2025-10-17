<?php
session_start();
include 'includes/db.php';

// Periksa apakah user sudah login dan memiliki peran 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $product_id = (int)$_POST['id'];
    
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Produk berhasil dihapus.";
    } else {
        $_SESSION['message'] = "Error: " . $stmt->error;
    }
}

header("Location: manage_products.php");
exit;
?>