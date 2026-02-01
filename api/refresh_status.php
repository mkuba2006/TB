<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $sql = "UPDATE produkty SET status = CASE 
                WHEN stan_magazyn < 10 THEN 'LOW!' 
                ELSE 'OK' 
            END";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $count = $stmt->rowCount();

    echo json_encode([
        "success" => true, 
        "message" => "Zaktualizowano statusy dla $count produktów."
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>