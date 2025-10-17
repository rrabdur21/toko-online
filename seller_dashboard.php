<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Periksa apakah user sudah login dan berperan sebagai 'seller'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'seller') {
    // Jika tidak, alihkan ke halaman login
    header("Location: login.php");
    exit;
}

$seller_id = $_SESSION['user_id'];

// Ambil data produk milik penjual ini
$sql_products = "SELECT id, name, price, stock FROM products WHERE seller_id = ?";
$stmt_products = $conn->prepare($sql_products);
$stmt_products->bind_param("i", $seller_id);
$stmt_products->execute();
$products_result = $stmt_products->get_result();

// Untuk kolom kedua yang juga menampilkan produk, kita perlu mengulang pengambilan hasil atau menyimpan data ke array.
// Agar efisien, kita akan menyimpan hasil ke array dan menggunakannya kembali.
$products_data_for_table = [];
if ($products_result->num_rows > 0) {
    // Kembali ke awal hasil query jika sudah pernah di-fetch di bagian ringkasan
    // Jika belum pernah di-fetch, ini akan mengambil dari awal
    $products_result->data_seek(0);
    while ($product_row = $products_result->fetch_assoc()) {
        $products_data_for_table[] = $product_row;
    }
}
// Mengatur ulang pointer hasil query untuk bagian list produk pertama
$products_result->data_seek(0);

?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Dashboard Penjual</h2>
        <div>
            <a href="tambah_produk.php" class="btn btn-success me-2"><i class="fas fa-plus me-2"></i> Tambah Produk Baru</a>
            <a href="manage_orders.php" class="btn btn-info"><i class="fas fa-clipboard-list me-2"></i> Kelola Pesanan</a>
        </div>
    </div>
    <p>Selamat datang kembali, **<?php echo htmlspecialchars($_SESSION['user_name']); ?>**.</p>
    <hr>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5>Produk Anda (Ringkasan)</h5>
                </div>
                <div class="card-body">
                    <?php if ($products_result->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while($product = $products_result->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($product['name']); ?></span>
                                    <small class="text-muted">Stok: <?php echo htmlspecialchars($product['stock']); ?></small>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-warning text-center">Anda belum memiliki produk. <a href="tambah_produk.php">Tambah produk pertama Anda!</a></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5>Manajemen Produk (Tabel Lengkap)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <?php if (count($products_data_for_table) > 0): ?>
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Nama Produk</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($products_data_for_table as $product): // Menggunakan data dari array yang sudah di-fetch ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($product['stock']); ?></td>
                                            <td>
                                                <a href="edit_produk.php?id=<?php echo $product['id']; ?>" class="btn btn-warning btn-sm me-2"><i class="fas fa-edit"></i> Edit</a>
                                                <form action="delete_produk.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">
                                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-warning text-center">Anda belum memiliki produk. Silakan <a href="tambah_produk.php">tambah produk baru</a>.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>