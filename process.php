<?php
// Ustawienie nagłówka odpowiedzi na JSON
header('Content-Type: application/json; charset=utf-8');

// Akceptuj tylko żądania POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Niedozwolona metoda żądania.'
    ]);
    exit;
}

// Pobranie surowych danych JSON z ciała żądania
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

// Tablice na błędy
$errors = [];

// Pobranie i oczyszczenie pól (Sanityzacja)
$name = isset($data['name']) ? trim(strip_tags($data['name'])) : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$message = isset($data['message']) ? trim(strip_tags($data['message'])) : '';

// 1. Walidacja imienia
if (empty($name)) {
    $errors['name'] = 'Imię jest wymagane (serwer).';
} elseif (mb_strlen($name) < 2) {
    $errors['name'] = 'Imię musi mieć co najmniej 2 znaki.';
}

// 2. Walidacja emaila
if (empty($email)) {
    $errors['email'] = 'Adres e-mail jest wymagany (serwer).';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Podano niepoprawny format adresu e-mail.';
}

// 3. Walidacja wiadomości
if (empty($message)) {
    $errors['message'] = 'Treść wiadomości jest wymagana (serwer).';
} elseif (mb_strlen($message) < 10) {
    $errors['message'] = 'Wiadomość musi zawierać co najmniej 10 znaków.';
}

// Jeśli wystąpiły błędy, zwróć kod 400 oraz listę błędów
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Wformularzu znajdują się błędy.',
        'errors' => $errors
    ]);
    exit;
}

// --- MIEJSCE NA LOGIKĘ BIZNESOWĄ ---
// W tym miejscu możesz np. wysłać e-mail za pomocą mail() / PHPMailer
// lub zapisać dane w bazie danych (np. MySQL / PDO).
// -----------------------------------

// Odpowiedź w przypadku sukcesu (HTTP 200)
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Dziękujemy! Twoja wiadomość została pomyślnie wysłana.'
]);