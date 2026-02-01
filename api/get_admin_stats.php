<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

    $stmtRev = $pdo->prepare("
        SELECT COALESCE(SUM(u.cena), 0) as total_revenue 
        FROM wizyty_users w 
        JOIN uslugi u ON w.id_uslugi = u.id_uslugi 
        WHERE w.status = 'Zakończona'
        AND MONTH(w.data_wizyty) = :m
        AND YEAR(w.data_wizyty) = :y
    ");
    $stmtRev->execute([':m' => $month, ':y' => $year]);
    $revenue = $stmtRev->fetch(PDO::FETCH_ASSOC)['total_revenue'];

    $stmtCost = $pdo->prepare("
        SELECT COALESCE(SUM(kwota), 0) as total_costs 
        FROM koszty
        WHERE MONTH(data_kosztu) = :m
        AND YEAR(data_kosztu) = :y
    ");
    $stmtCost->execute([':m' => $month, ':y' => $year]);
    $costs = $stmtCost->fetch(PDO::FETCH_ASSOC)['total_costs'];

    $stmtPop = $pdo->prepare("
        SELECT u.nazwa_uslugi as name, COUNT(*) as count 
        FROM wizyty_users w 
        JOIN uslugi u ON w.id_uslugi = u.id_uslugi 
        WHERE w.status = 'Zakończona' 
        AND MONTH(w.data_wizyty) = :m
        AND YEAR(w.data_wizyty) = :y
        GROUP BY u.nazwa_uslugi 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $stmtPop->execute([':m' => $month, ':y' => $year]);
    $popularServices = $stmtPop->fetchAll(PDO::FETCH_ASSOC);

    $stmtStock = $pdo->query("SELECT nazwa, marka, stan_magazyn FROM produkty WHERE stan_magazyn <= 5");
    $lowStock = $stmtStock->fetchAll(PDO::FETCH_ASSOC);

    $stmtInv = $pdo->prepare("
        SELECT id_kosztu, nazwa, kategoria, kwota, data_kosztu 
        FROM koszty 
        WHERE MONTH(data_kosztu) = :m
        AND YEAR(data_kosztu) = :y
        ORDER BY data_kosztu DESC
    ");
    $stmtInv->execute([':m' => $month, ':y' => $year]);
    $invoices = $stmtInv->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        "revenue" => $revenue,
        "costs" => $costs,
        "popularServices" => $popularServices,
        "lowStock" => $lowStock,
        "invoices" => $invoices
    ]);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>