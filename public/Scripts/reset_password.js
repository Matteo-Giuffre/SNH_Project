    // Funzione di validazione per il modulo
    function process_reset(event) {
        event.preventDefault();

        const PasswordError = document.getElementById('PasswordError');
        PasswordError.innerHTML = '';

        const password = document.getElementById('newpassword').value.trim();
        if (!password.match(/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&_-])[A-Za-z\d@$!%*?&_-]{8,64}$/)) {
            PasswordError.innerHTML = 'Invalid Password';
            return;
        }

        const confirmPassword = document.getElementById('confirmpassword').value.trim();
        if (confirmPassword !== password) {
            PasswordError.innerHTML = 'Passwords do not match';
            return;
        }

        // Retrieve elements
        const form = document.getElementById('reset-password-form');
        const instructions = document.getElementById('instruction-text');
        const messageContainer = document.getElementById('message-container');
        const messageElement = document.getElementById('message');
        const backhome = document.getElementById("backhome");
        
        // Recupera token
        const token = document.getElementById('token').value.trim();

        // Send data to process_reset.php
        fetch("process_reset.php", {
            method: "POST",
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `token=${encodeURIComponent(token)}&newpassword=${encodeURIComponent(password)}`
        })
        .then(response => response.json()) // Risposta JSON dal server
        .then(data => {
            if (data.status === 'success') {
                // Nascondi il modulo
                messageElement.innerHTML = data.message; // Mostra messaggio di successo
                messageElement.style.color = 'green'; // Colore verde per successo
                
                instructions.style.display = 'none';
                form.style.display = 'none';
                messageContainer.hidden = false;
                backhome.hidden = false;
            } else {
                // Nascondi il modulo
                messageElement.innerHTML = data.message; // Mostra messaggio di errore
                messageElement.style.color = 'red'; // Colore rosso per fallimento
                
                instructions.style.display = 'none';
                form.style.display = 'none';
                messageContainer.hidden = false;
                backhome.hidden = false;
            }
        })
        .catch(error => console.error("Error: ", error));
    };

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('reset-password-form');

        if (form) {
            form.addEventListener('submit', process_reset);
        }
    });