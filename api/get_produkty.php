<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); // Pozwala na dostęp z Reacta
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

$sql = "SELECT id_produktu, nazwa, marka, cena, CenaPKT, typ, gwiazdki, ocena, stan_magazyn FROM produkty";
$result = $conn->query($sql);

$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    echo json_encode($products);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Błąd SQL: " . $conn->error]);
}
$conn->close();
?>