<?php
// migrate_passwords.php
$DB_HOST = "sql106.infinityfree.com";
$DB_USER = "if0_39628444";
$DB_PASS = "yQkJsf8Vqf6FOl";
$DB_NAME = "if0_39628444_andhika";

$k = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (!$k) { die("DB error: ".mysqli_connect_error()); }

// DRY RUN: true = hanya lapor tanpa update; false = benar2 update
$DRY_RUN = false;

// ambil semua user (bisa dipaging kalau tabel besar)
$q = mysqli_query($k, "SELECT email, password FROM regiss");
if (!$q) { die("Query error: ".mysqli_error($k)); }

$total = 0; $alreadyHashed = 0; $migrated = 0; $skippedEmpty = 0;

mysqli_begin_transaction($k);
try {
  while ($row = mysqli_fetch_assoc($q)) {
    $total++;
    $email   = $row['email'];
    $pwd     = $row['password'];

    if ($pwd === null || $pwd === '') {
      $skippedEmpty++;
      continue;
    }

    // deteksi sudah hash
    $isHashed = preg_match('/^\$2y\$/', $pwd) || preg_match('/^\$argon2/i', $pwd);

    if ($isHashed) {
      $alreadyHashed++;
      continue;
    }

    // plaintext → hash sekarang
    $hash = password_hash($pwd, PASSWORD_DEFAULT);

    if (!$DRY_RUN) {
      // simpan salinan lama (kalau kolom password_old tersedia)
      // abaikan jika tidak ada kolomnya
      @mysqli_query($k, sprintf(
        "UPDATE regiss SET password_old='%s' WHERE email='%s'",
        mysqli_real_escape_string($k, $pwd),
        mysqli_real_escape_string($k, $email)
      ));

      // update password → hash
      $upd = mysqli_prepare($k, "UPDATE regiss SET password=? WHERE email=?");
      if ($upd) {
        mysqli_stmt_bind_param($upd, 'ss', $hash, $email);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
        $migrated++;
      } else {
        throw new Exception("Prepare update gagal: ".mysqli_error($k));
      }
    } else {
      // DRY RUN hanya lapor
      $migrated++;
    }
  }

  if ($DRY_RUN) {
    mysqli_rollback($k);
    echo "DRY RUN selesai. Tidak ada perubahan disimpan.\n";
  } else {
    mysqli_commit($k);
    echo "Migrasi sukses.\n";
  }
} catch (Throwable $e) {
  mysqli_rollback($k);
  die("Migrasi gagal: ".$e->getMessage());
}

mysqli_close($k);

echo "Total rows       : $total\n";
echo "Sudah hashed     : $alreadyHashed\n";
echo "Migrated (target): $migrated\n";
echo "Kosong/skip      : $skippedEmpty\n";
