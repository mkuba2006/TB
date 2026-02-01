<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); 
    exit(); 
}

$host = "localhost"; 
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

$encryption_key = "TwojBardzoSilnyTajemnyKlucz256Bit"; 
$cipher = "AES-256-CBC";
$iv_length = openssl_cipher_iv_length($cipher);
$iv = substr(hash('sha256', $encryption_key), 0, $iv_length); 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd połączenia z bazą: " . $e->getMessage()]);
    exit();
}

function decrypt_data($encrypted_data, $key, $cipher, $iv) {
    if (empty($encrypted_data)) return "Brak Danych";
    
    $ciphertext = base64_decode($encrypted_data);
    
    $decrypted = openssl_decrypt($ciphertext, $cipher, $key, 0, $iv); 
    
    return ($decrypted === false) ? "Błąd Deszyfrowania" : $decrypted;
}

$sql = "
    SELECT
        z.id_zamowienia,
        z.status_zamowienia AS status,
        p.nazwa AS nazwa_produktu,
        u.imie AS imie_klienta_enc,
        u.telefon AS telefon_klienta_enc
    FROM zamowienia z
    JOIN produkty p ON z.id_produktu = p.id_produktu
    JOIN users u ON z.id_uzytkownika = u.id
    ORDER BY z.id_zamowienia DESC
";

try {
    $stmt = $pdo->query($sql);
    $zamowienia_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd zapytania SQL: " . $e->getMessage()]);
    exit();
}

$zamowienia = [];
foreach ($zamowienia_raw as $z) {
    $imie_klienta = decrypt_data($z['imie_klienta_enc'], $encryption_key, $cipher, $iv);
    $telefon_klienta = decrypt_data($z['telefon_klienta_enc'], $encryption_key, $cipher, $iv);

    $zamowienia[] = [
        "id_zamowienia" => $z['id_zamowienia'],
        "nazwa_produktu" => $z['nazwa_produktu'],
        "status" => $z['status'],
        "imie_klienta" => $imie_klienta,
        "telefon_klienta" => $telefon_klienta,
    ];
}

echo json_encode(["success" => true, "zamowienia" => $zamowienia]);
?>