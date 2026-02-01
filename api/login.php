<?php
// Nagłówki CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Obsługa preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- KONFIGURACJA SZYFROWANIA ---
// KLUCZ MUSI BYĆ TAKI SAM JAK W PLIKU REJESTRACJI!
$encryption_key = "TwojBardzoSilnyTajemnyKlucz256Bit"; // Użyj tego samego klucza
$cipher = "AES-256-CBC";
$iv_length = openssl_cipher_iv_length($cipher);
$iv = substr(hash('sha256', $encryption_key), 0, $iv_length); // Stałe IV

/**
 * Funkcja deszyfrująca dane zaszyfrowane metodą AES-256-CBC.
 */
function decrypt_data($encrypted_data, $key, $cipher, $iv) {
    if (empty($encrypted_data)) return false;
    return openssl_decrypt(base64_decode($encrypted_data), $cipher, $key, 0, $iv);
}
// --- KONIEC KONFIGURACJI SZYFROWANIA ---

// Dane do połączenia z bazą
$host = "localhost";
$db = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

// Połączenie z bazą
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Błąd połączenia: " . $conn->connect_error]);
    exit;
}

// Sprawdzanie czy POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['email'], $data['haslo'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Brak danych logowania"]);
        exit;
    }

    $email_input = $data['email'];
    $password = $data['haslo'];

    // 1. GENEROWANIE HASHU DO WYSZUKIWANIA
    $email_search_hash = hash('sha256', strtolower($email_input));
    
    // 2. Zmienione zapytanie SQL: Wyszukujemy po 'email_hash_search' 
    // i pobieramy zaszyfrowane dane 'email', 'imie' i 'telefon'.
    $stmt = $conn->prepare("SELECT id, email, imie, haslo, telefon FROM users WHERE email_hash_search = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Błąd zapytania do bazy"]);
        exit;
    }
    $stmt->bind_param("s", $email_search_hash);
    $stmt->execute();
    $result = $stmt->get_result();

    // Sprawdzenie czy znaleziono użytkownika
    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Nieprawidłowy email lub hasło"]);
        exit;
    }

    $user = $result->fetch_assoc();
    $hashedPassword = $user['haslo'];

    // Sprawdzenie hasła
    if (password_verify($password, $hashedPassword)) {
        
        // --- DESZYFROWANIE DANYCH ---
        // Deszyfrowanie wszystkich pól zaszyfrowanych dwukierunkowo
        $decrypted_email = decrypt_data($user['email'], $encryption_key, $cipher, $iv);
        $decrypted_imie = decrypt_data($user['imie'], $encryption_key, $cipher, $iv);
        $decrypted_telefon = decrypt_data($user['telefon'], $encryption_key, $cipher, $iv); 
        
        // Sprawdzenie i fallback na komunikat błędu
        $decrypted_email = ($decrypted_email === false) ? "[Błąd odszyfrowania emaila]" : $decrypted_email;
        $decrypted_imie = ($decrypted_imie === false) ? "[Błąd odszyfrowania imienia]" : $decrypted_imie;
        $decrypted_telefon = ($decrypted_telefon === false) ? "[Błąd odszyfrowania telefonu]" : $decrypted_telefon;
        
        // --- KONIEC DESZYFROWANIA ---

        echo json_encode([
            "success" => true,
            "message" => "Zalogowano pomyślnie",
            "user" => [
                "id" => $user['id'],
                "email" => $decrypted_email, // Zwraca odszyfrowany email
                "imie" => $decrypted_imie, // Zwraca odszyfrowane imię
                "telefon" => $decrypted_telefon // Zwraca odszyfrowany telefon
            ]
        ]);
        exit;
    } else {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Nieprawidłowy email lub hasło"]);
        exit;
    }

    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Dozwolona tylko metoda POST"]);
    exit;
}

$conn->close();
?>