<?php
// Oturumu başlatıyoruz
session_start();

// Veritabanı bağlantımızı dahil ediyoruz
require_once 'baglanti.php';

// Kullanıcı giriş yapmış mı ve rolü hasta mı kontrol ediyoruz
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'hasta') {
    header("Location: index.php");
    exit();
}

// Form gönderildiyse randevu kayıt işlemlerini başlatıyoruz
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $hasta_id = $_SESSION['user_id'];
    $doktor_id = filter_var($_POST['doktor_id'] ?? null, FILTER_VALIDATE_INT);
    $poliklinik_id = filter_var($_POST['poliklinik_id'] ?? null, FILTER_VALIDATE_INT);
    $randevu_tarihi = isset($_POST['randevu_tarihi']) && is_string($_POST['randevu_tarihi']) ? $_POST['randevu_tarihi'] : null;
    $randevu_saati = isset($_POST['randevu_saati']) && is_string($_POST['randevu_saati']) ? $_POST['randevu_saati'] : null;
    $sikayet = isset($_POST['sikayet']) && is_string($_POST['sikayet']) ? trim($_POST['sikayet']) : '';

    // Boş alan kontrolü yapıyoruz
    if (empty($doktor_id) || empty($poliklinik_id) || empty($randevu_tarihi) || empty($randevu_saati)) {
        echo "<script>alert('Lütfen tüm zorunlu alanları doldurun!'); window.history.back();</script>";
        exit();
    }

    $tarih = DateTimeImmutable::createFromFormat('!Y-m-d', $randevu_tarihi);
    if (!$tarih || $tarih->format('Y-m-d') !== $randevu_tarihi || $randevu_tarihi < date('Y-m-d')) {
        echo "<script>alert('Lütfen bugünden itibaren geçerli bir randevu tarihi seçin.'); window.history.back();</script>";
        exit();
    }

    $doktor_stmt = $db->prepare("SELECT id FROM doktorlar WHERE id = :doktor_id AND poliklinik_id = :poliklinik_id");
    $doktor_stmt->execute([
        'doktor_id' => $doktor_id,
        'poliklinik_id' => $poliklinik_id
    ]);
    if (!$doktor_stmt->fetchColumn()) {
        echo "<script>alert('Seçilen doktor bu poliklinikte bulunmuyor. Lütfen yeniden seçim yapın.'); window.history.back();</script>";
        exit();
    }

    $gun_no = (int) $tarih->format('N');
    if ($gun_no >= 6) {
        echo "<script>alert('Hafta sonu (Cumartesi ve Pazar) günlerinde hastaneler kapalı olduğundan randevu alınamaz. Lütfen hafta içi müsait bir gün seçin!'); window.history.back();</script>";
        exit();
    }

    if ($randevu_tarihi === date('Y-m-d') && $randevu_saati <= date('H:i')) {
        echo "<script>alert('Geçmiş bir saat için randevu alınamaz. Lütfen ileri bir saat seçin.'); window.history.back();</script>";
        exit();
    }

    $izin_verilen_saatler = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:30', '14:00', '14:30', '15:00', '15:30'];
    if (!in_array($randevu_saati, $izin_verilen_saatler, true)) {
        echo "<script>alert('Lütfen listelenen randevu saatlerinden birini seçin.'); window.history.back();</script>";
        exit();
    }

    // GERÇEKÇİ KONTROL 2: Aynı doktorun aynı tarih ve saatte başka bir aktif randevusu var mı kontrol ediyoruz (Çakışma önleme)
    $kontrol_sql = "SELECT id FROM randevular WHERE doktor_id = :doktor_id AND randevu_tarihi = :tarih AND randevu_saati = :saat AND durum = 'Aktif'";
    $kontrol_stmt = $db->prepare($kontrol_sql);
    $kontrol_stmt->execute([
        'doktor_id' => $doktor_id,
        'tarih' => $randevu_tarihi,
        'saat' => $randevu_saati
    ]);

    if ($kontrol_stmt->rowCount() > 0) {
        echo "<script>alert('Seçilen doktorun bu tarih ve saatte dolu bir randevusu bulunmaktadır. Lütfen başka bir müsait saat seçin.'); window.history.back();</script>";
        exit();
    }

    // Randevuyu veritabanına 'Aktif' durumuyla kaydediyoruz
    $ekle_sql = "INSERT INTO randevular (hasta_id, doktor_id, randevu_tarihi, randevu_saati, sikayet, durum) VALUES (:hasta_id, :doktor_id, :tarih, :saat, :sikayet, 'Aktif')";
    $ekle_stmt = $db->prepare($ekle_sql);
    $sonuc = $ekle_stmt->execute([
        'hasta_id' => $hasta_id,
        'doktor_id' => $doktor_id,
        'tarih' => $randevu_tarihi,
        'saat' => $randevu_saati,
        'sikayet' => $sikayet
    ]);

    if ($sonuc) {
        echo "<script>alert('Randevunuz başarıyla oluşturuldu!'); window.location.href='hasta_panel.php';</script>";
    } else {
        echo "<script>alert('Randevu oluşturulurken bir hata oluştu.'); window.history.back();</script>";
    }
}
?>