# 🛍️ Toko Online PHP Sederhana

Proyek ini adalah sistem **toko online berbasis PHP dan MySQL**, yang memungkinkan pengguna untuk:
- Melihat dan membeli produk
- Melakukan login/register
- Admin dapat mengelola produk, kategori, pengguna, dan pesanan

---

## 🚀 Cara Menjalankan Proyek

1. **Clone atau download** repository ini ke folder `htdocs` (jika memakai XAMPP).
2. **Buat database** baru di phpMyAdmin, misalnya: `marketplace`.
3. **Import file SQL** yang ada (biasanya bernama `marketplace.sql`).
4. Pastikan file koneksi `includes/db.php` sesuai dengan pengaturan database-mu:
   ```php
   $koneksi = mysqli_connect("localhost", "root", "", "marketplace");


🧩 Struktur Folder & Penjelasan File
📂 includes/

Berisi file pendukung utama:

File	Fungsi
db.php	Koneksi ke database
header.php	Bagian atas halaman (navbar, link CSS)
footer.php	Bagian bawah halaman (copyright, script JS)
📂 uploads/

Folder untuk menyimpan gambar produk yang diunggah oleh admin/seller.

📄 File Utama
File	Fungsi
index.php	Halaman beranda, menampilkan produk
login.php	Login user/admin
register.php	Registrasi user baru
logout.php	Menghapus sesi login
🧑‍💼 Bagian Admin
File	Fungsi
admin_dashboard.php	Tampilan utama admin (statistik)
manage_users.php	CRUD data user
manage_categories.php	CRUD kategori produk
manage_orders.php	Kelola pesanan pelanggan
admin_view_all_products.php	Menampilkan semua produk di sistem
admin_delete_product.php	Menghapus produk tertentu
admin_delete_user.php	Menghapus akun user tertentu
🧠 Fitur Utama

🔐 Login & Register User/Admin

🛒 CRUD Produk dan Kategori

📦 Sistem Order dan Status Pesanan

📊 Dashboard Admin

🖼️ Upload Gambar Produk

🧑‍💻 Teknologi yang Digunakan

PHP (Native, tanpa framework)

MySQL untuk database

Bootstrap 5 untuk tampilan

Font Awesome untuk ikon

📸 Tampilan (opsional)

Kamu bisa menambahkan screenshot:

![Beranda](screenshots/homepage.png)
![Dashboard Admin](screenshots/dashboard.png)

📜 Lisensi

Proyek ini bersifat open-source dan dapat digunakan untuk pembelajaran.

💡 Pembuat

Ra'uf
Pelajar kelas 12, minat di bidang coding & teknologi.