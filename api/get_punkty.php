<?php
// Włączamy raportowanie błędów dla celów diagnostycznych
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = "localhost";
$db = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Błąd połączenia z bazą: " . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Nie podano prawidłowego ID użytkownika"]);
    exit;
}

$userId = (int)$_GET['id'];

$sql_punkty = "
    SELECT SUM(u.ile_pkt) AS suma_pkt
    FROM wizyty_users w
    JOIN uslugi u ON w.id_uslugi = u.id_uslugi
    WHERE w.`id_użytkownika` = ?
      AND w.status = 'Zakończona'
";

$stmt1 = $conn->prepare($sql_punkty);
if (!$stmt1) {
    http_response_code(500);
    echo json_encode(["error" => "Błąd SQL (wizyty): " . $conn->error]);
    exit;
}
$stmt1->bind_param("i", $userId);
$stmt1->execute();
$result1 = $stmt1->get_result();
$row1 = $result1->fetch_assoc();
$suma_pkt = (int)($row1['suma_pkt'] ?? 0);
$stmt1->close();

$sql_wydane = "
    SELECT COALESCE(SUM(CASE WHEN z.status_zamowienia IN ('oczekujące','zrealizowane', 'Do odbioru') THEN p.CenaPKT ELSE 0 END), 0) AS wydane_pkt
    FROM zamowienia z
    JOIN produkty p ON z.id_produktu = p.id_produktu
    WHERE z.id_uzytkownika = ?
";

$stmt2 = $conn->prepare($sql_wydane);
if (!$stmt2) {
    http_response_code(500);
    echo json_encode(["error" => "Błąd SQL (zamowienia): " . $conn->error]);
    exit;
}
$stmt2->bind_param("i", $userId);
$stmt2->execute();
$result2 = $stmt2->get_result();
$row2 = $result2->fetch_assoc();
$wydane_pkt = (int)($row2['wydane_pkt'] ?? 0);
$stmt2->close();

$conn->close();

$punkty = max(0, $suma_pkt - $wydane_pkt);

echo json_encode(["punkty" => $punkty]);
?>