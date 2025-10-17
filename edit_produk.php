<?php
session_start();
include 'includes/db.php';
include 'includes/header.php'; 

// Cek apakah user sudah login dan berperan sebagai 'seller'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'seller') {
    header("Location: login.php");
    exit;
}

$seller_id = $_SESSION['user_id'];
$message = '';
$product = null;

// Ambil semua kategori dari database
$categories = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$result_categories = $conn->query($sql_categories);
if ($result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Ambil ID produk dari URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id > 0) {
    // Ambil detail produk dari database (pastikan produk ini milik seller yang login)
    $sql_product = "SELECT * FROM products WHERE id = ? AND seller_id = ?";
    $stmt_product = $conn->prepare($sql_product);
    $stmt_product->bind_param("ii", $product_id, $seller_id);
    $stmt_product->execute();
    $result_product = $stmt_product->get_result();
    $product = $result_product->fetch_assoc();

    if (!$product) {
        $message = "<div class='alert alert-danger'>Produk tidak ditemukan atau bukan milik Anda.</div>";
        $product_id = 0; // Set ID ke 0 untuk mencegah pengeditan
    }
} else {
    $message = "<div class='alert alert-danger'>ID produk tidak valid.</div>";
}

// Logika untuk memperbarui produk
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product']) && $product_id > 0) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id']; // Ambil category_id

    // Validasi input
    if (empty($name) || empty($description) || empty($price) || empty($stock) || empty($category_id)) {
        $message = "<div class='alert alert-danger'>Semua kolom wajib diisi.</div>";
    } elseif ($price <= 0 || $stock < 0) {
        $message = "<div class='alert alert-danger'>Harga dan stok harus valid.</div>";
    } else {
        $current_image = $product['image']; // Ambil nama gambar saat ini

        // Proses upload gambar baru jika ada
        $uploadOk = 1;
        if (!empty($_FILES["image"]["name"])) {
            $image_name = uniqid() . "_" . basename($_FILES["image"]["name"]);
            $target_dir = "uploads/";
            $target_file = $target_dir . $image_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if($check === false) { $message = "<div class='alert alert-danger'>File bukan gambar.</div>"; $uploadOk = 0; }
            if ($_FILES["image"]["size"] > 5000000) { $message = "<div class='alert alert-danger'>Maaf, ukuran file terlalu besar (maks 5MB).</div>"; $uploadOk = 0; }
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) { $message = "<div class='alert alert-danger'>Maaf, hanya format JPG, JPEG, PNG & GIF yang diizinkan.</div>"; $uploadOk = 0; }

            if ($uploadOk == 1) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // Jika upload baru berhasil, hapus gambar lama (kecuali default.jpg)
                    if ($current_image != 'default.jpg' && file_exists($target_dir . $current_image)) {
                        unlink($target_dir . $current_image);
                    }
                    $current_image = $image_name; // Update nama gambar ke yang baru
                } else {
                     $message = "<div class='alert alert-danger'>Maaf, terjadi kesalahan saat mengunggah file gambar Anda.</div>";
                     $uploadOk = 0;
                }
            }
        }

        if ($uploadOk == 1) {
            // Update produk di database
            $sql_update = "UPDATE products SET name=?, description=?, price=?, stock=?, image=?, category_id=? WHERE id=? AND seller_id=?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("sssisiii", $name, $description, $price, $stock, $current_image, $category_id, $product_id, $seller_id);

            if ($stmt_update->execute()) {
                $message = "<div class='alert alert-success'>Produk berhasil diperbarui!</div>";
                // Refresh data produk setelah update
                $stmt_product->execute();
                $result_product = $stmt_product->get_result();
                $product = $result_product->fetch_assoc();
            } else {
                $message = "<div class='alert alert-danger'>Gagal memperbarui produk: " . $stmt_update->error . "</div>";
            }
        }
    }
}
?>

<div class="container my-5">
    <h2>Edit Produk</h2>
    <p>Selamat datang, **<?php echo htmlspecialchars($_SESSION['user_name']); ?>**. Edit detail produk Anda di sini.</p>
    <hr>
    <?php echo $message; ?>

    <?php if ($product_id > 0 && $product): ?>
    <div class="card p-4 shadow-sm">
        <form action="edit_product.php?id=<?php echo $product['id']; ?>" method="POST" enctype="multipart/form-data">
            <div class="mb-3 text-center">
                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="Gambar Produk" class="img-thumbnail" style="max-width: 200px; height: auto;">
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">Nama Produk</label>
                <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Harga</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="stock" class="form-label">Stok</label>
                <input type="number" class="form-control" id="stock" name="stock" min="0" required value="<?php echo htmlspecialchars($product['stock'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label">Kategori</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Pilih Kategori</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Ubah Gambar Produk</label>
                <input class="form-control" type="file" id="image" name="image">
                <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah gambar. Ukuran maks 5MB.</small>
            </div>
            <button type="submit" name="edit_product" class="btn btn-primary"><i class="fas fa-save me-2"></i> Simpan Perubahan</button>
            <a href="manage_products.php" class="btn btn-secondary ms-2">Kembali ke Manajemen Produk</a>
        </form>
    </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            Produk tidak ditemukan atau tidak dapat dimuat.
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>