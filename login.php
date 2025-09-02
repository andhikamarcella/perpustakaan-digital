<?php
// login.php

// 0) Session hardening
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
  'lifetime' => 0, 'path' => '/', 'domain' => '',
  'secure' => $https, 'httponly' => true, 'samesite' => 'Lax',
]);
session_start();

// 1) Koneksi
require 'koneksi.php';
if (!$koneksi || $koneksi->connect_errno) {
  error_log("DB connect error: " . ($koneksi ? $koneksi->connect_error : ''));
  header('Location: login.html?error=server');
  exit;
}

// 2) Validasi request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['login'])) { header('Location: login.html'); exit; }

// 3) Ambil input
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 1) {
  header('Location: login.html?error=invalid'); exit;
}

// 4) Rate limit
$_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
$_SESSION['locked_until']   = $_SESSION['locked_until']   ?? 0;
if (time() < (int)$_SESSION['locked_until']) { header('Location: login.html?error=locked'); exit; }

// 5) Ambil user (email case-insensitive) + password_old
$sql  = "SELECT email, password, password_old FROM regiss WHERE LOWER(email)=LOWER(?) LIMIT 1";
$stmt = mysqli_prepare($koneksi, $sql);
if (!$stmt) { error_log("Prepare failed: ".mysqli_error($koneksi)); header('Location: login.html?error=server'); exit; }
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$user = $res ? mysqli_fetch_assoc($res) : null;

$ok = false;

if ($user) {
  $dbHash = $user['password'] ?? '';
  $oldPlain = $user['password_old'] ?? null;

  // A) Coba verifikasi hash modern
  if (is_string($dbHash) && preg_match('/^\$2y\$/', $dbHash)) {
    $ok = password_verify($password, $dbHash);

    // Rehash jika perlu
    if ($ok && password_needs_rehash($dbHash, PASSWORD_DEFAULT)) {
      $newHash = password_hash($password, PASSWORD_DEFAULT);
      $upd = mysqli_prepare($koneksi, "UPDATE regiss SET password=?, password_old=NULL WHERE LOWER(email)=LOWER(?)");
      if ($upd) { mysqli_stmt_bind_param($upd, 'ss', $newHash, $email); mysqli_stmt_execute($upd); mysqli_stmt_close($upd); }
    }
  }

  // B) Jika gagal, tapi ada password_old (plaintext) → terima & migrasi
  if (!$ok && !empty($oldPlain) && hash_equals($oldPlain, $password)) {
    $ok = true;
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $upd = mysqli_prepare($koneksi, "UPDATE regiss SET password=?, password_old=NULL WHERE LOWER(email)=LOWER(?)");
    if ($upd) { mysqli_stmt_bind_param($upd, 'ss', $newHash, $email); mysqli_stmt_execute($upd); mysqli_stmt_close($upd); }
  }
}

// 6) Hasil
if ($ok) {
  $_SESSION['login_attempts'] = 0;
  $_SESSION['locked_until']   = 0;
  session_regenerate_id(true);
  $_SESSION['email'] = $user['email']; // simpan email aslinya dari DB
  mysqli_stmt_close($stmt); mysqli_close($koneksi);
  header('Location: index.php'); exit;
}

// 7) Gagal
usleep(random_int(200000, 450000));
$_SESSION['login_attempts'] = (int)$_SESSION['login_attempts'] + 1;
if ($_SESSION['login_attempts'] >= 5) {
  $_SESSION['locked_until'] = time() + 60;
  if ($stmt) mysqli_stmt_close($stmt);
  mysqli_close($koneksi);
  header('Location: login.html?error=locked'); exit;
}
if ($stmt) mysqli_stmt_close($stmt);
mysqli_close($koneksi);
header('Location: login.html?error=invalid'); exit;
