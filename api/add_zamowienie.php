<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    if (!isset($input['id_uzytkownika'], $input['id_produktu'], $input['status_zamowienia'])) {
        echo json_encode(["success" => false, "message" => "Brak wymaganych parametrów"]);
        http_response_code(400);
        exit();
    }

    $id_uzytkownika = (int)$input['id_uzytkownika'];
    $id_produktu = (int)$input['id_produktu'];
    $status_zamowienia = trim($input['status_zamowienia']);

    $stmt = $pdo->prepare("INSERT INTO zamowienia (id_uzytkownika, id_produktu, status_zamowienia) VALUES (?, ?, ?)");
    $stmt->execute([$id_uzytkownika, $id_produktu, $status_zamowienia]);

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
