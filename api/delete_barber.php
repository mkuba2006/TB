<?php
// delete_barber.php

// 1. Konfiguracja i CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. UWZGLĘDNIONE TWOJE DANE LOGOWANIA
$host = "localhost";
$db = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Błąd połączenia z bazą: " . $e->getMessage()]);
    exit;
}

// 3. Pobranie danych wejściowych
$data = json_decode(file_get_contents("php://input"), true);
$id_barbera = $data['id_barbera'] ?? null;

if (!$id_barbera) {
    echo json_encode(["success" => false, "error" => "Brak ID barbera."]);
    exit;
}

try {
    // 4. Pobranie imienia barbera, aby usunąć jego pliki zdjęć
    $stmt = $pdo->prepare("SELECT imie FROM barberzy WHERE id_barbera = :id");
    $stmt->bindParam(':id', $id_barbera);
    $stmt->execute();
    $barber = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($barber) {
        $imie = strtolower($barber['imie']);
        // Ścieżka do zdjęć - dostosuj jeśli masz inną strukturę folderów
        $targetDir = "../ted/assets/ObrazyBarberow/"; 
        
        // Lista możliwych rozszerzeń, jakie obsługuje system
        $extensions = ['png', 'jpg', 'jpeg', 'webp'];
        
        // Próba usunięcia wersji mini i big
        foreach ($extensions as $ext) {
            $miniFile = $targetDir . $imie . "-mini." . $ext;
            $bigFile = $targetDir . $imie . "-big." . $ext;
            
            if (file_exists($miniFile)) { @unlink($miniFile); }
            if (file_exists($bigFile)) { @unlink($bigFile); }
        }
    }

    // 5. Usuwanie z bazy danych
    // Najpierw usuwamy urlopy tego barbera (tabela dezaktywacje)
    $delUrlopy = $pdo->prepare("DELETE FROM dezaktywacje WHERE id_barbera = :id");
    $delUrlopy->bindParam(':id', $id_barbera);
    $delUrlopy->execute();

    // Następnie usuwamy samego barbera
    $sql = "DELETE FROM barberzy WHERE id_barbera = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id_barbera);
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Barber i jego zdjęcia zostały usunięte."]);

} catch (PDOException $e) {
    // Obsługa błędu klucza obcego (jeśli barber ma przypisane wizyty)
    if ($e->getCode() == '23000') {
        echo json_encode([
            "success" => false, 
            "error" => "Nie można usunąć barbera, ponieważ posiada on historię wizyt. Najpierw usuń wizyty lub zarchiwizuj pracownika."
        ]);
    } else {
        echo json_encode(["success" => false, "error" => "Błąd SQL: " . $e->getMessage()]);
    }
}
?>