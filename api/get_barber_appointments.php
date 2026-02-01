<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$encryption_key = "TwojBardzoSilnyTajemnyKlucz256Bit"; 
$cipher = "AES-256-CBC";
$iv_length = openssl_cipher_iv_length($cipher);
$iv = substr(hash('sha256', $encryption_key), 0, $iv_length); 

function decrypt_data($encrypted_data, $key, $cipher, $iv) {
    if (empty($encrypted_data)) return "";
    $decrypted = openssl_decrypt(base64_decode($encrypted_data), $cipher, $key, 0, $iv);
    return $decrypted !== false ? $decrypted : $encrypted_data;
}

$host = "localhost"; 
$db = "host574875_TEST"; 
$user = "host574875_kuba"; 
$pass = "kuba2006";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql_resources = "SELECT id_barbera, imie FROM barberzy ORDER BY id_barbera ASC";
    $stmt_res = $pdo->prepare($sql_resources);
    $stmt_res->execute();
    $raw_resources = $stmt_res->fetchAll(PDO::FETCH_ASSOC);

    $resources = [];
    foreach ($raw_resources as $res) {
        $resources[] = [
            'id' => (int)$res['id_barbera'], 
            'title' => $res['imie']
        ];
    }

    $sql_events = "
        SELECT 
            w.id_wizyty,
            w.data_wizyty,
            w.godzina_wizyty,
            u.id_barbera,         -- To pole łączy wizytę z kolumną barbera
            u.nazwa_uslugi,
            u.czas_wizyty AS czas_trwania,
            users.imie AS imie_encrypted,
            users.email AS email_encrypted,
            users.telefon AS telefon_encrypted
        FROM wizyty_users w
        JOIN uslugi u ON w.id_uslugi = u.id_uslugi          -- Łączymy wizytę z usługą
        JOIN users ON w.id_użytkownika = users.id           -- Łączymy z klientem
        WHERE w.status != 'Anulowana'                       -- Pomijamy anulowane
    ";

    $stmt_events = $pdo->prepare($sql_events);
    $stmt_events->execute();
    $wizyty = $stmt_events->fetchAll(PDO::FETCH_ASSOC);

    $events = [];

    foreach ($wizyty as $w) {
        $imie = decrypt_data($w['imie_encrypted'], $encryption_key, $cipher, $iv);
        $email = decrypt_data($w['email_encrypted'], $encryption_key, $cipher, $iv);
        $telefon = decrypt_data($w['telefon_encrypted'], $encryption_key, $cipher, $iv);

        $startStr = $w['data_wizyty'] . ' ' . $w['godzina_wizyty'];
        try {
            $start = new DateTime($startStr);
        } catch (Exception $e) { continue; }

        $czasParts = explode(':', $w['czas_trwania']); 
        $minutes = 60; 
        if (count($czasParts) >= 2) {
            $minutes = ($czasParts[0] * 60) + $czasParts[1];
        }
        
        $end = clone $start;
        $end->modify("+$minutes minutes");

        $events[] = [
            'id' => (int)$w['id_wizyty'],
            'resourceId' => (int)$w['id_barbera'],
            'title' => $imie . ' - ' . $w['nazwa_uslugi'], 
            'start' => $start->format('Y-m-d\TH:i:s'),
            'end' => $end->format('Y-m-d\TH:i:s'),
            'client_mail' => $email,
            'client_phone' => $telefon,
            'client_name' => $imie
        ];
    }

    echo json_encode([
        "events" => $events,
        "resources" => $resources
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => "Błąd bazy: " . $e->getMessage()]);
}
?>