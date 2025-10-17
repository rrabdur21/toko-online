<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Cek apakah user sudah login, jika tidak, arahkan ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_role = $_SESSION['user_role'];
$message = '';

// Logika untuk mengubah status pesanan (baik untuk admin maupun seller)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];

    // Query UPDATE berbeda tergantung peran
    if ($user_role == 'admin') {
        $sql_update = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $new_status, $order_id);
    } elseif ($user_role == 'seller') {
        $seller_id = $_SESSION['user_id'];
        // Pastikan penjual hanya bisa mengubah status pesanan yang terkait dengan produknya
        $sql_update = "UPDATE orders SET status = ? WHERE id = ? AND id IN (SELECT order_id FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE p.seller_id = ?)";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sii", $new_status, $order_id, $seller_id);
    }
    
    if ($stmt_update->execute()) {
        $message = "<div class='alert alert-success'>Status pesanan #{$order_id} berhasil diperbarui.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Gagal memperbarui status. Silakan coba lagi.</div>";
    }
}

// Logika untuk MENAMBAHKAN STOK produk (khusus untuk seller)
if ($user_role == 'seller' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_stock'])) {
    $product_id = (int)$_POST['product_id'];
    $stock_to_add = (int)$_POST['stock_to_add'];
    $seller_id = $_SESSION['user_id'];

    if ($stock_to_add > 0) {
        // Cek apakah produk benar-benar milik penjual ini sebelum update
        $sql_check_product = "SELECT id FROM products WHERE id = ? AND seller_id = ?";
        $stmt_check = $conn->prepare($sql_check_product);
        $stmt_check->bind_param("ii", $product_id, $seller_id);
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();

        if ($check_result->num_rows > 0) {
            // Update stok produk
            $sql_update_stock = "UPDATE products SET stock = stock + ? WHERE id = ?";
            $stmt_update_stock = $conn->prepare($sql_update_stock);
            $stmt_update_stock->bind_param("ii", $stock_to_add, $product_id);

            if ($stmt_update_stock->execute()) {
                $message = "<div class='alert alert-success'>Stok untuk produk ID #{$product_id} berhasil ditambahkan sebanyak {$stock_to_add}.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Gagal memperbarui stok. Silakan coba lagi.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Produk tidak ditemukan atau bukan milik Anda.</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Jumlah stok harus lebih dari 0.</div>";
    }
}

// Logika untuk mengambil data pesanan berdasarkan peran
if ($user_role == 'admin') {
    // Admin melihat SEMUA pesanan
    $sql_orders = "SELECT o.id, o.order_date, o.total_price, o.status, u.name AS buyer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC";
    $stmt_orders = $conn->prepare($sql_orders);
    $stmt_orders->execute();
    $orders_result = $stmt_orders->get_result();
    $page_title = "Manajemen Pesanan (Admin)";

} elseif ($user_role == 'seller') {
    // Penjual hanya melihat pesanan yang berisi produknya
    $seller_id = $_SESSION['user_id'];
    $sql_orders = "SELECT o.id, o.order_date, o.total_price, o.status, u.name AS buyer_name
                   FROM orders o
                   JOIN order_items oi ON o.id = oi.order_id
                   JOIN products p ON oi.product_id = p.id
                   JOIN users u ON o.user_id = u.id
                   WHERE p.seller_id = ?
                   GROUP BY o.id
                   ORDER BY o.order_date DESC";
    $stmt_orders = $conn->prepare($sql_orders);
    $stmt_orders->bind_param("i", $seller_id);
    $stmt_orders->execute();
    $orders_result = $stmt_orders->get_result();
    $page_title = "Manajemen Pesanan (Penjual)";
} else {
    // Arahkan ke halaman utama jika peran tidak valid atau tidak ada
    header("Location: index.php");
    exit;
}
?>

<div class="container my-5">
    <h2><?php echo $page_title; ?></h2>
    <p>Selamat datang, **<?php echo htmlspecialchars($_SESSION['user_name']); ?>**.</p>
    <hr>
    <?php echo $message; ?>

    <?php if ($orders_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th scope="col">ID Pesanan</th>
                        <th scope="col">Pembeli</th>
                        <th scope="col">Tanggal</th>
                        <th scope="col">Total</th>
                        <th scope="col">Status</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = $orders_result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                        <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                        <td><?php echo date('d M Y H:i', strtotime($order['order_date'])); ?></td>
                        <td>Rp <?php echo number_format($order['total_price'], 0, ',', '.'); ?></td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></td>
                        <td>
                            <form method="POST" class="d-flex align-items-center">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="status" class="form-select me-2 form-select-sm">
                                    <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="shipping" <?php echo ($order['status'] == 'shipping') ? 'selected' : ''; ?>>Shipping</option>
                                    <option value="completed" <?php echo ($order['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-primary btn-sm">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            Anda belum memiliki pesanan masuk.
        </div>
    <?php endif; ?>

    <?php if ($user_role == 'seller'): ?>
    <hr class="my-5">
    <h2>Tambah Stok Produk</h2>
    <p>Gunakan formulir ini untuk menambahkan stok pada produk Anda. Masukkan ID produk dan jumlah stok yang ingin ditambahkan.</p>
    <div class="card p-4 shadow-sm">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="product_id" class="form-label">ID Produk</label>
                    <input type="number" class="form-control" id="product_id" name="product_id" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="stock_to_add" class="form-label">Jumlah Stok Tambahan</label>
                    <input type="number" class="form-control" id="stock_to_add" name="stock_to_add" min="1" required>
                </div>
            </div>
            <button type="submit" name="add_stock" class="btn btn-success mt-3"><i class="fas fa-plus-circle me-2"></i> Tambah Stok</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>