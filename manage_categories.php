<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Cek apakah user sudah login dan berperan sebagai 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$edit_category = null; // Untuk menyimpan data kategori jika sedang dalam mode edit

// Logika untuk Menambah Kategori Baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);

    if (empty($category_name)) {
        $message = "<div class='alert alert-danger'>Nama kategori tidak boleh kosong.</div>";
    } else {
        // Cek duplikasi
        $sql_check = "SELECT id FROM categories WHERE name = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $category_name);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows > 0) {
            $message = "<div class='alert alert-warning'>Kategori dengan nama ini sudah ada.</div>";
        } else {
            $sql_insert = "INSERT INTO categories (name) VALUES (?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("s", $category_name);
            if ($stmt_insert->execute()) {
                $message = "<div class='alert alert-success'>Kategori berhasil ditambahkan!</div>";
                $_POST['category_name'] = ''; // Bersihkan input
            } else {
                $message = "<div class='alert alert-danger'>Gagal menambahkan kategori: " . $stmt_insert->error . "</div>";
            }
        }
    }
}

// Logika untuk Mengedit Kategori
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_category_submit'])) {
    $category_id = (int)$_POST['category_id'];
    $new_category_name = trim($_POST['new_category_name']);

    if (empty($new_category_name)) {
        $message = "<div class='alert alert-danger'>Nama kategori tidak boleh kosong.</div>";
    } else {
        // Cek duplikasi (selain kategori yang sedang diedit itu sendiri)
        $sql_check = "SELECT id FROM categories WHERE name = ? AND id != ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("si", $new_category_name, $category_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows > 0) {
            $message = "<div class='alert alert-warning'>Kategori dengan nama ini sudah ada.</div>";
        } else {
            $sql_update = "UPDATE categories SET name = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $new_category_name, $category_id);
            if ($stmt_update->execute()) {
                $message = "<div class='alert alert-success'>Kategori berhasil diperbarui!</div>";
            } else {
                $message = "<div class='alert alert-danger'>Gagal memperbarui kategori: " . $stmt_update->error . "</div>";
            }
        }
    }
}

// Logika untuk Menghapus Kategori
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_category'])) {
    $category_id = (int)$_POST['category_id'];

    $sql_delete = "DELETE FROM categories WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $category_id);
    if ($stmt_delete->execute()) {
        $message = "<div class='alert alert-success'>Kategori berhasil dihapus. Produk yang terkait dengan kategori ini sekarang tidak memiliki kategori (NULL).</div>";
    } else {
        $message = "<div class='alert alert-danger'>Gagal menghapus kategori: " . $stmt_delete->error . "</div>";
    }
}

// Jika ada permintaan untuk mode edit
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $sql_get_category = "SELECT id, name FROM categories WHERE id = ?";
    $stmt_get_category = $conn->prepare($sql_get_category);
    $stmt_get_category->bind_param("i", $edit_id);
    $stmt_get_category->execute();
    $result_get_category = $stmt_get_category->get_result();
    if ($result_get_category->num_rows > 0) {
        $edit_category = $result_get_category->fetch_assoc();
    } else {
        $message = "<div class='alert alert-danger'>Kategori tidak ditemukan untuk diedit.</div>";
    }
}


// Ambil semua kategori untuk ditampilkan
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$result_categories = $conn->query($sql_categories);
?>

<div class="container my-5">
    <h2 class="mb-4">Manajemen Kategori</h2>
    <p>Tambah, edit, atau hapus kategori produk.</p>
    <hr>
    <?php echo $message; ?>

    <div class="card p-4 shadow-sm mb-4">
        <h4><?php echo ($edit_category ? 'Edit Kategori' : 'Tambah Kategori Baru'); ?></h4>
        <form action="manage_categories.php" method="POST">
            <?php if ($edit_category): ?>
                <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($edit_category['id']); ?>">
                <div class="mb-3">
                    <label for="new_category_name" class="form-label">Nama Kategori</label>
                    <input type="text" class="form-control" id="new_category_name" name="new_category_name" value="<?php echo htmlspecialchars($edit_category['name']); ?>" required>
                </div>
                <button type="submit" name="edit_category_submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Simpan Perubahan</button>
                <a href="manage_categories.php" class="btn btn-secondary ms-2">Batal</a>
            <?php else: ?>
                <div class="mb-3">
                    <label for="category_name" class="form-label">Nama Kategori</label>
                    <input type="text" class="form-control" id="category_name" name="category_name" value="<?php echo htmlspecialchars($_POST['category_name'] ?? ''); ?>" required>
                </div>
                <button type="submit" name="add_category" class="btn btn-success"><i class="fas fa-plus-circle me-2"></i> Tambah Kategori</button>
            <?php endif; ?>
        </form>
    </div>

    <h4 class="mt-5">Daftar Kategori</h4>
    <?php if ($result_categories->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Nama Kategori</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($category = $result_categories->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['id']); ?></td>
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td>
                            <a href="manage_categories.php?action=edit&id=<?php echo $category['id']; ?>" class="btn btn-warning btn-sm me-2"><i class="fas fa-edit"></i> Edit</a>
                            <form action="manage_categories.php" method="POST" class="d-inline">
                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                <button type="submit" name="delete_category" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini? Produk yang terkait dengan kategori ini akan kehilangan kategorinya.');"><i class="fas fa-trash-alt"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            Belum ada kategori terdaftar.
        </div>
    <?php endif; ?>

    <a href="admin_dashboard.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard</a>
</div>

<?php include 'includes/footer.php'; ?>