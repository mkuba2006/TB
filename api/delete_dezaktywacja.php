<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$host = "localhost"; $db = "host574875_TEST"; $user = "host574875_kuba"; $pass = "kuba2006";

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id_dezaktywacji'] ?? null;

if (!$id) { echo json_encode(["success" => false]); exit; }

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $sql = "DELETE FROM dezaktywacje WHERE id_dezaktywacji = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>