<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Połączenie z bazą
$host = "localhost";
$db = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}

// Walidacja parametrów
if (!isset($_GET['barberId'], $_GET['date'])) {
    echo json_encode(['error' => 'Brak barberId lub date']);
    exit();
}

$barberId = intval($_GET['barberId']);
$date = $_GET['date'];

try {
    // Pobranie wizyt KONKRETNEGO BARBERA w danym dniu
    $stmt = $pdo->prepare("
        SELECT 
            w.godzina_wizyty,
            u.czas_wizyty_min AS serviceDuration
        FROM wizyty_users w
        JOIN uslugi u ON u.id_uslugi = w.id_uslugi
        WHERE u.id_barbera = :barberId
        AND w.data_wizyty = :date
        ORDER BY w.godzina_wizyty ASC
    ");

    $stmt->execute([
        ':barberId' => $barberId,
        ':date' => $date
    ]);

    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($appointments);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
