<?php
include 'time_check.php';
date_default_timezone_set("Asia/Jakarta");
$hour = date('H');

$current_file = basename($_SERVER['PHP_SELF']); // ambil nama file sekarang

// Kalau bukan login/logout & belum ada allow_after_hours & jam di luar range → redirect
if (
    !in_array($current_file, ['login.php', 'logout.php'])
    && !isset($_SESSION['allow_after_hours'])
    && ($hour >= 22 || $hour < 7)
) {
    header("Location: maintenance.html");
    exit();
}

// Login check untuk halaman yang butuh login
if (!in_array($current_file, ['login.php', 'maintenance.html']) && !isset($_SESSION['email'])) {
    header('location: login.html');
    exit();
}

require 'koneksi.php';

// Ambil data user
$email = $_SESSION['email'];
$stmt = $koneksi->prepare("SELECT nisn, nama, email FROM regiss WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Simpan waktu login
if (!isset($_SESSION['login_time'])) {
    date_default_timezone_set("Asia/Jakarta");
    $_SESSION['login_time'] = date('d F Y H:i');
}

// Kirim rating
if (isset($_POST['send_rating'])) {
    $bookTitle = $_POST['book_title'];
    $rating = $_POST['rating'];
    $to = "dhikaccp@gmail.com";
    $subject = "📚 Rating Buku - Perpustakaan Digital";
    $message = "Buku: $bookTitle\nRating: $rating bintang";
    $headers = "From: noreply@perpustakaan.com";
    mail($to, $subject, $message, $headers);
    echo "<script>alert('Terima kasih! Rating kamu sudah terkirim.');</script>";
}

// Kirim masukan & saran (hanya teks)
if (isset($_POST['send_feedback'])) {
    $sarann = mysqli_real_escape_string($koneksi, $_POST['feedback']);

    if (!empty($sarann)) {
        $insert = $koneksi->prepare("INSERT INTO saran (sarann) VALUES (?)");
        $insert->bind_param("s", $sarann);
        $insert->execute();

        echo "<script>alert('Terima kasih! Saran kamu sudah disimpan.');</script>";
    } else {
        echo "<script>alert('Saran tidak boleh kosong!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📚 Perpustakaan Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Intro.js CSS -->
    <link rel="stylesheet" href="https://unpkg.com/intro.js/minified/introjs.min.css">
    <!-- Intro.js JS -->
    <script src="https://unpkg.com/intro.js/minified/intro.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script>
        // Popup peringatan kalau buka jam terlarang
        document.addEventListener('DOMContentLoaded', () => {
            const now = new Date();
            const hour = now.getHours();
            if (hour >= 21 || hour < 7) {
                alert("⚠️ Waktu membaca buku digital sekarang tidak dianjurkan (21:00 - 07:00). Anda akan diarahkan ke halaman peringatan.");
                window.location.href = "maintenance.html";
            }
        });
    </script>
    <style>
        /* Style sama seperti yang kamu kirim sebelumnya */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to bottom right, #f9f9f9, #eceef6);
            transition: background 0.5s, color 0.5s;
        }

        .navbar {
            background: linear-gradient(90deg, #2a2d64, #6a1b9a);
        }

        .navbar-brand,
        .nav-link,
        #clock {
            color: #fff !important;
            font-weight: 500;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            cursor: pointer;
        }

        .card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
        }

        .bookmark-btn.active i {
            color: gold;
        }

        .rating-stars i {
            font-size: 1.5rem;
            color: gray;
            cursor: pointer;
        }

        .rating-stars i.active {
            color: gold;
        }

        footer {
            background: linear-gradient(90deg, #2575fc, #6a11cb);
            color: white;
            padding: 15px 0;
        }

        /* DARK MODE */
        body.dark-mode {
            background: linear-gradient(to bottom right, #121212, #1e1e2e);
            color: #f5f5f5;
        }

        .dark-mode .card {
            background: rgba(30, 30, 46, 0.95);
            color: #f5f5f5;
        }

        .dark-mode .navbar {
            background: linear-gradient(90deg, #111, #333);
        }

        .dark-mode .navbar-brand,
        .dark-mode .nav-link,
        .dark-mode #clock {
            color: #f5f5f5 !important;
        }

        .dark-mode .form-control,
        .dark-mode .form-select,
        .dark-mode textarea {
            background-color: #1f1f2e;
            color: #f5f5f5;
            border: 1px solid #444;
        }

        .dark-mode .form-control::placeholder,
        .dark-mode textarea::placeholder {
            color: #aaa;
        }

        .dark-mode .modal-content {
            background-color: #1e1e2e;
            color: #f5f5f5;
        }

        .dark-mode #readCount {
            color: gold !important;
        }

        .dark-mode .modal-header,
        .dark-mode .modal-footer {
            border-color: #444;
        }

        .dark-mode .btn-outline-secondary {
            color: #f5f5f5;
            border-color: #ccc;
        }

        .dark-mode .btn-outline-secondary:hover {
            background: #333;
        }

        .dark-mode .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        /* Custom Intro.js biar nyatu sama tema */
        .introjs-tooltip {
            background: linear-gradient(135deg, #6a1b9a, #2a2d64);
            color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .introjs-tooltiptext {
            font-size: 1rem;
            line-height: 1.4;
        }

        .introjs-progress {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .introjs-progressbar {
            background-color: gold;
        }

        .introjs-button {
            border-radius: 10px;
            font-weight: bold;
        }

        .introjs-nextbutton {
            background-color: gold;
            color: black;
        }

        .introjs-prevbutton {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .introjs-skipbutton {
            background: linear-gradient(135deg, #6a1b9a, #2a2d64);
            color: white !important;
            border: none;
            padding: 8px 16px;
            font-weight: bold;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        .introjs-skipbutton:hover {
            background: linear-gradient(135deg, #2a2d64, #6a1b9a);
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg" data-intro="Ini adalah menu navigasi untuk mengakses fitur-fitur utama." data-step="1">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#" data-intro="Klik ini untuk kembali ke halaman utama." data-step="2">
                <i class="fas fa-book-open"></i> Perpustakaan
            </a>
            <div class="d-flex align-items-center">
                <span id="clock" class="me-3"></span>
                <a href="profil.php" class="btn btn-outline-light btn-sm me-2" data-intro="Klik di sini untuk melihat profil kamu." data-step="3">
                    <i class="fas fa-user"></i> Profil
                </a>
                <button id="darkModeToggle" class="btn btn-outline-light btn-sm me-2" data-intro="Tombol ini untuk mengubah mode gelap atau terang." data-step="4">
                    <i class="fas fa-moon"></i>
                </button>
                <button id="showBookmarks" class="btn btn-outline-warning btn-sm me-2" data-intro="Klik di sini untuk melihat buku yang kamu simpan (Bookmark)." data-step="5">
                    <i class="fas fa-bookmark"></i>
                </button>
                <a href="logout.php" class="btn btn-light btn-sm" data-intro="Klik di sini untuk keluar dari akun." data-step="6">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Modal Welcome & Tutorial -->
    <div class="modal fade" id="welcomeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 text-white" style="background: linear-gradient(135deg, #6a1b9a, #2a2d64);">
                    <h5 class="modal-title fw-bold"><i class="fas fa-book-open me-2"></i>Selamat Datang, <?= $user['nama']; ?>!</h5>
                    <!--button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button-->
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-graduation-cap fa-3x text-warning mb-3"></i>
                        <h6 class="fw-bold">📖 Panduan Singkat</h6>
                        <p class="text-muted small">Ikuti tur singkat ini untuk mempelajari fitur-fitur di Perpustakaan Digital.</p>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><i class="fas fa-search text-primary me-2"></i> Cari buku favorit kamu</li>
                        <li class="list-group-item"><i class="fas fa-bookmark text-warning me-2"></i> Simpan buku ke Bookmark</li>
                        <li class="list-group-item"><i class="fas fa-moon text-secondary me-2"></i> Aktifkan Mode Gelap</li>
                        <li class="list-group-item"><i class="fas fa-star text-warning me-2"></i> Beri rating buku</li>
                    </ul>
                </div>
                <div class="modal-footer border-0 justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Nanti Saja</button>
                    <button type="button" class="btn btn-primary btn-sm px-4" id="startTutorialBtn" data-bs-dismiss="modal">Mulai 🚀</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Hero -->
    <div class="text-center text-white p-5" style="background: linear-gradient(120deg, #6a1b9a, #2a2d64);">
        <h1>Selamat Datang, <?= $user['nama']; ?> ✨</h1>
        <p>NISN: <?= $user['nisn']; ?> | Email: <?= $user['email']; ?> | Login: <?= $_SESSION['login_time']; ?></p>
    </div>

    <!-- Search & Filter -->
    <div class="container text-center my-4" data-intro="Gunakan kolom pencarian ini untuk mencari buku." data-step="7">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <input type="text" id="searchInput" class="form-control" placeholder="Cari buku...">
            </div>
            <div class="col-md-3" data-intro="Gunakan filter ini untuk memilih kategori buku." data-step="8">
                <select id="categoryFilter" class="form-select">
                    <option value="">Semua Kategori</option>
                    <option value="Fiksi">Fiksi</option>
                    <option value="Non-Fiksi">Non-Fiksi</option>
                    <option value="Majalah">Majalah</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Daftar Buku -->
    <div class="container py-4" data-intro="Klik pada kartu buku untuk membaca detailnya." data-step="9">

        <!-- Buku -->
        <div class="container py-4">
            <div class="row g-4" id="bookList">
                <?php
                $books = [
                    ["Fiksi", "Laut Bercerita Remake", "Seorang pelaut menemukan pulau yang tak ada di peta.", "https://drive.google.com/file/d/13n6eaRpVb0zi8XR-fvq-YAGMi-uJNzWp/view", "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQrrcovbucy_50MJjzszuh0u24F_Er7j2JLLA&s"],
                    ["Fiksi", "Bulan di Atas Lowina", "Malam itu, bulan seperti mengikuti perahu kecil.", "https://drive.google.com/file/d/FILE_ID_2/view", "https://mediahimapbsiuny.wordpress.com/wp-content/uploads/2020/10/content.jpg"],
                    ["Non-Fiksi", "Jejak-Jejak Peradaban Majapahit", "Menelusuri kejayaan kerajaan besar Nusantara.", "https://drive.google.com/file/d/FILE_ID_3/view", "https://cdn.gramedia.com/uploads/items/Jejak_jejak_Peradaban__Majapahit.jpg"],
                    ["Non-Fiksi", "Seporsi Mie Ayam Sebelum Mati", "Sebuah perjalanan rasa dan nostalgia di balik semangkuk mie ayam sederhana yang mengubah hidup.", "https://drive.google.com/file/d/FILE_ID_6/view", "https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1736474633i/223441713.jpg"],
                    ["Non-Fiksi", "Kisah, Perjuangan, & Inspirasi B.J. Habibie", "Kisah inspiratif presiden ketiga Indonesia.", "https://drive.google.com/file/d/FILE_ID_4/view", "https://cdn.gramedia.com/uploads/items/B.J._HABIBIE_KISAH_PERJUANGAN__INSPIRASI.jpg"],
                    ["Majalah", "Petualangan Kopi Sempurna [Edisi: 1]", "Cara menyeduh kopi yang nikmat di rumah.", "https://drive.google.com/file/d/FILE_ID_5/view", "https://imgv2-2-f.scribdassets.com/img/document/343571419/original/400a359416/1?v=1"],
                ];

                foreach ($books as $i => $b) {
                    echo "
            <div class='col-md-4 book-card' data-aos='zoom-in' data-index='{$i}' data-category='{$b[0]}' data-title='{$b[1]}' data-desc='{$b[2]}' data-pdf='{$b[3]}' data-img='{$b[4]}'>
                <div class='card h-100 shadow-lg'>
                    <img src='{$b[4]}' class='card-img-top rounded-top'>
                    <div class='card-body'>
                        <h5 class='card-title fw-bold'>{$b[1]}</h5>
                        <p class='card-text'>{$b[2]}</p>
                    </div>
                </div>
            </div>
            ";
                }
                ?>
            </div>
        </div>

        <!-- Suara Pembuka -->
        <audio id="startPokemonSound" src="https://www.myinstants.com/media/sounds/whos-that-pokemon_.mp3" preload="auto"></audio>

        <!-- Modal Game Pokemon -->
        <div class="modal fade" id="pokemonGameModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content text-center p-3">
                    <h3>🎮 Tebak Pokémon</h3>
                    <img id="pokemonImage" src="" alt="Pokémon" class="img-fluid my-3" style="max-height: 200px;">
                    <input type="text" id="pokemonGuess" class="form-control mb-3" placeholder="Masukkan nama Pokémon">
                    <button id="guessBtn" class="btn btn-success">Tebak!</button>
                    <p id="gameMessage" class="mt-3 fw-bold"></p>
                    <audio id="victorySound" src="https://www.myinstants.com/media/sounds/pokemon-tcg-pocket-you-have-a-booster.mp3" preload="auto"></audio>
                    <div id="gameEnd" class="mt-3"></div>
                </div>
            </div>
        </div>

        <!-- Suara Hore-->
        <audio id="victorySound" src="https://www.myinstants.com/media/sounds/pokemon-tcg-pocket-you-have-a-booster.mp3" preload="auto"></audio>
        <audio id="wrongSound" src="https://www.myinstants.com/media/sounds/wrong_JbK803k.mp3"></audio>
        <!-- Modal Buku -->
        <!-- (Modal, Form Saran, Footer, Script JS tetap sama persis kayak punya kamu sebelumnya) -->
        <?php
        // Potongan script modal dan JS tetap sama
        ?>
</body>

</html>

<!-- Modal Buku -->
<div class="modal fade" id="bookModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="bookTitle"></h5>
                <button class="btn btn-warning bookmark-btn me-2">
                    <i class="far fa-bookmark"></i> <span id="bookmarkText">Simpan ke Bookmark</span>
                </button>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <img id="bookImage" src="" class="img-fluid mb-3">
                <p id="bookDescription"></p>
                <small id="readCount" class="text-muted"></small>

                <!-- Rating -->
                <form method="POST" class="mt-3">
                    <input type="hidden" name="book_title" id="ratingBookTitle">
                    <div class="rating-stars mb-2">
                        <i class="fas fa-star" data-value="1"></i>
                        <i class="fas fa-star" data-value="2"></i>
                        <i class="fas fa-star" data-value="3"></i>
                        <i class="fas fa-star" data-value="4"></i>
                        <i class="fas fa-star" data-value="5"></i>
                    </div>
                    <input type="hidden" name="rating" id="ratingValue">
                    <button type="submit" name="send_rating" class="btn btn-warning btn-sm">Kirim Rating</button>
                </form>
            </div>
            <div class="modal-footer">
                <a id="shareWhatsApp" class="btn btn-success" target="_blank"><i class="fab fa-whatsapp"></i></a>
                <a id="shareTelegram" class="btn btn-info" target="_blank"><i class="fab fa-telegram"></i></a>
                <button id="prevBtn" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i></button>
                <button id="nextBtn" class="btn btn-outline-primary"><i class="fas fa-arrow-right"></i></button>
                <button id="readPdfBtn" class="btn btn-secondary">Lanjut Baca <i class="fas fa-arrow-right"></i></button>
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Form Masukan -->
<div class="container my-4">
    <h4>Kirim Masukan & Saran</h4>
    <form method="POST">
        <textarea class="form-control mb-2" name="feedback" rows="3" placeholder="Tulis saran kamu..."></textarea>
        <button type="submit" name="send_feedback" class="btn btn-primary">Kirim</button>
    </form>
</div>

<!-- Footer -->
<footer class="text-center mt-4">
    <p class="mb-0">© 2025 Perpustakaan Digital | Dibuat dengan ❤️</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init();

    // Jam realtime
    function updateClock() {
        const now = new Date();
        const options = {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        document.getElementById('clock').textContent = now.toLocaleTimeString('id-ID', options);
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Dark Mode
    const darkModeToggle = document.getElementById('darkModeToggle');
    darkModeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
    });
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }

    // Variabel global
    let bookmarks = JSON.parse(localStorage.getItem('bookmarks') || '[]');
    let readCounts = JSON.parse(localStorage.getItem('readCounts') || '{}');
    let books = document.querySelectorAll('.book-card');
    let bookModal = new bootstrap.Modal(document.getElementById('bookModal'));
    let currentIndex = 0;
    let showingBookmarks = false;

    // Fungsi buka buku
    function openBook(index) {
        const book = books[index];
        currentIndex = index;
        const title = book.getAttribute('data-title');
        const img = book.getAttribute('data-img');
        const desc = book.getAttribute('data-desc');
        const pdf = book.getAttribute('data-pdf');

        document.getElementById('bookTitle').textContent = title;
        document.getElementById('bookImage').src = img;
        document.getElementById('bookDescription').textContent = desc;

        // Counter baca
        readCounts[index] = (readCounts[index] || 0) + 1;
        localStorage.setItem('readCounts', JSON.stringify(readCounts));
        document.getElementById('readCount').textContent = `Dibaca ${readCounts[index]} kali`;

        // Simpan buku terakhir
        localStorage.setItem('lastRead', index);

        // Bookmark status
        const bookmarkIcon = document.querySelector('.bookmark-btn i');
        const bookmarkText = document.getElementById('bookmarkText');
        if (bookmarks.includes(index)) {
            bookmarkIcon.className = 'fas fa-bookmark';
            bookmarkText.textContent = 'Hapus dari Bookmark';
        } else {
            bookmarkIcon.className = 'far fa-bookmark';
            bookmarkText.textContent = 'Simpan ke Bookmark';
        }

        // Rating
        document.getElementById('ratingBookTitle').value = title;
        document.querySelectorAll('.rating-stars i').forEach(star => {
            star.classList.remove('active');
        });

        // Tombol baca
        document.getElementById('readPdfBtn').onclick = () => {
            window.open(pdf, '_blank');
        };

        // Share
        document.getElementById('shareWhatsApp').href = `https://wa.me/?text=Lihat buku ${encodeURIComponent(title)}: ${encodeURIComponent(pdf)}`;
        document.getElementById('shareTelegram').href = `https://t.me/share/url?url=${encodeURIComponent(pdf)}&text=${encodeURIComponent(title)}`;

        bookModal.show();
    }

    // Klik buku
    books.forEach((book, i) => {
        book.addEventListener('click', () => openBook(i));
        book.style.cursor = "pointer";
    });

    // Next & Prev
    document.getElementById('nextBtn').addEventListener('click', () => {
        openBook((currentIndex + 1) % books.length);
    });
    document.getElementById('prevBtn').addEventListener('click', () => {
        openBook((currentIndex - 1 + books.length) % books.length);
    });

    // Bookmark toggle di modal
    document.addEventListener('click', (e) => {
        if (e.target.closest('.bookmark-btn')) {
            const bookmarkIcon = document.querySelector('.bookmark-btn i');
            const bookmarkText = document.getElementById('bookmarkText');

            // Efek animasi
            bookmarkIcon.classList.add('bounce');
            setTimeout(() => bookmarkIcon.classList.remove('bounce'), 400);

            // Update array bookmarks
            if (!bookmarks.includes(currentIndex)) {
                bookmarks.push(currentIndex);
                bookmarkIcon.className = 'fas fa-bookmark';
                bookmarkText.textContent = 'Hapus dari Bookmark';
            } else {
                bookmarks = bookmarks.filter(i => i !== currentIndex);
                bookmarkIcon.className = 'far fa-bookmark';
                bookmarkText.textContent = 'Simpan ke Bookmark';
            }

            // Simpan di localStorage
            localStorage.setItem('bookmarks', JSON.stringify(bookmarks));
        }
    });

    // Lihat bookmark
    document.getElementById('showBookmarks').addEventListener('click', () => {
        showingBookmarks = !showingBookmarks;
        if (showingBookmarks) {
            books.forEach((book, i) => {
                book.style.display = bookmarks.includes(i) ? 'block' : 'none';
            });
            document.getElementById('showBookmarks').innerHTML = '<i class="fas fa-bookmark"></i> Semua Buku';
        } else {
            books.forEach(book => book.style.display = 'block');
            document.getElementById('showBookmarks').innerHTML = '<i class="fas fa-bookmark"></i> Bookmark';
        }
    });

    // Search & Filter
    function filterBooks() {
        const searchValue = document.getElementById('searchInput').value.toLowerCase();
        const categoryValue = document.getElementById('categoryFilter').value;
        books.forEach(book => {
            const title = book.getAttribute('data-title').toLowerCase();
            const category = book.getAttribute('data-category');
            if ((title.includes(searchValue) || searchValue === "") &&
                (category === categoryValue || categoryValue === "")) {
                book.style.display = 'block';
            } else {
                book.style.display = 'none';
            }
        });
    }
    document.getElementById('searchInput').addEventListener('input', filterBooks);
    document.getElementById('categoryFilter').addEventListener('change', filterBooks);

    // Rating bintang interaktif
    document.querySelectorAll('.rating-stars i').forEach(star => {
        star.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            document.getElementById('ratingValue').value = value;
            document.querySelectorAll('.rating-stars i').forEach(s => s.classList.remove('active'));
            for (let i = 0; i < value; i++) {
                document.querySelectorAll('.rating-stars i')[i].classList.add('active');
            }
        });
    });

    // Saat buka buku
    function openBook(index) {
        const book = books[index];
        currentIndex = index;
        const title = book.getAttribute('data-title');
        const img = book.getAttribute('data-img');
        const desc = book.getAttribute('data-desc');
        const pdf = book.getAttribute('data-pdf');

        document.getElementById('bookTitle').textContent = title;
        document.getElementById('bookImage').src = img;
        document.getElementById('bookDescription').textContent = desc;

        // Counter baca
        readCounts[index] = (readCounts[index] || 0) + 1;
        localStorage.setItem('readCounts', JSON.stringify(readCounts));
        document.getElementById('readCount').textContent = `Dibaca ${readCounts[index]} kali`;

        // Simpan sementara untuk lastRead (baru final kalau kirim rating)
        localStorage.setItem('tempLastRead', index);

        // Rating
        document.getElementById('ratingBookTitle').value = title;
        document.querySelectorAll('.rating-stars i').forEach(star => {
            star.classList.remove('active');
        });

        // Tombol baca
        document.getElementById('readPdfBtn').onclick = () => {
            window.open(pdf, '_blank');
        };

        // Share
        document.getElementById('shareWhatsApp').href = `https://wa.me/?text=Lihat buku ${encodeURIComponent(title)}: ${encodeURIComponent(pdf)}`;
        document.getElementById('shareTelegram').href = `https://t.me/share/url?url=${encodeURIComponent(pdf)}&text=${encodeURIComponent(title)}`;

        bookModal.show();
    }

    // Saat form rating dikirim, simpan tempLastRead jadi lastRead
    document.querySelector('form[method="POST"]').addEventListener('submit', () => {
        const tempIndex = localStorage.getItem('tempLastRead');
        if (tempIndex !== null) {
            localStorage.setItem('lastRead', tempIndex);
        }
    });

    /// Tutorial hanya muncul pertama kali
    if (!localStorage.getItem('welcomeShown')) {
        let welcomeModal = new bootstrap.Modal(document.getElementById('welcomeModal'));
        welcomeModal.show();

        document.getElementById('startTutorialBtn').addEventListener('click', () => {
            localStorage.setItem('welcomeShown', 'true');
            setTimeout(() => {
                introJs().setOptions({
                    showProgress: true,
                    nextLabel: 'Lanjut →',
                    prevLabel: '← Kembali',
                    doneLabel: 'Selesai',
                    skipLabel: '❌',
                    tooltipClass: 'customTooltip',
                    highlightClass: 'customHighlight',
                    exitOnOverlayClick: false,
                    disableInteraction: true
                }).start();
            }, 400);
        });
    }

    // Play "Who's That Pokémon" saat modal dibuka
    document.getElementById('pokemonGameModal').addEventListener('show.bs.modal', function() {
        const startSound = document.getElementById('startPokemonSound');
        startSound.currentTime = 0; // mulai dari awal
        startSound.play();
    });

    // Data Pokémon diperbanyak
    let allPokemon = [{
            name: "bulbasaur",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/1.png"
        },
        {
            name: "ivysaur",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/2.png"
        },
        {
            name: "venusaur",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/3.png"
        },
        {
            name: "charmander",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/4.png"
        },
        {
            name: "charmeleon",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/5.png"
        },
        {
            name: "charizard",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/6.png"
        },
        {
            name: "squirtle",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/7.png"
        },
        {
            name: "wartortle",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/8.png"
        },
        {
            name: "blastoise",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/9.png"
        },
        {
            name: "pikachu",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/25.png"
        },
        {
            name: "raichu",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/26.png"
        },
        {
            name: "jigglypuff",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/39.png"
        },
        {
            name: "wigglytuff",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/40.png"
        },
        {
            name: "meowth",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/52.png"
        },
        {
            name: "psyduck",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/54.png"
        },
        {
            name: "machop",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/66.png"
        },
        {
            name: "machoke",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/67.png"
        },
        {
            name: "machamp",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/68.png"
        },
        {
            name: "gastly",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/92.png"
        },
        {
            name: "haunter",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/93.png"
        },
        {
            name: "gengar",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/94.png"
        },
        {
            name: "magikarp",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/129.png"
        },
        {
            name: "gyarados",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/130.png"
        },
        {
            name: "snorlax",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/143.png"
        },
        {
            name: "mewtwo",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/150.png"
        },
        {
            name: "mew",
            img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/151.png"
        },
        // ===== GEN 2 =====
    { name: "chikorita", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/152.png" },
    { name: "bayleef", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/153.png" },
    { name: "meganium", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/154.png" },
    { name: "cyndaquil", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/155.png" },
    { name: "quilava", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/156.png" },
    { name: "typhlosion", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/157.png" },
    { name: "totodile", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/158.png" },
    { name: "croconaw", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/159.png" },
    { name: "feraligatr", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/160.png" },
     { name: "sentret", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/161.png" },
    // ... (lanjut sampai #251)

    // ===== GEN 3 =====
    { name: "treecko", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/252.png" },
    { name: "grovyle", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/253.png" },
    { name: "sceptile", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/254.png" },
    { name: "torchic", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/255.png" },
    { name: "combusken", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/256.png" },
    { name: "blaziken", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/257.png" },
    { name: "mudkip", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/258.png" },
    { name: "marshtomp", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/259.png" },
    { name: "swampert", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/260.png" },
    // ... (lanjut sampai #386)

    // ===== GEN 4 =====
    { name: "turtwig", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/387.png" },
    { name: "grotle", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/388.png" },
    { name: "torterra", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/389.png" },
    { name: "chimchar", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/390.png" },
    { name: "monferno", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/391.png" },
    { name: "infernape", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/392.png" },
    { name: "piplup", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/393.png" },
    { name: "prinplup", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/394.png" },
    { name: "empoleon", img: "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/395.png" },
    // ... (lanjut sampai #493)
];

    let currentPokemon = null;

    // Klik logo 3x → buka game
    let clickCount = 0;
    let clickTimer;
    document.querySelector(".navbar-brand").addEventListener("click", () => {
        clickCount++;
        if (clickCount === 3) {
            clickCount = 0;
            startPokemonGame();
        }
        clearTimeout(clickTimer);
        clickTimer = setTimeout(() => clickCount = 0, 800);
    });

    function startPokemonGame() {
        const randomPokemon = allPokemon[Math.floor(Math.random() * allPokemon.length)];
        currentPokemon = randomPokemon;
        document.getElementById("pokemonImage").src = randomPokemon.img;
        document.getElementById("pokemonGuess").value = "";
        document.getElementById("gameMessage").textContent = "";
        new bootstrap.Modal(document.getElementById("pokemonGameModal")).show();
    }

    document.getElementById("guessBtn").addEventListener("click", () => {
        const guess = document.getElementById("pokemonGuess").value.trim().toLowerCase();
        const correct = currentPokemon.name.toLowerCase();

        if (guess === correct) {
            document.getElementById("victorySound").play();
            document.getElementById("gameMessage").innerHTML = `🎉 Benar! Itu <b>${correct.toUpperCase()}</b>`;
            setTimeout(startPokemonGame, 1500);
        } else {
            document.getElementById("wrongSound").play();
            let clue = `❌ Salah! Clue: Huruf pertama <b>${correct.charAt(0).toUpperCase()}</b>, huruf terakhir <b>${correct.slice(-1).toUpperCase()}</b>, jumlah huruf <b>${correct.length}</b>`;
            document.getElementById("gameMessage").innerHTML = clue;
        }
    });

    function victoryEffect() {
        const victorySound = document.getElementById("victorySound");
        victorySound.currentTime = 0;
        victorySound.play();

        const duration = 2000;
        const animationEnd = Date.now() + duration;
        const defaults = {
            startVelocity: 30,
            spread: 360,
            ticks: 60,
            zIndex: 9999
        };

        function randomInRange(min, max) {
            return Math.random() * (max - min) + min;
        }

        const interval = setInterval(function() {
            const timeLeft = animationEnd - Date.now();
            if (timeLeft <= 0) {
                return clearInterval(interval);
            }
            const particleCount = 50 * (timeLeft / duration);
            confetti(Object.assign({}, defaults, {
                particleCount,
                origin: {
                    x: randomInRange(0, 1),
                    y: Math.random() - 0.2
                }
            }));
        }, 250);
    }

    function endPokemonGame() {
        localStorage.setItem("pokemonGameFinished", "true");
        document.getElementById("pokemonImage").style.display = "none";
        document.getElementById("pokemonGuess").style.display = "none";
        document.getElementById("guessBtn").style.display = "none";
        document.getElementById("gameMessage").textContent = "";
        document.getElementById("gameEnd").innerHTML = `
        <h4>🎯 Semua Pokémon sudah kamu tebak!</h4>
        <button onclick="window.location.href='index.php'" class="btn btn-primary mt-3">🏠 Balik ke Home</button>
    `;
    }
</script>
</body>

</html>