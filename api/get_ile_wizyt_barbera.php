<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

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

    $id_barbera = isset($_GET['id']) ? $_GET['id'] : null;
    $zakres = isset($_GET['zakres']) ? $_GET['zakres'] : 'miesiac'; // Domyślnie miesiąc

    if (!$id_barbera) {
        echo json_encode(["success" => false, "error" => "Brak ID barbera"]);
        exit;
    }

    // 1. Pobierz prowizję barbera
    $stmtUser = $pdo->prepare("SELECT prowizja FROM barberzy WHERE id_barbera = :id");
    $stmtUser->execute([':id' => $id_barbera]);
    $barber = $stmtUser->fetch(PDO::FETCH_ASSOC);

    $prowizja = $barber ? floatval($barber['prowizja']) : 0;

    // 2. Przygotuj warunek czasowy w zależności od wybranego zakresu
    $dateCondition = "";
    
    switch ($zakres) {
        case 'dzien':
            // Tylko dzisiejsza data
            $dateCondition = "AND DATE(w.data_wizyty) = CURRENT_DATE()";
            break;
        case 'tydzien':
            // Obecny tydzień (Poniedziałek - Niedziela)
            // YEARWEEK(..., 1) wymusza start tygodnia w poniedziałek
            $dateCondition = "AND YEARWEEK(w.data_wizyty, 1) = YEARWEEK(CURRENT_DATE(), 1)";
            break;
        case 'miesiac':
        default:
            // Obecny miesiąc
            $dateCondition = "AND MONTH(w.data_wizyty) = MONTH(CURRENT_DATE()) AND YEAR(w.data_wizyty) = YEAR(CURRENT_DATE())";
            break;
    }

    // 3. Policz wizyty i utarg z uwzględnieniem warunku czasowego
    $sql = "
        SELECT 
            COUNT(*) as ile_wizyt,
            COALESCE(SUM(u.cena), 0) as utarg_calkowity
        FROM wizyty_users w
        JOIN uslugi u ON w.id_uslugi = u.id_uslugi
        WHERE u.id_barbera = :id
          AND w.status = 'Zakończona'
          $dateCondition
    ";

    $stmtStats = $pdo->prepare($sql);
    $stmtStats->execute([':id' => $id_barbera]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

    $ile_wizyt = $stats['ile_wizyt'];
    $utarg = floatval($stats['utarg_calkowity']);

    // 4. Oblicz zarobek
    $zarobek = $utarg * ($prowizja / 100);

    echo json_encode([
        "success" => true,
        "wizyty" => $ile_wizyt,
        "zarobek" => round($zarobek, 2),
        "zakres" => $zakres
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Błąd bazy: " . $e->getMessage()]);
}
?>