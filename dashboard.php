<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Cek apakah user sudah login dan memiliki role 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// Ambil data statistik dari database
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_sellers = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetch_row()[0];
$total_products = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$total_orders = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];

// Ambil daftar semua pengguna
$users_result = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");

// Ambil daftar semua produk
$products_result = $conn->query("SELECT p.id, p.name, p.price, p.stock, u.name AS seller_name FROM products p JOIN users u ON p.seller_id = u.id ORDER BY p.created_at DESC");
?>

<div class="container my-5">
    <h2>Dashboard Admin</h2>
    <p>Selamat datang di panel kontrol, **<?php echo htmlspecialchars($_SESSION['user_name']); ?>**.</p>
    <hr>

    <div class="row text-center mb-5">
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Total Pengguna</h5>
                    <h1 class="display-4"><?php echo $total_users; ?></h1>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Total Penjual</h5>
                    <h1 class="display-4"><?php echo $total_sellers; ?></h1>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Total Produk</h5>
                    <h1 class="display-4"><?php echo $total_products; ?></h1>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Total Pesanan</h5>
                    <h1 class="display-4"><?php echo $total_orders; ?></h1>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5>Daftar Semua Pengguna</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Bergabung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($user['role']); ?></span></td>
                            <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5>Daftar Semua Produk</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Produk</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Penjual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($product = $products_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['id']); ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($product['stock']); ?></td>
                            <td><?php echo htmlspecialchars($product['seller_name']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>