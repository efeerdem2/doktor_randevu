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

$tavsiye_poliklinik = "";
$mesaj = "";

// Form gönderildiğinde çalışacak olan akıllı analiz bloğu
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sikayet_gonder'])) {
    $sikayet = mb_strtolower(trim($_POST['sikayet']), 'UTF-8');
    
    // Basit anahtar kelime eşleştirme mantığı ile poliklinik önerisi
    if (strpos($sikayet, 'bas') !== false || strpos($sikayet, 'agri') !== false || strpos($sikayet, 'bas donmesi') !== false) {
        $tavsiye_poliklinik = "Nöroloji veya Dahiliye";
        $mesaj = "Şikayetlerinize göre **Nöroloji** veya **İç Hastalıkları (Dahiliye)** polikliniğine başvurmanız önerilir.";
    } elseif (strpos($sikayet, 'kalp') !== false || strpos($sikayet, 'gogus') !== false || strpos($sikayet, 'nefes') !== false) {
        $tavsiye_poliklinik = "Kardiyoloji veya Göğüs Hastalıkları";
        $mesaj = "Şikayetlerinize göre **Kardiyoloji** veya **Göğüs Hastalıkları** polikliniğine başvurmanız önerilir.";
    } elseif (strpos($sikayet, 'dis') !== false || strpos($sikayet, 'agiz') !== false || strpos($sikayet, 'dis eti') !== false) {
        $tavsiye_poliklinik = "Diş Hekimliği";
        $mesaj = "Şikayetlerinize göre **Diş Hekimliği** polikliniğine başvurmanız önerilir.";
    } elseif (strpos($sikayet, 'goz') !== false || strpos($sikayet, 'bulan') !== false || strpos($sikayet, 'yanma') !== false) {
        $tavsiye_poliklinik = "Göz Hastalıkları";
        $mesaj = "Şikayetlerinize göre **Göz Hastalıkları** polikliniğine başvurmanız önerilir.";
    } else {
        $tavsiye_poliklinik = "Genel Cerrahi veya Dahiliye";
        $mesaj = "Belirttiğiniz şikayetler için genel bir değerlendirme adına **İç Hastalıkları (Dahiliye)** polikliniğini seçebilirsiniz.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neyim Var? - Doktor Randevu Sistemi</title>
    <!-- Bootstrap 5 CSS Kütüphanesini Dahil Ediyoruz -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap İkonları -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="uygulama.css">
    <script src="tema.js" defer></script>
    <style>
        /* Sayfanın tamamını dikey ve yatay olarak ortalamak için flexbox yapısı */
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Kart tasarımı için modern gölge ve köşe yumuşatma */
        .custom-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <!-- Şık ve Gölgelendirilmiş Ana Kart -->
                <div class="card custom-card bg-white">
                    <div class="card-header bg-primary text-white p-4">
                        <h4 class="mb-0 fw-bold"><i class="bi bi-search me-2"></i>Neyim Var? - Akıllı Poliklinik Yönlendirici</h4>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted mb-4">Lütfen şikayetlerinizi kısaca aşağıya yazın, sistem size en uygun polikliniği anında önersin.</p>
                        
                        <!-- Şikayet Giriş Formu -->
                        <form method="POST" action="">
                            <div class="mb-3">
                                <textarea name="sikayet" class="form-control rounded-3 p-3" rows="4" placeholder="Örn: Baş ağrısı ve göz çevresinde yanma hissi var..." required><?php echo isset($_POST['sikayet']) ? htmlspecialchars($_POST['sikayet']) : ''; ?></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" name="sikayet_gonder" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold">Analiz Et</button>
                                <a href="hasta_panel.php" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-semibold">Hasta Paneline Dön</a>
                            </div>
                        </form>

                        <!-- Yapay Zeka / Kelime Analiz Sonucu Bildirimi -->
                        <?php if (!empty($mesaj)): ?>
                            <div class="alert alert-success mt-4 rounded-4 border-0 shadow-sm p-3">
                                <h5 class="fw-bold text-success mb-2"><i class="bi bi-check-circle-fill me-2"></i>Analiz Sonucu:</h5>
                                <p class="mb-3 text-dark"><?php echo $mesaj; ?></p>
                                <a href="randevu_al.php" class="btn btn-success btn-sm px-4 rounded-pill fw-semibold">Randevu seçeneklerini görüntüle</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>