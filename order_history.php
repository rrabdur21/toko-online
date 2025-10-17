<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Periksa apakah user sudah login dan berperan sebagai 'buyer'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'buyer') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil semua pesanan milik user ini
$sql_orders = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$orders_result = $stmt_orders->get_result();
?>

<div class="container my-5">
    <h2>Riwayat Pesanan Anda</h2>
    <hr>
    
    <?php if ($orders_result->num_rows > 0): ?>
        <div class="accordion" id="orderHistoryAccordion">
            <?php while($order = $orders_result->fetch_assoc()): ?>
            <div class="accordion-item mb-3 shadow-sm">
                <h2 class="accordion-header" id="heading_<?php echo $order['id']; ?>">
                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?php echo $order['id']; ?>" aria-expanded="false" aria-controls="collapse_<?php echo $order['id']; ?>">
                        Pesanan #<?php echo htmlspecialchars($order['id']); ?>
                        <span class="ms-auto me-3 text-muted fw-normal">Tanggal: <?php echo date('d M Y', strtotime($order['order_date'])); ?></span>
                        <span class="badge bg-success">Total: Rp <?php echo number_format($order['total_price'], 0, ',', '.'); ?></span>
                    </button>
                </h2>
                <div id="collapse_<?php echo $order['id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading_<?php echo $order['id']; ?>" data-bs-parent="#orderHistoryAccordion">
                    <div class="accordion-body">
                        <p class="mb-3">Status: <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></p>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Harga Satuan</th>
                                        <th>Jumlah</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Ambil item produk untuk pesanan ini
                                    $sql_items = "SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
                                    $stmt_items = $conn->prepare($sql_items);
                                    $stmt_items->bind_param("i", $order['id']);
                                    $stmt_items->execute();
                                    $items_result = $stmt_items->get_result();

                                    while($item = $items_result->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                        <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            Anda belum memiliki riwayat pesanan. <a href="index.php">Mulai belanja sekarang!</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>