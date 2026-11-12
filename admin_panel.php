<?php
session_start();
require_once 'baglanti.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$csrf_anahtari = 'admin_panel_csrf';
if (empty($_SESSION[$csrf_anahtari])) {
    $_SESSION[$csrf_anahtari] = bin2hex(random_bytes(32));
}

$mesajlar = [
    'poliklinik_eklendi' => 'Poliklinik eklendi.',
    'poliklinik_guncellendi' => 'Poliklinik bilgileri güncellendi.',
    'poliklinik_silindi' => 'Poliklinik silindi.',
    'doktor_eklendi' => 'Doktor hesabı oluşturuldu.',
    'doktor_guncellendi' => 'Doktor bilgileri güncellendi.',
    'doktor_silindi' => 'Doktor hesabı silindi.',
    'sifre_sifirlandi' => 'Kullanıcı şifresi sıfırlandı.',
];
$durum = isset($_GET['durum']) && is_string($_GET['durum']) ? $_GET['durum'] : '';
$mesaj = $mesajlar[$durum] ?? '';
$hata = '';

function adminYonlendir($durum)
{
    header('Location: admin_panel.php?durum=' . urlencode($durum));
    exit();
}

function adminMetinAl($alan)
{
    $deger = $_POST[$alan] ?? '';
    return is_string($deger) ? trim($deger) : '';
}

