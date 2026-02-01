<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(); }

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $data = json_decode(file_get_contents("php://input"), true);

    $id = $data['id_produktu'];
    $field = $data['field']; 
    $value = $data['value'];

    $allowedFields = ['cena', 'CenaPKT', 'stan_magazyn'];
    if (!in_array($field, $allowedFields)) {
        throw new Exception("Nieprawidłowe pole.");
    }

    $sql = "UPDATE produkty SET $field = :value WHERE id_produktu = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':value' => $value, ':id' => $id]);

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>