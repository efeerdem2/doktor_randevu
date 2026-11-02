<?php
// Oturumu başlatıyoruz ki kullanıcının giriş yapıp yapmadığını kontrol edebilelim
session_start();

// Veritabanı bağlantı dosyamızı projeye dahil ediyoruz
require_once 'baglanti.php';

// Oturum kontrolü: Kullanıcı giriş yapmış mı ve rolü 'hasta' mı? Değilse anasayfaya yönlendiriyoruz.
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'hasta') {
    header("Location: index.php");
    exit();
}

// Giriş yapan hastanın veritabanındaki benzersiz ID'sini değişkene atıyoruz
$hasta_id = $_SESSION['user_id'];

// Hastanın randevularını, doktorun unvanını, doktorun adını ve poliklinik adını çekmek için SQL sorgusu hazırlıyoruz
$sql = "SELECT r.*, d.unvan, k.ad_soyad as doktor_adi, p.poliklinik_adi 
        FROM randevular r 
        JOIN doktorlar d ON r.doktor_id = d.id 
        JOIN kullanicilar k ON d.kullanici_id = k.id 
        JOIN poliklinikler p ON d.poliklinik_id = p.id 
        WHERE r.hasta_id = :hasta_id 
        ORDER BY r.randevu_tarihi DESC, r.randevu_saati DESC";

// Sorguyu hazırlayıp hasta ID'si ile güvenli bir şekilde çalıştırıyoruz
$stmt = $db->prepare($sql);
$stmt->execute(['hasta_id' => $hasta_id]);

// Gelen tüm randevu kayıtlarını ilişkisel dizi olarak alıyoruz
$randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);
$bugun = date('Y-m-d');
$aktifRandevular = array_filter($randevular, function ($randevu) use ($bugun) {
    return $randevu['durum'] === 'Aktif' && $randevu['randevu_tarihi'] >= $bugun;
});
$gecmisRandevular = array_filter($randevular, function ($randevu) use ($bugun) {
    return $randevu['durum'] !== 'Aktif' || $randevu['randevu_tarihi'] < $bugun;
});
$randevuGorunumu = isset($_GET['gorunum']) && $_GET['gorunum'] === 'randevular';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasta Paneli - Doktor Randevu Sistemi</title>
    <!-- Bootstrap 5 CSS Kütüphanesini Sayfaya Dahil Ediyoruz -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap İkonları Kütüphanesini Sayfaya Dahil Ediyoruz -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="uygulama.css">
    <script src="tema.js" defer></script>
    <style>
        .appointment-table td { min-width: 100px; }
    </style>
