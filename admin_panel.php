<?php
session_start();
require_once 'baglanti.php';

// Oturum ve rol kontrolü (Admin değilse giriş sayfasına at)
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Poliklinik Ekleme İşlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['poliklinik_ekle'])) {
    $poliklinik_adi = trim($_POST['poliklinik_adi']);
    if (!empty($poliklinik_adi)) {
        $stmt = $db->prepare("INSERT INTO poliklinikler (poliklinik_adi) VALUES (:adi)");
        $stmt->execute(['adi' => $poliklinik_adi]);
        header("Location: admin_panel.php");
        exit();
    }
}

// Doktor Ekleme İşlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['doktor_ekle'])) {
    $ad_soyad = trim($_POST['ad_soyad']);
    $tc_no = trim($_POST['tc_no']);
    $eposta = trim($_POST['eposta']);
    $sifre = password_hash(trim($_POST['sifre']), PASSWORD_DEFAULT);
    $poliklinik_id = $_POST['poliklinik_id'];
    $unvan = trim($_POST['unvan']);

    try {
        $db->beginTransaction();

        // 1. Önce kullanicilar tablosuna ekle (rolü doktor)
        $stmt = $db->prepare("INSERT INTO kullanicilar (ad_soyad, tc_no, eposta, sifre, rol) VALUES (:ad_soyad, :tc_no, :eposta, :sifre, 'doktor')");
        $stmt->execute([
            'ad_soyad' => $ad_soyad,
            'tc_no' => $tc_no,
            'eposta' => $eposta,
            'sifre' => $sifre
        ]);
        $kullanici_id = $db->lastInsertId();

        // 2. Ardından doktorlar tablosuna bağla
        $stmt2 = $db->prepare("INSERT INTO doktorlar (kullanici_id, poliklinik_id, unvan) VALUES (:kullanici_id, :poliklinik_id, :unvan)");
        $stmt2->execute([
            'kullanici_id' => $kullanici_id,
            'poliklinik_id' => $poliklinik_id,
            'unvan' => $unvan
        ]);

        $db->commit();
        header("Location: admin_panel.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $hata = "Doktor eklenirken bir hata oluştu: " . $e->getMessage();
    }
}

// Verileri Listeleme Sorguları
$poliklinikler = $db->query("SELECT * FROM poliklinikler")->fetchAll(PDO::FETCH_ASSOC);
$doktorlar = $db->query("SELECT d.id, d.unvan, k.ad_soyad, p.poliklinik_adi FROM doktorlar d JOIN kullanicilar k ON d.kullanici_id = k.id JOIN poliklinikler p ON d.poliklinik_id = p.id")->fetchAll(PDO::FETCH_ASSOC);
$tum_randevular = $db->query("SELECT r.*, h.ad_soyad as hasta_adi, d.unvan, dk.ad_soyad as doktor_adi, p.poliklinik_adi FROM randevular r JOIN kullanicilar h ON r.hasta_id = h.id JOIN doktorlar d ON r.doktor_id = d.id JOIN kullanicilar dk ON d.kullanici_id = dk.id JOIN poliklinikler p ON d.poliklinik_id = p.id ORDER BY r.randevu_tarihi DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Paneli - Doktor Randevu Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="uygulama.css">
    <script src="tema.js" defer></script>
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="admin_panel.php"><i class="bi bi-shield-lock-fill me-2"></i>Yönetim Paneli (Admin)</a>
            <div class="d-flex align-items-center">
                <span class="me-3"><i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></span>
                <a href="cikis.php" class="btn btn-outline-secondary btn-sm">Çıkış Yap</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        
        <?php if (isset($hata)): ?>
            <div class="alert alert-danger"><?php echo $hata; ?></div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <!-- Poliklinik Ekleme Kartı -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-body p-4">
                        <h4 class="fw-bold text-primary mb-3"><i class="bi bi-hospital me-2"></i>Yeni Poliklinik Ekle</h4>
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Poliklinik Adı</label>
                                <input type="text" class="form-control rounded-3" name="poliklinik_adi" placeholder="Örn: Nöroloji, Ortopedi..." required>
                            </div>
                            <button type="submit" name="poliklinik_ekle" class="btn btn-primary rounded-pill px-4 fw-semibold">Poliklinik Ekle</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Doktor Ekleme Kartı -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-body p-4">
                        <h4 class="fw-bold text-success mb-3"><i class="bi bi-person-plus-fill me-2"></i>Yeni Doktor Tanımla</h4>
                        <form action="" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Ad Soyad</label>
                                    <input type="text" class="form-control rounded-3" name="ad_soyad" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">T.C. Kimlik No</label>
                                    <input type="text" class="form-control rounded-3" name="tc_no" maxlength="11" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">E-posta</label>
                                    <input type="email" class="form-control rounded-3" name="eposta" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Şifre</label>
                                    <input type="password" class="form-control rounded-3" name="sifre" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Poliklinik</label>
                                    <select class="form-select rounded-3" name="poliklinik_id" required>
                                        <option value="" selected disabled>Seçiniz...</option>
                                        <?php foreach ($poliklinikler as $p): ?>
                                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['poliklinik_adi']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Unvan</label>
                                    <input type="text" class="form-control rounded-3" name="unvan" placeholder="Örn: Uzman Doktor" required>
                                </div>
                            </div>
                            <button type="submit" name="doktor_ekle" class="btn btn-success rounded-pill px-4 fw-semibold">Doktor Kaydet</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tüm Randevular Tablosu -->
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <h4 class="fw-bold text-dark mb-4"><i class="bi bi-calendar3 me-2"></i>Sistemdeki Tüm Randevular</h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Hasta Adı</th>
                                <th>Poliklinik</th>
                                <th>Doktor</th>
                                <th>Tarih</th>
                                <th>Saat</th>
                                <th>Şikayet</th>
                                <th>Muayene Notu</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($tum_randevular) > 0): ?>
                                <?php foreach ($tum_randevular as $r): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($r['hasta_adi']); ?></td>
                                        <td><?php echo htmlspecialchars($r['poliklinik_adi']); ?></td>
                                        <td><?php echo htmlspecialchars($r['unvan'] . ' ' . $r['doktor_adi']); ?></td>
                                        <td><?php echo htmlspecialchars($r['randevu_tarihi']); ?></td>
                                        <td><?php echo htmlspecialchars($r['randevu_saati']); ?></td>
                                        <td><?php echo htmlspecialchars($r['sikayet'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($r['muayene_notu'] ?: 'Girilmedi'); ?></td>
                                        <td>
                                            <?php if ($r['durum'] == 'Aktif'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php elseif ($r['durum'] == 'Iptal'): ?>
                                                <span class="badge bg-danger">İptal</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Tamamlandı</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Sistemde henüz kayıtlı randevu bulunmamaktadır.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>