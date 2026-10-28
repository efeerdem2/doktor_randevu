<?php
session_start();
require_once 'baglanti.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'hasta') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $hasta_id = $_SESSION['user_id'];
    $doktor_id = $_POST['doktor_id'];
    $randevu_tarihi = $_POST['randevu_tarihi'];
    $randevu_saati = $_POST['randevu_saati'];
    $sikayet = trim($_POST['sikayet']);

    try {
        $sql = "INSERT INTO randevular (hasta_id, doktor_id, randevu_tarihi, randevu_saati, sikayet, durum) 
                VALUES (:hasta_id, :doktor_id, :randevu_tarihi, :randevu_saati, :sikayet, 'Aktif')";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'hasta_id' => $hasta_id,
            'doktor_id' => $doktor_id,
            'randevu_tarihi' => $randevu_tarihi,
            'randevu_saati' => $randevu_saati,
            'sikayet' => $sikayet
        ]);

        echo "<script>alert('Randevunuz başarıyla oluşturuldu!'); window.location.href='hasta_panel.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Bu tarih ve saatte bu doktora zaten bir randevu alınmış veya bir hata oluştu!'); window.location.href='randevu_al.php';</script>";
    }
}
?>