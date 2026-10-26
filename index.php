<?php
// Jeśli żądanie przychodzi metodą POST (z fetch), obsłuż logikę serwera i zwróć JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    // Pobranie surowych danych JSON z ciała żądania
    $inputJSON = file_get_contents('php://input');
    $data = json_decode($inputJSON, true);

    $errors = [];

    // Czyszczenie i pobieranie danych
    $name = isset($data['name']) ? trim(strip_tags($data['name'])) : '';
    $email = isset($data['email']) ? trim($data['email']) : '';
    $message = isset($data['message']) ? trim(strip_tags($data['message'])) : '';

    // Walidacja po stronie serwera
    if (empty($name)) {
        $errors['name'] = 'Imię jest wymagane.';
    } elseif (mb_strlen($name) < 2) {
        $errors['name'] = 'Imię musi mieć co najmniej 2 znaki.';
    }

    if (empty($email)) {
        $errors['email'] = 'Adres e-mail jest wymagany.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Podano niepoprawny format adresu e-mail.';
    }

    if (empty($message)) {
        $errors['message'] = 'Treść wiadomości jest wymagana.';
    } elseif (mb_strlen($message) < 10) {
        $errors['message'] = 'Wiadomość musi zawierać co najmniej 10 znaków.';
    }

    // Odpowiedź w przypadku błędów
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Wformularzu znajdują się błędy.',
            'errors' => $errors
        ]);
        exit;
    }

    // Sukces (tutaj możesz dodać wysyłanie maila lub zapis do bazy danych)
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Dziękujemy! Twoja wiadomość została pomyślnie wysłana.'
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formularz Kontaktowy PHP</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .contact-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 500px;
        }
        h2 { font-size: 1.8rem; margin-bottom: 6px; color: #1a1a1a; }
        .subtitle { font-size: 0.95rem; color: #666; margin-bottom: 24px; }
        .form-group { margin-bottom: 20px; display: flex; flex-direction: column; }
        label { font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; color: #444; }
        input[type="text"], input[type="email"], textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #dcdfe6;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus, textarea:focus { border-color: #4f46e5; }
        input.is-invalid, textarea.is-invalid { border-color: #e53e3e; }
        .error-message { color: #e53e3e; font-size: 0.825rem; margin-top: 5px; min-height: 18px; }
        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background-color: #4f46e5;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        button[type="submit"]:hover { background-color: #4338ca; }
        button[type="submit"]:disabled { background-color: #9ca3af; cursor: not-allowed; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert.success { background-color: #def7ec; color: #03543f; border: 1px solid #bcf0da; }
        .alert.error { background-color: #fde8e8; color: #9b1c1c; border: 1px solid #fbd5d5; }
        .hidden { display: none !important; }
    </style>
</head>
<body>

    <div class="contact-container">
        <h2>Napisz do nas</h2>
        <p class="subtitle">Masz pytania? Wypełnij poniższy formularz.</p>

        <div id="form-alert" class="alert hidden"></div>

        <form id="contactForm" novalidate>
            <div class="form-group">
                <label for="name">Imię i nazwisko</label>
                <input type="text" id="name" name="name" placeholder="np. Jan Kowalski">
                <span class="error-message" id="nameError"></span>
            </div>

            <div class="form-group">
                <label for="email">Adres e-mail</label>
                <input type="email" id="email" name="email" placeholder="np. jan@example.com">
                <span class="error-message" id="emailError"></span>
            </div>

            <div class="form-group">
                <label for="message">Wiadomość</label>
                <textarea id="message" name="message" rows="5" placeholder="Wpisz treść wiadomości..."></textarea>
                <span class="error-message" id="messageError"></span>
            </div>

            <button type="submit" id="submitBtn">
                <span class="btn-text">Wyślij wiadomość</span>
            </button>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('contactForm');
        const submitBtn = document.getElementById('submitBtn');
        const alertBox = document.getElementById('form-alert');

        const fields = {
            name: { input: document.getElementById('name'), error: document.getElementById('nameError') },
            email: { input: document.getElementById('email'), error: document.getElementById('emailError') },
            message: { input: document.getElementById('message'), error: document.getElementById('messageError') }
        };

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            resetErrors();

            if (!validateForm()) return;

            const formData = {
                name: fields.name.input.value.trim(),
                email: fields.email.input.value.trim(),
                message: fields.message.input.value.trim()
            };

            setLoading(true);

            try {
                // Zapytanie wysyłane do tego samego pliku ('index.php')
                const response = await fetch('index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    showAlert('success', result.message);
                    form.reset();
                } else {
                    if (result.errors) showFieldErrors(result.errors);
                    showAlert('error', result.message || 'Wystąpił błąd podczas wysyłania.');
                }
            } catch (error) {
                showAlert('error', 'Brak połączenia z serwerem. Spróbuj ponownie później.');
            } finally {
                setLoading(false);
            }
        });

        function validateForm() {
            let valid = true;

            if (!fields.name.input.value.trim()) {
                setError(fields.name, 'Pole "Imię" jest wymagane.');
                valid = false;
            } else if (fields.name.input.value.trim().length < 2) {
                setError(fields.name, 'Imię musi mieć co najmniej 2 znaki.');
                valid = false;
            }

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!fields.email.input.value.trim()) {
                setError(fields.email, 'Pole "E-mail" jest wymagane.');
                valid = false;
            } else if (!emailPattern.test(fields.email.input.value.trim())) {
                setError(fields.email, 'Podaj poprawny adres e-mail.');
                valid = false;
            }

            if (!fields.message.input.value.trim()) {
                setError(fields.message, 'Pole "Wiadomość" jest wymagane.');
                valid = false;
            } else if (fields.message.input.value.trim().length < 10) {
                setError(fields.message, 'Wiadomość musi mieć co najmniej 10 znaków.');
                valid = false;
            }

            return valid;
        }

        function setError(fieldObj, message) {
            fieldObj.input.classList.add('is-invalid');
            fieldObj.error.textContent = message;
        }

        function showFieldErrors(serverErrors) {
            for (const [key, msg] of Object.entries(serverErrors)) {
                if (fields[key]) setError(fields[key], msg);
            }
        }

        function resetErrors() {
            alertBox.className = 'alert hidden';
            for (const key in fields) {
                fields[key].input.classList.remove('is-invalid');
                fields[key].error.textContent = '';
            }
        }

        function showAlert(type, message) {
            alertBox.textContent = message;
            alertBox.className = `alert ${type}`;
        }

        function setLoading(isLoading) {
            submitBtn.disabled = isLoading;
            submitBtn.querySelector('.btn-text').textContent = isLoading ? 'Wysyłanie...' : 'Wyślij wiadomość';
        }
    });
    </script>
</body>
</html>