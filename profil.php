<?php
session_start();
require 'koneksi.php'; // koneksi database

if (!isset($_SESSION['email'])) {
    header('location: login.html');
    exit();
}

$email = $_SESSION['email'];

// Jika tombol hapus akun ditekan
if (isset($_POST['hapus_akun'])) {
    $stmt = $koneksi->prepare("DELETE FROM regiss WHERE email = ?");
    $stmt->bind_param("s", $email);
    $hapus_sukses = $stmt->execute();
    $stmt->close();
    $koneksi->close();

    session_unset();
    session_destroy();

    echo "<script>
        sessionStorage.setItem('hapus_status', '".($hapus_sukses ? "sukses" : "gagal")."');
        window.location.href = 'login.html';
    </script>";
    exit();
}

// Ambil data user
$query = $koneksi->prepare("SELECT nisn, nama, email, foto FROM regiss WHERE email=?");
$query->bind_param("s", $email);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// Hitung kelengkapan profil
$fields = ['nisn','nama','email','foto'];
$filled = 0;
foreach ($fields as $field) {
    if (!empty($user[$field])) $filled++;
}
$progress = ($filled / count($fields)) * 100;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil Pengguna</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #ece9e6, #ffffff);
        transition: background 0.5s, color 0.5s;
    }
    .navbar {
        background: linear-gradient(90deg, #6a11cb, #2575fc);
    }
    .card {
        border-radius: 20px;
        backdrop-filter: blur(10px);
        background: rgba(255,255,255,0.85);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: transform 0.3s;
    }
    .card:hover { transform: translateY(-5px); }
    .profile-pic {
        width: 130px;
        height: 130px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #6a11cb;
        box-shadow: 0 0 20px rgba(106,17,203,0.6);
        transition: transform 0.3s;
    }
    .profile-pic:hover { transform: scale(1.05); }
    footer {
        background: linear-gradient(90deg, #2575fc, #6a11cb);
        color: white;
        padding: 15px 0;
    }
    body.dark-mode {
        background: linear-gradient(135deg, #121212, #1e1e1e);
        color: white;
    }
    body.dark-mode .card {
        background: rgba(30,30,30,0.85);
        color: white;
    }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#"><i class="fas fa-user"></i> Profil</a>
        <div class="d-flex">
            <button id="darkModeToggle" class="btn btn-outline-light btn-sm me-2"><i class="fas fa-moon"></i></button>
            <a href="index.php" class="btn btn-light btn-sm me-2"><i class="fas fa-home"></i> Dashboard</a>
            <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<!-- Profil -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6" data-aos="fade-up">
            <div class="card shadow p-4 text-center">
                <!-- Foto Profil di tengah -->
                <img src="uploads/<?php echo !empty($user['foto']) ? htmlspecialchars($user['foto']) : 'default.jpg'; ?>" 
                     alt="Foto Profil" class="profile-pic mx-auto d-block mb-3">
                <h4 class="mb-3"><?php echo htmlspecialchars($user['nama']); ?></h4>

                <!-- Progress Bar -->
                <div class="mb-3">
                    <small>Kelengkapan Profil</small>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $progress; ?>%;"></div>
                    </div>
                </div>

                <!-- Tabel Data -->
                <table class="table table-bordered text-start">
                    <tr><th style="width: 30%;">NISN</th><td><?php echo htmlspecialchars($user['nisn']); ?></td></tr>
                    <tr><th>Nama</th><td><?php echo htmlspecialchars($user['nama']); ?></td></tr>
                    <tr><th>Email</th><td><?php echo htmlspecialchars($user['email']); ?></td></tr>
                </table>

                <!-- Tombol Aksi -->
                <div class="mt-3 d-flex flex-wrap gap-2 justify-content-center">
                    <a href="edit_profile.php" class="btn btn-primary"><i class="fas fa-edit"></i> Edit Profil</a>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
                    <form id="hapusForm" method="POST" class="d-inline">
                        <input type="hidden" name="hapus_akun" value="1">
                        <button type="button" class="btn btn-danger" onclick="confirmDelete();"><i class="fas fa-trash"></i> Hapus Akun</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="text-center mt-4">
    <p class="mb-0">© 2025 Perpustakaan Digital | Dibuat dengan ❤️</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init();

// Dark mode
document.getElementById('darkModeToggle').addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
});
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
}

// SweetAlert konfirmasi hapus akun (fix submit)
function confirmDelete() {
    Swal.fire({
        title: 'Yakin ingin hapus akun?',
        text: "Tindakan ini tidak bisa dibatalkan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('hapusForm').submit();
        }
    });
}
</script>
</body>
</html>
