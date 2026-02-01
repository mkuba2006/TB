<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$host = "localhost"; $db = "host574875_TEST"; $user = "host574875_kuba"; $pass = "kuba2006";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT d.id_dezaktywacji, d.data_od, d.data_do, b.imie 
            FROM dezaktywacje d 
            JOIN barberzy b ON d.id_barbera = b.id_barbera 
            ORDER BY d.data_od ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $dezaktywacje = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "dezaktywacje" => $dezaktywacje]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>