function adminIdAl($alan)
{
    $deger = $_POST[$alan] ?? null;
    return is_string($deger) ? filter_var($deger, FILTER_VALIDATE_INT) : false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = adminMetinAl('csrf_token');
    if (!hash_equals($_SESSION[$csrf_anahtari], $csrf_token)) {
        http_response_code(400);
        exit('İstek doğrulanamadı. Sayfayı yenileyip tekrar deneyin.');
    }

    $islem = adminMetinAl('islem');
    try {
        if ($islem === 'poliklinik_ekle' || $islem === 'poliklinik_guncelle') {
            $poliklinik_adi = adminMetinAl('poliklinik_adi');
            $poliklinik_id = adminIdAl('poliklinik_id');
            if ($poliklinik_adi === '') {
                throw new InvalidArgumentException('Poliklinik adı boş bırakılamaz.');
            }

            if ($islem === 'poliklinik_ekle') {
                $stmt = $db->prepare("INSERT INTO poliklinikler (poliklinik_adi) VALUES (:adi)");
                $stmt->execute(['adi' => $poliklinik_adi]);
                adminYonlendir('poliklinik_eklendi');
            }

            if (!$poliklinik_id) {
                throw new InvalidArgumentException('Geçerli bir poliklinik seçin.');
            }
            $stmt = $db->prepare("UPDATE poliklinikler SET poliklinik_adi = :adi WHERE id = :id");
            $stmt->execute(['adi' => $poliklinik_adi, 'id' => $poliklinik_id]);
            adminYonlendir('poliklinik_guncellendi');
        }

        if ($islem === 'poliklinik_sil') {
            $poliklinik_id = adminIdAl('poliklinik_id');
            if (!$poliklinik_id) {
                throw new InvalidArgumentException('Geçerli bir poliklinik seçin.');
            }
            $kontrol = $db->prepare("SELECT COUNT(*) FROM doktorlar WHERE poliklinik_id = :id");
            $kontrol->execute(['id' => $poliklinik_id]);
            if ((int) $kontrol->fetchColumn() > 0) {
                throw new InvalidArgumentException('Bu polikliniğe bağlı doktorlar var. Önce doktorların polikliniklerini değiştirin.');
            }
            $stmt = $db->prepare("DELETE FROM poliklinikler WHERE id = :id");
            $stmt->execute(['id' => $poliklinik_id]);
            adminYonlendir('poliklinik_silindi');
        }

        if ($islem === 'doktor_ekle' || $islem === 'doktor_guncelle') {
            $ad_soyad = adminMetinAl('ad_soyad');
            $tc_no = adminMetinAl('tc_no');
            $eposta = adminMetinAl('eposta');
            $unvan = adminMetinAl('unvan');
            $poliklinik_id = adminIdAl('poliklinik_id');
            $doktor_id = adminIdAl('doktor_id');
            if ($ad_soyad === '' || $unvan === '' || !$poliklinik_id) {
                throw new InvalidArgumentException('Doktor adı, unvanı ve polikliniği zorunludur.');
            }
            if (!preg_match('/^\d{11}$/', $tc_no) || !filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('T.C. kimlik numarası 11 rakam olmalı ve geçerli bir e-posta girilmelidir.');
            }
            $poliklinik_kontrol = $db->prepare("SELECT COUNT(*) FROM poliklinikler WHERE id = :id");
            $poliklinik_kontrol->execute(['id' => $poliklinik_id]);
            if ((int) $poliklinik_kontrol->fetchColumn() === 0) {
                throw new InvalidArgumentException('Seçilen poliklinik bulunamadı.');
            }

            if ($islem === 'doktor_ekle') {
                $sifre = adminMetinAl('sifre');
                if (strlen($sifre) < 8) {
                    throw new InvalidArgumentException('Doktor şifresi en az 8 karakter olmalıdır.');
                }
                $db->beginTransaction();
                $stmt = $db->prepare(
                    "INSERT INTO kullanicilar (ad_soyad, tc_no, eposta, sifre, rol)
                     VALUES (:ad_soyad, :tc_no, :eposta, :sifre, 'doktor')"
                );
                $stmt->execute([
                    'ad_soyad' => $ad_soyad,
                    'tc_no' => $tc_no,
                    'eposta' => $eposta,
                    'sifre' => password_hash($sifre, PASSWORD_DEFAULT),
                ]);
                $stmt = $db->prepare(
                    "INSERT INTO doktorlar (kullanici_id, poliklinik_id, unvan)
                     VALUES (:kullanici_id, :poliklinik_id, :unvan)"
                );
                $stmt->execute([
                    'kullanici_id' => $db->lastInsertId(),
                    'poliklinik_id' => $poliklinik_id,
                    'unvan' => $unvan,
                ]);
                $db->commit();
                adminYonlendir('doktor_eklendi');
            }

            if (!$doktor_id) {
                throw new InvalidArgumentException('Geçerli bir doktor seçin.');
            }
            $stmt = $db->prepare(
                "UPDATE kullanicilar k
                 JOIN doktorlar d ON d.kullanici_id = k.id
                 SET k.ad_soyad = :ad_soyad, k.tc_no = :tc_no, k.eposta = :eposta,
                     d.unvan = :unvan, d.poliklinik_id = :poliklinik_id
                 WHERE d.id = :doktor_id AND k.rol = 'doktor'"
            );
            $stmt->execute([
                'ad_soyad' => $ad_soyad,
                'tc_no' => $tc_no,
                'eposta' => $eposta,
                'unvan' => $unvan,
                'poliklinik_id' => $poliklinik_id,
                'doktor_id' => $doktor_id,
            ]);
            adminYonlendir('doktor_guncellendi');
        }

        if ($islem === 'doktor_sil') {
            $doktor_id = adminIdAl('doktor_id');
            if (!$doktor_id) {
                throw new InvalidArgumentException('Geçerli bir doktor seçin.');
            }
            $db->beginTransaction();
            $kontrol = $db->prepare("SELECT kullanici_id FROM doktorlar WHERE id = :id FOR UPDATE");
            $kontrol->execute(['id' => $doktor_id]);
            $kullanici_id = $kontrol->fetchColumn();
            if (!$kullanici_id) {
                throw new InvalidArgumentException('Doktor kaydı bulunamadı.');
            }
            $randevu_kontrol = $db->prepare("SELECT COUNT(*) FROM randevular WHERE doktor_id = :id");
            $randevu_kontrol->execute(['id' => $doktor_id]);
            if ((int) $randevu_kontrol->fetchColumn() > 0) {
                throw new InvalidArgumentException('Randevu geçmişi olan doktor silinemez. Doktor bilgilerini düzenleyebilirsiniz.');
            }
            $stmt = $db->prepare("DELETE FROM doktorlar WHERE id = :id");
            $stmt->execute(['id' => $doktor_id]);
            $stmt = $db->prepare("DELETE FROM kullanicilar WHERE id = :id AND rol = 'doktor'");
            $stmt->execute(['id' => $kullanici_id]);
            $db->commit();
            adminYonlendir('doktor_silindi');
        }

        if ($islem === 'sifre_sifirla') {
            $kullanici_id = adminIdAl('kullanici_id');
            $yeni_sifre = adminMetinAl('yeni_sifre');
            if (!$kullanici_id || strlen($yeni_sifre) < 8) {
                throw new InvalidArgumentException('Yeni şifre en az 8 karakter olmalıdır.');
            }
            $stmt = $db->prepare(
                "UPDATE kullanicilar SET sifre = :sifre
                 WHERE id = :id AND rol IN ('hasta', 'doktor')"
            );
            $stmt->execute([
                'sifre' => password_hash($yeni_sifre, PASSWORD_DEFAULT),
                'id' => $kullanici_id,
            ]);
            if ($stmt->rowCount() === 0) {
                throw new InvalidArgumentException('Şifresi sıfırlanacak hasta veya doktor hesabı bulunamadı.');
            }
            adminYonlendir('sifre_sifirlandi');
        }

        throw new InvalidArgumentException('Geçersiz yönetim işlemi.');
    } catch (InvalidArgumentException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $hata = $e->getMessage();
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Admin panel işlemi başarısız: ' . $e->getMessage());
        $hata = 'İşlem tamamlanamadı. Bilgileri kontrol edin; kayıt başka veriler tarafından kullanılıyor olabilir.';
    }
}

