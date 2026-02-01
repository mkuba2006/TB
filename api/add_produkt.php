<?php
// 1. Diagnostyka
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. CORS i Nagłówki
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$response = array("success" => false, "message" => "", "error" => "");

try {
    // 3. Połączenie z bazą
    $host = "localhost";
    $db   = "host574875_TEST";
    $user = "host574875_kuba";
    $pass = "kuba2006";

    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        throw new Exception("Błąd połączenia z bazą: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // 4. Pobranie danych
    if (empty($_POST['nazwa']) || empty($_POST['marka']) || empty($_POST['cena'])) {
        throw new Exception("Brak wymaganych danych (Nazwa, Marka lub Cena).");
    }

    $nazwa        = trim($_POST['nazwa']);
    $marka        = trim($_POST['marka']);
    $cena         = str_replace(',', '.', trim($_POST['cena']));
    $CenaPKT      = isset($_POST['CenaPKT']) ? (int)$_POST['CenaPKT'] : 0;
    $pojemnosc    = isset($_POST['pojemnosc']) ? trim($_POST['pojemnosc']) : "";
    $stan_magazyn = isset($_POST['stan_magazyn']) ? (int)$_POST['stan_magazyn'] : 0;
    $typ          = isset($_POST['typ']) ? trim($_POST['typ']) : "";
    $gwiazdki     = isset($_POST['gwiazdki']) ? (int)$_POST['gwiazdki'] : 5;
    $ocena        = isset($_POST['ocena']) ? (float)$_POST['ocena'] : 5.0;
    $status       = isset($_POST['status']) ? trim($_POST['status']) : "OK";

    // 5. OBSŁUGA ZDJĘCIA (Z zachowaniem wielkości liter i polskich znaków)
    if (isset($_FILES['zdjecie']) && $_FILES['zdjecie']['error'] === UPLOAD_ERR_OK) {
        
        $targetDir = "../ted/assets/ObrazyProduktow/"; 
        
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                throw new Exception("Nie udało się utworzyć katalogu: $targetDir");
            }
        }

        $fileExt = pathinfo($_FILES["zdjecie"]["name"], PATHINFO_EXTENSION);
        
        // 1. Łączymy markę i nazwę (Dokładnie tak jak wpisano)
        $rawName = $marka . "_" . $nazwa; 
        
        // --- USUNIĘTO mb_strtolower ABY ZACHOWAĆ DUŻE LITERY ---

        // 2. Zamieniamy spacje na podkreślniki
        $safeName = str_replace(' ', '_', $rawName);

        // 3. Czyścimy nazwę, ale ZOSTAWIAMY litery (duże i małe, w tym polskie)
        // Regex /[^\\p{L}\\p{N}_-]+/u usuwa wszystko co nie jest literą, cyfrą, _ lub -
        $safeName = preg_replace('/[^\p{L}\p{N}_-]+/u', '', $safeName);

        // 4. Usuwamy ewentualne podwójne podkreślniki
        $safeName = preg_replace('/_+/', '_', $safeName);
        $safeName = trim($safeName, '_');

        $finalFileName = $safeName . "." . $fileExt;
        $targetFile = $targetDir . $finalFileName;

        // Przenoszenie pliku
        if (!move_uploaded_file($_FILES["zdjecie"]["tmp_name"], $targetFile)) {
            throw new Exception("Błąd zapisu pliku na serwerze.");
        }
    }

    // 6. INSERT DO BAZY
    $sql = "INSERT INTO produkty (
                nazwa, 
                marka, 
                cena, 
                CenaPKT, 
                stan_magazyn, 
                typ, 
                gwiazdki, 
                ocena, 
                pojemnosc, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Błąd SQL: " . $conn->error);
    }

    $stmt->bind_param(
        "ssdiisidss", 
        $nazwa, 
        $marka, 
        $cena, 
        $CenaPKT, 
        $stan_magazyn, 
        $typ, 
        $gwiazdki, 
        $ocena, 
        $pojemnosc, 
        $status
    );

    if ($stmt->execute()) {
        $response["success"] = true;
        $response["message"] = "Produkt dodany pomyślnie.";
    } else {
        throw new Exception("Błąd zapisu do bazy: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $response["success"] = false;
    $response["error"] = $e->getMessage();
}

echo json_encode($response);
?>