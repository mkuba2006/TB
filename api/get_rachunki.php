<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$host = "localhost";
$db = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Zapytanie SQL
    $sql = "
        SELECT 
            b.id_barbera, 
            b.imie, 
            b.prowizja,
            
            -- 1. UTARG DZIENNY (Tylko dzisiaj)
            COALESCE(SUM(CASE 
                WHEN w.status = 'Zakończona' 
                     AND DATE(w.data_wizyty) = CURRENT_DATE 
                THEN u.cena ELSE 0 END), 0) as utarg_dzien,

            -- 2. UTARG TYGODNIOWY (Poniedziałek - Niedziela)
            COALESCE(SUM(CASE 
                WHEN w.status = 'Zakończona' 
                     AND YEARWEEK(w.data_wizyty, 1) = YEARWEEK(CURRENT_DATE, 1) 
                THEN u.cena ELSE 0 END), 0) as utarg_tydzien,

            -- 3. UTARG MIESIĘCZNY (Od 1-go do ostatniego dnia obecnego miesiąca)
            -- Sprawdzamy czy MIESIĄC i ROK są te same co dzisiaj. 
            -- To automatycznie obejmuje zakres od 1. dnia do ostatniego dnia tego miesiąca.
            COALESCE(SUM(CASE 
                WHEN w.status = 'Zakończona' 
                     AND MONTH(w.data_wizyty) = MONTH(CURRENT_DATE) 
                     AND YEAR(w.data_wizyty) = YEAR(CURRENT_DATE) 
                THEN u.cena ELSE 0 END), 0) as utarg_miesiac

        FROM barberzy b
        LEFT JOIN uslugi u ON b.id_barbera = u.id_barbera
        LEFT JOIN wizyty_users w ON u.id_uslugi = w.id_uslugi
        GROUP BY b.id_barbera
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $result]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>