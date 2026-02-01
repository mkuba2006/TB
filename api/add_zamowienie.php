<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Obsługa preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

// Dane do połączenia z bazą
$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

try {
    // Połączenie PDO z obsługą UTF-8
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Pobranie danych POST (JSON lub form-data)
    $input = json_decode(file_get_contents('php://input'), true);

    // Jeśli dane nie przyszły w JSON, spróbuj z $_POST
    if (!$input) {
        $input = $_POST;
    }

    // Walidacja danych
    if (!isset($input['id_uzytkownika'], $input['id_produktu'], $input['status_zamowienia'])) {
        echo json_encode(["success" => false, "message" => "Brak wymaganych parametrów"]);
        http_response_code(400);
        exit();
    }

    $id_uzytkownika = (int)$input['id_uzytkownika'];
    $id_produktu = (int)$input['id_produktu'];
    $status_zamowienia = trim($input['status_zamowienia']);

    // Prepared statement - wstawienie do bazy
    $stmt = $pdo->prepare("INSERT INTO zamowienia (id_uzytkownika, id_produktu, status_zamowienia) VALUES (?, ?, ?)");
    $stmt->execute([$id_uzytkownika, $id_produktu, $status_zamowienia]);

    // Odpowiedź JSON
    echo json_encode([
        "success" => true,
        "message" => "Zamówienie dodane",
        "id_zamowienia" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Błąd serwera: " . $e->getMessage()
    ]);
}
