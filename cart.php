<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Inisialisasi keranjang jika belum ada atau jika bukan array
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$total_price = 0;
$message = '';

// --- Logika untuk MENAMBAH produk ke keranjang (dari product.php) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity <= 0) {
        $message = "<div class='alert alert-warning'>Jumlah produk harus lebih dari 0.</div>";
    } else {
        // Ambil detail produk dari database
        $sql = "SELECT p.id, p.name, p.price, p.image, p.stock, u.name as seller_name 
                FROM products p JOIN users u ON p.seller_id = u.id WHERE p.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if ($product) {
            if ($quantity > $product['stock']) {
                $message = "<div class='alert alert-warning'>Stok tidak mencukupi untuk produk " . htmlspecialchars($product['name']) . ". Stok tersedia: " . htmlspecialchars($product['stock']) . "</div>";
            } else {
                $item_found = false;
                // Cek apakah produk sudah ada di keranjang
                foreach ($_SESSION['cart'] as &$item) { // Gunakan & untuk referensi agar bisa diubah
                    if ($item['id'] == $product_id) {
                        $item['quantity'] += $quantity;
                        $item_found = true;
                        $message = "<div class='alert alert-success'>Jumlah produk " . htmlspecialchars($product['name']) . " di keranjang berhasil diperbarui.</div>";
                        break;
                    }
                }
                unset($item); // Hapus referensi

                if (!$item_found) {
                    // Tambahkan produk baru ke keranjang dengan semua detail
                    $_SESSION['cart'][] = [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'image' => $product['image'],
                        'seller_name' => $product['seller_name'],
                        'quantity' => $quantity
                    ];
                    $message = "<div class='alert alert-success'>Produk " . htmlspecialchars($product['name']) . " berhasil ditambahkan ke keranjang.</div>";
                }
            }
        } else {
            $message = "<div class='alert alert-danger'>Produk tidak ditemukan.</div>";
        }
    }
    // Redirect untuk menghindari resubmit form (PRG pattern)
    // Simpan pesan di sesi agar tetap terlihat setelah redirect
    $_SESSION['cart_message'] = $message;
    header("Location: cart.php");
    exit;
}

// Tampilkan pesan dari sesi jika ada
if (isset($_SESSION['cart_message'])) {
    $message = $_SESSION['cart_message'];
    unset($_SESSION['cart_message']); // Hapus pesan setelah ditampilkan
}

// --- Logika untuk MENGHAPUS produk dari keranjang ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_item'])) {
    $product_id_to_remove = (int)$_POST['product_id'];
    
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $product_id_to_remove) {
                unset($_SESSION['cart'][$key]);
                break;
            }
        }
        // Atur ulang indeks array setelah penghapusan
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        $message = "<div class='alert alert-success'>Produk berhasil dihapus dari keranjang.</div>";
    }
    // Redirect untuk menghindari resubmit form
    $_SESSION['cart_message'] = $message;
    header("Location: cart.php");
    exit;
}

// --- Logika untuk MEMPERBARUI JUMLAH produk di keranjang ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $new_quantity = (int)$_POST['quantity'];

    if ($new_quantity <= 0) {
        // Jika jumlah 0 atau kurang, hapus item
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $product_id) {
                unset($_SESSION['cart'][$key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index
                $message = "<div class='alert alert-success'>Produk berhasil dihapus dari keranjang.</div>";
                break;
            }
        }
    } else {
        // Cek stok lagi sebelum update
        $sql_stock = "SELECT stock FROM products WHERE id = ?";
        $stmt_stock = $conn->prepare($sql_stock);
        $stmt_stock->bind_param("i", $product_id);
        $stmt_stock->execute();
        $stock_result = $stmt_stock->get_result()->fetch_assoc();
        $available_stock = $stock_result['stock'] ?? 0;

        if ($new_quantity > $available_stock) {
            $message = "<div class='alert alert-warning'>Jumlah yang diminta melebihi stok tersedia (" . htmlspecialchars($available_stock) . ").</div>";
        } else {
            // Perbarui jumlah produk di keranjang
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] == $product_id) {
                    $item['quantity'] = $new_quantity;
                    $message = "<div class='alert alert-success'>Jumlah produk berhasil diperbarui.</div>";
                    break;
                }
            }
            unset($item); // Hapus referensi
        }
    }
    $_SESSION['cart_message'] = $message;
    header("Location: cart.php");
    exit;
}

// --- Ambil data produk di keranjang untuk ditampilkan ---
// Data keranjang sudah ada di $_SESSION['cart'] dengan detail lengkap
?>

<div class="container my-5">
    <h2>Keranjang Belanja</h2>
    <hr>
    <?php echo $message; // Tampilkan pesan ?>

    <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col">Produk</th>
                                <th scope="col">Harga</th>
                                <th scope="col">Jumlah</th>
                                <th scope="col">Subtotal</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_price = 0; // Reset total_price di sini
                            foreach ($_SESSION['cart'] as $item): 
                                $subtotal = $item['price'] * $item['quantity'];
                                $total_price += $subtotal;
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" width="80" class="img-thumbnail me-3">
                                        <div class="ms-3">
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                            <small class="text-muted">Dijual oleh: <?php echo htmlspecialchars($item['seller_name']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                <td>
                                    <!-- Form untuk update jumlah -->
                                    <form method="POST" class="d-flex">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1" class="form-control me-2" style="width: 80px;">
                                        <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-secondary" title="Perbarui Jumlah"><i class="fas fa-sync-alt"></i></button>
                                    </form>
                                </td>
                                <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                                <td>
                                    <!-- Form untuk hapus item -->
                                    <form method="POST">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove_item" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white text-center">
                        Ringkasan Belanja
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Total Harga:</h5>
                        <h3 class="card-text">Rp <?php echo number_format($total_price, 0, ',', '.'); ?></h3>
                        <a href="checkout.php" class="btn btn-primary d-grid mt-4">Lanjutkan ke Pembayaran</a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center" role="alert">
            Keranjang belanja Anda kosong. <a href="index.php">Mulai belanja sekarang!</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>