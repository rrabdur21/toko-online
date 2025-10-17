<?php
session_start();
include 'includes/db.php';
include 'includes/header.php'; // Atau header_no_js.php jika itu yang Anda gunakan

$message = '';

// Ambil semua kategori untuk filter
$categories = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$result_categories = $conn->query($sql_categories);
if ($result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Ambil category_id dari URL untuk filter
$filter_category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// Query untuk mengambil produk
$sql = "SELECT p.id, p.name, p.description, p.price, p.image, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id"; // Join dengan tabel categories
$params = [];
$types = "";

if ($filter_category_id > 0) {
    $sql .= " WHERE p.category_id = ?";
    $params[] = $filter_category_id;
    $types .= "i";
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if ($filter_category_id > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container my-5">
    <h2 class="mb-4">Daftar Produk</h2>
    <hr>

    <div class="mb-4">
        <form action="index.php" method="GET" class="d-flex align-items-center">
            <label for="category_filter" class="form-label me-2 mb-0">Filter Kategori:</label>
            <select class="form-select me-2" id="category_filter" name="category_id" onchange="this.form.submit()" style="width: auto;">
                <option value="0">Semua Kategori</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo ($filter_category_id == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            </form>
    </div>

    <?php echo $message; ?>

    <?php if ($result->num_rows > 0): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php while($product = $result->fetch_assoc()): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text text-muted small">Kategori: <?php echo htmlspecialchars($product['category_name'] ?? 'Tidak Dikategorikan'); ?></p>
                        <p class="card-text description-truncate"><?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="card-text fs-4 fw-bold text-primary">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm">Lihat Detail</a>
                            <form action="cart.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" name="add_to_cart" class="btn btn-success btn-sm"><i class="fas fa-cart-plus me-1"></i> Tambah ke Keranjang</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center mt-4">
            Belum ada produk yang tersedia di kategori ini.
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
