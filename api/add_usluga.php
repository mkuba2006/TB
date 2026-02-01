<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

$host = "localhost"; $db = "host574875_TEST"; $user = "host574875_kuba"; $pass = "kuba2006";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Błąd połączenia z bazą: " . $e->getMessage()]); exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$id_barbera = $data['id_barbera'] ?? null;
$nazwa_uslugi = $data['nazwa_uslugi'] ?? null;
$cena = $data['cena'] ?? null;        // NOWE POLE
$ile_pkt = $data['ile_pkt'] ?? null;
$czas_wizyty = $data['czas_wizyty'] ?? null;

if (empty($id_barbera) || empty($nazwa_uslugi) || empty($cena) || empty($ile_pkt) || empty($czas_wizyty)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Brak wymaganych danych (cena, punkty, nazwa)."]);
    exit;
}

try {
    // ZAKTUALIZOWANE ZAPYTANIE - dodano kolumnę 'cena'
    $sql = "
        INSERT INTO uslugi (id_barbera, nazwa_uslugi, cena, ile_pkt, czas_wizyty) 
        VALUES (:id_barbera, :nazwa_uslugi, :cena, :ile_pkt, :czas_wizyty)
    ";
    
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':id_barbera', $id_barbera, PDO::PARAM_INT);
    $stmt->bindParam(':nazwa_uslugi', $nazwa_uslugi);
    $stmt->bindParam(':cena', $cena);          // Bindowanie ceny
    $stmt->bindParam(':ile_pkt', $ile_pkt, PDO::PARAM_INT);
    $stmt->bindParam(':czas_wizyty', $czas_wizyty);
    
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Usługa dodana pomyślnie."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd bazy danych: " . $e->getMessage()]);
}
?>