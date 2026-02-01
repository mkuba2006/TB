<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Dane do połączenia z bazą
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT id, email, haslo, imie, telefon FROM users");

    if (!$result) {
        http_response_code(500);
        echo json_encode(["error" => "Błąd zapytania SQL: " . $conn->error]);
        $conn->close();
        exit;
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode($users);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Tylko metoda GET jest dozwolona"]);
}

$conn->close();
?>
