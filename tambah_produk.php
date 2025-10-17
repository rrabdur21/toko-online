<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Periksa apakah user sudah login dan berperan sebagai seller
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'seller') {
    header("Location: login.php");
    exit;
}

$message = '';

// Ambil semua kategori dari database untuk dropdown
$categories = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$result_categories = $conn->query($sql_categories);
if ($result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id']; // Ambil category_id dari form
    $seller_id = $_SESSION['user_id'];
    $image = ''; // Placeholder untuk nama file gambar

    // Validasi input
    if (empty($name) || empty($description) || empty($price) || empty($stock) || empty($category_id)) {
        $message = "<div class='alert alert-danger'>Semua kolom wajib diisi.</div>";
    } elseif ($price <= 0 || $stock < 0) {
        $message = "<div class='alert alert-danger'>Harga dan stok harus valid (harga > 0, stok >= 0).</div>";
    } else {
        // Proses upload file gambar
        $uploadOk = 1;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "uploads/";
            // Buat nama unik untuk gambar agar tidak menimpa file lain
            $image_name = uniqid('img_') . '_' . basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $image_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Validasi file gambar
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if($check === false) {
                $message = "<div class='alert alert-danger'>File bukan gambar.</div>";
                $uploadOk = 0;
            }

            // Batasi ukuran file (misal: max 5MB)
            if ($_FILES["image"]["size"] > 5000000) {
                $message = "<div class='alert alert-danger'>Maaf, ukuran file terlalu besar (maks 5MB).</div>";
                $uploadOk = 0;
            }

            // Izinkan hanya format tertentu
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
                $message = "<div class='alert alert-danger'>Maaf, hanya format JPG, JPEG, PNG & GIF yang diizinkan.</div>";
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image = $image_name; // Simpan hanya nama filenya
                } else {
                    $message = "<div class='alert alert-danger'>Maaf, ada error saat mengunggah gambar.</div>";
                    $uploadOk = 0; // Gagal upload, set uploadOk ke 0
                }
            }
        } else {
             // Jika tidak ada gambar diupload, gunakan gambar default
             $image = 'default.jpg';
        }

        if ($uploadOk == 1) { // Lanjutkan hanya jika upload gambar (atau tidak ada upload) berhasil
            // Tambahkan category_id ke query INSERT
            $sql = "INSERT INTO products (seller_id, name, description, price, stock, image, category_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            // "issdisi" -> integer, string, string, double, integer, string, integer
            $stmt->bind_param("issdisi", $seller_id, $name, $description, $price, $stock, $image, $category_id);

            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Produk berhasil ditambahkan!</div>";
                // Reset form setelah berhasil
                $_POST = array();
            } else {
                $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
            }
        }
    }
}
?>

<div class="container mt-5">
    <h2>Tambah Produk Baru</h2>
    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="name" class="form-label">Nama Produk</label>
            <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Deskripsi</label>
            <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Harga</label>
            <input type="number" step="0.01" class="form-control" id="price" name="price" required value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="stock" class="form-label">Stok</label>
            <input type="number" class="form-control" id="stock" name="stock" required value="<?php echo htmlspecialchars($_POST['stock'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="category_id" class="form-label">Kategori</label>
            <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Pilih Kategori</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Gambar Produk</label>
            <input type="file" class="form-control" id="image" name="image">
            <small class="form-text text-muted">Biarkan kosong untuk menggunakan gambar default. Ukuran maks 5MB. Format: JPG, JPEG, PNG, GIF.</small>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Produk</button>
        <a href="manage_products.php" class="btn btn-secondary ms-2">Kembali ke Manajemen Produk</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>