<?php
// register.php — final, battle-tested for InfinityFree
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('Asia/Jakarta');

function back($msg){
  // kirim error balik ke form
  $msg = urlencode($msg);
  header("Location: form_register.html?err={$msg}");
  exit;
}

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit'])) {
    header("Location: form_register.html");
    exit;
  }

  // ===== 1) Koneksi DB =====
  require __DIR__ . '/koneksi.php'; // harus membuat $koneksi (mysqli)
  if (!$koneksi || $koneksi->connect_errno) {
    back("Koneksi database gagal. Coba lagi nanti.");
  }
  $koneksi->set_charset('utf8mb4');

  // ===== 2) Ambil & validasi input =====
  $nisn = isset($_POST['nisn']) ? trim($_POST['nisn']) : '';
  $nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

  if (!preg_match('/^[0-9]{8,20}$/', $nisn)) back('NISN harus 8–20 digit angka.');
  if (mb_strlen($nama) < 2) back('Nama belum valid.');
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) back('Format email tidak valid.');

  // Password rules: min 8 + upper + lower + digit + symbol
  $pwOk = (strlen($password) >= 8) &&
          preg_match('/[A-Z]/', $password) &&
          preg_match('/[a-z]/', $password) &&
          preg_match('/[0-9]/', $password) &&
          preg_match('/[^A-Za-z0-9]/', $password);
  if (!$pwOk) back('Password belum memenuhi syarat.');

  // ===== 3) Cek duplikat (email / nisn) =====
  $q = $koneksi->prepare("SELECT email, nisn FROM regiss WHERE email=? OR nisn=? LIMIT 1");
  $q->bind_param('ss', $email, $nisn);
  $q->execute();
  $dup = $q->get_result()->fetch_assoc();
  if ($dup) {
    if (strcasecmp($dup['email'], $email) === 0) back('Email sudah terdaftar.');
    if ($dup['nisn'] === $nisn) back('NISN sudah terdaftar.');
  }

  // ===== 4) Upload foto (opsional fallback ke default) =====
  $fotoName = 'default.png';
  if (isset($_FILES['foto']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
    $file = $_FILES['foto'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
      back('Upload foto gagal. Coba file lain (maks 2MB, JPG/PNG).');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
      back('Ukuran foto melebihi 2MB.');
    }
    // validasi mime
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $ext = '';
    if ($mime === 'image/jpeg') $ext = 'jpg';
    elseif ($mime === 'image/png') $ext = 'png';
    else back('Format foto harus JPG atau PNG.');

    // nama file aman
    $fotoName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

    $destDir = __DIR__ . '/uploads';
    if (!is_dir($destDir)) @mkdir($destDir, 0775, true);

    $destPath = $destDir . '/' . $fotoName;

    // InfinityFree kadang membatasi folder → jika gagal, jangan gagalkan registrasi
    if (!@move_uploaded_file($file['tmp_name'], $destPath)) {
      // fallback: kembalikan nama default agar tetap sukses insert
      $fotoName = 'default.png';
    }
  } else {
    back('Foto profil wajib dipilih.');
  }

  // ===== 5) Hash & Insert =====
  $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

  $stmt = $koneksi->prepare("INSERT INTO regiss (nisn, nama, email, password, foto) VALUES (?,?,?,?,?)");
  $stmt->bind_param('sssss', $nisn, $nama, $email, $hash, $fotoName);
  $stmt->execute();

  // ===== 6) Selesai → redirect ke login
  header("Location: login.html");
  exit;

} catch (mysqli_sql_exception $e) {
  $msg  = $e->getMessage();
  $code = (int)$e->getCode();

  if ($code === 1062) { // duplicate entry
    if (
      stripos($msg, "for key 'email'") !== false ||
      stripos($msg, "for key 'uniq_email'") !== false ||
      (stripos($msg, "Duplicate entry") !== false && stripos($msg, "email") !== false)
    ) {
      back('Email sudah terdaftar.');
    } elseif (
      stripos($msg, "for key 'nisn'") !== false ||
      stripos($msg, "for key 'uniq_nisn'") !== false ||
      (stripos($msg, "Duplicate entry") !== false && stripos($msg, "nisn") !== false)
    ) {
      back('NISN sudah terdaftar.');
    } else {
      back('Data sudah terdaftar (duplikat).');
    }
  } else {
    back('Terjadi kesalahan server. (' . htmlspecialchars((string)$code, ENT_QUOTES, 'UTF-8') . ')');
  }
} catch (Throwable $t) {
  back('Terjadi error tak terduga.');
}