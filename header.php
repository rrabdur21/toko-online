<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>toko mahal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="#">
            <img src="foto/logo.png" alt="tokoku" width="60" height="50">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link active me-6" href="index.php"><i class="fa-solid fa-house"></i>home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cart.php"><i class="fa-solid fa-cart-shopping"></i>keranjang</a>
                </li>
                                </ul>
                        <div class="d-flex align-items-center">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_role'] == 'admin'): ?>
                        <a href="admin_dashboard.php" class="btn btn-outline-danger me-2"><i class="fas fa-user-shield me-1"></i> Dashboard Admin</a>
                    <?php elseif ($_SESSION['user_role'] == 'seller'): ?>
                        <a href="seller_dashboard.php" class="btn btn-outline-success me-2"><i class="fas fa-chart-line me-1"></i> Dashboard Penjual</a>
                    <?php endif; ?>
                    
                    <a href="profil.php" class="btn btn-outline-primary me-2"><i class="fas fa-user me-1"></i> Profil</a>
                    
                    <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt me-1"></i> Keluar</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary me-2"><i class="fas fa-sign-in-alt me-1"></i> Masuk</a>
                    <a href="register.php" class="btn btn-primary"><i class="fas fa-user-plus me-1"></i> Daftar</a>
                <?php endif; ?>
            </div>
        </div>
</nav>

<header class="hero-section text-bla text-center py-5">
    <div class="container">
        <h1 class="display-4 fw-bold">Temukan Produk Impian Anda</h1>
        <p class="lead">Ribuan produk dari penjual terpercaya, hanya di sini.</p>
        <form action="product.php" method="get" class="input-group my-4 w-75 mx-auto">
            <input type="text" class="form-control form-control-lg rounded-pill" placeholder="Cari produk..." aria-label="Cari produk..." name="keyword">
            <button class="btn btn-primary rounded-pill ms-2"><i class="fas fa-search"></i> Cari</button>
        </form>
    </div>
</header>