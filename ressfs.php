<?php
// register.php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('Asia/Jakarta');

function log_reg($msg)
{
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    @file_put_contents($dir . '/register.log', '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

require 'koneksi.php'; // harus $koneksi

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit'])) {
        header("Location: form_register.html");
        exit;
    }

    if (!$koneksi) {
        $_SESSION['register_error'] = "Koneksi database gagal.";
        log_reg("DB connect error");
        header("Location: form_register.html?err=" . urlencode($_SESSION['register_error']));
        exit;
    }

    // ===== Ambil & validasi input =====
    $nisn     = trim($_POST['nisn'] ?? '');
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $errors = [];
    if ($nisn === '' || !preg_match('/^\d{8,20}$/', $nisn)) $errors[] = "NISN wajib diisi (angka 8–20 digit).";
    if ($nama === '' || mb_strlen($nama) < 2) $errors[] = "Nama wajib diisi (min. 2 karakter).";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid.";
    if (strlen($password) < 8) $errors[] = "Password minimal 8 karakter.";

    if ($errors) {
        $_SESSION['register_error'] = implode(' ', $errors);
        log_reg("Input invalid: " . $_SESSION['register_error']);
        header("Location: form_register.html?err=" . urlencode($_SESSION['register_error']));
        exit;
    }

    // ===== Cek duplikat =====
    $stmt = mysqli_prepare($koneksi, "SELECT 1 FROM regiss WHERE email=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        $_SESSION['register_error'] = "Email sudah terdaftar.";
        header("Location: form_register.html?err=" . urlencode($_SESSION['register_error']));
        exit;
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($koneksi, "SELECT 1 FROM regiss WHERE nisn=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $nisn);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        $_SESSION['register_error'] = "NISN sudah terdaftar.";
        log_reg("Duplicate NISN: $nisn");
        header("Location: form_register.html?err=" . urlencode($_SESSION['register_error']));
        exit;
    }
    mysqli_stmt_close($stmt);

    // ===== Upload foto =====
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE) {
        $_SESSION['register_error'] = "Foto wajib diunggah.";
        header("Location: form_register.html?err=" . urlencode($_SESSION['register_error']));
        exit;
    }

    $foto = $_FILES['foto'];
    if ($foto['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['register_error'] = "Error saat upload foto (kode {$foto['error']}).";
        header("Location: form_register.html?err=" . urlencode($_SESSION['register_error']));
        exit;
    }
    if ($foto['size'] > 2 * 1024 * 1024) {
        $_SESSION['register_error'] = "Ukuran foto terlalu besar (maks 2MB).";
        header("Location: form_register.html?err=" . urlencode($_SESSION['register_error']));
        exit;
    }

    $origExt = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png'];

    $mime = null;
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = @$finfo->file($foto['tmp_name']);
    } elseif (function_exists('mime_content_type')) {
        $mime  = @mime_content_type($foto['tmp_name']);
    }
    $mimeMap = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $ext = null;
    if ($mime && isset($mimeMap[$mime])) $ext = $mimeMap[$mime];
    elseif (in_array($origExt, $allowedExt, true)) $ext = ($origExt === 'jpeg') ? 'jpg' : $origExt;

    if (!$ext) {
        $_SESSION['register_error'] = "Format foto harus JPG atau PNG.";
        header("Location: form_register.html?err=" . urlencode($_SESSION['register_error']));
        exit;
    }

    $targetDir = __DIR__ . "/uploads/";
    if (!is_dir($targetDir)) @mkdir($targetDir, 0775, true);
    if (!is_writable($targetDir)) @chmod($targetDir, 0755); // InfinityFree biasanya 0755


    $foto_new_name = 'foto_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $foto_dest_abs = $targetDir . $foto_new_name;

    if (!move_uploaded_file($foto['tmp_name'], $foto_dest_abs)) {
        $_SESSION['register_error'] = "Gagal menyimpan file foto (periksa permission folder uploads/).";
        header("Location: form_register.html?err=" . urlencode($_SESSION['register_error']));
        exit;
    }
    @chmod($foto_dest_abs, 0644);

    // ===== Insert =====
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO regiss (nisn, nama, email, password, foto) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, 'sssss', $nisn, $nama, $email, $hash, $foto_new_name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $_SESSION['register_success'] = "Pendaftaran berhasil! Silakan login.";
    log_reg("Register OK: email=$email, nisn=$nisn, foto=$foto_new_name");
    header("Location: login.html");
    exit;
} catch (Throwable $e) {
    // kalau file sudah terupload, hapus
    if (!empty($foto_dest_abs) && is_file($foto_dest_abs)) {
        @unlink($foto_dest_abs);
    }
    $_SESSION['register_error'] = "Terjadi kesalahan server. (cek logs/register.log)";
    log_reg("EXCEPTION: " . $e->getMessage());
    header("Location: form_register.html?err=" . urlencode($_SESSION['register_error']));
    exit;
}
