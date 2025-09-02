<?php
$koneksi = mysqli_connect("sql106.infinityfree.com", "if0_39628444", "yQkJsf8Vqf6FOl", "if0_39628444_andhika");

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
echo "Koneksi OK";
