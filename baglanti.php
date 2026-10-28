<?php
$host = "localhost";
$dbname = "doktor_randevu_db";
$kullanici = "root";
$sifre = "";

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $kullanici, $sifre);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veri tabanı bağlantı hatası: " . $e->getMessage());
}
?>