<?php
session_start();
include 'includes/db.php';
include 'includes/header.php'; // Atau header.php, sesuai kebutuhan navigasi

// Cek apakah user sudah login dan berperan sebagai 'seller'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'seller') {
    header("Location: login.php");
    exit;
}

$seller_id = $_SESSION['user_id'];
$message = '';

// Ambil semua kategori dari database
$categories = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$result_categories = $conn->query($sql_categories);
if ($result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Logika untuk menambah produk baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id']; // Ambil category_id dari form

    // Validasi input
    if (empty($name) || empty($description) || empty($price) || empty($stock) || empty($category_id)) {
        $message = "<div class='alert alert-danger'>Semua kolom wajib diisi.</div>";
    } elseif ($price <= 0 || $stock < 0) {
        $message = "<div class='alert alert-danger'>Harga dan stok harus valid.</div>";
    } else {
        // Upload gambar
        $target_dir = "uploads/";
        $image_name = ''; // Default jika tidak ada gambar diupload
        $uploadOk = 1;

        if (!empty($_FILES["image"]["name"])) {
            $image_name = uniqid() . "_" . basename($_FILES["image"]["name"]); // Buat nama unik
            $target_file = $target_dir . $image_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Validasi file gambar
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if($check === false) {
                $message = "<div class='alert alert-danger'>File bukan gambar.</div>";
                $uploadOk = 0;
            }

            // Check file size (max 5MB)
            if ($_FILES["image"]["size"] > 5000000) {
                $message = "<div class='alert alert-danger'>Maaf, ukuran file terlalu besar (maks 5MB).</div>";
                $uploadOk = 0;
            }

            // Allow certain file formats
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
                $message = "<div class='alert alert-danger'>Maaf, hanya format JPG, JPEG, PNG & GIF yang diizinkan.</div>";
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                     $message = "<div class='alert alert-danger'>Maaf, terjadi kesalahan saat mengunggah file gambar Anda.</div>";
                     $uploadOk = 0; // Gagal upload, jangan masukkan ke DB
                }
            }
        } else {
             $image_name = 'default.jpg'; // Menggunakan gambar default jika tidak ada yang diupload
        }


        if ($uploadOk == 1) {
            // Masukkan produk ke database, termasuk category_id
            $sql = "INSERT INTO products (seller_id, name, description, price, stock, image, category_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssisi", $seller_id, $name, $description, $price, $stock, $image_name, $category_id);

            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Produk baru berhasil ditambahkan!</div>";
                // Reset form setelah berhasil
                $_POST = array();
            } else {
                $message = "<div class='alert alert-danger'>Gagal menambahkan produk: " . $stmt->error . "</div>";
            }
        }
    }
}
?>

<div class="container my-5">
    <h2>Tambah Produk Baru</h2>
    <p>Selamat datang, **<?php echo htmlspecialchars($_SESSION['user_name']); ?>**. Tambahkan produk baru Anda di sini.</p>
    <hr>
    <?php echo $message; ?>

    <div class="card p-4 shadow-sm">
        <form action="add_product.php" method="POST" enctype="multipart/form-data">
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
                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="stock" class="form-label">Stok</label>
                <input type="number" class="form-control" id="stock" name="stock" min="0" required value="<?php echo htmlspecialchars($_POST['stock'] ?? ''); ?>">
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
                <input class="form-control" type="file" id="image" name="image">
                <small class="form-text text-muted">Biarkan kosong untuk menggunakan gambar default. Ukuran maks 5MB.</small>
            </div>
            <button type="submit" name="add_product" class="btn btn-primary"><i class="fas fa-plus-circle me-2"></i> Tambah Produk</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>