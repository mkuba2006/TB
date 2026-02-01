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

// --- Walidacja ---
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
// --- Koniec Walidacji ---

try {
    // 1. Rozpoczynamy transakcję (wszystko albo nic)
    $pdo->beginTransaction();

    // 2. Pobieramy obecny status i ID produktu przypisanego do zamówienia
    // Zakładam, że w tabeli 'zamowienia' masz kolumnę 'id_produktu'
    $stmtCheck = $pdo->prepare("SELECT status_zamowienia, id_produktu FROM zamowienia WHERE id_zamowienia = :id");
    $stmtCheck->bindParam(':id', $id_zamowienia, PDO::PARAM_INT);
    $stmtCheck->execute();
    $currentOrder = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$currentOrder) {
        throw new Exception("Nie znaleziono zamówienia o podanym ID.");
    }

    $old_status = $currentOrder['status_zamowienia'];
    $id_produktu = $currentOrder['id_produktu'];

    // 3. Aktualizujemy status zamówienia
    $sql = "UPDATE zamowienia SET status_zamowienia = :status WHERE id_zamowienia = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':id', $id_zamowienia, PDO::PARAM_INT);
    $stmt->execute();

    // 4. LOGIKA MAGAZYNU: Zmniejszamy stan tylko jeśli nowy status to "Do odbioru"
    // ORAZ stary status był inny (żeby nie odejmować w kółko przy odświeżaniu)
    if ($new_status === "Do odbioru" && $old_status !== "Do odbioru") {
        // Zmniejsz stan magazynu o 1 w tabeli produkty
        $sqlUpdateStock = "UPDATE produkty SET stan_magazyn = stan_magazyn - 1 WHERE id_produktu = :id_prod AND stan_magazyn > 0";
        $stmtStock = $pdo->prepare($sqlUpdateStock);
        $stmtStock->bindParam(':id_prod', $id_produktu, PDO::PARAM_INT);
        $stmtStock->execute();
    }

    // 5. Zatwierdzamy zmiany w bazie
    $pdo->commit();

    echo json_encode(["success" => true, "message" => "Status zaktualizowany, magazyn przeliczony."]);

} catch (Exception $e) {
    // W razie błędu cofamy wszystkie zmiany (rollback)
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd: " . $e->getMessage()]);
}
?>