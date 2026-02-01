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

// === USTAWIENIA ŚCIEŻEK ===
// Ścieżka fizyczna do folderu ze zdjęciami (względem pliku API)
// Zakładamy, że api jest w /public_html/api/ a zdjęcia w /public_html/ted/assets/ObrazyBarberow/
$imgDir = "../ted/assets/ObrazyBarberow/";
// URL publiczny do folderu zdjęć (do wyświetlania w React)
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

        // === LOGIKA ZDJĘĆ ===
        // Szukamy plików zaczynających się od imienia (np. adrian-mini.*)
        // Używamy glob, aby znaleźć dowolne rozszerzenie (jpg, png, svg)
        $imieLower = strtolower($row['imie']); // Pliki są małymi literami np. adrian-mini.png

        // Szukaj MINI
        $miniFiles = glob($imgDir . $imieLower . "-mini.*");
        if (!empty($miniFiles)) {
            // Pobieramy nazwę pliku z pełnej ścieżki
            $fileName = basename($miniFiles[0]);
            $row['img_mini'] = $imgUrlBase . $fileName . "?t=" . time(); // time() zapobiega cache'owaniu
        } else {
            $row['img_mini'] = null;
        }

        // Szukaj BIG
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