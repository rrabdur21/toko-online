<?php
session_start();
include 'includes/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $message = "<div class='alert alert-danger text-center'>Email dan password harus diisi.</div>";
    } else {
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] == 'admin') {
                    header("Location: admin_dashboard.php");
                } elseif ($user['role'] == 'seller') {
                    header("Location: seller_dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $message = "<div class='alert alert-danger text-center'>Email atau password salah.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger text-center'>Email atau password salah.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sederhana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .login-box {
            max-width: 400px;
            margin: 80px auto;
            padding: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        .login-box h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-primary {
            width: 100%;
        }
        a {
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Login</h2>
    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password:</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Masuk</button>
        <p class="text-center mt-3">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </p>
    </form>
</div>

</body>
</html>
