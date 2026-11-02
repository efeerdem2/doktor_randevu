<?php
session_start();
require_once 'bagl  anti.php';

if (isset($_SESSION['user_id']) && $_SESSION['rol'] == 'hasta' && isset($_GET['id'])) {
    $randevu_id = $_GET['id'];
    $hasta_id = $_SESSION['user_id'];

    // Randevunun bu hastaya ait olup olmadığını kontrol ederek iptal et
    $sql = "UPDATE randevular SET durum = 'Iptal' WHERE id = :randevu_id AND hasta_id = :hasta_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'randevu_id' => $randevu_id,
        'hasta_id' => $hasta_id
    ]);
}

header("Location: hasta_panel.php");
exit();
?>