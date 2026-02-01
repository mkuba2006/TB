<?php
// === KONFIGURACJA BŁĘDÓW ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$response = ["success" => false, "debug" => []];

// === 1. POŁĄCZENIE Z BAZĄ ===
$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Błąd bazy: " . $conn->connect_error]);
    exit;
}

// === 2. WALIDACJA ===
if (!isset($_POST["id_barbera"])) {
    echo json_encode(["success" => false, "error" => "Brak ID barbera"]);
    exit;
}
$id = intval($_POST["id_barbera"]);

// === 3. SQL (TEKST) ===
$check = $conn->query("SELECT id_barbera FROM barberzy_dane WHERE id_barbera = $id");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO barberzy_dane (id_barbera) VALUES ($id)");
}

$sql = "UPDATE barberzy_dane SET 
  opis = ?, rating = ?, clients = ?, instagram = ?, 
  spec1_name = ?, spec1_level = ?, spec2_name = ?, spec2_level = ?, 
  spec3_name = ?, spec3_level = ?, spec4_name = ?, spec4_level = ?, 
  spec5_name = ?, spec5_level = ? 
  WHERE id_barbera = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success" => false, "error" => "Błąd SQL: " . $conn->error]);
    exit;
}

$opis       = $_POST["opis"] ?? "";
$rating     = floatval($_POST["rating"] ?? 0);
$clients    = $_POST["clients"] ?? "";
$instagram  = $_POST["instagram"] ?? "";
$spec1_name = $_POST["spec1_name"] ?? ""; $spec1_lvl = intval($_POST["spec1_level"] ?? 0);
$spec2_name = $_POST["spec2_name"] ?? ""; $spec2_lvl = intval($_POST["spec2_level"] ?? 0);
$spec3_name = $_POST["spec3_name"] ?? ""; $spec3_lvl = intval($_POST["spec3_level"] ?? 0);
$spec4_name = $_POST["spec4_name"] ?? ""; $spec4_lvl = intval($_POST["spec4_level"] ?? 0);
$spec5_name = $_POST["spec5_name"] ?? ""; $spec5_lvl = intval($_POST["spec5_level"] ?? 0);

$stmt->bind_param("sdsssisisisisii", 
  $opis, $rating, $clients, $instagram,
  $spec1_name, $spec1_lvl, $spec2_name, $spec2_lvl, 
  $spec3_name, $spec3_lvl, $spec4_name, $spec4_lvl, 
  $spec5_name, $spec5_lvl, $id
);

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "error" => "Błąd zapisu SQL: " . $stmt->error]);
    exit;
}
$stmt->close();
$conn->close();

// === 4. PLIKI (USUWANIE wszystkiego co ma myślnik LUB podłogę) ===

$baseDir = $_SERVER['DOCUMENT_ROOT'];
$uploadDir = $baseDir . '/ted/assets/ObrazyBarberow/';

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

$rawName = $_POST['imie'] ?? 'barber';
$imieClean = preg_replace('/[^a-zA-Z0-9]/', '', $rawName);
$imieClean = strtolower($imieClean); 

function replacePhoto($fileKey, $suffix, $uploadDir, $imieClean, &$response) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // 1. Definiujemy oba wzorce nazw (z podłogą i myślnikiem)
    // Wzorzec 1: krystian_big.*
    $patternUnderscore = $uploadDir . $imieClean . '_' . $suffix . ".*";
    // Wzorzec 2: krystian-big.* (TO USUNIE TEGO ZŁEGO WEBP)
    $patternDash       = $uploadDir . $imieClean . '-' . $suffix . ".*";

    // 2. Szukamy plików
    $filesUnderscore = glob($patternUnderscore);
    $filesDash       = glob($patternDash);

    // Łączymy wyniki wyszukiwania (zabezpieczenie jeśli glob zwróci false)
    if (!$filesUnderscore) $filesUnderscore = [];
    if (!$filesDash)       $filesDash = [];
    
    $filesToDelete = array_merge($filesUnderscore, $filesDash);

    $response['debug'][] = "Szukam: " . basename($patternUnderscore) . " ORAZ " . basename($patternDash);

    // 3. Kasujemy wszystko co znaleźliśmy
    if (!empty($filesToDelete)) {
        foreach ($filesToDelete as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $response['debug'][] = "USUNIĘTO STARY: " . basename($file);
                } else {
                    $response['debug'][] = "BŁĄD USUWANIA: " . basename($file);
                }
            }
        }
    }

    // 4. Wgrywamy nowy plik (zawsze z PODŁOGĄ, żeby trzymać porządek)
    $ext = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
    $targetName = $imieClean . '-' . $suffix . '.' . $ext;
    $targetPath = $uploadDir . $targetName;

    if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
        $response['debug'][] = "WGRANO NOWY: $targetName";
        return true;
    } else {
        $response['debug'][] = "BŁĄD UPLOADU: Nie udało się przenieść pliku.";
        return false;
    }
}

$updates = [];
if (replacePhoto('mini', 'mini', $uploadDir, $imieClean, $response)) {
    $updates[] = "Mini";
}
if (replacePhoto('big', 'big', $uploadDir, $imieClean, $response)) {
    $updates[] = "Big";
}

if (ob_get_length()) ob_clean();

$response['success'] = true;
$response['message'] = "Zaktualizowano. " . (count($updates) ? "Pliki: " . implode(", ", $updates) : "");

echo json_encode($response);
?>