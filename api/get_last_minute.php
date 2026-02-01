<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$host = "localhost";
$db = "host574875_TEST";
$user = "host574875_kuba";
$pass = "kuba2006";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    date_default_timezone_set('Europe/Warsaw');

    // --- KONFIGURACJA ---
    $shopOpenHour = 8;
    $shopCloseHour = 18;
    
    // ZMIANA: Ustawiamy 60 minut jako domyślny czas szukanej wizyty
    // Jeśli chcesz, by było to dynamiczne, możesz pobierać to z $_GET['duration']
    $serviceDuration = isset($_GET['duration']) ? intval($_GET['duration']) : 60; 
    
    $buffer = 15;          // Czas na sprzątanie
    $maxDaysToCheck = 14; 
    $arrivalBuffer = 45;   // Ile czasu dajemy klientowi na dojazd (tylko dla DZIŚ)

    // Pobieramy listę barberów
    $stmtBarbers = $pdo->query("SELECT id_barbera, imie FROM barberzy");
    $barbers = $stmtBarbers->fetchAll(PDO::FETCH_ASSOC);

    $bestSlot = null;

    // --- 1. PĘTLA PO DNIACH ---
    for ($dayOffset = 0; $dayOffset < $maxDaysToCheck; $dayOffset++) {
        
        $currentDate = date('Y-m-d', strtotime("+$dayOffset days"));
        
        // Pomijanie Niedziel (0) i Poniedziałków (1)
        $dayNum = (int)date('w', strtotime($currentDate));
        if ($dayNum === 0 || $dayNum === 1) continue; 

        $shopOpenTimestamp = strtotime("$currentDate $shopOpenHour:00:00");
        $shopCloseTimestamp = strtotime("$currentDate $shopCloseHour:00:00");

        // Ustalanie startu szukania
        if ($dayOffset === 0) {
            // DZIŚ: Teraz + czas na dojazd
            $minStartTimestamp = time() + ($arrivalBuffer * 60);
            
            // Zaokrąglenie w górę do 15 min
            $remainder = $minStartTimestamp % (15 * 60);
            if ($remainder != 0) {
                $minStartTimestamp += ((15 * 60) - $remainder);
            }

            if ($minStartTimestamp >= $shopCloseTimestamp) continue;
            if ($minStartTimestamp < $shopOpenTimestamp) $minStartTimestamp = $shopOpenTimestamp;

        } else {
            // INNE DNI: Od otwarcia
            $minStartTimestamp = $shopOpenTimestamp;
        }

        // --- 2. PĘTLA PO BARBERACH ---
        foreach ($barbers as $barber) {
            $barberId = $barber['id_barbera'];

            // A. Urlopy
            $stmtUrlop = $pdo->prepare("SELECT COUNT(*) FROM dezaktywacje WHERE id_barbera = :bid AND :currentDate BETWEEN data_od AND data_do");
            $stmtUrlop->execute([':bid' => $barberId, ':currentDate' => $currentDate]);
            if ($stmtUrlop->fetchColumn() > 0) continue; 

            // B. Pobieranie zajętych terminów
            $stmtWizyty = $pdo->prepare("
                SELECT u.godzina_wizyty, s.czas_wizyty
                FROM wizyty_users u
                JOIN uslugi s ON u.id_uslugi = s.id_uslugi
                WHERE s.id_barbera = :bid 
                AND u.data_wizyty = :currentDate 
                AND u.status = 'Umówiona'
                ORDER BY u.godzina_wizyty ASC
            ");
            $stmtWizyty->execute([':bid' => $barberId, ':currentDate' => $currentDate]);
            $wizyty = $stmtWizyty->fetchAll(PDO::FETCH_ASSOC);

            // C. Tworzenie mapy zajętości (Start -> Koniec + Bufor)
            $bookedSlots = [];
            foreach ($wizyty as $w) {
                $czasCzesci = explode(':', $w['czas_wizyty']);
                $czasSekundy = ($czasCzesci[0] * 3600) + ($czasCzesci[1] * 60);
                
                $visitStart = strtotime("$currentDate " . $w['godzina_wizyty']);
                $visitFullEnd = $visitStart + $czasSekundy + ($buffer * 60); // Zajęte aż do końca sprzątania
                
                $bookedSlots[] = ['start' => $visitStart, 'end' => $visitFullEnd];
            }

            // D. Szukanie okienka
            for ($time = $minStartTimestamp; $time < $shopCloseTimestamp; $time += 15 * 60) {
                
                // Obliczamy ramy czasowe NOWEJ potencjalnej wizyty
                $newSlotStart = $time;
                $newSlotWorkEnd = $newSlotStart + ($serviceDuration * 60); 
                $newSlotFullEnd = $newSlotWorkEnd + ($buffer * 60); // Musi być wolne aż do końca sprzątania po nas

                // Warunek 1: Czy skończymy przed zamknięciem?
                if ($newSlotWorkEnd > $shopCloseTimestamp) {
                    break; 
                }

                // Warunek 2: Kolizje z innymi wizytami
                $isFree = true;
                foreach ($bookedSlots as $booked) {
                    // WZÓR NA KOLIZJĘ: (Start A < Koniec B) ORAZ (Koniec A > Start B)
                    // Tutaj "Koniec" oznacza czas z wliczonym buforem.
                    
                    // Przykład z Twojego błędu (dla 60 min):
                    // Nowa wizyta (15:45): Start 15:45, KoniecPracy 16:45, KoniecZBuforem 17:00
                    // Istniejąca wizyta (16:45): Start 16:45
                    
                    // Sprawdzenie:
                    // 1. 15:45 < 18:00 (koniec istniejącej) -> PRAWDA
                    // 2. 17:00 > 16:45 (start istniejącej)  -> PRAWDA
                    // WNIOSEK: Kolizja! ($isFree = false)
                    
                    if ($newSlotStart < $booked['end'] && $newSlotFullEnd > $booked['start']) {
                        $isFree = false;
                        break;
                    }
                }

                if ($isFree) {
                    // Znaleziono termin
                    if ($bestSlot === null || $time < $bestSlot['timestamp']) {
                        
                        $terminText = "";
                        if ($dayOffset === 0) {
                            $terminText = "DZIŚ, " . date("H:i", $time);
                        } elseif ($dayOffset === 1) {
                            $terminText = "JUTRO, " . date("H:i", $time);
                        } else {
                            $daysPL = ['Nd', 'Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'So'];
                            $dayOfWeek = $daysPL[date('w', strtotime($currentDate))];
                            $formattedDate = date("d.m", strtotime($currentDate));
                            $terminText = "$dayOfWeek ($formattedDate), " . date("H:i", $time);
                        }

                        $bestSlot = [
                            'timestamp' => $time,
                            'termin' => $terminText,
                            'barber' => $barber['imie']
                        ];
                    }
                    // Mamy najlepszy termin dla tego barbera w tym dniu, przerywamy pętlę godzin
                    break; 
                }
            }
        } // koniec barberów

        // Jeśli mamy termin dzisiaj, nie szukamy jutro
        if ($bestSlot !== null) {
            break;
        }
    }

    if ($bestSlot) {
        echo json_encode([
            "success" => true,
            "termin" => $bestSlot['termin'],
            "barber" => $bestSlot['barber']
        ]);
    } else {
        echo json_encode([
            "success" => false, 
            "message" => "Brak terminów na 60-minutową wizytę."
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>