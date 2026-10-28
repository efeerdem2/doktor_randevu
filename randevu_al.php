<?php
session_start();
require_once 'baglanti.php';

// Oturum kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'hasta') {
    header("Location: index.php");
    exit();
}

// Poliklinikleri çekelim
$sql_poliklinik = "SELECT * FROM poliklinikler";
$stmt_poliklinik = $db->query($sql_poliklinik);
$poliklinikler = $stmt_poliklinik->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Al - Doktor Randevu Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-white border-bottom shadow-sm py-2">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="hasta_panel.php">
                <i class="bi bi-hospital fs-3 me-2 text-danger"></i>
                <span class="text-dark">Doktor Randevu Sistemi</span>
            </a>
            <div class="d-flex align-items-center">
                <a href="hasta_panel.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Panele Dön</a>
            </div>
        </div>
    </nav>

    <!-- Form Alanı -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card shadow-sm border-0 rounded-4 bg-white">
                    <div class="card-body p-4 p-md-5">
                        
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-dark">Hastane Randevusu Oluştur</h3>
                            <p class="text-muted small">Poliklinik ve doktor seçerek randevunuzu kolayca planlayın.</p>
                        </div>

                        <form action="randevu_kaydet.php" method="POST">
                            
                            <!-- Poliklinik Seçimi -->
                            <div class="mb-3">
                                <label for="poliklinik_id" class="form-label fw-semibold">Poliklinik Seçin</label>
                                <select class="form-select rounded-3 py-2" id="poliklinik_id" name="poliklinik_id" required>
                                    <option value="" selected disabled>Poliklinik seçiniz...</option>
                                    <?php foreach ($poliklinikler as $p): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['poliklinik_adi']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Doktor Seçimi (Önce poliklinik seçilmeli) -->
                            <div class="mb-3">
                                <label for="doktor_id" class="form-label fw-semibold">Doktor Seçin</label>
                                <select class="form-select rounded-3 py-2" id="doktor_id" name="doktor_id" required>
                                    <option value="" selected disabled>Önce poliklinik seçiniz...</option>
                                    <!-- PHP/AJAX ile dinamik yüklenecek veya tümü listelenecek -->
                                    <?php
                                    $sql_doktor = "SELECT d.id, d.unvan, k.ad_soyad, p.poliklinik_adi FROM doktorlar d JOIN kullanicilar k ON d.kullanici_id = k.id JOIN poliklinikler p ON d.poliklinik_id = p.id";
                                    $stmt_doktor = $db->query($sql_doktor);
                                    $doktorlar = $stmt_doktor->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($doktorlar as $d) {
                                        echo "<option value='{$d['id']}'>{$d['unvan']} {$d['ad_soyad']} ({$d['poliklinik_adi']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Tarih Seçimi -->
                            <div class="mb-3">
                                <label for="randevu_tarihi" class="form-label fw-semibold">Randevu Tarihi</label>
                                <input type="date" class="form-control rounded-3 py-2" id="randevu_tarihi" name="randevu_tarihi" required min="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <!-- Saat Seçimi -->
                            <div class="mb-3">
                                <label for="randevu_saati" class="form-label fw-semibold">Randevu Saati</label>
                                <select class="form-select rounded-3 py-2" id="randevu_saati" name="randevu_saati" required>
                                    <option value="" selected disabled>Saat seçiniz...</option>
                                    <option value="09:00">09:00</option>
                                    <option value="09:30">09:30</option>
                                    <option value="10:00">10:00</option>
                                    <option value="10:30">10:30</option>
                                    <option value="11:00">11:00</option>
                                    <option value="11:30">11:30</option>
                                    <option value="13:30">13:30</option>
                                    <option value="14:00">14:00</option>
                                    <option value="14:30">14:30</option>
                                    <option value="15:00">15:00</option>
                                    <option value="15:30">15:30</option>
                                </select>
                            </div>

                            <!-- Şikayet Alanı -->
                            <div class="mb-4">
                                <label for="sikayet" class="form-label fw-semibold">Şikayet / Açıklama (İsteğe Bağlı)</label>
                                <textarea class="form-control rounded-3" id="sikayet" name="sikayet" rows="3" placeholder="Şikayetinizi kısaca yazabilirsiniz..."></textarea>
                            </div>

                            <!-- Butonlar -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm">Randevuyu Onayla</button>
                                <a href="hasta_panel.php" class="btn btn-outline-secondary rounded-pill">Vazgeç</a>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>