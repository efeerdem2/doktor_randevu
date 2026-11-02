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
$sql_doktor = "SELECT d.id, d.poliklinik_id, d.unvan, k.ad_soyad
               FROM doktorlar d
               JOIN kullanicilar k ON d.kullanici_id = k.id
               ORDER BY k.ad_soyad";
$doktorlar = $db->query($sql_doktor)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Al - Doktor Randevu Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="uygulama.css">
    <script src="tema.js" defer></script>
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg py-2">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="hasta_panel.php">
                <i class="bi bi-heart-pulse fs-3 me-2"></i>
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
                            <h1 class="h3 fw-bold text-dark">Randevunu planla</h1>
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
                                <select class="form-select rounded-3 py-2" id="doktor_id" name="doktor_id" required disabled>
                                    <option value="" selected disabled>Önce poliklinik seçiniz...</option>
                                    <?php foreach ($doktorlar as $d): ?>
                                        <option value="<?php echo (int) $d['id']; ?>" data-poliklinik="<?php echo (int) $d['poliklinik_id']; ?>">
                                            <?php echo htmlspecialchars($d['unvan'] . ' ' . $d['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
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
                                <select class="form-select rounded-3 py-2" id="randevu_saati" name="randevu_saati" required disabled>
                                    <option value="" selected disabled>Saat seçiniz...</option>
                                </select>
                                <div id="musaitlik_mesaji" class="form-text" role="status" aria-live="polite">Önce poliklinik, doktor ve tarih seçiniz.</div>
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
    <script>
        const poliklinikSelect = document.getElementById('poliklinik_id');
        const doktorSelect = document.getElementById('doktor_id');
        const tarihInput = document.getElementById('randevu_tarihi');
        const saatSelect = document.getElementById('randevu_saati');
        const musaitlikMesaji = document.getElementById('musaitlik_mesaji');
        const randevuFormu = document.querySelector('form[action="randevu_kaydet.php"]');
        const doktorOptions = Array.from(doktorSelect.options).slice(1);
        const randevuSaatleri = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:30', '14:00', '14:30', '15:00', '15:30'];
        let musaitlikYuklendi = false;
        let musaitlikIstegi = null;

        function saatleriSifirla(mesaj) {
            saatSelect.replaceChildren(new Option('Saat seçiniz...', '', true, true));
            saatSelect.disabled = true;
            musaitlikYuklendi = false;
            musaitlikMesaji.textContent = mesaj;
            if (musaitlikIstegi) {
                musaitlikIstegi.abort();
                musaitlikIstegi = null;
            }
        }

        async function musaitSaatleriYukle() {
            saatleriSifirla('Müsait saatler kontrol ediliyor...');
            const doktorId = doktorSelect.value;
            const tarih = tarihInput.value;

            if (!doktorId || !tarih) {
                musaitlikMesaji.textContent = 'Önce poliklinik, doktor ve tarih seçiniz.';
                return;
            }

            const secilenGun = new Date(`${tarih}T00:00:00`).getDay();
            if (secilenGun === 0 || secilenGun === 6) {
                musaitlikMesaji.textContent = 'Hafta sonu için randevu alınamaz. Hafta içi bir gün seçiniz.';
                return;
            }

            const istek = new AbortController();
            musaitlikIstegi = istek;

            try {
                const params = new URLSearchParams({ doktor_id: doktorId, randevu_tarihi: tarih });
                const yanit = await fetch(`randevu_musaitlik.php?${params}`, { signal: istek.signal });
                const sonuc = await yanit.json();

                if (!yanit.ok) {
                    throw new Error(sonuc.error || 'Müsaitlik bilgisi alınamadı.');
                }

                if (doktorSelect.value !== doktorId || tarihInput.value !== tarih) {
                    return;
                }

                const doluSaatler = new Set(sonuc.dolu_saatler);
                const bugunTarihi = new Date();
                const bugun = `${bugunTarihi.getFullYear()}-${String(bugunTarihi.getMonth() + 1).padStart(2, '0')}-${String(bugunTarihi.getDate()).padStart(2, '0')}`;
                const simdikiSaat = new Date().toTimeString().slice(0, 5);
                let musaitSaatSayisi = 0;
                saatSelect.replaceChildren(new Option('Saat seçiniz...', '', true, true));

                randevuSaatleri.forEach((saat) => {
                    const dolu = doluSaatler.has(saat);
                    const zamaniGecmis = tarih === bugun && saat <= simdikiSaat;
                    const option = new Option(
                        `${saat}${dolu ? ' - Dolu' : zamaniGecmis ? ' - Saati geçti' : ''}`,
                        saat
                    );
                    option.disabled = dolu || zamaniGecmis;
                    saatSelect.add(option);
                    if (!option.disabled) {
                        musaitSaatSayisi++;
                    }
                });

                saatSelect.disabled = musaitSaatSayisi === 0;
                musaitlikYuklendi = true;
                musaitlikMesaji.textContent = musaitSaatSayisi
                    ? 'Dolu saatler seçilemez; uygun bir saat seçiniz.'
                    : 'Bu tarih için müsait saat bulunmuyor. Başka bir tarih deneyiniz.';
            } catch (hata) {
                if (hata.name === 'AbortError') {
                    return;
                }
                saatleriSifirla(hata.message);
            } finally {
                if (musaitlikIstegi === istek) {
                    musaitlikIstegi = null;
                }
            }
        }

        poliklinikSelect.addEventListener('change', () => {
            const poliklinikId = poliklinikSelect.value;
            const doktorSayisi = doktorOptions.filter((option) => option.dataset.poliklinik === poliklinikId).length;

            doktorSelect.selectedIndex = 0;
            doktorSelect.options[0].textContent = doktorSayisi
                ? 'Doktor seçiniz...'
                : 'Bu poliklinikte doktor bulunmuyor.';
            doktorOptions.forEach((option) => {
                option.hidden = option.dataset.poliklinik !== poliklinikId;
            });
            doktorSelect.disabled = doktorSayisi === 0;
            saatleriSifirla('Doktor seçtikten sonra tarih ve müsait saatleri görebilirsiniz.');
        });

        doktorSelect.addEventListener('change', musaitSaatleriYukle);
        tarihInput.addEventListener('change', musaitSaatleriYukle);

        randevuFormu.addEventListener('submit', (event) => {
            const seciliSaat = saatSelect.selectedOptions[0];
            if (!musaitlikYuklendi || !seciliSaat || seciliSaat.disabled) {
                event.preventDefault();
                musaitlikMesaji.textContent = 'Randevu almadan önce müsait saat listesinden bir saat seçiniz.';
                saatSelect.focus();
            }
        });
    </script>
</body>
</html>