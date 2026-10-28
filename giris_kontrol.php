<?php
session_start();
require_once 'baglanti.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tc_veya_eposta = trim($_POST['tc_veya_eposta']);
    $sifre = $_POST['sifre'];

    if (empty($tc_veya_eposta) || empty($sifre)) {
        die("Lütfen tüm alanları doldurun. <a href='index.php'>Geri dön</a>");
    }

    // Kullanıcıyı T.C. veya E-posta ile bul
    $sql = "SELECT * FROM kullanicilar WHERE tc_no = :giris OR eposta = :giris";
    $stmt = $db->prepare($sql);
    $stmt->execute(['giris' => $tc_veya_eposta]);
    $kullanici = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($kullanici && password_verify($sifre, $kullanici['sifre'])) {
        // Oturum değişkenlerini ata
        $_SESSION['user_id'] = $kullanici['id'];
        $_SESSION['ad_soyad'] = $kullanici['ad_soyad'];
        $_SESSION['rol'] = $kullanici['rol'];

        // Role göre yönlendirme yap
        if ($kullanici['rol'] == 'admin') {
            header("Location: admin_panel.php");
        } elseif ($kullanici['rol'] == 'doktor') {
            header("Location: doktor_panel.php");
        } else {
            header("Location: hasta_panel.php");
        }
        exit();
    } else {
        echo "<script>alert('Hatalı T.C./E-posta veya şifre!'); window.location.href='index.php';</script>";
    }
}
?>