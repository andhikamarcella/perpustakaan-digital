<?php
// diag_insert.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require 'koneksi.php';

try {
  $nisn  = '999932434299';
  $nama  = 'TEST INSERT';
  $email = 'test_ayam@example.com';
  $pass  = password_hash('Password123!', PASSWORD_DEFAULT);
  $foto  = 'default.png';

  $sql = "INSERT INTO regiss (nisn, nama, email, password, foto) VALUES (?,?,?,?,?)";
  $st  = mysqli_prepare($koneksi, $sql);
  mysqli_stmt_bind_param($st, 'sssss', $nisn, $nama, $email, $pass, $foto);
  mysqli_stmt_execute($st);
  echo "OK: inserted id = " . mysqli_insert_id($koneksi);
} catch (Throwable $e){
  http_response_code(500);
  echo "FAIL: " . $e->getMessage();
}
