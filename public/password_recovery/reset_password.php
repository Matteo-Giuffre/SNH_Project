<?php
    ob_clean();

    // Configurazione del database
    require_once '/var/www/mysql_client/config_db.php';
    require_once '/var/www/app/logger.php';

    // Verifica se il token è presente nell'URL
    if (!isset($_GET['token'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit;
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
        logs_webapp("tried to use an invalid/expired token", getClientIP(), "password_recovery.log");
        header("Location: expired.html");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Novelist Space</title>
    <link rel="stylesheet" href="/Styles/password_recovery_style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Reset Password</h1>
            <p id="instruction-text"> Enter a new password for your account.</p>
            <form id='reset-password-form'>
                <input type="hidden" id="token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
                <label for="newpassword">New Password</label>
                <input type="password" id="newpassword" placeholder="Enter new password" required>
                <p class="password-requirements">
                    Password must be at least 8 characters long and include:
                    - At least one Capital letter
                    - At least one number
                    - At least one special character (@,$,!,%,*,?,&,_,-)
                </p>
                <label for="confirmpassword">Confirm Password</label>
                <input type="password" id="confirmpassword" placeholder="Confirm password" required>
                <span class="error-message2" id="PasswordError"></span>
                <button type="submit">Change Password</button>
            </form>
            <div id="message-container" hidden>
                <br>
                <!-- Questo div mostrerà il messaggio di successo o errore -->
                <p id="message" class="message"></p>
            </div>
            <a id="backhome" href="/" class="back-link" hidden>Back to Home</a>
        </div>
    </div>

    <footer class="footer">
        <p id="p_footer"></p>
    </footer>

    <script src='/Scripts/copyright.js'></script>
    <script src='/Scripts/reset_password.js'></script>

</body>
</html>
