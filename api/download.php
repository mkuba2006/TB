<?php
header("Access-Control-Allow-Origin: *");

if (isset($_GET['file'])) {
    $fileName = basename($_GET['file']); 
    $filePath = "../faktury/" . $fileName;

    if (file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        
        readfile($filePath);
        exit;
    } else {
        http_response_code(404);
        die("Błąd: Plik nie istnieje na serwerze.");
    }
} else {
    die("Błąd: Nie podano nazwy pliku.");
}
?>