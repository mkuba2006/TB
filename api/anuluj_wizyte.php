<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Tylko żądania POST są dozwolone.']);
    exit;
}

$host = "localhost";
$db = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Błąd połączenia z bazą: ' . $conn->connect_error]);
    exit;
}

$json_data = file_get_contents("php://input");
$data = json_decode($json_data);

$id = isset($data->id) ? intval($data->id) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowe ID wizyty.']);
    $conn->close();
    exit;
}

$nowy_status = "Anulowana";
$stmt = $conn->prepare("UPDATE wizyty_users SET status = ? WHERE id_wizyty = ?");
$stmt->bind_param("si", $nowy_status, $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Status wizyty został zmieniony na Anulowana.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nie znaleziono wizyty o podanym ID lub status był już Anulowana.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Błąd przy aktualizacji statusu wizyty: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>