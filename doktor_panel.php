<?php
session_start();
require_once 'baglanti.php';

// Oturum ve rol kontrolü (Doktor değilse giriş sayfasına at)
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'doktor') {
    header("Location: index.php");
    exit();
}

// Giriş yapan kullanıcının doktor tablosundaki ID'sini bulalım
$kullanici_id = $_SESSION['user_id'];
$stmt_doktor = $db->prepare("SELECT id, unvan FROM doktorlar WHERE kullanici_id = :kullanici_id");
$stmt_doktor->execute(['kullanici_id' => $kullanici_id]);
$doktor = $stmt_doktor->fetch(PDO::FETCH_ASSOC);

if (!$doktor) {
    die("Bu kullanıcıya ait doktor profili bulunamadı! Lütfen admin veya veritabanı üzerinden doktorlar tablosuna bağlayın.");
}

$doktor_id = $doktor['id'];

// Bu doktora ait randevuları ve hasta bilgilerini çekelim
$sql = "SELECT r.*, k.ad_soyad as hasta_adi, k.tc_no 
        FROM randevular r 
        JOIN kullanicilar k ON r.hasta_id = k.id 
        WHERE r.doktor_id = :doktor_id 
        ORDER BY r.randevu_tarihi DESC, r.randevu_saati DESC";
$stmt = $db->prepare($sql);
$stmt->execute(['doktor_id' => $doktor_id]);
$randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doktor Paneli - Doktor Randevu Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="doktor_panel.php"><i class="bi bi-hospital me-2"></i>Doktor Paneli</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3"><i class="bi bi-person-badge me-1"></i> <?php echo htmlspecialchars($doktor['unvan'] . ' ' . $_SESSION['ad_soyad']); ?></span>
                <a href="cikis.php" class="btn btn-outline-light btn-sm">Çıkış Yap</a>
            </div>
        </div>
    </nav>

    <!-- İçerik -->
    <div class="container my-5">
        <div class="row mb-4">
            <div class="col-md-12">
                <h3 class="fw-bold text-dark">Gelen Randevular</h3>
                <p class="text-muted">Size alınan randevuları listeleyebilir ve muayene sonrası teşhis/not girebilirsiniz.</p>
            </div>
        </div>

        <div class="card shadow border-0 rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Hasta T.C.</th>
                                <th>Hasta Adı Soyadı</th>
                                <th>Tarih</th>
                                <th>Saat</th>
                                <th>Şikayet</th>
                                <th>Muayene Notu</th>
                                <th>Durum</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($randevular) > 0): ?>
                                <?php foreach ($randevular as $randevu): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($randevu['tc_no']); ?></td>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($randevu['hasta_adi']); ?></td>
                                        <td><?php echo htmlspecialchars($randevu['randevu_tarihi']); ?></td>
                                        <td><?php echo htmlspecialchars($randevu['randevu_saati']); ?></td>
                                        <td><?php echo htmlspecialchars($randevu['sikayet'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($randevu['muayene_notu'] ?: 'Henüz girilmedi'); ?></td>
                                        <td>
                                            <?php if ($randevu['durum'] == 'Aktif'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php elseif ($randevu['durum'] == 'Iptal'): ?>
                                                <span class="badge bg-danger">İptal</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Tamamlandı</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- Muayene Notu Ekleme Butonu (Modal Tetikleyici) -->
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#muayeneModal<?php echo $randevu['id']; ?>">
                                                Muayene Notu Gir
                                            </button>

                                            <!-- Muayene Notu Modal -->
                                            <div class="modal fade" id="muayeneModal<?php echo $randevu['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content rounded-4 border-0">
                                                        <div class="modal-header bg-primary text-white">
                                                            <h5 class="modal-title fw-bold">Muayene Notu ve Teşhis</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form action="muayene_notu_kaydet.php" method="POST">
                                                            <div class="modal-body p-4">
                                                                <input type="hidden" name="randevu_id" value="<?php echo $randevu['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold">Hasta: <?php echo htmlspecialchars($randevu['hasta_adi']); ?></label>
                                                                    <textarea class="form-control rounded-3" name="muayene_notu" rows="4" placeholder="Teşhis, tedavi ve reçete detaylarını yazınız..." required><?php echo htmlspecialchars($randevu['muayene_notu']); ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-0 pb-3 px-4">
                                                                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Kapat</button>
                                                                <button type="submit" class="btn btn-primary rounded-pill px-4">Kaydet</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">Size atanmış herhangi bir randevu bulunmamaktadır.</td>
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