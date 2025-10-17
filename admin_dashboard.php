<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Cek apakah user sudah login dan berperan sebagai 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit;
}
?>

<div class="container my-5">
    <h2 class="mb-4">Dashboard Admin</h2>
    <p>Selamat datang, **<?php echo htmlspecialchars($_SESSION['user_name']); ?>**. Ini adalah panel kontrol Anda sebagai administrator.</p>
    <hr>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-4x text-primary mb-3"></i>
                    <h5 class="card-title">Manajemen Pengguna</h5>
                    <p class="card-text">Kelola akun pengguna (pembeli, penjual, admin).</p>
                    <a href="manage_users.php" class="btn btn-primary mt-2"><i class="fas fa-arrow-right me-2"></i> Kelola Pengguna</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-tags fa-4x text-success mb-3"></i>
                    <h5 class="card-title">Manajemen Kategori</h5>
                    <p class="card-text">Tambah, edit, atau hapus kategori produk.</p>
                    <a href="manage_categories.php" class="btn btn-success mt-2"><i class="fas fa-arrow-right me-2"></i> Kelola Kategori</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-box fa-4x text-info mb-3"></i>
                    <h5 class="card-title">Lihat Semua Produk</h5>
                    <p class="card-text">Lihat semua produk dari seluruh penjual.</p>
                    <a href="admin_view_all_products.php" class="btn btn-info mt-2"><i class="fas fa-arrow-right me-2"></i> Lihat Produk</a>
                </div>
            </div>
        </div>
        </div>
</div>

<?php include 'includes/footer.php'; ?>
    