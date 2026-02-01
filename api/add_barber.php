<?php
// 1. WŁĄCZENIE RAPORTOWANIA BŁĘDÓW (Kluczowe przy błędzie 500)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. NAGŁÓWKI CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Obsługa preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$response = array("success" => false, "message" => "");

try {
    // 3. POŁĄCZENIE Z BAZĄ DANYCH (Wpisane bezpośrednio tutaj)
    $host = "localhost";
    $db   = "host574875_TEST";
    $user = "host574875_kuba";
    $pass = "kuba2006";

    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        throw new Exception("Błąd połączenia z bazą: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");

    // 4. WALIDACJA DANYCH
    if (!isset($_POST['imie']) || !isset($_POST['mail']) || !isset($_POST['haslo'])) {
        throw new Exception("Brak wymaganych pól tekstowych.");
    }
    
    // Sprawdzenie błędów uploadu plików
    if (!isset($_FILES['mini']) || $_FILES['mini']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Błąd zdjęcia MINI: " . ($_FILES['mini']['error'] ?? 'brak pliku'));
    }
    if (!isset($_FILES['big']) || $_FILES['big']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Błąd zdjęcia BIG: " . ($_FILES['big']['error'] ?? 'brak pliku'));
    }

    $imie = trim($_POST['imie']);
    $mail = trim($_POST['mail']);
    $haslo = trim($_POST['haslo']);

    // 5. OBSŁUGA PLIKÓW
    // Ścieżka do folderu z obrazkami (dostosuj jeśli struktura jest inna)
    $targetDir = "../ted/assets/ObrazyBarberow/"; 
    
    // Sprawdzenie czy folder istnieje
    if (!is_dir($targetDir)) {
        // Próba utworzenia folderu (0755)
        if (!mkdir($targetDir, 0755, true)) {
            throw new Exception("Folder '$targetDir' nie istnieje i nie udało się go utworzyć.");
        }
    }

    // Zamiana imienia na małe litery (np. Artur -> artur)
    $imieLower = strtolower($imie);

    // Nazwy plików
    $miniExt = pathinfo($_FILES["mini"]["name"], PATHINFO_EXTENSION);
    $miniName = $imieLower . "-mini." . $miniExt; 
    $miniTarget = $targetDir . $miniName;

    $bigExt = pathinfo($_FILES["big"]["name"], PATHINFO_EXTENSION);
    $bigName = $imieLower . "-big." . $bigExt; 
    $bigTarget = $targetDir . $bigName;

    // Przenoszenie plików
    if (!move_uploaded_file($_FILES["mini"]["tmp_name"], $miniTarget)) {
        throw new Exception("Nie udało się zapisać pliku MINI w folderze: $targetDir. Sprawdź uprawnienia (CHMOD 777).");
    }

    if (!move_uploaded_file($_FILES["big"]["tmp_name"], $bigTarget)) {
        throw new Exception("Nie udało się zapisać pliku BIG w folderze: $targetDir.");
    }

    // 6. ZAPIS DO BAZY
    $sql = "INSERT INTO barberzy (imie, haslo, mail) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Błąd zapytania SQL: " . $conn->error);
    }

    $stmt->bind_param("sss", $imie, $haslo, $mail);

    if ($stmt->execute()) {
        $response["success"] = true;
        $response["message"] = "Barber dodany pomyślnie.";
    } else {
        throw new Exception("Błąd zapisu do bazy: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // Przechwytujemy błąd i wysyłamy go jako JSON, żeby React nie krzyczał "Unexpected end of JSON"
    $response["success"] = false;
    $response["message"] = "Błąd serwera: " . $e->getMessage();
}

// Zwracamy odpowiedź jako JSON
header('Content-Type: application/json');
echo json_encode($response);
?>