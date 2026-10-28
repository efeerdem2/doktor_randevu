<?php
require_once 'baglanti.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad_soyad = trim($_POST['ad_soyad']);
    $tc_no = trim($_POST['tc_no']);
    $eposta = trim($_POST['eposta']);
    $sifre = $_POST['sifre'];

    // Boş alan kontrolü
    if (empty($ad_soyad) || empty($tc_no) || empty($eposta) || empty($sifre)) {
        die("Lütfen tüm alanları doldurun. <a href='index.php'>Geri dön</a>");
    }

    // Şifreyi güvenli bir şekilde şifrele (Hashleme)
    $hashed_sifre = password_hash($sifre, PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO kullanicilar (ad_soyad, tc_no, eposta, sifre, rol) VALUES (:ad_soyad, :tc_no, :eposta, :sifre, 'hasta')";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'ad_soyad' => $ad_soyad,
            'tc_no' => $tc_no,
            'eposta' => $eposta,
            'sifre' => $hashed_sifre
        ]);

        echo "<script>alert('Kayıt başarıyla oluşturuldu. Giriş yapabilirsiniz.'); window.location.href='index.php';</script>";
    } catch (PDOException $e) {
        // T.C. veya E-posta zaten kayıtlıysa hata yakalama
        echo "<script>alert('Bu T.C. Kimlik No veya E-posta zaten sistemde kayıtlı!'); window.location.href='index.php';</script>";
    }
}
?>