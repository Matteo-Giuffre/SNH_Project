<?php

// Configurazione del database
require_once '../config_db.php';

// Verifica se il token è presente nell'URL
if (!isset($_GET['token'])) {
    http_response_code(400); // Richiesta non valida
    exit();
}

$token = $_GET['token'];

// Cerca il token nel database e verifica la scadenza
$query = "SELECT user_id FROM password_resets WHERE token = :token AND expiry > :current_time";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':token', $token, PDO::PARAM_STR);
$stmt->bindValue(':current_time', time(), PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    header("Location: expired.html");
    exit();
}

$userId = $result['user_id']; // ID utente per il reset della password

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Novelist Space</title>
    <link rel="stylesheet" href="Styles/password_recovery_style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Reset Password</h1>
            <p id="instruction-text"> Enter a new password for your account.</p>
            <form onsubmit="process_reset(event)">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <label for="newpassword">New Password</label>
                <input type="password" id="newpassword" name="newpassword" placeholder="Enter new password" required
                    pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,64}$">
                <p class="password-requirements">
                    Password must be at least 8 characters long and include:
                    - At least one Capital letter
                    - At least one number
                    - At least one special character (@,$,!,%,*,?,&)
                </p>
                <label for="confirmpassword">Confirm Password</label>
                <input type="password" id="confirmpassword" placeholder="Confirm password" required>
                <span class="error-message2" id="PasswordError"></span>
                <button type="submit">Change Password</button>
            </form>
            <div id="message-container" style="display:none;">
                <!-- Questo div mostrerà il messaggio di successo o errore -->
                <p id="message" class="message"></p>
            </div>
            <a id="backhome" href="../index.html" class="back-link" hidden>Back to Home</a>
        </div>
    </div>

    <script>
        // Funzione di validazione per il modulo
        function process_reset(event){
            
            event.preventDefault();
            var password = document.getElementById('newpassword').value;
            var confirmPassword = document.getElementById('confirmpassword').value;
            var PasswordError = document.getElementById('PasswordError');
            var isValid = true;

            // Rimuovi eventuali messaggi di errore precedenti
            PasswordError.textContent = '';

            // Controllo se le due password corrispondono
            if (password !== confirmPassword) {
                PasswordError.textContent = 'Passwords do not match!';
                isValid = false;
            }
            // Se il modulo non è valido, blocca l'invio
            if (!isValid) {
                return;
            }
            else{
                // Recupera user_id e token da campi nascosti nel form
                var form = event.target;
                var formData = new FormData(form);
                // Aggiungi manualmente la nuova password
                formData.append("newpassword", password);

                // Send data to process_reset.php
                fetch("process_reset.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json()) // Risposta JSON dal server
                .then(data => {
                    // A seconda del risultato, mostriamo il messaggio di successo o errore
                    if (data.message === "Invalid Password") {
                            PasswordError.textContent = '';
                            PasswordError.textContent = 'The new password must be different from the previous one';
                        }
                    else{
                        // Nascondi il modulo
                        form.style.display = 'none';
                        // Mostra il messaggio
                        var messageContainer = document.getElementById('message-container');
                        var messageElement = document.getElementById('message');

                        messageContainer.style.display = 'block'; // Mostra il contenitore del messaggio
                        if (data.success) {
                            document.getElementById('instruction-text').style.display = 'none';
                            messageElement.textContent = data.message; // Mostra messaggio di successo
                            messageElement.style.color = 'green'; // Colore verde per successo
                        } else {
                            document.getElementById('instruction-text').style.display = 'none';
                            messageElement.textContent = data.message; // Mostra messaggio di errore
                            messageElement.style.color = 'red'; // Colore rosso per errore
                        }
                        document.getElementById("backhome").hidden = false;
                    }
                })
                .catch(error => console.error("Error: ", error));
            }
        };
    </script>
</body>
</html>
