<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

$host = "localhost"; $db = "host574875_TEST"; $user = "host574875_kuba"; $pass = "kuba2006";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Błąd połączenia: " . $e->getMessage()]); exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$id_uslugi = $data['id_uslugi'] ?? null;
$field = $data['field'] ?? null; // 'cena' lub 'ile_pkt'
$value = $data['value'] ?? null;

// Walidacja podstawowa
if (empty($id_uslugi) || empty($field) || !isset($value)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Brak danych do aktualizacji."]);
    exit;
}

// Zabezpieczenie przed SQL Injection w nazwie kolumny (tylko dozwolone pola)
$allowedFields = ['cena', 'ile_pkt'];
if (!in_array($field, $allowedFields)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Niedozwolone pole do edycji."]);
    exit;
}

try {
    // Dynamiczne zapytanie SQL z bezpieczną nazwą kolumny
    $sql = "UPDATE uslugi SET $field = :value WHERE id_uslugi = :id_uslugi";
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':value', $value);
    $stmt->bindParam(':id_uslugi', $id_uslugi, PDO::PARAM_INT);
    
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Zaktualizowano pomyślnie."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd SQL: " . $e->getMessage()]);
}
?>