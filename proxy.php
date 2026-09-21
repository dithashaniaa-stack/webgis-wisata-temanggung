<?php
// =========================================================
// PROXY.PHP
// Penghubung antara WebGIS (Leaflet) dan GeoServer lokal.
// Browser pengguna tidak memanggil localhost:8080 secara langsung,
// tapi lewat file ini, supaya WebGIS tetap bisa diakses dari
// komputer lain (misalnya lewat Cloudflared) tanpa masalah
// alamat "localhost" yang berbeda-beda tiap perangkat.
// =========================================================

// Alamat GeoServer yang berjalan di laptop/komputer pembuat
$geoserverUrl = "http://localhost:8080/geoserver/wms";

// =========================================================
// 1. Membatasi metode request yang boleh diteruskan ke GeoServer.
//    Hanya 4 jenis request WMS ini yang diizinkan lewat proxy.
// =========================================================
$allowedRequests = ['GetMap', 'GetCapabilities', 'GetLegendGraphic', 'GetFeatureInfo'];

// Ambil semua parameter GET yang dikirim dari Leaflet
$params = $_GET;

// =========================================================
// 4. Fallback: kalau proxy.php dibuka tanpa parameter sama sekali,
//    otomatis dianggap sebagai request GetCapabilities.
//    Ini memudahkan pengujian langsung lewat browser.
// =========================================================
if (empty($params)) {
    $params = [
        'service' => 'WMS',
        'version' => '1.1.0',
        'request' => 'GetCapabilities'
    ];
}

// Validasi nilai parameter "request", kalau ada tapi tidak termasuk
// yang diizinkan, proxy akan menolak dan berhenti di sini.
if (isset($params['request']) && !in_array($params['request'], $allowedRequests)) {
    http_response_code(400);
    echo "Request tidak diizinkan: " . htmlspecialchars($params['request']);
    exit;
}

// =========================================================
// 2. Menyusun URL tujuan ke GeoServer, membawa semua parameter
//    yang tadi diterima dari Leaflet.
// =========================================================
$targetUrl = $geoserverUrl . '?' . http_build_query($params);

// =========================================================
// 3. Mengambil respons dari GeoServer menggunakan cURL.
// =========================================================
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);

// Cek kalau cURL gagal konek ke GeoServer (misalnya GeoServer belum jalan)
if (curl_errno($ch)) {
    http_response_code(502);
    echo "Gagal menghubungi GeoServer: " . curl_error($ch);
    curl_close($ch);
    exit;
}

// Ambil Content-Type asli dari respons GeoServer
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

// GeoServer kadang mengirim tipe MIME khusus OGC seperti
// application/vnd.ogc.wms_xml yang tidak dikenali browser,
// sehingga browser malah menawarkan download daripada menampilkan.
// Di sini kita ganti jadi text/xml supaya tetap bisa dilihat langsung.
$tipeXmlOgc = ['application/vnd.ogc.wms_xml', 'application/vnd.ogc.se_xml'];
if ($contentType && in_array(strtolower(trim(explode(';', $contentType)[0])), $tipeXmlOgc)) {
    $contentType = 'text/xml; charset=UTF-8';
}

// =========================================================
// 5. Mengirim kembali respons GeoServer ke browser dengan
//    Content-Type yang sama seperti aslinya (misalnya image/png
//    untuk GetMap, atau text/xml untuk GetCapabilities).
// =========================================================
if ($contentType) {
    header("Content-Type: " . $contentType);
}

// =========================================================
// 6. Kirim balik isi respons GeoServer ke browser pengguna.
// =========================================================
echo $response;
