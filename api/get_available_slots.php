<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

$host = "localhost";
$db = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Błąd połączenia: " . $conn->connect_error]);
    exit;
}

if (!isset($_GET['id'], $_GET['date'], $_GET['duration'])) {
    http_response_code(400);
    echo json_encode(["error" => "Brak parametrów"]);
    exit;
}

$barberId = intval($_GET['id']);
$date = $_GET['date'];
$duration = intval($_GET['duration']); 
$stmt = $conn->prepare("
    SELECT 
        w.godzina_wizyty,
        u.czas_wizyty
    FROM wizyty_users w
    JOIN uslugi u ON w.id_uslugi = u.id_uslugi
    WHERE u.id_barbera = ?
    AND w.data_wizyty = ?
    ORDER BY w.godzina_wizyty ASC
");
$stmt->bind_param("is", $barberId, $date);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = [
        "start" => $row['godzina_wizyty'],
        "duration" => $row['czas_wizyty']
    ];
}

function timeToMinutes($time)
{
    list($h, $m) = explode(":", substr($time, 0, 5));
    return intval($h) * 60 + intval($m);
}

$startDay = 8 * 60;
$endDay = 18 * 60;
$slots = [];

for ($t = $startDay; $t + $duration <= $endDay; $t += 15) {

    $free = true;

    foreach ($appointments as $app) {
        $appStart = timeToMinutes($app['start']);
        $appDuration = timeToMinutes($app['duration']);
        $appEnd = $appStart + $appDuration + 15; // przerwa

        $slotEnd = $t + $duration;

        if (!($slotEnd <= $appStart || $t >= $appEnd)) {
            $free = false;
            break;
        }
    }

    if ($free) {
        $h = floor($t / 60);
        $m = $t % 60;
        $slots[] = sprintf("%02d:%02d", $h, $m);
    }
}

echo json_encode($slots);

$stmt->close();
$conn->close();
?>
