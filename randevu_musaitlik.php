<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] ?? null) !== 'hasta') {
    http_response_code(401);
    echo json_encode(['error' => 'Müsaitlik bilgisi için hasta girişi yapmalısınız.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$doktor_id = filter_var($_GET['doktor_id'] ?? null, FILTER_VALIDATE_INT);
$randevu_tarihi = isset($_GET['randevu_tarihi']) && is_string($_GET['randevu_tarihi'])
    ? $_GET['randevu_tarihi']
    : '';
$tarih = DateTimeImmutable::createFromFormat('!Y-m-d', $randevu_tarihi);

if (!$doktor_id || !$tarih || $tarih->format('Y-m-d') !== $randevu_tarihi || $randevu_tarihi < date('Y-m-d')) {
    http_response_code(400);
    echo json_encode(['error' => 'Geçerli bir doktor ve randevu tarihi seçin.'], JSON_UNESCAPED_UNICODE);
    exit();
}

if ((int) $tarih->format('N') >= 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Hafta sonu için randevu alınamaz. Lütfen hafta içi bir gün seçin.'], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once 'baglanti.php';

$doktor_stmt = $db->prepare("SELECT id FROM doktorlar WHERE id = :doktor_id");
$doktor_stmt->execute(['doktor_id' => $doktor_id]);
if (!$doktor_stmt->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['error' => 'Seçilen doktor bulunamadı.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt = $db->prepare(
    "SELECT randevu_saati FROM randevular
     WHERE doktor_id = :doktor_id AND randevu_tarihi = :tarih AND durum = 'Aktif'"
);
$stmt->execute([
    'doktor_id' => $doktor_id,
    'tarih' => $randevu_tarihi
]);

echo json_encode(['dolu_saatler' => $stmt->fetchAll(PDO::FETCH_COLUMN)], JSON_UNESCAPED_UNICODE);
