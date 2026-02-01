<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
  echo json_encode(["success" => false, "error" => "Błąd połączenia z bazą"]);
  exit;
}

$imgDir = "../ted/assets/ObrazyBarberow/";
$imgUrlBase = "https://jmdeveloper.pl/ted/assets/ObrazyBarberow/";

$sql = "
SELECT 
  b.id_barbera,
  b.imie,
  d.rating,
  d.clients,
  d.instagram
FROM barberzy b
LEFT JOIN barberzy_dane d ON b.id_barbera = d.id_barbera
ORDER BY b.imie ASC
";

$result = $conn->query($sql);
$barberzy = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['rating'] = ($row['rating'] && $row['rating'] > 0) ? $row['rating'] : "0.0";
        $row['clients'] = ($row['clients']) ? $row['clients'] : "0+";
        
        $imieLower = strtolower($row['imie']);

        $bigFiles = glob($imgDir . $imieLower . "-big.*");
        
        if (!empty($bigFiles)) {
            $row['img_big'] = $imgUrlBase . basename($bigFiles[0]);
        } else {
            $row['img_big'] = null;
        }
        
        $miniFiles = glob($imgDir . $imieLower . "-mini.*");
         if (!empty($miniFiles)) {
            $row['img_mini'] = $imgUrlBase . basename($miniFiles[0]);
        } else {
            $row['img_mini'] = null;
        }

        $barberzy[] = $row;
    }
}

echo json_encode([
  "success" => true,
  "barberzy" => $barberzy
]);

$conn->close();
?>