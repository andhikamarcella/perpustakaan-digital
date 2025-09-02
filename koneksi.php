<?php
mysqli_report(MYSQLI_REPORT_OFF);

$DB_HOST = getenv('DB_HOST');
$DB_USER = getenv('DB_USER');
$DB_PASS = getenv('DB_PASS');
$DB_NAME = getenv('DB_NAME');

$koneksi = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($koneksi && !$koneksi->connect_errno) {
  $koneksi->set_charset('utf8mb4');
}
