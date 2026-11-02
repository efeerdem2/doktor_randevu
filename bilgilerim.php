<?php
// Oturumu başlatıyoruz ki kullanıcının kim olduğunu bilelim
session_start();

// Veritabanı bağlantı dosyamızı dahil ediyoruz
require_once 'baglanti.php';

// Oturum kontrolü: Kullanıcı giriş yapmış mı ve rolü hasta mı?
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'hasta') {
    header("Location: index.php");
    exit();
}

$hasta_id = $_SESSION['user_id'];
$mesaj = "";
$hata = "";

// Form gönderildiğinde (Bilgiler güncellenmek istendiğinde) çalışacak blok
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad_soyad = trim($_POST['ad_soyad']);
    $eposta = trim($_POST['eposta']);
    
    if (!empty($ad_soyad) && !empty($eposta)) {
        // Kullanıcı bilgilerini güncelleyen SQL sorgusu
        $update_sql = "UPDATE kullanicilar SET ad_soyad = :ad_soyad, eposta = :eposta WHERE id = :id";
        $update_stmt = $db->prepare($update_sql);
        $sonuc = $update_stmt->execute([
            'ad_soyad' => $ad_soyad,
            'eposta' => $eposta,
            'id' => $hasta_id
        ]);

        if ($sonuc) {
            // Oturumdaki ismi de güncelliyoruz ki navbar anında değişsin
            $_SESSION['ad_soyad'] = $ad_soyad;
            $mesaj = "Bilgileriniz başarıyla güncellendi.";
        } else {
            $hata = "Güncelleme sırasında bir hata oluştu.";
        }
    } else {
        $hata = "Lütfen tüm alanları doldurun.";
    }
}

// Kullanıcının güncel bilgilerini veritabanından çekiyoruz
$sql = "SELECT * FROM kullanicilar WHERE id = :id";
$stmt = $db->prepare($sql);
$stmt->execute(['id' => $hasta_id]);
$kullanici = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilgilerim - Doktor Randevu Sistemi</title>
    <!-- Bootstrap 5 CSS Kütüphanesi -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap İkonları -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="uygulama.css">
    <script src="tema.js" defer></script>
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .profile-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="card profile-card bg-white p-4">
                    <div class="card-body">
                        <!-- Başlık ve İkon -->
                        <div class="text-center mb-4">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                <i class="bi bi-person-badge fs-1"></i>
                            </div>
                            <h3 class="fw-bold text-dark">Profil Bilgilerim</h3>
                            <p class="text-muted small">Kişisel bilgilerinizi buradan görüntüleyebilir ve güncelleyebilirsiniz.</p>
                        </div>

                        <!-- Başarı veya Hata Mesajları -->
                        <?php if (!empty($mesaj)): ?>
                            <div class="alert alert-success py-2 text-center"><?php echo $mesaj; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($hata)): ?>
                            <div class="alert alert-danger py-2 text-center"><?php echo $hata; ?></div>
                        <?php endif; ?>

                        <!-- Bilgi Güncelleme Formu -->
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary">T.C. Kimlik Numarası</label>
                                <!-- T.C. Kimlik numarası güvenlik ve tutarlılık açısından değiştirilemez (readonly) yapılmıştır -->
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($kullanici['tc_no']); ?>" readonly disabled>
                                <div class="form-text">T.C. Kimlik numarası sistem üzerinden değiştirilemez.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary">Ad Soyad</label>
                                <input type="text" name="ad_soyad" class="form-control" value="<?php echo htmlspecialchars($kullanici['ad_soyad']); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold text-secondary">E-Posta Adresi</label>
                                <input type="email" name="eposta" class="form-control" value="<?php echo htmlspecialchars($kullanici['eposta']); ?>" required>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold">Değişiklikleri Kaydet</button>
                                <a href="hasta_panel.php" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-semibold">Panele Dön</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>