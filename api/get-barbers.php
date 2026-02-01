<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Pobieramy tylko id_barbera i imie
    $result = $conn->query("SELECT id_barbera, imie FROM barberzy");
    if (!$result) {
        http_response_code(500);
        echo json_encode(["error" => "Błąd zapytania SQL: " . $conn->error]);
        $conn->close();
        exit;
    }

    $barberzy = [];
    while ($row = $result->fetch_assoc()) {
        $barberzy[] = $row;
    }

    echo json_encode($barberzy);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Tylko metoda GET jest dozwolona"]);
}

$conn->close();
?>
