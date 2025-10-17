<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Cek apakah user sudah login dan berperan sebagai 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';

// Logika untuk menghapus produk (admin bisa menghapus produk siapapun)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['product_id'];

    // Ambil nama gambar sebelum dihapus
    $sql_get_image = "SELECT image FROM products WHERE id = ?";
    $stmt_get_image = $conn->prepare($sql_get_image);
    $stmt_get_image->bind_param("i", $product_id);
    $stmt_get_image->execute();
    $result_image = $stmt_get_image->get_result();
    $image_row = $result_image->fetch_assoc();
    $image_to_delete = $image_row['image'] ?? null;

    // Hapus produk dari database
    $sql_delete = "DELETE FROM products WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $product_id);

    if ($stmt_delete->execute()) {
        // Hapus file gambar dari folder uploads (kecuali default.jpg)
        if ($image_to_delete && $image_to_delete != 'default.jpg' && file_exists('uploads/' . $image_to_delete)) {
            unlink('uploads/' . $image_to_delete);
        }
        $message = "<div class='alert alert-success'>Produk berhasil dihapus oleh admin.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Gagal menghapus produk: " . $stmt_delete->error . "</div>";
    }
}

// Ambil semua produk dari semua penjual, termasuk nama penjual dan kategori
$sql_products = "SELECT p.id, p.name, p.price, p.stock, p.image, u.name AS seller_name, c.name AS category_name
                 FROM products p
                 LEFT JOIN users u ON p.seller_id = u.id
                 LEFT JOIN categories c ON p.category_id = c.id
                 ORDER BY p.created_at DESC";
$products_result = $conn->query($sql_products);
?>

<div class="container my-5">
    <h2 class="mb-4">Daftar Semua Produk (Admin View)</h2>
    <p>Lihat dan kelola semua produk yang ada di sistem dari berbagai penjual.</p>
    <hr>
    <?php echo $message; ?>

    <?php if ($products_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Gambar</th>
                        <th scope="col">Nama Produk</th>
                        <th scope="col">Penjual</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Harga</th>
                        <th scope="col">Stok</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($product = $products_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['id']); ?></td>
                        <td><img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" width="50" height="50" style="object-fit: cover;"></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['seller_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Tidak Dikategorikan'); ?></td>
                        <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($product['stock']); ?></td>
                        <td>
                            <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-info btn-sm me-2"><i class="fas fa-eye"></i> Lihat</a>
                            <form action="admin_view_all_products.php" method="POST" class="d-inline">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="delete_product" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini secara permanen?');"><i class="fas fa-trash-alt"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            Belum ada produk yang terdaftar di sistem.
        </div>
    <?php endif; ?>

    <a href="admin_dashboard.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard</a>
</div>

<?php include 'includes/footer.php'; ?>