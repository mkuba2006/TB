<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

$encryption_key = "TwojBardzoSilnyTajemnyKlucz256Bit"; 
$cipher = "AES-256-CBC";
$iv_length = openssl_cipher_iv_length($cipher);
$iv = substr(hash('sha256', $encryption_key), 0, $iv_length); 

function encrypt_data($data, $key, $cipher, $iv) {
    if (empty($data)) return "";
    return base64_encode(openssl_encrypt($data, $cipher, $key, 0, $iv));
}

$host = "localhost";
$db = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Błąd połączenia z bazą: " . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['email'], $data['imie'], $data['haslo'], $data['telefon'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Brak wymaganych danych"]);
        exit;
    }

    $email_plain = $data['email']; 
    $imie = $data['imie']; 
    $telefon = $data['telefon'];
    $haslo_raw = $data['haslo'];

    $email_search_hash = hash('sha256', strtolower($email_plain));
    
    $email_encrypted = encrypt_data($email_plain, $encryption_key, $cipher, $iv);
    $imie_encrypted = encrypt_data($imie, $encryption_key, $cipher, $iv);
    $telefon_encrypted = encrypt_data($telefon, $encryption_key, $cipher, $iv);
    
    $haslo_hashed = password_hash($haslo_raw, PASSWORD_DEFAULT);
    $check = $conn->prepare("SELECT id FROM users WHERE email_hash_search = ?");
    $check->bind_param("s", $email_search_hash); 
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        http_response_code(409);
        echo json_encode(["success" => false, "message" => "Użytkownik z tym e-mailem już istnieje"]);
        $check->close();
        $conn->close();
        exit;
    }
    $check->close();

    $stmt = $conn->prepare("INSERT INTO users (email_hash_search, email, imie, haslo, telefon) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", 
        $email_search_hash, 
        $email_encrypted, 
        $imie_encrypted, 
        $haslo_hashed, 
        $telefon_encrypted
    );

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Użytkownik dodany"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Błąd dodawania użytkownika: " . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Tylko metoda POST jest dozwolona"]);
}

$conn->close();
?>