<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id_barbera) || !isset($data->prowizja)) {
    echo json_encode(["success" => false, "error" => "Brak danych"]);
    exit;
}

$host = "localhost";
$db = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "UPDATE barberzy SET prowizja = :prowizja WHERE id_barbera = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':prowizja', $data->prowizja);
    $stmt->bindParam(':id', $data->id_barbera);
    
    if($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Prowizja zaktualizowana"]);
    } else {
        echo json_encode(["success" => false, "error" => "Błąd aktualizacji"]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>