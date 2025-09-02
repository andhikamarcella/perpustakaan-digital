<?php
date_default_timezone_set("Asia/Jakarta");
session_start();

$hour = date('H');
$current_file = basename($_SERVER['PHP_SELF']);

// Halaman yang bebas dari pengecekan jam
$allow_pages = ['login.php', 'logout.php', 'maintenance.html', 'continue.php'];

// Kalau bukan halaman bebas, dan belum izin after hours, serta jam di luar range → ke maintenance
if (!in_array($current_file, $allow_pages) && !isset($_SESSION['allow_after_hours']) && ($hour >= 16 || $hour < 7)) {
    header("Location: maintenance.html");
    exit();
}
