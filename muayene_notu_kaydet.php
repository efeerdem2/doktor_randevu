<?php
session_start();
require_once 'baglanti.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'doktor') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $randevu_id = $_POST['randevu_id'];
    $muayene_notu = trim($_POST['muayene_notu']);

    try {
        $sql = "UPDATE randevular SET muayene_notu = :muayene_notu, durum = 'Tamamlandi' WHERE id = :randevu_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'muayene_notu' => $muayene_notu,
            'randevu_id' => $randevu_id
        ]);

        echo "<script>alert('Muayene notu başarıyla kaydedildi.'); window.location.href='doktor_panel.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Bir hata oluştu!'); window.location.href='doktor_panel.php';</script>";
    }
}
?>