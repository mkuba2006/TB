<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

$encryption_key = "TwojBardzoSilnyTajemnyKlucz256Bit"; 
$cipher = "AES-256-CBC";
$iv_length = openssl_cipher_iv_length($cipher);
$iv = substr(hash('sha256', $encryption_key), 0, $iv_length); 
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }


function decryptData($encryptedData, $key, $cipher, $iv) {
    if (empty($encryptedData)) {
        return null;
    }
    
    $encryptedText = base64_decode($encryptedData);
    
    if ($encryptedText === false) {
        return 'Błąd dekodowania Base64';
    }

    $decrypted = openssl_decrypt($encryptedText, $cipher, $key, 0, $iv); 
    
    if ($decrypted === false) {
        return 'Błąd odszyfrowania (sprawdź klucz/IV)';
    }
    return $decrypted;
}

function connectDB($host, $db, $user, $pass) {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        http_response_code(500);
        die(json_encode(["error" => "Błąd połączenia z bazą danych: " . $e->getMessage()]));
    }
}


$pdo = connectDB($host, $db, $user, $pass);
$sql = "SELECT id, imie, telefon FROM users";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();

$decryptedUsers = [];
foreach ($users as $user) {
    $decryptedUser = $user;

    if (!empty($user['imie'])) {
        $decryptedName = decryptData($user['imie'], $encryption_key, $cipher, $iv);
        $decryptedUser['imie'] = $decryptedName; 
    } else {
        $decryptedUser['imie'] = null;
    }
    if (!empty($user['telefon'])) {
        $decryptedPhone = decryptData($user['telefon'], $encryption_key, $cipher, $iv);
        $decryptedUser['telefon'] = $decryptedPhone;
    } else {
        $decryptedUser['telefon'] = null;
    }
    
    $decryptedUsers[] = $decryptedUser;
}

echo json_encode($decryptedUsers);
?>