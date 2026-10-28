<?php
session_start();
require_once 'baglanti.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'hasta') {
    header("Location: index.php");
    exit();
}

$hasta_id = $_SESSION['user_id'];

$sql = "SELECT r.*, d.unvan, k.ad_soyad as doktor_adi, p.poliklinik_adi 
        FROM randevular r 
        JOIN doktorlar d ON r.doktor_id = d.id 
        JOIN kullanicilar k ON d.kullanici_id = k.id 
        JOIN poliklinikler p ON d.poliklinik_id = p.id 
        WHERE r.hasta_id = :hasta_id 
        ORDER BY r.randevu_tarihi DESC, r.randevu_saati DESC";
$stmt = $db->prepare($sql);
$stmt->execute(['hasta_id' => $hasta_id]);
$randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasta Paneli - Doktor Randevu Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .mhrs-card-teal { background-color: #3b938d; color: white; transition: 0.3s; }
        .mhrs-card-teal:hover { background-color: #317b76; color: white; }
        .mhrs-card-red { background-color: #9b1c1c; color: white; transition: 0.3s; }
        .mhrs-card-red:hover { background-color: #7f1717; color: white; }
        .mhrs-card-dark { background-color: #2d1b4e; color: white; transition: 0.3s; }
        .mhrs-card-dark:hover { background-color: #23143c; color: white; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-white border-bottom shadow-sm py-2">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="hasta_panel.php">
                <i class="bi bi-hospital fs-3 me-2 text-danger"></i>
                <span class="text-dark">Doktor Randevu Sistemi</span>
            </a>
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle rounded-pill px-4" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-fill me-1"></i> <?php echo htmlspecialchars($_SESSION['ad_soyad']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person-badge me-2"></i>Bilgilerim</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="cikis.php"><i class="bi bi-box-arrow-right me-2"></i>Çıkış Yap</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 my-4">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card mhrs-card-teal border-0 rounded-4 p-3 mb-3 shadow-sm text-decoration-none">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-person-heart fs-2 me-3"></i>
                            <h5 class="card-title fw-bold mb-0">Aile Hekimi Randevusu Al</h5>
                        </div>
                        <p class="card-text small opacity-75 mb-0">Bağlı bulunduğunuz Aile Hekiminizden randevu alabilirsiniz.</p>
                    </div>
                </div>

                <a href="randevu_al.php" class="card mhrs-card-red border-0 rounded-4 p-3 mb-3 shadow-sm text-decoration-none">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-hospital fs-2 me-3"></i>
                            <h5 class="card-title fw-bold mb-0">Hastane Randevusu Al</h5>
                        </div>
                        <p class="card-text small opacity-75 mb-0">Hastanede bulunan kliniklerden randevu alabilirsiniz.</p>
                    </div>
                </a>

                <div class="card mhrs-card-dark border-0 rounded-4 p-3 shadow-sm text-decoration-none">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-activity fs-2 me-3"></i>
                            <h5 class="card-title fw-bold mb-0">Sağlıklı Hayat Merkezi Randevusu Al</h5>
                        </div>
                        <p class="card-text small opacity-75 mb-0">Sağlıklı Hayat Merkezlerinde bulunan kliniklerden randevu alabilirsiniz.</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-body p-4">
                        <ul class="nav nav-tabs border-bottom-0 mb-4" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold text-dark border-0 border-bottom border-primary border-3" id="aktif-tab" data-bs-toggle="tab" data-bs-target="#aktif-randevular" type="button">Randevularım</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link text-muted fw-semibold border-0" id="gecmis-tab" data-bs-toggle="tab" data-bs-target="#gecmis-randevular" type="button">Geçmiş Randevularım</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="aktif-randevular">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
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
                                            <?php if (count($randevular) > 0): ?>
                                                <?php foreach ($randevular as $randevu): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($randevu['poliklinik_adi']); ?></td>
                                                        <td><?php echo htmlspecialchars($randevu['unvan'] . ' ' . $randevu['doktor_adi']); ?></td>
                                                        <td><?php echo htmlspecialchars($randevu['randevu_tarihi']); ?></td>
                                                        <td><?php echo htmlspecialchars($randevu['randevu_saati']); ?></td>
                                                        <td><?php echo htmlspecialchars($randevu['sikayet'] ?: '-'); ?></td>
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
                                                            <?php if ($randevu['durum'] == 'Aktif'): ?>
                                                                <a href="randevu_iptal.php?id=<?php echo $randevu['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Randevuyu iptal etmek istediğinize emin misiniz?');">İptal Et</a>
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center py-5">
                                                        <div class="text-muted mb-2"><i class="bi bi-calendar-x fs-1"></i></div>
                                                        <p class="text-muted mb-0">Aktif randevunuz bulunmamaktadır.</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="gecmis-randevular">
                                <p class="text-center text-muted py-5">Geçmiş randevunuz bulunmamaktadır.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="neyimVarModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-primary"><i class="bi bi-info-circle-fill me-2"></i>Bilgi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="text-muted">Şikayetlerinizi girerek muhtemel tanınızı ve gideceğiniz polikliniği öğrenerek randevu almak ister misiniz?</p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" data-bs-dismiss="modal">Kapat</button>
                    <button type="button" class="btn btn-primary px-4 rounded-pill" data-bs-dismiss="modal">Neyim Var?</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            var myModal = new bootstrap.Modal(document.getElementById('neyimVarModal'));
            myModal.show();
        });
    </script>
</body>
</html>