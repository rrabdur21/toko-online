<?php
session_start();
include 'includes/db.php';
include 'includes/header_no_js.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';

// Logika untuk mengubah peran user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_role'])) {
    $user_id_to_update = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'];

    
    if ($user_id_to_update == $_SESSION['user_id']) {
        $message = "<div class='alert alert-danger'>Anda tidak bisa mengubah peran Anda sendiri di sini.</div>";
    } elseif (!in_array($new_role, ['buyer', 'seller', 'admin'])) {
        $message = "<div class='alert alert-danger'>Peran tidak valid.</div>";
    } else {
        $sql_update_role = "UPDATE users SET role = ? WHERE id = ?";
        $stmt_update_role = $conn->prepare($sql_update_role);
        $stmt_update_role->bind_param("si", $new_role, $user_id_to_update);
        if ($stmt_update_role->execute()) {
            $message = "<div class='alert alert-success'>Peran pengguna berhasil diubah.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal mengubah peran: " . $stmt_update_role->error . "</div>";
        }
    }
}

// Logika untuk menghapus user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $user_id_to_delete = (int)$_POST['user_id'];

    // Admin tidak bisa menghapus akunnya sendiri
    if ($user_id_to_delete == $_SESSION['user_id']) {
        $message = "<div class='alert alert-danger'>Anda tidak bisa menghapus akun Anda sendiri.</div>";
    } else {
        // Hapus user dari database (FOREIGN KEY ON DELETE CASCADE akan menangani produk/keranjang)
        $sql_delete_user = "DELETE FROM users WHERE id = ?";
        $stmt_delete_user = $conn->prepare($sql_delete_user);
        $stmt_delete_user->bind_param("i", $user_id_to_delete);
        if ($stmt_delete_user->execute()) {
            $message = "<div class='alert alert-success'>Pengguna berhasil dihapus.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal menghapus pengguna: " . $stmt_delete_user->error . "</div>";
        }
    }
}

// Ambil semua pengguna dari database
$sql_users = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC";
$result_users = $conn->query($sql_users);
?>

<div class="container my-5">
    <h2 class="mb-4">Manajemen Pengguna</h2>
    <p>Kelola semua akun pengguna yang terdaftar di sistem.</p>
    <hr>
    <?php echo $message; ?>

    <?php if ($result_users->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Nama</th>
                        <th scope="col">Email</th>
                        <th scope="col">Peran</th>
                        <th scope="col">Terdaftar Sejak</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $result_users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <form action="manage_users.php" method="POST" class="d-flex align-items-center">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <select name="new_role" class="form-select form-select-sm me-2" style="width: 120px;">
                                    <option value="buyer" <?php echo ($user['role'] == 'buyer') ? 'selected' : ''; ?>>Pembeli</option>
                                    <option value="seller" <?php echo ($user['role'] == 'seller') ? 'selected' : ''; ?>>Penjual</option>
                                    <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                </select>
                                <button type="submit" name="change_role" class="btn btn-sm btn-info me-2" <?php echo ($user['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>Ubah Peran</button>
                            </form>
                        </td>
                        <td><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <form action="manage_users.php" method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini? Semua produk dan keranjang belanja user ini juga akan dihapus!');" <?php echo ($user['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            Belum ada pengguna terdaftar.
        </div>
    <?php endif; ?>

    <a href="admin_dashboard.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard</a>
</div>

<?php include 'includes/footer.php'; ?>