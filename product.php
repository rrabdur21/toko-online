<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

$message = '';

// ========== [MODE PENCARIAN PRODUK] ==========
if (isset($_GET['keyword'])) {
    $keyword = trim($_GET['keyword']);

    $sql = "SELECT p.*, u.name AS seller_name, c.name AS category_name
            FROM products p
            JOIN users u ON p.seller_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.name LIKE ? OR p.description LIKE ?";
    $stmt = $conn->prepare($sql);
    $search = "%" . $keyword . "%";
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>

    <div class="container my-5">
        <h2 class="mb-4">
            Hasil Pencarian untuk "<b><?php echo htmlspecialchars($keyword); ?></b>"
        </h2>

        <?php if ($result->num_rows > 0): ?>
            <div class="row">
                <?php while ($product = $result->fetch_assoc()): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="text-muted mb-1">Penjual: <?php echo htmlspecialchars($product['seller_name']); ?></p>
                                <p class="text-muted mb-1">Kategori: <?php echo htmlspecialchars($product['category_name'] ?? 'Tidak ada'); ?></p>
                                <p class="fw-bold text-primary">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-eye me-1"></i> Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">Tidak ada produk yang cocok dengan pencarian Anda.</div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; exit; }
    
// ========== [MODE DETAIL PRODUK] ==========
elseif (isset($_GET['id'])) {

    $product_id = (int)$_GET['id'];

    $sql = "SELECT p.*, u.name AS seller_name, c.name AS category_name
            FROM products p
            JOIN users u ON p.seller_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        echo "<div class='container mt-5'><div class='alert alert-danger'>Produk tidak ditemukan.</div></div>";
        include 'includes/footer.php';
        exit;
    }

    // Tambah ke keranjang
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
        $quantity = (int)$_POST['quantity'];

        if (!isset($_SESSION['user_id'])) {
            $message = "<div class='alert alert-warning'>Anda harus login untuk menambahkan produk ke keranjang.</div>";
        } elseif ($quantity <= 0) {
            $message = "<div class='alert alert-warning'>Kuantitas harus lebih dari 0.</div>";
        } elseif ($quantity > $product['stock']) {
            $message = "<div class='alert alert-warning'>Stok tidak mencukupi. Stok tersedia: " . htmlspecialchars($product['stock']) . "</div>";
        } else {
            $user_id = $_SESSION['user_id'];

            $sql_check_cart = "SELECT quantity FROM carts WHERE user_id = ? AND product_id = ?";
            $stmt_check_cart = $conn->prepare($sql_check_cart);
            $stmt_check_cart->bind_param("ii", $user_id, $product_id);
            $stmt_check_cart->execute();
            $res_check_cart = $stmt_check_cart->get_result();

            if ($res_check_cart->num_rows > 0) {
                $current_quantity = $res_check_cart->fetch_assoc()['quantity'];
                $new_quantity = $current_quantity + $quantity;

                if ($new_quantity > $product['stock']) {
                    $message = "<div class='alert alert-warning'>Penambahan ini melebihi stok. Stok tersedia: " . htmlspecialchars($product['stock']) . "</div>";
                } else {
                    $sql_update_cart = "UPDATE carts SET quantity = ? WHERE user_id = ? AND product_id = ?";
                    $stmt_update_cart = $conn->prepare($sql_update_cart);
                    $stmt_update_cart->bind_param("iii", $new_quantity, $user_id, $product_id);
                    if ($stmt_update_cart->execute()) {
                        $message = "<div class='alert alert-success'>Kuantitas di keranjang diperbarui!</div>";
                    }
                }
            } else {
                $sql_insert_cart = "INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)";
                $stmt_insert_cart = $conn->prepare($sql_insert_cart);
                $stmt_insert_cart->bind_param("iii", $user_id, $product_id, $quantity);
                if ($stmt_insert_cart->execute()) {
                    $message = "<div class='alert alert-success'>Produk ditambahkan ke keranjang!</div>";
                }
            }
        }
    }
    ?>

    <div class="container my-5">
        <?php echo $message; ?>

        <div class="row">
            <div class="col-md-6">
                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" class="img-fluid rounded shadow-sm" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <div class="col-md-6">
                <h1 class="display-5"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="text-muted mb-2">Dijual oleh: <span class="fw-bold"><?php echo htmlspecialchars($product['seller_name']); ?></span></p>
                <p class="text-muted mb-4">Kategori: <span class="fw-bold"><?php echo htmlspecialchars($product['category_name'] ?? 'Tidak Dikategorikan'); ?></span></p>

                <p class="lead"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

                <div class="d-flex align-items-baseline mb-3">
                    <h2 class="text-primary fw-bold me-3">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></h2>
                    <?php if ($product['stock'] > 0): ?>
                        <span class="badge bg-success fs-6">Stok: <?php echo htmlspecialchars($product['stock']); ?> Unit</span>
                    <?php else: ?>
                        <span class="badge bg-danger fs-6">Stok Habis</span>
                    <?php endif; ?>
                </div>

                <?php if ($product['stock'] > 0): ?>
                <form action="product.php?id=<?php echo $product['id']; ?>" method="POST" class="d-flex align-items-center mt-4">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <div class="input-group w-50 me-3">
                        <span class="input-group-text">Jumlah</span>
                        <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?php echo $product['stock']; ?>">
                    </div>
                    <button type="submit" name="add_to_cart" class="btn btn-primary d-flex align-items-center">
                        <i class="fas fa-shopping-cart me-2"></i> Tambah ke Keranjang
                    </button>
                </form>
                <?php else: ?>
                    <button class="btn btn-secondary btn-lg mt-4" disabled><i class="fas fa-shopping-cart me-2"></i> Stok Habis</button>
                <?php endif; ?>

                <a href="index.php" class="btn btn-outline-secondary mt-4"><i class="fas fa-arrow-left me-2"></i> Kembali</a>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; exit; }

// ========== [MODE DEFAULT: TANPA PARAMETER] ==========
else {
    header("Location: index.php");
    exit;
}
?>
