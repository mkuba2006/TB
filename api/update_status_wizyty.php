<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Obsługa żądania Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Ustawienia połączenia z bazą danych
$host = "localhost"; 
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

// --- Połączenie z bazą danych ---
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd połączenia z bazą: " . $e->getMessage()]);
    exit();
}

// Odbieranie danych JSON z ciała żądania POST
$data = json_decode(file_get_contents("php://input"), true);

$id_wizyty = $data['id_wizyty'] ?? null;
$new_status = $data['status'] ?? null; // Klucz to 'status', zgodnie z React

// --- Walidacja ---
if (empty($id_wizyty) || empty($new_status)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Brak wymaganych danych: ID wizyty lub status."]);
    exit;
}

// Walidacja dozwolonych statusów (zgodnie z logiką Reacta)
$allowed_statuses = ["Zakończona", "Anulowana", "Umówiona"];
if (!in_array($new_status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Nieprawidłowy status. Dozwolone: " . implode(', ', $allowed_statuses)]);
    exit;
}
// --- Koniec Walidacji ---

// --- Aktualizacja statusu ---
try {
    $sql = "UPDATE wizyty_users SET status = :status WHERE id_wizyty = :id";
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':id', $id_wizyty, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Status zaktualizowany na: " . $new_status]);
    } else {
        // Zwracamy success=false, jeśli rekord nie został zmieniony/znaleziony
        echo json_encode(["success" => false, "error" => "Nie znaleziono wizyty o podanym ID lub status się nie zmienił."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd bazy danych podczas aktualizacji statusu: " . $e->getMessage()]);
}
?>