<?php
session_start();
include 'includes/db.php';

// Periksa login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'seller') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $product_id = $_POST['id'];
    $seller_id = $_SESSION['user_id'];

    // Hapus produk dari database
    $sql = "DELETE FROM products WHERE id = ? AND seller_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $product_id, $seller_id);
    
    if ($stmt->execute()) {
        // Redirect kembali ke dashboard setelah berhasil
        header("Location: seller_dashboard.php");
        exit;
    } else {
        // Jika gagal, bisa menampilkan pesan error
        echo "Error: " . $conn->error;
    }
} else {
    // Jika tidak ada ID, redirect kembali
    header("Location: seller_dashboard.php");
    exit;
}
?>