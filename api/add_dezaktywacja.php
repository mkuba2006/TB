<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$host = "localhost"; 
$db = "host574875_TEST"; 
$user = "host574875_kuba"; 
$pass = "kuba2006";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Błąd połączenia: " . $e->getMessage()]); exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$id_barbera = $data['id_barbera'] ?? null;
$data_od = $data['data_od'] ?? null;
$data_do = $data['data_do'] ?? null;

// Walidacja
if (empty($id_barbera) || empty($data_od) || empty($data_do)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Brak danych."]);
    exit;
}

if ($data_od > $data_do) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Data początkowa nie może być późniejsza niż końcowa."]);
    exit;
}

try {
    // Rozpoczynamy transakcję, żeby obie operacje (dodanie urlopu i anulowanie wizyt) wykonały się razem
    $pdo->beginTransaction();

    // 1. Dodajemy urlop do tabeli dezaktywacje
    $sqlInsert = "INSERT INTO dezaktywacje (id_barbera, data_od, data_do) VALUES (:id_barbera, :data_od, :data_do)";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindParam(':id_barbera', $id_barbera);
    $stmtInsert->bindParam(':data_od', $data_od);
    $stmtInsert->bindParam(':data_do', $data_do);
    $stmtInsert->execute();

    // 2. Automatycznie anulujemy wizyty w tym terminie dla tego barbera
    // Musimy połączyć wizyty_users z usługami, żeby wiedzieć który barber wykonuje usługę
    $sqlUpdate = "
        UPDATE wizyty_users wu
        JOIN uslugi u ON wu.id_uslugi = u.id_uslugi
        SET wu.status = 'Anulowana'
        WHERE u.id_barbera = :id_barbera
        AND wu.data_wizyty BETWEEN :data_od AND :data_do
        AND wu.status != 'Anulowana' 
        AND wu.status != 'Zakończona'
    ";
    
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->bindParam(':id_barbera', $id_barbera);
    $stmtUpdate->bindParam(':data_od', $data_od);
    $stmtUpdate->bindParam(':data_do', $data_do);
    $stmtUpdate->execute();

    // Pobieramy liczbę anulowanych wizyt, żeby poinformować frontend (opcjonalne)
    $deletedCount = $stmtUpdate->rowCount();

    // Zatwierdzamy transakcję
    $pdo->commit();

    echo json_encode([
        "success" => true, 
        "message" => "Dodano urlop. Anulowano wizyt: " . $deletedCount
    ]);

} catch (PDOException $e) {
    // W razie błędu cofamy zmiany
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>