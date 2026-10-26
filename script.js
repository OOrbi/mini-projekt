document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    const alertBox = document.getElementById('form-alert');

    // Elementy pól i ich komunikaty błędów
    const fields = {
        name: {
            input: document.getElementById('name'),
            error: document.getElementById('nameError')
        },
        email: {
            input: document.getElementById('email'),
            error: document.getElementById('emailError')
        },
        message: {
            input: document.getElementById('message'),
            error: document.getElementById('messageError')
        }
    };

    // Obsługa zdarzenia submit
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Reset poprzednich błędów i alertów
        resetErrors();

        // 1. Walidacja w JavaScript
        const isValid = validateForm();

        if (!isValid) return;

        // Pobranie danych z formularza
        const formData = {
            name: fields.name.input.value.trim(),
            email: fields.email.input.value.trim(),
            message: fields.message.input.value.trim()
        };

        // Zablokuj przycisk na czas wysyłania
        setLoading(true);

        try {
            // 2. Wysyłanie danych przez fetch() do PHP
            const response = await fetch('process.php', {
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
                form.reset(); // Czyszczenie pól po sukcesie
            } else {
                // Jeśli serwer zwrócił błędy konkretnych pól
                if (result.errors) {
                    showFieldErrors(result.errors);
                }
                showAlert('error', result.message || 'Wystąpił błąd podczas wysyłania.');
            }
        } catch (error) {
            showAlert('error', 'Brak połączenia z serwerem. Spróbuj ponownie później.');
            console.error('Fetch error:', error);
        } finally {
            setLoading(false);
        }
    });

    // Funkcja walidująca po stronie klienta
    function validateForm() {
        let valid = true;

        // Walidacja Imienia
        if (!fields.name.input.value.trim()) {
            setError(fields.name, 'Pole "Imię" jest wymagane.');
            valid = false;
        } else if (fields.name.input.value.trim().length < 2) {
            setError(fields.name, 'Imię musi mieć co najmniej 2 znaki.');
            valid = false;
        }

        // Walidacja Email
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!fields.email.input.value.trim()) {
            setError(fields.email, 'Pole "E-mail" jest wymagane.');
            valid = false;
        } else if (!emailPattern.test(fields.email.input.value.trim())) {
            setError(fields.email, 'Podaj poprawny adres e-mail.');
            valid = false;
        }

        // Walidacja Wiadomości
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
            if (fields[key]) {
                setError(fields[key], msg);
            }
        }
    }

    function resetErrors() {
        alertBox.classList.add('hidden');
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
        if (isLoading) {
            submitBtn.disabled = true;
            submitBtn.querySelector('.btn-text').textContent = 'Wysyłanie...';
        } else {
            submitBtn.disabled = false;
            submitBtn.querySelector('.btn-text').textContent = 'Wyślij wiadomość';
        }
    }
});