<?php
session_start();
require_once 'baglanti.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'doktor') {
    header("Location: index.php");
    exit();
}

$kullanici_id = (int) $_SESSION['user_id'];
$stmt_doktor = $db->prepare(
    "SELECT d.id, d.unvan, k.ad_soyad, k.eposta, p.poliklinik_adi
     FROM doktorlar d
     JOIN kullanicilar k ON d.kullanici_id = k.id
     JOIN poliklinikler p ON d.poliklinik_id = p.id
     WHERE d.kullanici_id = :kullanici_id"
);
$stmt_doktor->execute(['kullanici_id' => $kullanici_id]);
$doktor = $stmt_doktor->fetch(PDO::FETCH_ASSOC);

if (!$doktor) {
    die("Bu kullanıcıya ait doktor profili bulunamadı! Lütfen admin veya veritabanı üzerinden doktorlar tablosuna bağlayın.");
}

$doktor_id = (int) $doktor['id'];
$baslangic_tarihi = isset($_GET['baslangic_tarihi']) && is_string($_GET['baslangic_tarihi'])
    ? trim($_GET['baslangic_tarihi'])
    : '';
$bitis_tarihi = isset($_GET['bitis_tarihi']) && is_string($_GET['bitis_tarihi'])
    ? trim($_GET['bitis_tarihi'])
    : '';
$filtre_hatasi = '';
$tarih_filtreleri = [];

foreach (['baslangic_tarihi' => $baslangic_tarihi, 'bitis_tarihi' => $bitis_tarihi] as $alan => $tarih) {
    if ($tarih === '') {
        continue;
    }

    $tarih_nesnesi = DateTime::createFromFormat('!Y-m-d', $tarih);
    if (!$tarih_nesnesi || $tarih_nesnesi->format('Y-m-d') !== $tarih) {
        $filtre_hatasi = 'Lütfen geçerli bir tarih seçin.';
        break;
    }

    $tarih_filtreleri[$alan] = $tarih;
}

if (
    $filtre_hatasi === ''
    && isset($tarih_filtreleri['baslangic_tarihi'], $tarih_filtreleri['bitis_tarihi'])
    && $tarih_filtreleri['baslangic_tarihi'] > $tarih_filtreleri['bitis_tarihi']
) {
    $filtre_hatasi = 'Başlangıç tarihi bitiş tarihinden sonra olamaz.';
}

$sql = "SELECT r.*, k.ad_soyad AS hasta_adi, k.tc_no
        FROM randevular r
        JOIN kullanicilar k ON r.hasta_id = k.id
        WHERE r.doktor_id = :doktor_id";
$parametreler = ['doktor_id' => $doktor_id];

if ($filtre_hatasi === '') {
    if (isset($tarih_filtreleri['baslangic_tarihi'])) {
        $sql .= " AND r.randevu_tarihi >= :baslangic_tarihi";
        $parametreler['baslangic_tarihi'] = $tarih_filtreleri['baslangic_tarihi'];
    }
    if (isset($tarih_filtreleri['bitis_tarihi'])) {
        $sql .= " AND r.randevu_tarihi <= :bitis_tarihi";
        $parametreler['bitis_tarihi'] = $tarih_filtreleri['bitis_tarihi'];
    }
}

$sql .= " ORDER BY r.randevu_tarihi DESC, r.randevu_saati DESC";
$stmt = $db->prepare($sql);
$stmt->execute($parametreler);
$randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);

$toplam_randevu = count($randevular);
$aktif_randevu = 0;
$tamamlanan_muayene = 0;
$iptal_randevu = 0;
foreach ($randevular as $randevu) {
    if ($randevu['durum'] === 'Aktif') {
        $aktif_randevu++;
    } elseif ($randevu['durum'] === 'Tamamlandi') {
        $tamamlanan_muayene++;
    } elseif ($randevu['durum'] === 'Iptal') {
        $iptal_randevu++;
    }
}