</head>
<body class="bg-light">

    <!-- Üst Menü (Navbar) Alanı -->
    <nav class="navbar navbar-expand-lg py-2">
        <div class="container-fluid px-4">
            <!-- Logo ve Site Başlığı -->
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="hasta_panel.php">
                <i class="bi bi-heart-pulse fs-3 me-2"></i>
                <span class="text-dark">Doktor Randevu Sistemi</span>
            </a>
            <!-- Sağ Üst Profil ve Çıkış Dropdown Menüsü -->
            <div class="d-flex align-items-center">
                <div class="dropdown me-2">
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
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle rounded-pill px-4" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-fill me-1"></i> <?php echo htmlspecialchars($_SESSION['ad_soyad']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 profile-menu">
                        <li><a class="dropdown-item" href="bilgilerim.php"><i class="bi bi-person-vcard me-2"></i>Hesap Bilgileri</a></li>
                        <li><a class="dropdown-item" href="hasta_panel.php?gorunum=randevular#randevular"><i class="bi bi-calendar2-check me-2"></i>Randevu Bilgileri</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="cikis.php"><i class="bi bi-box-arrow-right me-2"></i>Çıkış Yap</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Ana İçerik Konteynerı -->
    <div class="container-fluid px-4 my-4">
        <?php if (!$randevuGorunumu): ?>
        <section class="dashboard-intro p-4 p-lg-5 mb-4">
            <p class="text-uppercase small fw-bold text-primary mb-2">Kişisel sağlık alanınız</p>
            <h1 class="h2 fw-bold mb-2">Merhaba, <?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></h1>
            <p class="text-muted mb-0">Randevularınızı görüntüleyin veya yeni bir ziyaret planlayın.</p>
        </section>
        <?php endif; ?>
        <div class="row g-4">
            <!-- Sol Menü / İşlem Kartları Alanı -->
            <?php if (!$randevuGorunumu): ?>
            <div class="col-lg-4">
                
                <a href="neyim_var.php" class="feature-card feature-card--clinic text-decoration-none p-4 mb-3">
                    <div class="feature-icon mb-3"><i class="bi bi-compass fs-4"></i></div>
                    <h2 class="h5 fw-bold mb-2">Doğru kliniği bul</h2>
                    <p class="text-muted small mb-0">Şikayetlerinize göre uygun poliklinik için yönlendirme alın.</p>
                </a>

                <a href="randevu_al.php" class="feature-card feature-card--appointment text-decoration-none p-4 mb-3">
                    <div class="feature-icon mb-3"><i class="bi bi-calendar-plus fs-4"></i></div>
                    <h2 class="h5 fw-bold mb-2">Randevu oluştur</h2>
                    <p class="text-muted small mb-0">Poliklinik ve doktor seçerek ziyaretinizi planlayın.</p>
                </a>

                <a href="bilgilerim.php" class="feature-card feature-card--profile text-decoration-none p-4">
                    <div class="feature-icon mb-3"><i class="bi bi-person-vcard fs-4"></i></div>
                    <h2 class="h5 fw-bold mb-2">Profil bilgilerin</h2>
                    <p class="text-muted small mb-0">İletişim bilgilerinizi görüntüleyin ve güncelleyin.</p>
                </a>
            </div>
            <?php endif; ?>

            <!-- Sağ Taraf / Randevularım Listeleme Alanı -->
            <div class="<?php echo $randevuGorunumu ? 'col-12' : 'col-lg-8'; ?>" id="randevular">
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-body p-4">
                        <?php if ($randevuGorunumu): ?>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <h1 class="h4 fw-bold mb-0">Randevu Geçmişi Listesi</h1>
                                <a href="hasta_panel.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Panele Dön</a>
                            </div>
                        <?php endif; ?>
                        <!-- Sekmeler (Tabs) -->
                        <ul class="nav nav-tabs border-bottom-0 mb-4" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold text-dark border-0 border-bottom border-primary border-3" id="aktif-tab" data-bs-toggle="tab" data-bs-target="#aktif-randevular" type="button">Randevularım</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link text-muted fw-semibold border-0" id="gecmis-tab" data-bs-toggle="tab" data-bs-target="#gecmis-randevular" type="button">Geçmiş Randevularım</button>
                            </li>
                        </ul>

                        <!-- Sekme İçerikleri -->
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="aktif-randevular">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle appointment-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Poliklinik</th>
                                                <th>Doktor</th>
                                                <th>Tarih</th>
                                                <th>Saat</th>
                                                <th>Şikayet</th>
                                                <th>Durum</th>
                                                <th>İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Veritabanından gelen randevu sayısını kontrol ediyoruz -->
                                            <?php if (count($aktifRandevular) > 0): ?>
                                                <?php foreach ($aktifRandevular as $randevu): ?>
                                                    <tr>
                                                        <!-- Poliklinik Adı -->
                                                        <td><?php echo htmlspecialchars($randevu['poliklinik_adi']); ?></td>
                                                        <!-- Doktor Unvanı ve Adı Soyadı -->
                                                        <td><?php echo htmlspecialchars($randevu['unvan'] . ' ' . $randevu['doktor_adi']); ?></td>
                                                        <!-- Randevu Tarihi -->
                                                        <td><?php echo htmlspecialchars($randevu['randevu_tarihi']); ?></td>
                                                        <!-- Randevu Saati -->
                                                        <td><?php echo htmlspecialchars($randevu['randevu_saati']); ?></td>
                                                        <!-- Şikayet Bilgisi -->
                                                        <td><?php echo htmlspecialchars($randevu['sikayet'] ?: '-'); ?></td>
                                                        <!-- Randevu Durum Rozeti -->
                                                        <td>
                                                            <?php if ($randevu['durum'] == 'Aktif'): ?>
                                                                <span class="badge bg-success">Aktif</span>
                                                            <?php elseif ($randevu['durum'] == 'Iptal'): ?>
                                                                <span class="badge bg-danger">İptal</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Tamamlandı</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <!-- İşlem Butonu -->
                                                        <td>
                                                            <a href="randevu_iptal.php?id=<?php echo (int) $randevu['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Randevuyu iptal etmek istediğinize emin misiniz?');">İptal Et</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <!-- Randevu Yoksa Gösterilecek Mesaj -->
                                                <tr>
                                                    <td colspan="7" class="text-center py-5">
                                                        <div class="text-muted mb-2"><i class="bi bi-calendar-x fs-1"></i></div>
                                                        <p class="text-muted mb-0">Planlanmış randevunuz yok. Yeni bir randevu oluşturabilirsiniz.</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="gecmis-randevular">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle appointment-table">
                                        <thead>
                                            <tr>
                                                <th>Poliklinik</th><th>Doktor</th><th>Tarih</th><th>Saat</th><th>Şikayet</th><th>Durum</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($gecmisRandevular) > 0): ?>
                                                <?php foreach ($gecmisRandevular as $randevu): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($randevu['poliklinik_adi']); ?></td>
                                                        <td><?php echo htmlspecialchars($randevu['unvan'] . ' ' . $randevu['doktor_adi']); ?></td>
                                                        <td><?php echo htmlspecialchars($randevu['randevu_tarihi']); ?></td>
                                                        <td><?php echo htmlspecialchars($randevu['randevu_saati']); ?></td>
                                                        <td><?php echo htmlspecialchars($randevu['sikayet'] ?: '-'); ?></td>
                                                        <td>
                                                            <?php if ($randevu['durum'] === 'Iptal'): ?>
                                                                <span class="badge bg-danger">İptal</span>
                                                            <?php elseif ($randevu['durum'] === 'Aktif'): ?>
                                                                <span class="badge bg-secondary">Tarihi geçti</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-success">Tamamlandı</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="6" class="text-center text-muted py-5">Geçmiş randevunuz bulunmuyor.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Kütüphanesi -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>