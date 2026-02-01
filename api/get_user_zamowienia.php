<?php
// Włączamy raportowanie błędów dla diagnostyki
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Błąd połączenia: " . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

$id = null;
if (isset($_GET['id'])) $id = (int)$_GET['id'];
elseif (isset($_POST['id'])) $id = (int)$_POST['id'];

if (!$id) {
    echo json_encode([]);
    exit;
}

// --- POPRAWIONE ZAPYTANIE ---
// 1. Usunięto p.zdjecie (bo taka kolumna nie istnieje w bazie)
// 2. Użyto p.CenaPKT AS ile_pkt (zgodnie z nazwą kolumny w bazie)
// 3. id_uzytkownika w tabeli zamowienia jest bez "ż"
$sql = "
    SELECT 
        z.id_zamowienia,
        z.id_produktu,
        z.status_zamowienia,
        p.nazwa,
        p.marka,
        p.CenaPKT AS ile_pkt
    FROM zamowienia z
    INNER JOIN produkty p ON z.id_produktu = p.id_produktu
    WHERE z.id_uzytkownika = ?
    ORDER BY z.id_zamowienia DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Błąd SQL: " . $conn->error]);
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

$stmt->close();
$conn->close();
?>