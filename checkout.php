<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Cek jika keranjang kosong
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$total_price = 0;
$message = '';
$order_placed_successfully = false;
$order_id = null;

// Proses pesanan saat form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    try {
        // Mulai transaksi database
        $conn->begin_transaction();

        // 1. Hitung total harga dan validasi stok
        $total_price = 0;
        $order_items_to_process = [];
        foreach ($_SESSION['cart'] as $item) {
            // Ambil data produk terbaru dari database untuk validasi stok
            $sql_stock_check = "SELECT id, price, stock FROM products WHERE id = ? FOR UPDATE";
            $stmt_stock = $conn->prepare($sql_stock_check);
            $stmt_stock->bind_param("i", $item['id']);
            $stmt_stock->execute();
            $product_db = $stmt_stock->get_result()->fetch_assoc();

            if (!$product_db || $product_db['stock'] < $item['quantity']) {
                $conn->rollback();
                $message = "<div class='alert alert-danger'>Maaf, stok untuk produk " . htmlspecialchars($item['name']) . " tidak mencukupi.</div>";
                throw new Exception("Stok tidak mencukupi");
            }

            // Simpan data produk yang valid
            $total_price += $item['price'] * $item['quantity'];
            $order_items_to_process[] = $item;
            
            // 2. Kurangi stok produk
            $sql_update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update_stock);
            $stmt_update->bind_param("ii", $item['quantity'], $item['id']);
            $stmt_update->execute();
        }

        // 3. Masukkan pesanan utama ke tabel 'orders'
        $sql_order = "INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, 'pending')";
        $stmt_order = $conn->prepare($sql_order);
        $stmt_order->bind_param("id", $user_id, $total_price);
        $stmt_order->execute();
        $order_id = $conn->insert_id;

        // 4. Masukkan setiap item dari keranjang ke tabel 'order_items'
        $sql_items = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt_items = $conn->prepare($sql_items);
        
        foreach ($order_items_to_process as $item) {
            $stmt_items->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
            $stmt_items->execute();
        }

        // Jika semua berhasil, commit transaksi
        $conn->commit();
        
        // Hapus keranjang setelah pesanan berhasil
        unset($_SESSION['cart']);
        
        // Atur flag untuk menampilkan halaman sukses
        $order_placed_successfully = true;

    } catch (Exception $e) {
        // Jika ada error, batalkan semua perubahan
        $conn->rollback();
        if (!isset($message) || empty($message)) {
            $message = "<div class='alert alert-danger'>Terjadi kesalahan saat memproses pesanan: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<div class="container my-5">
    <?php if ($order_placed_successfully): ?>
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-sm p-4 text-center">
                    <i class="fas fa-check-circle text-success" style="font-size: 80px;"></i>
                    <h2 class="mt-4 text-success">Pesanan Berhasil!</h2>
                    <p class="lead">Terima kasih, **<?php echo htmlspecialchars($_SESSION['user_name']); ?>**.</p>
                    <p>Pesanan Anda dengan ID **#<?php echo htmlspecialchars($order_id); ?>** telah berhasil dibuat.</p>
                    <hr class="my-4">
                    <div class="text-start">
                        <h5>Ringkasan Pesanan</h5>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <strong>Total Pembayaran</strong>
                                <span class="fw-bold fs-5 text-primary">Rp <?php echo number_format($total_price, 0, ',', '.'); ?></span>
                            </li>
                        </ul>
                    </div>
                    <a href="index.php" class="btn btn-primary btn-lg mt-3">Kembali ke Halaman Utama</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <h2>Konfirmasi Pesanan</h2>
        <hr>
        <?php if (isset($message)) echo $message; ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5>Daftar Produk</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Jumlah</th>
                                        <th>Harga Satuan</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_price_display = 0;
                                    if (!empty($_SESSION['cart'])) {
                                        foreach ($_SESSION['cart'] as $item):
                                            $subtotal = $item['price'] * $item['quantity'];
                                            $total_price_display += $subtotal;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                        <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                        <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach;
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Ringkasan Pesanan</h5>
                        <hr>
                        <p class="fs-5 fw-bold">Total Harga: <span class="text-primary">Rp <?php echo number_format($total_price_display, 0, ',', '.'); ?></span></p>
                        <p class="text-muted small">Dengan mengklik "Bayar Sekarang", Anda menyetujui syarat dan ketentuan kami.</p>
                        <form action="checkout.php" method="POST">
                            <input type="hidden" name="place_order" value="1">
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-success"><i class="fas fa-check-circle me-2"></i> Bayar Sekarang</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>