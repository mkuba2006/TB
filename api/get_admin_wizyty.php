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
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$sql = "
SELECT 
    wizyty.id_wizyty,
    wizyty.data_wizyty,
    users.email AS user_email,
    users.telefon AS user_telefon,
    uslugi.nazwa_uslugi AS usluga_nazwa,
    barberzy.imie AS barber_imie
FROM 
    wizyty
JOIN 
    users ON wizyty.id_użytkownika = users.id
JOIN 
    uslugi ON wizyty.id_uslugi = uslugi.id_uslugi
JOIN 
    barberzy ON uslugi.id_barbera = barberzy.id_barbera
ORDER BY 
    wizyty.data_wizyty DESC
";


$result = $conn->query($sql);

$wizyty = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $wizyty[] = $row;
    }
    echo json_encode($wizyty);
} else {
    echo json_encode([]);
}

$conn->close();
?>
