<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// --- 1. Ustawienia i konfiguracja ---

// Ustawienia połączenia z bazą danych
$host = "localhost";
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

// Ustawienia szyfrowania (ZGODNIE Z TWOIMI WYMAGANIAMI)
$encryption_key = "TwojBardzoSilnyTajemnyKlucz256Bit"; 
$cipher = "AES-256-CBC";
$iv_length = openssl_cipher_iv_length($cipher);
// Stały IV (wektor inicjujący) utworzony z klucza
$iv = substr(hash('sha256', $encryption_key), 0, $iv_length); 

// Obsługa żądania Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }


// --- 2. Funkcje Pomocnicze ---

// Funkcja do deszyfrowania danych (przyjmuje stały IV)
function decryptData($encryptedData, $key, $cipher, $iv) {
    if (empty($encryptedData)) {
        return null;
    }
    
    // 1. Dekodowanie Base64
    $encryptedText = base64_decode($encryptedData);
    
    if ($encryptedText === false) {
        return 'Błąd dekodowania Base64';
    }

    // 2. Deszyfrowanie przy użyciu stałego IV i flagi OPENSSL_RAW_DATA
    // Zmieniono flagę z OPENSSL_RAW_DATA na 0, aby być w 100% zgodnym z Twoim kodem add user.php (jeśli używa 0)
    $decrypted = openssl_decrypt($encryptedText, $cipher, $key, 0, $iv); 
    
    if ($decrypted === false) {
        return 'Błąd odszyfrowania (sprawdź klucz/IV)';
    }
    return $decrypted;
}

// Funkcja połączenia z bazą danych
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

// --- 3. Główna logika (Połączenie i pobranie danych) ---

$pdo = connectDB($host, $db, $user, $pass);

// Pobieramy IMIĘ i TELEFON (które są zaszyfrowane)
$sql = "SELECT id, imie, telefon FROM users";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();

$decryptedUsers = [];
foreach ($users as $user) {
    $decryptedUser = $user;
    
    // 1. Odszyfrowanie IMIENIA
    if (!empty($user['imie'])) {
        $decryptedName = decryptData($user['imie'], $encryption_key, $cipher, $iv);
        // Zapisujemy odszyfrowaną wartość
        $decryptedUser['imie'] = $decryptedName; 
    } else {
        $decryptedUser['imie'] = null;
    }
    
    // 2. Odszyfrowanie numeru telefonu
    if (!empty($user['telefon'])) {
        $decryptedPhone = decryptData($user['telefon'], $encryption_key, $cipher, $iv);
        // Zapisujemy odszyfrowaną wartość
        $decryptedUser['telefon'] = $decryptedPhone;
    } else {
        $decryptedUser['telefon'] = null;
    }
    
    $decryptedUsers[] = $decryptedUser;
}

echo json_encode($decryptedUsers);
?>