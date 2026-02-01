<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "localhost";
$db = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Błąd połączenia z bazą: " . $conn->connect_error]);
    exit();
}

$conn->set_charset("utf8mb4");

$id_uzytkownika = $_POST['id_uzytkownika'] ?? null;
$data_wizyty = $_POST['data_wizyty'] ?? null;
$godzina = $_POST['godzina'] ?? null;
$id_uslugi = $_POST['id_uslugi'] ?? null;

if (!$id_uzytkownika || !$data_wizyty || !$godzina || !$id_uslugi) {
    echo json_encode([
        "success" => false, 
        "message" => "Brak wymaganych danych. Otrzymano: ID_User=$id_uzytkownika, Data=$data_wizyty, Godzina=$godzina, ID_Uslugi=$id_uslugi"
    ]);
    exit();
}

if (strlen($godzina) == 5) { 
    $godzina .= ":00"; 
}


$sql = "INSERT INTO wizyty_users (id_użytkownika, data_wizyty, godzina_wizyty, id_uslugi) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Błąd przygotowania zapytania SQL: " . $conn->error
    ]);
    exit();
}

$stmt->bind_param("issi", $id_uzytkownika, $data_wizyty, $godzina, $id_uslugi);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Zarezerwowano pomyślnie"]);
} else {
    echo json_encode(["success" => false, "message" => "Błąd wykonania: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>