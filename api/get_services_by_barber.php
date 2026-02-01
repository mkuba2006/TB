<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = "localhost";
$db = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Błąd połączenia: " . $conn->connect_error]);
    exit;
}

$conn->set_charset("utf8mb4");

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Brak parametru id"]);
    exit;
}

$id_barbera = intval($_GET['id']);

$sql = "SELECT id_uslugi, id_barbera, nazwa_uslugi, cena, ile_pkt, czas_wizyty
        FROM uslugi 
        WHERE id_barbera = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Błąd przygotowania zapytania: " . $conn->error]);
    exit;
}

$stmt->bind_param("i", $id_barbera);
$stmt->execute();
$result = $stmt->get_result();

$services = [];
while ($row = $result->fetch_assoc()) {
    $row['cena'] = number_format((float)$row['cena'], 2, '.', '');
    $services[] = $row;
}

echo json_encode($services);

$stmt->close();
$conn->close();
?>