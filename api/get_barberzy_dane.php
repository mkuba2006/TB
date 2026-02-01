<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

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
  echo json_encode(["success" => false, "error" => "Błąd bazy"]);
  exit;
}

$imgDir = "../ted/assets/ObrazyBarberow/";
$imgUrlBase = "https://jmdeveloper.pl/ted/assets/ObrazyBarberow/";

$sql = "
SELECT 
  b.id_barbera,
  b.imie,
  d.opis,
  d.rating,
  d.clients,
  d.instagram,
  d.spec1_name, d.spec1_level,
  d.spec2_name, d.spec2_level,
  d.spec3_name, d.spec3_level,
  d.spec4_name, d.spec4_level,
  d.spec5_name, d.spec5_level
FROM barberzy b
LEFT JOIN barberzy_dane d ON b.id_barbera = d.id_barbera
ORDER BY b.imie ASC
";

$result = $conn->query($sql);
$barberzy_dane = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Czyścimy NULLe
        foreach ($row as $key => $val) {
            if ($val === null) {
                if (strpos($key, 'level') !== false || $key === 'rating') {
                    $row[$key] = 0;
                } else {
                    $row[$key] = "";
                }
            }
        }

        $imieLower = strtolower($row['imie']);
        $miniFiles = glob($imgDir . $imieLower . "-mini.*");
        if (!empty($miniFiles)) {
            $fileName = basename($miniFiles[0]);
            $row['img_mini'] = $imgUrlBase . $fileName . "?t=" . time(); 
        } else {
            $row['img_mini'] = null;
        }

        $bigFiles = glob($imgDir . $imieLower . "-big.*");
        if (!empty($bigFiles)) {
            $fileName = basename($bigFiles[0]);
            $row['img_big'] = $imgUrlBase . $fileName . "?t=" . time();
        } else {
            $row['img_big'] = null;
        }

        $barberzy_dane[] = $row;
    }
}

echo json_encode([
  "success" => true,
  "barberzy_dane" => $barberzy_dane
]);

$conn->close();
?>