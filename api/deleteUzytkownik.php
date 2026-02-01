<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Ustawienia połączenia z bazą danych
$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

// Funkcja połączenia z bazą danych
function connectDB($host, $db, $user, $pass) {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        http_response_code(500);
        die(json_encode(["error" => "Błąd połączenia z bazą danych: " . $e->getMessage()]));
    }
}

$pdo = connectDB($host, $db, $user, $pass);

// Sprawdzenie, czy ID zostało przekazane
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Brak ID użytkownika do usunięcia."]);
    exit;
}

$userId = $_GET['id'];

try {
    // Zapytanie DELETE
    $sql = "DELETE FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Użytkownik usunięty pomyślnie."]);
    } else {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Nie znaleziono użytkownika o podanym ID."]);
    }

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd bazy danych: " . $e->getMessage()]);
}
?>