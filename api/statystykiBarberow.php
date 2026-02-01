<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    die(json_encode(["error" => "Błąd połączenia z bazą danych: " . $e->getMessage()]));
}

$sql = "
    SELECT 
        b.imie,
        COALESCE(COUNT(wu.id_wizyty), 0) AS wizyty
    FROM 
        barberzy b 
    LEFT JOIN 
        uslugi u ON b.id_barbera = u.id_barbera
    LEFT JOIN 
        wizyty_users wu ON u.id_uslugi = wu.id_uslugi AND wu.status = 'Zakończona'
    GROUP BY 
        b.imie
    ORDER BY
        wizyty DESC
";
$stmt = $pdo->query($sql);
$results = $stmt->fetchAll();

echo json_encode($results);
?>