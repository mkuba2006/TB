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

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Brak parametru 'id' w zapytaniu"]);
    exit;
}

$id_uzytkownika = intval($_GET['id']); 

// $sql = "
//     SELECT  wizyty.*,  uslugi.id_uslugi AS id_uslugi, uslugi.nazwa_uslugi,  uslugi.ile_pkt,  barberzy.imie AS imie_barbera
//     FROM wizyty
//     LEFT JOIN uslugi ON wizyty.id_uslugi = uslugi.id_uslugi
//     LEFT JOIN barberzy ON uslugi.id_barbera = barberzy.id_barbera
//     WHERE wizyty.`id_użytkownika` = ?
//     ORDER BY wizyty.data_wizyty DESC
// ";

$sql = "
    SELECT  wizyty_users.*,  
            wizyty_users.godzina_wizyty,  
            uslugi.id_uslugi AS id_uslugi, 
            uslugi.nazwa_uslugi,  
            uslugi.ile_pkt,  
            barberzy.imie AS imie_barbera,
            uslugi.czas_wizyty AS czas_wizyty,
            wizyty_users.id_wizyty
    FROM wizyty_users
    LEFT JOIN uslugi ON wizyty_users.id_uslugi = uslugi.id_uslugi
    LEFT JOIN barberzy ON uslugi.id_barbera = barberzy.id_barbera
    WHERE wizyty_users.`id_użytkownika` = ?
    ORDER BY wizyty_users.data_wizyty DESC
";



$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_uzytkownika);
$stmt->execute();
$result = $stmt->get_result();

$wizyty = [];
while ($row = $result->fetch_assoc()) {
    $wizyty[] = $row;
}

// Zwróć dane jako JSON
echo json_encode($wizyty);

// Zamknij połączenie
$stmt->close();
$conn->close();
?>
