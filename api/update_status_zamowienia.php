<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

$host = "localhost"; 
$db = "host574875_TEST"; 
$user = "host574875_kuba"; 
$pass = "kuba2006";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Błąd połączenia z bazą: " . $e->getMessage()]); exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$id_zamowienia = $data['id_zamowienia'] ?? null;
$new_status = $data['new_status'] ?? null; 

if (empty($id_zamowienia) || empty($new_status)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Brak ID zamówienia lub nowego statusu."]);
    exit;
}

$allowed_statuses = ["Do odbioru", "zrealizowane"];
if (!in_array($new_status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Nieprawidłowa wartość statusu."]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmtCheck = $pdo->prepare("SELECT status_zamowienia, id_produktu FROM zamowienia WHERE id_zamowienia = :id");
    $stmtCheck->bindParam(':id', $id_zamowienia, PDO::PARAM_INT);
    $stmtCheck->execute();
    $currentOrder = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$currentOrder) {
        throw new Exception("Nie znaleziono zamówienia o podanym ID.");
    }

    $old_status = $currentOrder['status_zamowienia'];
    $id_produktu = $currentOrder['id_produktu'];

    $sql = "UPDATE zamowienia SET status_zamowienia = :status WHERE id_zamowienia = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':id', $id_zamowienia, PDO::PARAM_INT);
    $stmt->execute();

    if ($new_status === "Do odbioru" && $old_status !== "Do odbioru") {
        $sqlUpdateStock = "UPDATE produkty SET stan_magazyn = stan_magazyn - 1 WHERE id_produktu = :id_prod AND stan_magazyn > 0";
        $stmtStock = $pdo->prepare($sqlUpdateStock);
        $stmtStock->bindParam(':id_prod', $id_produktu, PDO::PARAM_INT);
        $stmtStock->execute();
    }

    $pdo->commit();

    echo json_encode(["success" => true, "message" => "Status zaktualizowany, magazyn przeliczony."]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd: " . $e->getMessage()]);
}
?>