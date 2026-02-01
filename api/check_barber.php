<?php
// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

// --- Połączenie z bazą ---
$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Błąd połączenia z bazą"]);
    exit();
}

// Pobranie danych JSON z POST
$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$haslo = $data['haslo'] ?? '';

if (!$email || !$haslo) {
    echo json_encode(["success" => false, "error" => "Brak danych"]);
    exit();
}

// Zapytanie o barbera
$stmt = $pdo->prepare("
    SELECT id_barbera, imie, mail, haslo
    FROM barberzy
    WHERE mail = :email AND haslo = :haslo
    LIMIT 1
");

$stmt->execute([
    ":email" => $email,
    ":haslo" => $haslo
]);

$barber = $stmt->fetch(PDO::FETCH_ASSOC);

// Zwrot danych
if ($barber) {
    echo json_encode([
        "success" => true,
        "user" => [
            "id_barbera" => $barber['id_barbera'],
            "imie" => $barber['imie'],
            "mail" => $barber['mail']
        ]
    ]);
} else {
    echo json_encode(["success" => false]);
}
?>
