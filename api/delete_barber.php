<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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

$data = json_decode(file_get_contents("php://input"), true);
$id_barbera = $data['id_barbera'] ?? null;

if (!$id_barbera) {
    echo json_encode(["success" => false, "error" => "Brak ID barbera."]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT imie FROM barberzy WHERE id_barbera = :id");
    $stmt->bindParam(':id', $id_barbera);
    $stmt->execute();
    $barber = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($barber) {
        $imie = strtolower($barber['imie']);
        $targetDir = "../ted/assets/ObrazyBarberow/"; 
        $extensions = ['png', 'jpg', 'jpeg', 'webp'];
        foreach ($extensions as $ext) {
            $miniFile = $targetDir . $imie . "-mini." . $ext;
            $bigFile = $targetDir . $imie . "-big." . $ext;
            
            if (file_exists($miniFile)) { @unlink($miniFile); }
            if (file_exists($bigFile)) { @unlink($bigFile); }
        }
    }
    $delUrlopy = $pdo->prepare("DELETE FROM dezaktywacje WHERE id_barbera = :id");
    $delUrlopy->bindParam(':id', $id_barbera);
    $delUrlopy->execute();

    $sql = "DELETE FROM barberzy WHERE id_barbera = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id_barbera);
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Barber i jego zdjęcia zostały usunięte."]);

} catch (PDOException $e) {
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