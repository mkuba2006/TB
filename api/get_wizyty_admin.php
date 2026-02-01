<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

$host = "localhost"; 
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

$encryption_key = "TwojBardzoSilnyTajemnyKlucz256Bit"; 
$cipher = "AES-256-CBC";

$iv_length = openssl_cipher_iv_length($cipher);
$iv = substr(hash('sha256', $encryption_key), 0, $iv_length); 

function decrypt_data($encrypted_data, $key, $cipher, $iv) {
    if (empty($encrypted_data)) return "Brak Danych";
    $ciphertext = base64_decode($encrypted_data);
    $decrypted = openssl_decrypt($ciphertext, $cipher, $key, 0, $iv); 
    return ($decrypted === false) ? "Błąd Deszyfrowania" : $decrypted;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd połączenia z bazą: " . $e->getMessage()]);
    exit();
}

$sql = "
    SELECT
        wu.id_wizyty,
        wu.data_wizyty,
        wu.godzina_wizyty,
        wu.status,
        b.imie AS imie_barbera, 
        u.imie AS imie_klienta_enc,
        u.telefon AS telefon_klienta_enc,
        usl.nazwa_uslugi
    FROM wizyty_users wu
    JOIN uslugi usl ON wu.id_uslugi = usl.id_uslugi
    JOIN barberzy b ON usl.id_barbera = b.id_barbera 
    JOIN users u ON wu.id_użytkownika = u.id  /* POPRAWKA: ZAKŁADAMY, ŻE NAZWA TO id_uzytkownika */
    ORDER BY wu.data_wizyty DESC
";

try {
    $stmt = $pdo->query($sql);
    $wizyty_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd zapytania SQL: " . $e->getMessage()]);
    exit();
}

$wizyty = [];
foreach ($wizyty_raw as $w) {
    $imie_klienta = decrypt_data($w['imie_klienta_enc'], $encryption_key, $cipher, $iv);
    $telefon_klienta = decrypt_data($w['telefon_klienta_enc'], $encryption_key, $cipher, $iv);

    $wizyty[] = [
        "id_wizyty" => $w['id_wizyty'],
        "imie_barbera" => $w['imie_barbera'],
        "imie_klienta" => $imie_klienta,
        "telefon_klienta" => $telefon_klienta,
        "nazwa_uslugi" => $w['nazwa_uslugi'],
        "data_wizyty" => $w['data_wizyty'], 
        "godzina_wizyty" => substr($w['godzina_wizyty'], 0, 5),
        "status" => $w['status'],
    ];
}

echo json_encode(["success" => true, "wizyty" => $wizyty]);
?>