$poliklinikler = $db->query(
    "SELECT p.id, p.poliklinik_adi, COUNT(d.id) AS doktor_sayisi
     FROM poliklinikler p
     LEFT JOIN doktorlar d ON d.poliklinik_id = p.id
     GROUP BY p.id, p.poliklinik_adi
     ORDER BY p.poliklinik_adi"
)->fetchAll(PDO::FETCH_ASSOC);
$doktorlar = $db->query(
    "SELECT d.id, d.kullanici_id, d.unvan, d.poliklinik_id, k.ad_soyad, k.tc_no, k.eposta, p.poliklinik_adi,
            (SELECT COUNT(*) FROM randevular r WHERE r.doktor_id = d.id) AS randevu_sayisi
     FROM doktorlar d
     JOIN kullanicilar k ON d.kullanici_id = k.id
     JOIN poliklinikler p ON d.poliklinik_id = p.id
     ORDER BY k.ad_soyad"
)->fetchAll(PDO::FETCH_ASSOC);
$kullanicilar = $db->query(
    "SELECT id, ad_soyad, tc_no, eposta, rol FROM kullanicilar
     WHERE rol IN ('hasta', 'doktor') ORDER BY rol, ad_soyad"
)->fetchAll(PDO::FETCH_ASSOC);
$tum_randevular = $db->query("SELECT r.*, h.ad_soyad as hasta_adi, d.unvan, dk.ad_soyad as doktor_adi, p.poliklinik_adi FROM randevular r JOIN kullanicilar h ON r.hasta_id = h.id JOIN doktorlar d ON r.doktor_id = d.id JOIN kullanicilar dk ON d.kullanici_id = dk.id JOIN poliklinikler p ON d.poliklinik_id = p.id ORDER BY r.randevu_tarihi DESC")->fetchAll(PDO::FETCH_ASSOC);
$istatistikler = [
    'hasta' => (int) $db->query("SELECT COUNT(*) FROM kullanicilar WHERE rol = 'hasta'")->fetchColumn(),
    'doktor' => (int) $db->query("SELECT COUNT(*) FROM doktorlar d JOIN kullanicilar k ON d.kullanici_id = k.id WHERE k.rol = 'doktor'")->fetchColumn(),
    'bugun' => (int) $db->query("SELECT COUNT(*) FROM randevular WHERE randevu_tarihi = CURDATE()")->fetchColumn(),
    'poliklinik' => count($poliklinikler),
];
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
            <div class="d-flex align-items-center gap-2">
                <span class="me-3"><i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></span>
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

    <main class="container my-5">
        
        <?php if (isset($hata)): ?>
            <div class="alert alert-danger rounded-4"><?php echo htmlspecialchars($hata); ?></div>
        <?php endif; ?>
        <?php if ($mesaj !== ''): ?>
            <div class="alert alert-success rounded-4"><?php echo htmlspecialchars($mesaj); ?></div>
        <?php endif; ?>

        <section class="row g-3 mb-4" aria-label="Sistem istatistikleri">
            <div class="col-6 col-lg-3">
                <div class="admin-stat-card admin-stat-card--patients h-100 p-3 p-lg-4">
                    <span class="admin-stat-icon"><i class="bi bi-people"></i></span>
                    <div class="admin-stat-value"><?php echo $istatistikler['hasta']; ?></div>
                    <div class="admin-stat-label">Toplam hasta</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="admin-stat-card admin-stat-card--doctors h-100 p-3 p-lg-4">
                    <span class="admin-stat-icon"><i class="bi bi-person-badge"></i></span>
                    <div class="admin-stat-value"><?php echo $istatistikler['doktor']; ?></div>
                    <div class="admin-stat-label">Kayıtlı doktor</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="admin-stat-card admin-stat-card--appointments h-100 p-3 p-lg-4">
                    <span class="admin-stat-icon"><i class="bi bi-calendar2-check"></i></span>
                    <div class="admin-stat-value"><?php echo $istatistikler['bugun']; ?></div>
                    <div class="admin-stat-label">Bugünkü randevu</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="admin-stat-card admin-stat-card--clinics h-100 p-3 p-lg-4">
                    <span class="admin-stat-icon"><i class="bi bi-hospital"></i></span>
                    <div class="admin-stat-value"><?php echo $istatistikler['poliklinik']; ?></div>
                    <div class="admin-stat-label">Poliklinik</div>
                </div>
            </div>
        </section>

        <div class="row g-4 mb-5">
            <!-- Poliklinik Ekleme Kartı -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-body p-4">
                        <h4 class="fw-bold text-primary mb-3"><i class="bi bi-hospital me-2"></i>Yeni Poliklinik Ekle</h4>
                        <form action="" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION[$csrf_anahtari]); ?>">
                            <input type="hidden" name="islem" value="poliklinik_ekle">
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
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION[$csrf_anahtari]); ?>">
                            <input type="hidden" name="islem" value="doktor_ekle">
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
                                    <input type="password" class="form-control rounded-3" name="sifre" minlength="8" required>
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
                            <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold">Doktor Kaydet</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <section class="card border-0 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                    <div>
                        <h2 class="h4 fw-bold mb-1"><i class="bi bi-hospital me-2 text-primary"></i>Kayıtlı Poliklinikler</h2>
                        <p class="text-muted small mb-0">Poliklinik bilgilerini düzenleyin veya kullanılmayan kayıtları silin.</p>
                    </div>
                    <span class="badge rounded-pill text-bg-light px-3 py-2"><?php echo count($poliklinikler); ?> poliklinik</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr><th>Poliklinik adı</th><th>Doktor sayısı</th><th class="text-end">İşlem</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($poliklinikler): ?>
                                <?php foreach ($poliklinikler as $p): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($p['poliklinik_adi']); ?></td>
                                        <td><?php echo (int) $p['doktor_sayisi']; ?></td>
                                        <td class="text-end text-nowrap">
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#poliklinikDuzenle<?php echo (int) $p['id']; ?>"><i class="bi bi-pencil me-1"></i>Düzenle</button>
                                            <form action="" method="POST" class="d-inline" onsubmit="return confirm('Bu polikliniği silmek istediğinize emin misiniz?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION[$csrf_anahtari]); ?>">
                                                <input type="hidden" name="islem" value="poliklinik_sil">
                                                <input type="hidden" name="poliklinik_id" value="<?php echo (int) $p['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" <?php echo (int) $p['doktor_sayisi'] > 0 ? 'disabled title="Önce bu polikliniğe bağlı doktorları taşıyın"' : ''; ?>><i class="bi bi-trash me-1"></i>Sil</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">Henüz poliklinik eklenmemiş.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php foreach ($poliklinikler as $p): ?>
                <div class="modal fade" id="poliklinikDuzenle<?php echo (int) $p['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0">
                            <form action="" method="POST">
                                <div class="modal-header">
                                    <h3 class="modal-title fs-5 fw-bold">Polikliniği düzenle</h3>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION[$csrf_anahtari]); ?>">
                                    <input type="hidden" name="islem" value="poliklinik_guncelle">
                                    <input type="hidden" name="poliklinik_id" value="<?php echo (int) $p['id']; ?>">
                                    <label class="form-label fw-semibold" for="poliklinikAdi<?php echo (int) $p['id']; ?>">Poliklinik adı</label>
                                    <input id="poliklinikAdi<?php echo (int) $p['id']; ?>" type="text" class="form-control" name="poliklinik_adi" value="<?php echo htmlspecialchars($p['poliklinik_adi']); ?>" required>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Vazgeç</button>
                                    <button type="submit" class="btn btn-primary">Değişiklikleri kaydet</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="card border-0 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                    <div>
                        <h2 class="h4 fw-bold mb-1"><i class="bi bi-person-vcard me-2 text-primary"></i>Kayıtlı Doktorlar</h2>
                        <p class="text-muted small mb-0">Doktor profillerini yönetin. Randevu geçmişi olan doktorlar silinemez.</p>
                    </div>
                    <span class="badge rounded-pill text-bg-light px-3 py-2"><?php echo count($doktorlar); ?> doktor</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr><th>Doktor</th><th>Poliklinik</th><th>E-posta</th><th>Randevu</th><th class="text-end">İşlem</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($doktorlar): ?>
                                <?php foreach ($doktorlar as $d): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($d['unvan'] . ' ' . $d['ad_soyad']); ?></div>
                                            <div class="small text-muted">T.C. <?php echo htmlspecialchars($d['tc_no']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($d['poliklinik_adi']); ?></td>
                                        <td><?php echo htmlspecialchars($d['eposta']); ?></td>
                                        <td><?php echo (int) $d['randevu_sayisi']; ?></td>
                                        <td class="text-end text-nowrap">
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#doktorDuzenle<?php echo (int) $d['id']; ?>"><i class="bi bi-pencil me-1"></i>Düzenle</button>
                                            <form action="" method="POST" class="d-inline" onsubmit="return confirm('Bu doktor hesabını silmek istediğinize emin misiniz?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION[$csrf_anahtari]); ?>">
                                                <input type="hidden" name="islem" value="doktor_sil">
                                                <input type="hidden" name="doktor_id" value="<?php echo (int) $d['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" <?php echo (int) $d['randevu_sayisi'] > 0 ? 'disabled title="Randevu geçmişi olan doktor silinemez"' : ''; ?>><i class="bi bi-trash me-1"></i>Sil</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">Henüz doktor eklenmemiş.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php foreach ($doktorlar as $d): ?>
                <div class="modal fade" id="doktorDuzenle<?php echo (int) $d['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content border-0">
                            <form action="" method="POST">
                                <div class="modal-header">
                                    <h3 class="modal-title fs-5 fw-bold">Doktor bilgilerini düzenle</h3>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION[$csrf_anahtari]); ?>">
                                    <input type="hidden" name="islem" value="doktor_guncelle">
                                    <input type="hidden" name="doktor_id" value="<?php echo (int) $d['id']; ?>">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label fw-semibold">Ad Soyad</label><input type="text" class="form-control" name="ad_soyad" value="<?php echo htmlspecialchars($d['ad_soyad']); ?>" required></div>
                                        <div class="col-md-6"><label class="form-label fw-semibold">Unvan</label><input type="text" class="form-control" name="unvan" value="<?php echo htmlspecialchars($d['unvan']); ?>" required></div>
                                        <div class="col-md-6"><label class="form-label fw-semibold">T.C. Kimlik No</label><input type="text" class="form-control" name="tc_no" value="<?php echo htmlspecialchars($d['tc_no']); ?>" maxlength="11" pattern="[0-9]{11}" required></div>
                                        <div class="col-md-6"><label class="form-label fw-semibold">E-posta</label><input type="email" class="form-control" name="eposta" value="<?php echo htmlspecialchars($d['eposta']); ?>" required></div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Poliklinik</label>
                                            <select class="form-select" name="poliklinik_id" required>
                                                <?php foreach ($poliklinikler as $p): ?>
                                                    <option value="<?php echo (int) $p['id']; ?>" <?php echo (int) $p['id'] === (int) $d['poliklinik_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['poliklinik_adi']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Vazgeç</button>
                                    <button type="submit" class="btn btn-primary">Değişiklikleri kaydet</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="card border-0 mb-4">
            <div class="card-body p-4">
                <div class="mb-3">
                    <h2 class="h4 fw-bold mb-1"><i class="bi bi-key me-2 text-primary"></i>Kullanıcı Şifresi Sıfırlama</h2>
                    <p class="text-muted small mb-0">Hasta veya doktor için yeni bir geçici şifre belirleyin ve kullanıcıyla paylaşın.</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Kullanıcı</th><th>Rol</th><th>T.C. Kimlik No</th><th>E-posta</th><th class="text-end">İşlem</th></tr></thead>
                        <tbody>
                            <?php if ($kullanicilar): ?>
                                <?php foreach ($kullanicilar as $k): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($k['ad_soyad']); ?></td>
                                        <td><?php echo $k['rol'] === 'hasta' ? 'Hasta' : 'Doktor'; ?></td>
                                        <td><?php echo htmlspecialchars($k['tc_no']); ?></td>
                                        <td><?php echo htmlspecialchars($k['eposta']); ?></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-outline-primary btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#sifreSifirlamaModal" data-kullanici-id="<?php echo (int) $k['id']; ?>" data-kullanici-adi="<?php echo htmlspecialchars($k['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-key me-1"></i>Şifre sıfırla</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">Sıfırlanabilecek hasta veya doktor hesabı bulunamadı.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal fade" id="sifreSifirlamaModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0">
                        <form action="" method="POST">
                            <div class="modal-header">
                                <h3 class="modal-title fs-5 fw-bold">Kullanıcı şifresini sıfırla</h3>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION[$csrf_anahtari]); ?>">
                                <input type="hidden" name="islem" value="sifre_sifirla">
                                <input type="hidden" name="kullanici_id" id="sifreSifirlamaKullaniciId">
                                <p class="text-muted">Yeni şifre belirlenecek hesap: <strong class="text-dark" id="sifreSifirlamaKullaniciAdi"></strong></p>
                                <label class="form-label fw-semibold" for="yeniSifre">Yeni geçici şifre</label>
                                <input id="yeniSifre" type="password" class="form-control" name="yeni_sifre" minlength="8" autocomplete="new-password" required>
                                <div class="form-text">Şifre en az 8 karakter olmalı; kullanıcıya güvenli biçimde iletin.</div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Vazgeç</button>
                                <button type="submit" class="btn btn-primary">Şifreyi sıfırla</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

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

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sifreSifirlamaModal = document.getElementById('sifreSifirlamaModal');
        sifreSifirlamaModal.addEventListener('show.bs.modal', (event) => {
            const dugme = event.relatedTarget;
            document.getElementById('sifreSifirlamaKullaniciId').value = dugme.dataset.kullaniciId;
            document.getElementById('sifreSifirlamaKullaniciAdi').textContent = dugme.dataset.kullaniciAdi;
            document.getElementById('yeniSifre').value = '';
        });
    </script>
</body>
</html>