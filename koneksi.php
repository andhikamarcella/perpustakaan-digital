<?php
mysqli_report(MYSQLI_REPORT_OFF);

$DB_HOST = "sql106.infinityfree.com";
$DB_USER = "if0_39628444";
$DB_PASS = "yQkJsf8Vqf6FOl";
$DB_NAME = "if0_39628444_andhika";

$koneksi = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($koneksi && !$koneksi->connect_errno) {
  $koneksi->set_charset('utf8mb4');
}