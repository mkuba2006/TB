<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

date_default_timezone_set('Europe/Warsaw');

$host = "localhost";
$db = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

$barberId = isset($_GET['barberId']) ? intval($_GET['barberId']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';
$serviceDuration = isset($_GET['duration']) ? intval($_GET['duration']) : 60; 
$buffer = 15; // Czas na sprzątanie (minuty)

if ($barberId <= 0 || !$date) {
    echo json_encode([]);
    exit;
}

$todayDate = date("Y-m-d");  
$currentTimestamp = time();       

$sqlUrlop = "
    SELECT COUNT(*) as cnt 
    FROM dezaktywacje 
    WHERE id_barbera = ? 
    AND ? BETWEEN data_od AND data_do
";
$stmtUrlop = $conn->prepare($sqlUrlop);
$stmtUrlop->bind_param("is", $barberId, $date);
$stmtUrlop->execute();
$resultUrlop = $stmtUrlop->get_result();
$rowUrlop = $resultUrlop->fetch_assoc();

if ($rowUrlop['cnt'] > 0) {
    echo json_encode([]); // Urlop - brak terminów
    exit;
}
$stmtUrlop->close();

$sql = "
    SELECT u.godzina_wizyty, s.czas_wizyty
    FROM wizyty_users u
    JOIN uslugi s ON u.id_uslugi = s.id_uslugi
    WHERE s.id_barbera = ? AND u.data_wizyty = ? AND u.status = 'Umówiona'
    ORDER BY u.godzina_wizyty ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $barberId, $date);
$stmt->execute();
$result = $stmt->get_result();

$bookedSlots = [];
$baseDate = "1970-01-01"; 

while ($row = $result->fetch_assoc()) {
    $visitStartTimestamp = strtotime($baseDate . ' ' . $row['godzina_wizyty']);
    
    $parts = explode(':', $row['czas_wizyty']);
    $durationSeconds = ($parts[0] * 3600) + ($parts[1] * 60);
    
    $visitFullEnd = $visitStartTimestamp + $durationSeconds + ($buffer * 60);

    $bookedSlots[] = [
        'start' => $visitStartTimestamp,
        'end'   => $visitFullEnd
    ];
}
$stmt->close();

$availableSlots = [];
$startTime = strtotime($baseDate . " 08:00:00");
$endTime = strtotime($baseDate . " 18:00:00");

for ($time = $startTime; $time < $endTime; $time += 15 * 60) {
    
    $newSlotStart = $time;
    $newSlotWorkEnd = $newSlotStart + ($serviceDuration * 60);
    $newSlotFullEnd = $newSlotWorkEnd + ($buffer * 60);

    if ($date === $todayDate) {
        $slotTimeStr = date("H:i:s", $time);
        $slotRealTimestamp = strtotime($date . " " . $slotTimeStr);
        
        if ($slotRealTimestamp <= ($currentTimestamp + 300)) { 
            continue; 
        }
    }

    if ($newSlotWorkEnd > $endTime) {
        break; 
    }

    $isFree = true;
    foreach ($bookedSlots as $booked) {
        if ($newSlotStart < $booked['end'] && $newSlotFullEnd > $booked['start']) {
            $isFree = false;
            break;
        }
    }

    if ($isFree) {
        $availableSlots[] = date("H:i", $time);
    }
}

echo json_encode($availableSlots);
$conn->close();
?>