<?php
// Raportowanie błędów
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_POST['kategoria'], $_POST['kwota'], $_POST['data_kosztu'], $_POST['nazwa'])) {
        throw new Exception("Brak wymaganych danych.");
    }

    $nazwa = $_POST['nazwa'];
    $kategoria = $_POST['kategoria'];
    $kwota = $_POST['kwota'];
    $data_kosztu = $_POST['data_kosztu']; 

    // === 1. ZAPIS DO BAZY DANYCH ===
    $stmt = $pdo->prepare("INSERT INTO koszty (nazwa, kategoria, kwota, data_kosztu) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nazwa, $kategoria, $kwota, $data_kosztu]);
    $lastId = $pdo->lastInsertId();

    // === 2. GENEROWANIE PLIKU TXT ===
    $targetDir = "../faktury/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // --- KLUCZOWA POPRAWKA NAZWY PLIKU ---
    // 1. Zamiana polskich znaków na łacińskie (np. Próbka -> Probka)
    $cleanName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nazwa);
    // 2. Usunięcie wszystkiego co nie jest literą, cyfrą, podkreślnikiem lub myślnikiem
    $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $cleanName);
    
    $datePart = str_replace('-', '', $data_kosztu); 
    
    // Budujemy nazwę: koszt_ID_DATA_NAZWA.txt
    $fileName = "koszt_" . $lastId . "_" . $datePart . "_" . $safeName . ".txt";
    $filePath = $targetDir . $fileName;

    // Treść pliku
    $content = "DOKUMENT KOSZTOWY NR: " . $lastId . "\n";
    $content .= "---------------------------\n";
    $content .= "Nazwa: " . $nazwa . "\n";
    $content .= "Kategoria: " . $kategoria . "\n";
    $content .= "Kwota: " . $kwota . " PLN\n";
    $content .= "Data kosztu: " . $data_kosztu . "\n";
    $content .= "Data utworzenia wpisu: " . date("Y-m-d H:i:s") . "\n";

    if (file_put_contents($filePath, $content) === false) {
        throw new Exception("Nie udało się zapisać pliku TXT.");
    }

    echo json_encode([
        "success" => true, 
        "message" => "Koszt dodany pomyślnie.",
        "debug_filename" => $fileName 
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>