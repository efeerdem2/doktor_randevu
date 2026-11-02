<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doktor Randevu Sistemi - Giriş</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="uygulama.css">
    <script src="tema.js" defer></script>
    <style>
        body, html {
            min-height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            min-height: 100vh;
        }
        .right-bg > * {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>

<div class="container-fluid min-vh-100">
    <div class="row min-vh-100">
        
        <!-- SOL TARAF: Form Alanı -->
        <div class="col-lg-5 d-flex flex-column justify-content-between p-5 bg-white">
            <div>
                <!-- Üst Logo / İkon -->
                <div class="text-center mb-4">
                    <div class="text-primary fs-1 mb-2">
                        <i class="bi bi-hospital-fill"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Doktor Randevu Sistemi</h3>
                    <p class="text-muted small">Randevu ve sağlık bilgileriniz tek ekranda.</p>
                </div>

                <!-- Giriş / Kayıt Sekme Butonları -->
                <ul class="nav nav-pills nav-fill mb-4 bg-light p-1 rounded-pill" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill fw-semibold" id="pills-giris-tab" data-bs-toggle="pill" data-bs-target="#pills-giris" type="button" role="tab">Giriş Yap</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-semibold text-muted" id="pills-kayit-tab" data-bs-toggle="pill" data-bs-target="#pills-kayit" type="button" role="tab">Kayıt Ol</button>
                    </li>
                </ul>

                <!-- Sekme İçerikleri -->
                <div class="tab-content" id="pills-tabContent">
                    
                    <!-- GİRİŞ YAP FORMU -->
                    <div class="tab-pane fade show active" id="pills-giris" role="tabpanel">
                        <form action="giris_kontrol.php" method="POST">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="giris_tc" name="tc_veya_eposta" placeholder="T.C. Kimlik No veya E-posta" required>
                                <label for="giris_tc"><i class="bi bi-person me-2"></i>T.C. Kimlik No veya E-posta</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="giris_sifre" name="sifre" placeholder="Şifre" required>
                                <label for="giris_sifre"><i class="bi bi-lock me-2"></i>Şifre</label>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="small text-muted" title="Şifre yenileme için sistem yöneticinize başvurun.">Şifre yenileme için yöneticinize başvurun.</span>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-sm">Giriş Yap</button>
                        </form>
                    </div>

                    <!-- KAYIT OL FORMU -->
                    <div class="tab-pane fade" id="pills-kayit" role="tabpanel">
                        <form action="kayit_islem.php" method="POST">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="kayit_ad" name="ad_soyad" placeholder="Ad Soyad" required>
                                <label for="kayit_ad">Ad Soyad</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="kayit_tc" name="tc_no" maxlength="11" placeholder="T.C. Kimlik No" required>
                                <label for="kayit_tc">T.C. Kimlik No</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="kayit_eposta" name="eposta" placeholder="E-posta Adresi" required>
                                <label for="kayit_eposta">E-posta Adresi</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="kayit_sifre" name="sifre" placeholder="Şifre" required>
                                <label for="kayit_sifre">Şifre</label>
                            </div>
                            <button type="submit" class="btn btn-success w-100 py-3 fw-bold rounded-3 shadow-sm">Kayıt Ol</button>
                        </form>
                    </div>

                </div>
            </div>

            <!-- Alt Telif -->
            <div class="text-center text-muted small mt-4">
                &copy; 2026 Doktor Randevu Sistemi. Tüm hakları saklıdır.
            </div>
        </div>

        <!-- SAĞ TARAF: Görsel ve Bilgilendirme Alanı -->
        <div class="col-lg-7 right-bg d-none d-lg-flex flex-column justify-content-between text-white p-5">
            <div></div> <!-- Üst boşluk -->
            <div>
                <h1 class="display-5 fw-bold mb-3">Sağlık yolculuğunuz<br>daha kolay başlasın.</h1>
                <p class="lead text-light mb-5 w-75">Randevularınızı tek bir yerden planlayın, doktorunuzla ilgili bilgileri takip edin ve ihtiyaç duyduğunuzda doğru polikliniğe ulaşın.</p>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center bg-white bg-opacity-10 p-3 rounded-3">
                            <div class="fs-2 me-3 text-warning"><i class="bi bi-calendar-check"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold">Kolay Randevu</h6>
                                <small class="text-light">Saniyeler içinde planlayın</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center bg-white bg-opacity-10 p-3 rounded-3">
                            <div class="fs-2 me-3 text-info"><i class="bi bi-calendar2-week"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold">Düzenli Takip</h6>
                                <small class="text-light">Randevularınız tek ekranda</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div></div> <!-- Alt boşluk -->
        </div>

    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>