$muayene_mesajlari = [
    'kaydedildi' => ['success', 'Muayene notu başarıyla kaydedildi.'],
    'iptal' => ['danger', 'İptal edilmiş randevulara muayene notu girilemez.'],
    'gecersiz' => ['warning', 'Lütfen geçerli ve boş olmayan bir muayene notu girin.'],
    'bulunamadi' => ['danger', 'Randevu bulunamadı veya bu randevu size ait değil.'],
    'hata' => ['danger', 'Muayene notu kaydedilemedi. Lütfen tekrar deneyin.'],
];
$muayene_durumu = $_GET['muayene'] ?? '';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doktor Paneli - Doktor Randevu Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="uygulama.css">
    <script src="tema.js" defer></script>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="doktor_panel.php"><i class="bi bi-hospital me-2"></i>Doktor Paneli</a>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small d-none d-sm-inline"><i class="bi bi-person-badge me-1"></i><?php echo htmlspecialchars($doktor['unvan'] . ' ' . $doktor['ad_soyad']); ?></span>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary settings-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Görünüm ayarları" title="Görünüm ayarları">
                        <i class="bi bi-gear" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 settings-menu">
                        <li><h2 class="dropdown-header">Görünüm</h2></li>
                        <li><button class="dropdown-item" type="button" data-theme-option="light"><i class="bi bi-sun me-2"></i>Açık tema</button></li>
                        <li><button class="dropdown-item" type="button" data-theme-option="dark"><i class="bi bi-moon-stars me-2"></i>Koyu tema</button></li>
                        <li><button class="dropdown-item" type="button" data-theme-option="system"><i class="bi bi-display me-2"></i>Sistem ayarını kullan</button></li>
                    </ul>
                </div>
                <a href="cikis.php" class="btn btn-outline-secondary btn-sm">Çıkış Yap</a>
            </div>
        </div>
    </nav>

    <main class="container py-4 py-lg-5">
        <?php if (isset($muayene_mesajlari[$muayene_durumu])): ?>
            <div class="alert alert-<?php echo $muayene_mesajlari[$muayene_durumu][0]; ?> alert-dismissible fade show rounded-4" role="alert">
                <?php echo htmlspecialchars($muayene_mesajlari[$muayene_durumu][1]); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>
        <?php endif; ?>

        <?php if ($filtre_hatasi !== ''): ?>
            <div class="alert alert-warning rounded-4" role="alert"><?php echo htmlspecialchars($filtre_hatasi); ?></div>
        <?php endif; ?>

        <section class="doctor-welcome p-4 p-lg-5 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="welcome-kicker"><i class="bi bi-heart-pulse me-2"></i>Doktor çalışma alanı</span>
                    <h1 class="display-6 fw-bold mt-3 mb-2">Hoş geldiniz, <?php echo htmlspecialchars($doktor['unvan'] . ' ' . $doktor['ad_soyad']); ?></h1>
                    <p class="mb-0 text-muted">Randevularınızı takip edin, muayenelerinizi yönetin ve hasta notlarını güvenle kaydedin.</p>
                </div>
                <div class="col-lg-4">
                    <div class="doctor-profile-card p-3 p-lg-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="doctor-avatar"><i class="bi bi-person-vcard"></i></span>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($doktor['unvan'] . ' ' . $doktor['ad_soyad']); ?></div>
                                <div class="small text-muted">Doktor profili</div>
                            </div>
                        </div>
                        <div class="doctor-profile-detail"><i class="bi bi-hospital"></i><span><?php echo htmlspecialchars($doktor['poliklinik_adi']); ?></span></div>
                        <div class="doctor-profile-detail"><i class="bi bi-envelope"></i><span class="text-break"><?php echo htmlspecialchars($doktor['eposta']); ?></span></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="row g-3 mb-4" aria-label="Randevu özeti">
            <div class="col-6 col-xl-3">
                <div class="summary-card summary-card--total h-100 p-3 p-lg-4">
                    <span class="summary-icon"><i class="bi bi-calendar2-week"></i></span>
                    <div class="summary-value"><?php echo $toplam_randevu; ?></div>
                    <div class="summary-label">Listelenen randevu</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="summary-card summary-card--active h-100 p-3 p-lg-4">
                    <span class="summary-icon"><i class="bi bi-calendar-check"></i></span>
                    <div class="summary-value"><?php echo $aktif_randevu; ?></div>
                    <div class="summary-label">Aktif randevu</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="summary-card summary-card--completed h-100 p-3 p-lg-4">
                    <span class="summary-icon"><i class="bi bi-clipboard2-pulse"></i></span>
                    <div class="summary-value"><?php echo $tamamlanan_muayene; ?></div>
                    <div class="summary-label">Tamamlanan muayene</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="summary-card summary-card--cancelled h-100 p-3 p-lg-4">
                    <span class="summary-icon"><i class="bi bi-calendar-x"></i></span>
                    <div class="summary-value"><?php echo $iptal_randevu; ?></div>
                    <div class="summary-label">İptal edilen</div>
                </div>
            </div>
        </section>

        <section class="card border-0 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1"><i class="bi bi-funnel me-2 text-primary"></i>Muayene tarih filtresi</h2>
                        <p class="text-muted small mb-0">Randevu tarihine göre listeyi daraltın.</p>
                    </div>
                    <a href="doktor_panel.php#randevular" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise me-1"></i>Filtreyi Temizle</a>
                </div>
                <form method="GET" action="doktor_panel.php#randevular" class="row g-3 align-items-end">
                    <div class="col-sm-6 col-lg-4">
                        <label for="baslangic_tarihi" class="form-label fw-semibold">Başlangıç tarihi</label>
                        <input type="date" id="baslangic_tarihi" name="baslangic_tarihi" class="form-control" value="<?php echo htmlspecialchars($baslangic_tarihi); ?>">
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <label for="bitis_tarihi" class="form-label fw-semibold">Bitiş tarihi</label>
                        <input type="date" id="bitis_tarihi" name="bitis_tarihi" class="form-control" value="<?php echo htmlspecialchars($bitis_tarihi); ?>">
                    </div>
                    <div class="col-lg-4">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-2"></i>Tarihleri Uygula</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card border-0" id="randevular">
            <div class="card-header bg-transparent border-0 px-4 pt-4 pb-0">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                    <div>
                        <h2 class="h4 fw-bold mb-1">Randevular ve muayeneler</h2>
                        <p class="text-muted small mb-0">Hasta bilgileri ve muayene kayıtları</p>
                    </div>
                    <span class="badge rounded-pill text-bg-light px-3 py-2"><?php echo $toplam_randevu; ?> kayıt</span>
                </div>
            </div>
            <div class="card-body p-0 mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 doctor-appointments">
                        <thead>
                            <tr>
                                <th>Hasta</th>
                                <th>Tarih / Saat</th>
                                <th>Şikayet</th>
                                <th>Muayene notu</th>
                                <th>Durum</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($randevular): ?>
                                <?php foreach ($randevular as $randevu): ?>
                                    <?php
                                    $iptal_edildi = $randevu['durum'] === 'Iptal';
                                    $tamamlandi = $randevu['durum'] === 'Tamamlandi';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($randevu['hasta_adi']); ?></div>
                                            <div class="small text-muted">T.C. <?php echo htmlspecialchars($randevu['tc_no']); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars(date('d.m.Y', strtotime($randevu['randevu_tarihi']))); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars(substr($randevu['randevu_saati'], 0, 5)); ?></div>
                                        </td>
                                        <td class="appointment-complaint"><?php echo htmlspecialchars($randevu['sikayet'] ?: '-'); ?></td>
                                        <td class="appointment-note"><?php echo htmlspecialchars($randevu['muayene_notu'] ?: 'Henüz girilmedi'); ?></td>
                                        <td>
                                            <?php if ($randevu['durum'] === 'Aktif'): ?>
                                                <span class="badge rounded-pill text-bg-success-subtle text-success-emphasis">Aktif</span>
                                            <?php elseif ($iptal_edildi): ?>
                                                <span class="badge rounded-pill text-bg-danger-subtle text-danger-emphasis">İptal</span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill text-bg-secondary-subtle text-secondary-emphasis">Tamamlandı</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$iptal_edildi): ?>
                                                <button type="button" class="btn btn-sm <?php echo $tamamlandi ? 'btn-outline-secondary' : 'btn-outline-primary'; ?> text-nowrap" data-bs-toggle="modal" data-bs-target="#muayeneModal<?php echo (int) $randevu['id']; ?>">
                                                    <i class="bi bi-pencil-square me-1"></i><?php echo $tamamlandi ? 'Notu Düzenle' : 'Muayene Notu'; ?>
                                                </button>
                                                <div class="modal fade" id="muayeneModal<?php echo (int) $randevu['id']; ?>" tabindex="-1" aria-labelledby="muayeneBaslik<?php echo (int) $randevu['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content border-0">
                                                            <div class="modal-header bg-primary text-white">
                                                                <h3 class="modal-title fs-5 fw-bold" id="muayeneBaslik<?php echo (int) $randevu['id']; ?>">Muayene notu</h3>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
                                                            </div>
                                                            <form action="muayene_notu_kaydet.php" method="POST">
                                                                <div class="modal-body p-4">
                                                                    <input type="hidden" name="randevu_id" value="<?php echo (int) $randevu['id']; ?>">
                                                                    <label class="form-label fw-semibold" for="not_<?php echo (int) $randevu['id']; ?>"><?php echo htmlspecialchars($randevu['hasta_adi']); ?> için teşhis ve muayene notu</label>
                                                                    <textarea id="not_<?php echo (int) $randevu['id']; ?>" class="form-control" name="muayene_notu" rows="5" placeholder="Muayene notunu yazınız..." required><?php echo htmlspecialchars($randevu['muayene_notu'] ?? ''); ?></textarea>
                                                                </div>
                                                                <div class="modal-footer border-0 px-4 pb-4">
                                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Vazgeç</button>
                                                                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Kaydet</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="small text-muted"><i class="bi bi-lock me-1"></i>Not eklenemez</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="empty-state-icon mb-3"><i class="bi bi-calendar2-x"></i></div>
                                        <div class="fw-semibold">Bu tarih aralığında randevu bulunamadı</div>
                                        <div class="small text-muted mt-1">Farklı bir tarih aralığı seçebilir veya filtreyi temizleyebilirsiniz.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
