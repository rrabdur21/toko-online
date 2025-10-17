<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Ambil data user dari database
$sql_user = "SELECT id, name, email, role, created_at FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();

// Logika untuk mengubah kata sandi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "<div class='alert alert-danger'>Konfirmasi kata sandi tidak cocok.</div>";
    } else {
        // Enkripsi kata sandi baru
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update kata sandi di database
        $sql_update = "UPDATE users SET password = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $hashed_password, $user_id);

        if ($stmt_update->execute()) {
            $message = "<div class='alert alert-success'>Kata sandi berhasil diubah!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal mengubah kata sandi.</div>";
        }
    }
}
?>

<div class="container my-5">
    <h2>Profil Saya</h2>
    <hr>
    <?php echo $message; ?>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5>Informasi Akun</h5>
                </div>
                <div class="card-body">
                    <p><strong>Nama:</strong> <?php echo htmlspecialchars($user_data['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
                    <p><strong>Role:</strong> <span class="badge bg-info"><?php echo htmlspecialchars(ucfirst($user_data['role'])); ?></span></p>
                    <p><strong>Bergabung Sejak:</strong> <?php echo date('d M Y', strtotime($user_data['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5>Ganti Kata Sandi</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Kata Sandi Baru</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Kata Sandi Baru</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Ubah Kata Sandi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>