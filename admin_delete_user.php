<?php
session_start();
include 'includes/db.php';

// Periksa apakah user sudah login dan memiliki peran 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $user_id = (int)$_POST['id'];
    
    // Pastikan admin tidak menghapus dirinya sendiri
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = "Anda tidak bisa menghapus akun Anda sendiri.";
        header("Location: manage_users.php");
        exit;
    }

    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Pengguna berhasil dihapus.";
    } else {
        $_SESSION['message'] = "Error: " . $stmt->error;
    }
}

header("Location: manage_users.php");
exit;
?>
