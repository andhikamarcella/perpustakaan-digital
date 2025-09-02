<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

$email = $_SESSION['email'];
$stmt = $koneksi->prepare("SELECT nisn, nama, email, password, foto FROM regiss WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Hitung kelengkapan profil
$fields = ['nisn', 'nama', 'email', 'password', 'foto'];
$filled = 0;
foreach ($fields as $field) {
    if (!empty($user[$field])) $filled++;
}
$progress = ($filled / count($fields)) * 100;

$status = '';

if (isset($_POST['update'])) {
    $nisn = $_POST['nisn'];
    $nama = $_POST['nama'];
    $emailBaru = $_POST['email'];
    $password = $_POST['password'];

    // Upload foto
    $foto = $user['foto'];
    if (!empty($_FILES['foto']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES["foto"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileType, $allowTypes)) {
            $status = 'format_salah';
        } elseif ($_FILES['foto']['size'] > 2*1024*1024) {
            $status = 'ukuran_besar';
        } else {
            if (move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFilePath)) {
                $foto = $fileName;
            } else {
                $status = 'upload_gagal';
            }
        }
    }

    if ($status == '') {
        $stmtUpdate = $koneksi->prepare("UPDATE regiss SET nisn=?, nama=?, email=?, password=?, foto=? WHERE email=?");
        $stmtUpdate->bind_param("ssssss", $nisn, $nama, $emailBaru, $password, $foto, $email);

        if ($stmtUpdate->execute()) {
            $_SESSION['email'] = $emailBaru;
            $status = 'sukses';
        } else {
            $status = 'gagal';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ubah Profil</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #6a11cb, #2575fc);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.card {
    border-radius: 20px;
    backdrop-filter: blur(10px);
    background: rgba(255,255,255,0.85);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
.profile-pic {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #6a11cb;
}
.glow {
    box-shadow: 0 0 20px rgba(106, 17, 203, 0.8),
                0 0 40px rgba(37, 117, 252, 0.6);
    animation: pulseGlow 2s infinite;
}
@keyframes pulseGlow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(106, 17, 203, 0.8),
                    0 0 40px rgba(37, 117, 252, 0.6);
    }
    50% {
        box-shadow: 0 0 30px rgba(106, 17, 203, 1),
                    0 0 60px rgba(37, 117, 252, 0.8);
    }
}
.progress { height: 8px; border-radius: 10px; }
.input-group-text { background: #6a11cb; color: white; }
.btn-custom {
    background: linear-gradient(90deg, #6a11cb, #2575fc);
    color: white;
    border: none;
}
.btn-custom:hover { opacity: 0.9; }
</style>
</head>
<body>

<div class="container">
    <div class="col-md-6 col-lg-5 mx-auto">
        <div class="card p-4 text-center">
            <h3 class="mb-3 fw-bold">Ubah Profil</h3>

            <img src="uploads/<?php echo htmlspecialchars($user['foto'] ?: 'default.jpg'); ?>" 
                 class="profile-pic mb-3 mx-auto d-block glow" id="previewFoto">

            <div class="mb-3">
                <small>Kelengkapan Profil</small>
                <div class="progress">
                    <div class="progress-bar bg-success" style="width: <?php echo $progress; ?>%;"></div>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Foto Profil</label>
                    <input type="file" name="foto" class="form-control" accept="image/*" onchange="previewImage(event)">
                </div>
                <div class="mb-3 input-group">
                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                    <input type="text" name="nisn" value="<?php echo htmlspecialchars($user['nisn']); ?>" class="form-control" required>
                </div>
                <div class="mb-3 input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" class="form-control" required>
                </div>
                <div class="mb-3 input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-control" required>
                </div>
                <div class="mb-3 input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" id="password" name="password" value="<?php echo htmlspecialchars($user['password']); ?>" class="form-control" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()"><i class="fas fa-eye"></i></button>
                </div>
                <button type="submit" name="update" class="btn btn-custom w-100">💾 Simpan Perubahan</button>
            </form>
            <div class="text-center mt-3">
                <a href="profil.php" class="text-decoration-none"><i class="fas fa-arrow-left"></i> Kembali ke Profil</a>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordField = document.getElementById("password");
    passwordField.type = passwordField.type === "password" ? "text" : "password";
}
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function(){
        document.getElementById('previewFoto').src = reader.result;
    }
    reader.readAsDataURL(event.target.files[0]);
}

<?php if ($status): ?>
    <?php if ($status == 'sukses'): ?>
        Swal.fire('Berhasil!', 'Profil berhasil diperbarui.', 'success');
    <?php elseif ($status == 'gagal'): ?>
        Swal.fire('Gagal!', 'Terjadi kesalahan saat memperbarui.', 'error');
    <?php elseif ($status == 'format_salah'): ?>
        Swal.fire('Format Salah!', 'Hanya file JPG, JPEG, PNG, GIF yang diizinkan.', 'warning');
    <?php elseif ($status == 'ukuran_besar'): ?>
        Swal.fire('Ukuran Terlalu Besar!', 'Maksimal ukuran file 2MB.', 'warning');
    <?php elseif ($status == 'upload_gagal'): ?>
        Swal.fire('Gagal Upload!', 'Terjadi kesalahan saat mengunggah foto.', 'error');
    <?php endif; ?>
<?php endif; ?>
</script>

</body>
</html>
