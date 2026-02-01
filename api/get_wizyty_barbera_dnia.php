<?php
// WŁĄCZENIE WYŚWIETLANIA BŁĘDÓW (usuń na produkcji)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// --- Obsługa żądania Preflight OPTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

// --- Konfiguracja bazy danych i szyfrowania ---
$host = "localhost"; 
$db   = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

$encryption_key = "TwojBardzoSilnyTajemnyKlucz256Bit"; 
$cipher = "AES-256-CBC";

$iv_length = openssl_cipher_iv_length($cipher);
$iv = substr(hash('sha256', $encryption_key), 0, $iv_length); 

// --- Funkcja deszyfrująca ---
function decrypt_data($encrypted_data, $key, $cipher, $iv) {
    if (empty($encrypted_data)) return "Brak Danych";
    $ciphertext = base64_decode($encrypted_data);
    $decrypted = openssl_decrypt($ciphertext, $cipher, $key, 0, $iv); 
    return ($decrypted === false) ? "Błąd Deszyfrowania" : $decrypted;
}

// --- Połączenie z bazą danych ---
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd połączenia z bazą: " . $e->getMessage()]);
    exit();
}

// Odbieranie danych z ciała POST
$data = json_decode(file_get_contents("php://input"), true);
$id_barbera = $data['id_barbera'] ?? null;
$data_wizyty = $data['data_wizyty'] ?? null; // Format YYYY-MM-DD

if (empty($id_barbera) || empty($data_wizyty)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Brak ID barbera lub daty wizyty w żądaniu."]);
    exit();
}


// --- Złożone Zapytanie SQL (Grafik dnia) ---
$sql = "
    SELECT
        wu.id_wizyty,
        wu.godzina_wizyty,
        usl.nazwa_uslugi,
        u.imie AS imie_klienta_enc,
        u.telefon AS telefon_klienta_enc
    FROM wizyty_users wu
    JOIN uslugi usl ON wu.id_uslugi = usl.id_uslugi
    JOIN users u ON wu.id_użytkownika = u.id 
    WHERE 
        usl.id_barbera = :id_barbera AND 
        wu.data_wizyty = :data_wizyty AND
        wu.status = 'Umówiona' /* Pobieramy tylko aktywne/umówione wizyty */
    ORDER BY 
        wu.godzina_wizyty ASC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_barbera' => $id_barbera,
        ':data_wizyty' => $data_wizyty
    ]);
    $wizyty_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Błąd zapytania SQL: " . $e->getMessage()]);
    exit();
}

$wizyty = [];
foreach ($wizyty_raw as $w) {
    // Deszyfrowanie danych klienta
    $imie_klienta = decrypt_data($w['imie_klienta_enc'], $encryption_key, $cipher, $iv);
    $telefon_klienta = decrypt_data($w['telefon_klienta_enc'], $encryption_key, $cipher, $iv);

    $wizyty[] = [
        "id_wizyty" => $w['id_wizyty'],
        "imie_klienta" => $imie_klienta,
        "telefon_klienta" => $telefon_klienta,
        "nazwa_uslugi" => $w['nazwa_uslugi'],
        "godzina_wizyty" => substr($w['godzina_wizyty'], 0, 5), // Format HH:MM
    ];
}

echo json_encode(["success" => true, "wizyty" => $wizyty]);
?>