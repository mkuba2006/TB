<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS, DELETE"); 
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Ustawienia bazy danych 
$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Logika pobierania ID (obsługa DELETE/GET/POST)
    $userId = null;
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $data = json_decode(file_get_contents("php://input"), true);
        $userId = $data['id'] ?? null;
    } else if (isset($_GET['id'])) {
        $userId = $_GET['id'];
    } else if (isset($_POST['id'])) {
        $userId = $_POST['id'];
    }

    if (!$userId) {
        echo json_encode(["success" => false, "message" => "Brak ID użytkownika do usunięcia."]);
        exit;
    }

    if (!filter_var($userId, FILTER_VALIDATE_INT) || $userId <= 0) {
        echo json_encode(["success" => false, "message" => "Nieprawidłowy format ID użytkownika."]);
        exit;
    }

    // Wykonanie zapytania DELETE
    $stmt = $pdo->prepare("DELETE FROM usurs WHERE id = ?");
    $stmt->execute([$userId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Konto użytkownika o ID: $userId zostało pomyślnie usunięte."]);
    } else {
        echo json_encode(["success" => false, "message" => "Nie znaleziono konta użytkownika o ID: $userId lub usunięcie nie powiodło się."]);
    }

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Błąd bazy danych: " . $e->getMessage()]);
} catch (\Exception $e) {
    http_response_code(500); 
    echo json_encode(["success" => false, "message" => "Wystąpił nieoczekiwany błąd."]);
}
?>