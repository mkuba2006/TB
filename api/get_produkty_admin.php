<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // POBIERAMY WSZYSTKIE NOWE KOLUMNY
    $sql = "SELECT 
                id_produktu, 
                nazwa, 
                marka, 
                cena, 
                CenaPKT, 
                pojemnosc, 
                stan_magazyn, 
                status 
            FROM produkty 
            ORDER BY id_produktu DESC";

    $stmt = $pdo->query($sql);
    $produkty = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "produkty" => $produkty
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>