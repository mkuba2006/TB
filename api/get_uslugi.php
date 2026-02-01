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

// Połączenie z bazą
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Błąd połączenia: " . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "
        SELECT b.id_barbera, b.imie, u.nazwa_uslugi, u.ile_pkt
        FROM barberzy b
        LEFT JOIN uslugi u ON b.id_barbera = u.id_barbera
        ORDER BY b.imie
    ";

    $result = $conn->query($sql);
    if (!$result) {
        http_response_code(500);
        echo json_encode(["error" => "Błąd zapytania SQL: " . $conn->error]);
        $conn->close();
        exit;
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $id = $row['id_barbera'];
        if (!isset($data[$id])) {
            $data[$id] = [
                "imie" => $row['imie'],
                "uslugi" => []
            ];
        }

        if ($row['nazwa_uslugi']) {
            $data[$id]["uslugi"][] = [
                "nazwa_uslugi" => $row['nazwa_uslugi'],
                "ile_pkt" => $row['ile_pkt']
            ];
        }
    }

    echo json_encode(array_values($data));
} else {
    http_response_code(405);
    echo json_encode(["error" => "Tylko metoda GET jest dozwolona"]);
}

$conn->close();
?>
