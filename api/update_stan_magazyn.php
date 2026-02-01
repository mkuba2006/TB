<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd połączenia z bazą: " . $e->getMessage()]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$id_produktu = $data['id_produktu'] ?? null;
$stan_magazyn = $data['stan_magazyn'] ?? null;

// Walidacja
if (empty($id_produktu) || $stan_magazyn === null || !is_numeric($stan_magazyn)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Nieprawidłowe dane (wymagane: id_produktu, stan_magazyn)."]);
    exit;
}

try {
    $sql = "UPDATE produkty SET stan_magazyn = :stan_magazyn WHERE id_produktu = :id_produktu";
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':stan_magazyn', $stan_magazyn, PDO::PARAM_INT);
    $stmt->bindParam(':id_produktu', $id_produktu, PDO::PARAM_INT);
    
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Stan magazynu zaktualizowany pomyślnie."]);
    } else {
        echo json_encode(["success" => true, "message" => "Brak zmian."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd bazy danych podczas aktualizacji: " . $e->getMessage()]);
}
?>