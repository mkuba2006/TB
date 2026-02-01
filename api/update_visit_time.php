<?php
// 1. NAGŁÓWKI CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// 2. OBSŁUGA PREFLIGHT
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 3. DIAGNOSTYKA
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

function logger($msg) {
    file_put_contents('debug.txt', date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

// --- KONFIGURACJA BAZY I SZYFROWANIA ---
$host = "localhost"; 
$db = "host574875_TEST"; 
$user = "host574875_kuba"; 
$pass = "kuba2006";

$encryption_key = "TwojBardzoSilnyTajemnyKlucz256Bit"; 
$cipher = "AES-256-CBC";
$iv_length = openssl_cipher_iv_length($cipher);
$iv = substr(hash('sha256', $encryption_key), 0, $iv_length); 

function decrypt_data($data, $key, $cipher, $iv) {
    if (empty($data)) return "";
    return openssl_decrypt(base64_decode($data), $cipher, $key, 0, $iv);
}

// --- GŁÓWNA LOGIKA ---
try {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!isset($data['id_wizyty']) || !isset($data['new_start'])) {
        throw new Exception("Brak wymaganych danych (id_wizyty lub new_start)");
    }

    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $id_wizyty = $data['id_wizyty'];
    $new_start = $data['new_start'];
    $notify_client = $data['notify_client'] ?? false;

    // Konwersja czasu
    $dateObj = new DateTime($new_start);
    $dateObj->setTimezone(new DateTimeZone('Europe/Warsaw')); 
    $data_wizyty = $dateObj->format('Y-m-d');
    $godzina_wizyty = $dateObj->format('H:i:s');
    $pelna_data = $dateObj->format('Y-m-d H:i'); // Format do maila

    // Aktualizacja w bazie
    $sql = "UPDATE wizyty_users SET data_wizyty = :d, godzina_wizyty = :t WHERE id_wizyty = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':d' => $data_wizyty, ':t' => $godzina_wizyty, ':id' => $id_wizyty]);

    $mail_sent = false;

    if ($notify_client) {
        $stmtUser = $pdo->prepare("
            SELECT u.email, u.imie 
            FROM wizyty_users w 
            JOIN users u ON w.id_użytkownika = u.id 
            WHERE w.id_wizyty = :id
        ");
        $stmtUser->execute([':id' => $id_wizyty]);
        $client = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if ($client) {
            $decrypted_email = decrypt_data($client['email'], $encryption_key, $cipher, $iv);
            $decrypted_imie = decrypt_data($client['imie'], $encryption_key, $cipher, $iv);

            if (!empty($decrypted_email)) {
                // --- KONFIGURACJA MAILA ---
                
                // 1. ADRESY
                $server_sender_email = "no-reply@jmdeveloper.pl"; 
                $reply_to_email = "TedBarber@gmail.com"; // Tu odpisze klient
                $sender_name = "TedBarber";

                $subject = "📅 Zmiana godziny wizyty - Twój Barber";

                // 2. TREŚĆ HTML
                $message_html = '
                <html>
                <head>
                  <title>Zmiana terminu wizyty</title>
                </head>
                <body style="font-family: Helvetica, Arial, sans-serif; margin: 0; padding: 0;">
                  <div style="max-width: 600px; margin: 20px auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #eeeeee;">
                    
                    <h2 style="color: #222222; margin-top: 0; font-size: 24px;">Cześć, '.$decrypted_imie.'! 👋</h2>
                    
                    <p style="color: #555555; font-size: 16px; line-height: 1.6;">
                      Ważna informacja: Godzina Twojej wizyty została zaktualizowana. Poniżej znajdziesz nowe szczegóły:
                    </p>
                    
                    <div style="background-color: #e3f2fd; border-left: 6px solid #2196f3; padding: 20px; margin: 25px 0; border-radius: 4px;">
                      <p style="margin: 0; text-transform: uppercase; font-size: 12px; font-weight: bold; color: #1976d2; letter-spacing: 1px;">NOWY TERMIN</p>
                      <p style="margin: 8px 0 0 0; font-size: 22px; font-weight: bold; color: #333333;">📅 '.$pelna_data.'</p>
                    </div>

                    <hr style="border: 0; border-top: 1px solid #eeeeee; margin: 30px 0;">
                    
                    <div style="text-align: center;">
                      <h1 style="text-decoration: none; font-size: 13px; color: #888;">TED BARBER TEAM</h1>
                    </div>
                  </div>
                </body>
                </html>
                ';

                // 3. NAGŁÓWKI
                $headers  = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
                $headers .= "From: $sender_name <$server_sender_email>" . "\r\n";
                $headers .= "Reply-To: $reply_to_email" . "\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();

                // 4. WYSYŁKA Z PARAMETREM -f (NAPRAWA SPAMU)
                // Wymusza ustawienie Envelope Sender na domenę jmdeveloper.pl
                $params = "-f" . $server_sender_email;

                if(mail($decrypted_email, $subject, $message_html, $headers, $params)) {
                    $mail_sent = true;
                    logger("Mail HTML (z parametrem -f) wysłano do: $decrypted_email");
                } else {
                    logger("Błąd funkcji mail() do: $decrypted_email");
                }
            }
        }
    }

    echo json_encode(["success" => true, "mail_sent" => $mail_sent]);

} catch (Exception $e) {
    http_response_code(500);
    logger("Błąd krytyczny: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>