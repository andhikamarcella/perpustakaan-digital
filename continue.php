<?php

session_start();
$_SESSION['allow_after_hours'] = true;
header("Location: index.php"); // Ganti ke halaman utama kamu
exit();
