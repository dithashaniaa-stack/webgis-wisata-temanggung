<?php
// =========================================================
// LOGIKA PEMBEDA HALAMAN
// Jika URL diakses dengan ?halaman=peta -> tampilkan halaman peta
// Jika tidak ada parameter / selain itu -> tampilkan halaman beranda
// =========================================================
$halaman = isset($_GET['halaman']) ? $_GET['halaman'] : 'beranda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Temangscape | WebGIS Destinasi Temanggung</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

<?php if ($halaman === 'peta'): ?>
<!-- Library Leaflet, hanya dimuat di halaman peta -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html {
        scroll-behavior: smooth;
    }

    body {
        font-family: 'Inter', sans-serif;
    }

    /* ================================
       PALET WARNA & TEMA
       Terinspirasi dari kebun teh & kabut
       pegunungan Sindoro-Sumbing, dengan
       aksen emas fajar ala sunrise Posong
    ================================= */
    body.theme-dark {
        --bg-page: #16281F;
        --bg-alt: #10201A;
        --text-color: #E7ECE6;
        --text-muted: #B7C7BC;
        --nav-bg: rgba(16, 26, 20, 0.55);
        --overlay-from: rgba(10, 18, 13, 0.75);
        --overlay-to: rgba(10, 18, 13, 0.25);
        --toggle-bg: rgba(231, 236, 230, 0.12);
        --card-bg: rgba(231, 236, 230, 0.06);
        --card-border: rgba(231, 236, 230, 0.14);
        --chip-border: rgba(231, 236, 230, 0.3);
        --contour-a: rgba(231, 236, 230, 0.35);
        --contour-b: rgba(127, 169, 127, 0.5);
        --contour-c: rgba(232, 163, 61, 0.45);
    }

    body.theme-light {
        --bg-page: #EEF2EA;
        --bg-alt: #E1E8DD;
        --text-color: #10201A;
        --text-muted: #43524A;
        --nav-bg: rgba(238, 242, 234, 0.7);
        --overlay-from: rgba(16, 32, 26, 0.55);
        --overlay-to: rgba(16, 32, 26, 0.12);
        --toggle-bg: rgba(16, 32, 26, 0.08);
        --card-bg: rgba(16, 32, 26, 0.04);
        --card-border: rgba(16, 32, 26, 0.12);
        --chip-border: rgba(16, 32, 26, 0.22);
        --contour-a: rgba(16, 32, 26, 0.28);
        --contour-b: rgba(60, 100, 70, 0.4);
        --contour-c: rgba(200, 130, 30, 0.4);
    }

    body {
        background: var(--bg-page);
        color: var(--text-color);
        transition: background 0.4s ease, color 0.4s ease;
    }

    /* ================================
       NAVIGASI
    ================================= */
    .navbar {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 32px;
        z-index: 50;
        background: var(--nav-bg);
        backdrop-filter: blur(8px);
        transition: background 0.4s ease;
    }

    .navbar .nav-brand {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.15rem;
        font-weight: 700;
        letter-spacing: 1px;
        color: var(--text-color);
    }

    .theme-toggle {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: var(--toggle-bg);
        border: 1px solid var(--chip-border);
        border-radius: 50px;
        color: var(--text-color);
        cursor: pointer;
        font-family: 'Inter', sans-serif;
        font-size: 0.9rem;
        transition: transform 0.2s ease;
    }

    .theme-toggle:hover {
        transform: translateY(-2px);
    }

    /* ================================
       HERO
    ================================= */
    .hero {
        position: relative;
        width: 100%;
        height: 100vh;
        min-height: 620px;
        overflow: hidden;
    }

    .bg-slide {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: center;
        opacity: 0;
        transform: scale(1);
        transition: opacity 2s ease-in-out, transform 7s ease-in-out;
    }

    .bg-slide.active {
        opacity: 1;
        transform: scale(1.08);
        z-index: 1;
    }

    .hero-overlay {
        position: absolute;
        inset: 0;
        z-index: 2;
        background: linear-gradient(100deg, var(--overlay-from) 35%, var(--overlay-to) 100%);
        transition: background 0.4s ease;
    }

    /* Detail koordinat asli lokasi -- bukan label hiasan */
    .geo-tag {
        position: absolute;
        top: 96px;
        left: 32px;
        z-index: 5;
        font-family: 'Space Grotesk', monospace;
        font-size: 0.78rem;
        letter-spacing: 0.5px;
        color: #E7ECE6;
        opacity: 0.85;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .geo-tag .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #E8A33D;
        flex-shrink: 0;
    }

    .hero-content {
        position: relative;
        z-index: 4;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 0 8vw;
        max-width: 760px;
        animation: fadeUp 1.2s ease-in-out;
    }

    .hero-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: clamp(2.6rem, 6vw, 4.4rem);
        font-weight: 700;
        letter-spacing: 1px;
        line-height: 1.02;
        color: #ffffff;
        text-shadow: 0 4px 24px rgba(0,0,0,0.4);
        margin-bottom: 18px;
    }

    .hero-subtitle {
        font-size: 1.08rem;
        font-weight: 400;
        line-height: 1.6;
        max-width: 46ch;
        color: #EDF1EA;
        text-shadow: 0 2px 12px rgba(0,0,0,0.35);
        margin-bottom: 28px;
    }

    .kategori-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 34px;
    }

    .kategori-chips span {
        font-size: 0.82rem;
        font-weight: 500;
        padding: 7px 16px;
        border-radius: 50px;
        border: 1px solid rgba(255,255,255,0.4);
        color: #ffffff;
        background: rgba(255,255,255,0.08);
        backdrop-filter: blur(4px);
    }

    .btn-masuk {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        width: fit-content;
        padding: 15px 34px;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1rem;
        font-weight: 600;
        color: #16281F;
        background: #E8A33D;
        border: none;
        border-radius: 50px;
        text-decoration: none;
        letter-spacing: 0.3px;
        box-shadow: 0 10px 28px rgba(232, 163, 61, 0.35);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .btn-masuk:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 32px rgba(232, 163, 61, 0.45);
    }

    .scroll-cue {
        position: absolute;
        bottom: 86px;
        left: 8vw;
        z-index: 4;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8rem;
        color: #EDF1EA;
        text-decoration: none;
        opacity: 0.85;
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(24px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Garis kontur topografi -- pemisah antar-section,
       motif diambil dari garis elevasi peta kontur */
    .contour-divider {
        position: relative;
        z-index: 5;
        display: block;
        width: 100%;
        line-height: 0;
        margin-top: -6px;
    }

    .contour-divider svg {
        width: 100%;
        height: 90px;
        display: block;
    }

    /* ================================
       SECTION KATEGORI
    ================================= */
    .kategori-section {
        background: var(--bg-page);
        padding: 30px 8vw 80px;
    }

    .section-heading {
        font-family: 'Space Grotesk', sans-serif;
        font-size: clamp(1.6rem, 3vw, 2.1rem);
        font-weight: 600;
        max-width: 30ch;
        margin-bottom: 12px;
    }

    .section-desc {
        color: var(--text-muted);
        max-width: 56ch;
        line-height: 1.6;
        margin-bottom: 40px;
    }

    .kategori-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
    }

    .kategori-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 18px;
        padding: 26px 22px;
        transition: transform 0.25s ease, border-color 0.25s ease;
    }

    .kategori-card:hover {
        transform: translateY(-4px);
        border-color: var(--chip-border);
    }

    .kategori-card svg {
        width: 30px;
        height: 30px;
        color: #E8A33D;
        margin-bottom: 16px;
    }

    .kategori-card h3 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.05rem;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .kategori-card p {
        font-size: 0.9rem;
        color: var(--text-muted);
        line-height: 1.55;
    }

    /* ================================
       SECTION STATISTIK
    ================================= */
    .stats-section {
        background: var(--bg-alt);
        padding: 56px 8vw;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        text-align: left;
    }

    .stat-item .stat-number {
        font-family: 'Space Grotesk', sans-serif;
        font-size: clamp(2.2rem, 4vw, 3rem);
        font-weight: 700;
        color: #E8A33D;
        line-height: 1;
        margin-bottom: 8px;
    }

    .stat-item .stat-label {
        font-size: 0.92rem;
        color: var(--text-muted);
    }

    /* ================================
       FOOTER
    ================================= */
    .site-footer {
        background: var(--bg-page);
        padding: 26px 8vw;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        border-top: 1px solid var(--card-border);
    }

    .site-footer span {
        font-size: 0.82rem;
        color: var(--text-muted);
    }

    .site-footer .footer-brand {
        font-family: 'Space Grotesk', sans-serif;
        font-weight: 600;
        color: var(--text-color);
    }

    /* ================================
       HALAMAN PETA
    ================================= */
    #map {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        z-index: 1;
    }

    .peta-topbar {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        padding: 14px 24px;
        background: rgba(16, 26, 20, 0.85);
        color: #ffffff;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .peta-topbar .brand {
        font-family: 'Space Grotesk', sans-serif;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .peta-topbar a {
        color: #ffffff;
        text-decoration: none;
        padding: 6px 16px;
        border: 1px solid rgba(255,255,255,0.4);
        border-radius: 30px;
        font-size: 0.9rem;
    }

    .peta-topbar a:hover {
        background: rgba(255,255,255,0.15);
    }

    .leaflet-top.leaflet-right {
        top: 70px;
    }

    /* ================================
       RESPONSIVE
    ================================= */
    @media (max-width: 860px) {
        .kategori-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .stats-section {
            grid-template-columns: 1fr;
            gap: 28px;
        }
    }

    @media (max-width: 560px) {
        .navbar { padding: 16px 20px; }
        .geo-tag { left: 20px; top: 84px; font-size: 0.7rem; }
        .hero-content { padding: 0 24px; }
        .scroll-cue { left: 24px; bottom: 64px; }
        .kategori-grid { grid-template-columns: 1fr; }
        .kategori-section { padding: 26px 24px 60px; }
        .stats-section { padding: 44px 24px; }
        .site-footer { padding: 22px 24px; flex-direction: column; align-items: flex-start; }
    }

    @media (prefers-reduced-motion: reduce) {
        .bg-slide, .hero-content, .btn-masuk, .kategori-card {
            transition: none !important;
            animation: none !important;
        }
    }
</style>
</head>
<body class="theme-dark" id="mainBody">

<?php if ($halaman === 'peta'): ?>

    <!-- =========================================================
         HALAMAN PETA
    ========================================================== -->
    <div class="peta-topbar">
        <div class="brand">TEMANGSCAPE</div>
        <a href="index.php">&larr; Kembali ke Beranda</a>
    </div>

    <div id="map"></div>

    <script>
        // Titik tengah awal peta, kira-kira di sekitar Kabupaten Temanggung
        const map = L.map('map').setView([-7.3167, 110.1667], 11);

        // Basemap dari OpenStreetMap sebagai latar belakang
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Layer titik wisata dari GeoServer, dipanggil lewat proxy.php
        // supaya browser tidak langsung memanggil localhost:8080
        const layerTitikWisata = L.tileLayer.wms('proxy.php', {
            layers: 'webgis_tmg:pointtttt',
            format: 'image/png',
            transparent: true,
            version: '1.1.0'
        });

        // Layer garis batas administrasi dari GeoServer
        const layerBatasAdmin = L.tileLayer.wms('proxy.php', {
            layers: 'webgis_tmg:line brooo',
            format: 'image/png',
            transparent: true,
            version: '1.1.0'
        });

        // Tambahkan kedua layer ke peta secara default
        layerBatasAdmin.addTo(map);
        layerTitikWisata.addTo(map);

        // Kontrol layer supaya pengguna bisa nyala/matikan tiap layer
        const overlayLayers = {
            "Titik Wisata": layerTitikWisata,
            "Batas Administrasi": layerBatasAdmin
        };
        L.control.layers(null, overlayLayers, { collapsed: false }).addTo(map);
    </script>

<?php else: ?>

    <!-- =========================================================
         HALAMAN BERANDA
    ========================================================== -->

    <nav class="navbar">
        <div class="nav-brand">TEMANGSCAPE</div>
        <button class="theme-toggle" id="themeToggleBtn" onclick="gantiTema()">
            <span id="themeIcon">🌙</span>
            <span id="themeLabel">Dark</span>
        </button>
    </nav>

    <!-- ============ HERO ============ -->
    <section class="hero">

        <div class="bg-slide active" style="background-image: url('asset/temanggung1.jpg');"></div>
        <div class="bg-slide" style="background-image: url('asset/temanggung2.jpg');"></div>
        <div class="bg-slide" style="background-image: url('asset/temanggung3.jpg');"></div>

        <div class="hero-overlay"></div>

        <div class="geo-tag">
            <span class="dot"></span>
            7°19'00"S&nbsp; 110°10'00"E &nbsp;·&nbsp; Kabupaten Temanggung, Jawa Tengah
        </div>

        <div class="hero-content">
            <h1 class="hero-title">TEMANGSCAPE</h1>
            <p class="hero-subtitle">
                Satu peta untuk menjelajahi ragam destinasi Temanggung —
                dari kaki Gunung Sindoro-Sumbing, kuliner khas dataran tinggi,
                jejak budaya, sampai spot foto favorit.
            </p>
            <div class="kategori-chips">
                <span>Wisata Alam</span>
                <span>Kuliner</span>
                <span>Budaya &amp; Sejarah</span>
                <span>Spot Foto</span>
            </div>
            <a href="index.php?halaman=peta" class="btn-masuk">
                Masuk ke WebGIS
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        </div>

        <a href="#kategori" class="scroll-cue">
            Lihat kategori destinasi
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
        </a>

        <!-- Garis kontur topografi sebagai pemisah section, khas peta elevasi -->
        <div class="contour-divider">
            <svg viewBox="0 0 1440 90" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0,55 C180,20 340,80 520,50 C700,20 860,70 1040,45 C1220,20 1340,60 1440,40 L1440,90 L0,90 Z" fill="var(--bg-page)"/>
                <path d="M0,40 C160,65 320,15 500,42 C680,68 840,18 1020,44 C1200,68 1320,30 1440,50" fill="none" stroke="var(--contour-a)" stroke-width="1.2"/>
                <path d="M0,58 C170,30 350,72 540,48 C720,26 880,64 1060,42 C1240,22 1350,55 1440,36" fill="none" stroke="var(--contour-b)" stroke-width="1.2"/>
                <path d="M0,24 C150,48 330,10 510,34 C690,58 850,14 1030,38 C1210,60 1330,18 1440,30" fill="none" stroke="var(--contour-c)" stroke-width="1.2"/>
            </svg>
        </div>
    </section>

    <!-- ============ KATEGORI ============ -->
    <section class="kategori-section" id="kategori">
        <h2 class="section-heading">Empat kategori, satu peta</h2>
        <p class="section-desc">
        Empat kategori destinasi, satu peta interaktif untuk menjelajahi Temanggung.
        </p>

        <div class="kategori-grid">
            <div class="kategori-card">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 19l6-10 4 6 2-3 6 7H3z"/></svg>
                <h3>Wisata Alam</h3>
                <p>Gunung, embung, dan hamparan kebun teh yang bikin betah berlama-lama.</p>
            </div>
            <div class="kategori-card">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v7a2 2 0 002 2v9M6 3v9M4 3v9M18 3c-1.5 1.5-2 3-2 5s.5 4 2 7v4"/></svg>
                <h3>Kuliner</h3>
                <p>Kopi, tembakau, dan cita rasa khas dataran tinggi Temanggung.</p>
            </div>
            <div class="kategori-card">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V10M20 21V10M2 10l10-6 10 6M8 21v-6a4 4 0 018 0v6"/></svg>
                <h3>Budaya &amp; Sejarah</h3>
                <p>Candi, tradisi, dan jejak masa lalu yang masih terjaga.</p>
            </div>
            <div class="kategori-card">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7l1.5-3h5L16 7"/><circle cx="12" cy="13.5" r="3.2"/></svg>
                <h3>Spot Foto</h3>
                <p>Sudut-sudut favorit, dari sunrise Posong sampai reruntuhan bersejarah.</p>
            </div>
        </div>
    </section>

    <!-- ============ STATISTIK ============ -->
    <section class="stats-section">
        <!-- TODO: ganti tiga angka di bawah sesuai data asli kamu -->
        <div class="stat-item">
            <div class="stat-number">40+</div>
            <div class="stat-label">Titik lokasi telah dipetakan</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">20</div>
            <div class="stat-label">Kecamatan tercakup</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">4</div>
            <div class="stat-label">Kategori destinasi</div>
        </div>
    </section>

    <!-- ============ FOOTER ============ -->
    <footer class="site-footer">
        <span class="footer-brand">TEMANGSCAPE</span>
        <span>WebGIS Pemetaan Destinasi Kabupaten Temanggung &mdash; Kerja Praktik, Teknik Geodesi</span>
    </footer>

    <script>
        // Logika slideshow background dengan transisi fade + zoom
        const slides = document.querySelectorAll('.bg-slide');
        let currentSlide = 0;
        const intervalTime = 5000; // ganti gambar setiap 5 detik

        function gantiSlide() {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }

        setInterval(gantiSlide, intervalTime);

        // Logika toggle tema dark/light
        const mainBody = document.getElementById('mainBody');
        const themeIcon = document.getElementById('themeIcon');
        const themeLabel = document.getElementById('themeLabel');

        const temaTersimpan = localStorage.getItem('temangscape_theme');
        if (temaTersimpan === 'light') {
            mainBody.classList.remove('theme-dark');
            mainBody.classList.add('theme-light');
            themeIcon.textContent = '☀️';
            themeLabel.textContent = 'Light';
        }

        function gantiTema() {
            const isDark = mainBody.classList.contains('theme-dark');

            if (isDark) {
                mainBody.classList.remove('theme-dark');
                mainBody.classList.add('theme-light');
                themeIcon.textContent = '☀️';
                themeLabel.textContent = 'Light';
                localStorage.setItem('temangscape_theme', 'light');
            } else {
                mainBody.classList.remove('theme-light');
                mainBody.classList.add('theme-dark');
                themeIcon.textContent = '🌙';
                themeLabel.textContent = 'Dark';
                localStorage.setItem('temangscape_theme', 'dark');
            }
        }
    </script>

<?php endif; ?>

</body>
</html>
