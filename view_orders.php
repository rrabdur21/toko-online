<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Periksa apakah user sudah login dan memiliki peran 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil ID pesanan dari URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id === 0) {
    header("Location: manage_orders.php");
    exit;
}

// --- Proses pembaruan status pesanan ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $sql_update = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $new_status, $order_id);
    $stmt_update->execute();
    
    // Redirect untuk menghindari resubmit
    header("Location: view_order.php?id=" . $order_id);
    exit;
}

// --- Ambil data pesanan utama ---
$sql_order = "SELECT o.*, u.name AS user_name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?";
$stmt_order = $conn->prepare($sql_order);
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$order = $stmt_order->get_result()->fetch_assoc();

// --- Ambil item-item pesanan ---
$sql_items = "SELECT oi.*, p.name AS product_name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

if (!$order) {
    echo "<div class='container my-5'><div class='alert alert-danger'>Pesanan tidak ditemukan.</div></div>";
    include 'includes/footer.php';
    exit;
}
?>

<div class="container my-5">
    <h2>Detail Pesanan #<?php echo htmlspecialchars($order['id']); ?></h2>
    <hr>
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5>Informasi Pelanggan & Pesanan</h5>
                </div>
                <div class="card-body">
                    <p><strong>Nama Pelanggan:</strong> <?php echo htmlspecialchars($order['user_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                    <p><strong>Total Harga:</strong> Rp <?php echo number_format($order['total_price'], 0, ',', '.'); ?></p>
                    <p><strong>Alamat Pengiriman:</strong> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                    <p><strong>Tanggal Pesanan:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-warning text-dark">
                    <h5>Perbarui Status</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status Saat Ini: <span class="badge bg-secondary"><?php echo htmlspecialchars($order['status']); ?></span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="update_status" class="btn btn-primary">Perbarui Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <h4>Daftar Item Pesanan</h4>
        <hr>
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Jumlah</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $result_items->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" width="50" class="me-3">
                                <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>