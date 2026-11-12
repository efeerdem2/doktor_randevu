<?php
session_start();
require_once 'baglanti.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'doktor') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: doktor_panel.php");
    exit();
}

$randevu_id_girdisi = $_POST['randevu_id'] ?? null;
$randevu_id = is_string($randevu_id_girdisi) ? filter_var($randevu_id_girdisi, FILTER_VALIDATE_INT) : false;
$muayene_notu_girdisi = $_POST['muayene_notu'] ?? '';
$muayene_notu = is_string($muayene_notu_girdisi) ? trim($muayene_notu_girdisi) : '';
if (!$randevu_id || $muayene_notu === '') {
    header("Location: doktor_panel.php?muayene=gecersiz#randevular");
    exit();
}

$doktor_stmt = $db->prepare("SELECT id FROM doktorlar WHERE kullanici_id = :kullanici_id");
$doktor_stmt->execute(['kullanici_id' => (int) $_SESSION['user_id']]);
$doktor_id = $doktor_stmt->fetchColumn();

if (!$doktor_id) {
    http_response_code(403);
    exit('Doktor profili bulunamadı.');
}

$randevu_stmt = $db->prepare(
    "SELECT durum FROM randevular WHERE id = :randevu_id AND doktor_id = :doktor_id"
);
$randevu_stmt->execute([
    'randevu_id' => $randevu_id,
    'doktor_id' => $doktor_id,
]);
$randevu = $randevu_stmt->fetch(PDO::FETCH_ASSOC);

if (!$randevu) {
    header("Location: doktor_panel.php?muayene=bulunamadi#randevular");
    exit();
}

if ($randevu['durum'] === 'Iptal') {
    header("Location: doktor_panel.php?muayene=iptal#randevular");
    exit();
}

try {
    $sql = "UPDATE randevular
            SET muayene_notu = :muayene_notu, durum = 'Tamamlandi'
            WHERE id = :randevu_id
              AND doktor_id = :doktor_id
              AND durum <> 'Iptal'";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'muayene_notu' => $muayene_notu,
        'randevu_id' => $randevu_id,
        'doktor_id' => $doktor_id,
    ]);

    if ($stmt->rowCount() === 0) {
        $durum_stmt = $db->prepare(
            "SELECT durum FROM randevular WHERE id = :randevu_id AND doktor_id = :doktor_id"
        );
        $durum_stmt->execute([
            'randevu_id' => $randevu_id,
            'doktor_id' => $doktor_id,
        ]);
        if ($durum_stmt->fetchColumn() === 'Iptal') {
            header("Location: doktor_panel.php?muayene=iptal#randevular");
            exit();
        }
    }

    header("Location: doktor_panel.php?muayene=kaydedildi#randevular");
    exit();
} catch (PDOException $e) {
    error_log('Muayene notu kaydedilemedi: ' . $e->getMessage());
    header("Location: doktor_panel.php?muayene=hata#randevular");
    exit();
}
?>
