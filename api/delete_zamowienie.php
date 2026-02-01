<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

$host = "localhost"; 
$db = "host574875_TEST"; 
$user = "host574875_kuba"; 
$pass = "kuba2006";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit(); 
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Błąd połączenia: " . $e->getMessage()]); 
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$id_zamowienia = $data['id_zamowienia'] ?? null;

if (empty($id_zamowienia)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Brak ID zamówienia."]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Pobieramy dane o zamówieniu, żeby wiedzieć co oddać
    // (Joinujemy produkty, żeby znać aktualną CenęPKT)
    $sqlInfo = "
        SELECT 
            z.id_zamowienia,
            z.id_uzytkownika, 
            z.id_produktu, 
            p.CenaPKT 
        FROM zamowienia z
        LEFT JOIN produkty p ON z.id_produktu = p.id_produktu
        WHERE z.id_zamowienia = :id
    ";
    
    $stmtCheck = $pdo->prepare($sqlInfo);
    $stmtCheck->bindParam(':id', $id_zamowienia, PDO::PARAM_INT);
    $stmtCheck->execute();
    $orderInfo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($orderInfo) {
        $id_uzytkownika = $orderInfo['id_uzytkownika'];
        $id_produktu = $orderInfo['id_produktu'];
        $koszt_punktowy = $orderInfo['CenaPKT'] ?? 0;

        // 3. PRZYWRACAMY TOWAR NA MAGAZYN
        if ($id_produktu) {
            $stmtRestock = $pdo->prepare("UPDATE produkty SET stan_magazyn = stan_magazyn + 1 WHERE id_produktu = :pid");
            $stmtRestock->bindParam(':pid', $id_produktu, PDO::PARAM_INT);
            $stmtRestock->execute();
        }
    }

    $stmtDelete = $pdo->prepare("DELETE FROM zamowienia WHERE id_zamowienia = :id");
    $stmtDelete->bindParam(':id', $id_zamowienia, PDO::PARAM_INT);
    $stmtDelete->execute();

    $pdo->commit();

    echo json_encode(["success" => true, "message" => "Zamówienie usunięte, punkty zwrócone."]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd: " . $e->getMessage()]);
}
?>