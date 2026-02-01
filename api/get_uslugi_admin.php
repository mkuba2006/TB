<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ZAPYTANIE SQL - Kluczowe jest dodanie 'u.cena' do listy
    $sql = "
        SELECT 
            u.id_uslugi, 
            u.nazwa_uslugi, 
            u.cena,       
            u.ile_pkt, 
            u.czas_wizyty, 
            b.imie AS imie_barbera
        FROM uslugi u
        LEFT JOIN barberzy b ON u.id_barbera = b.id_barbera
        ORDER BY u.id_uslugi ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $uslugi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "uslugi" => $uslugi]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd bazy: " . $e->getMessage()]);
}
?>