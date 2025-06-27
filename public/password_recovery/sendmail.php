<?php
    ob_clean();
    header('Content-Type: application/json');

    require "/var/www/app/vendor/autoload.php";

    // Configurazione del database
    require_once '/var/www/mysql_client/config_db.php';

    // Configurazione del server SMTP
    require_once '/var/www/smtp/smtp_connection.php';

    // Sanitizer config and logging functions
    require_once '/var/www/app/sanitizer.php';
    require_once '/var/www/app/logger.php';

    $sanitizer = new InputSanitizer();

    // Ricevi l'email dal POST
    if (!isset($_POST['email'])) {
        logs_webapp("bad request (email missing)", getClientIP(), "password_recovery.log");
        
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit;
    }
    // Sanitize email
    $email = $_POST['email'];
    if (!($email = $sanitizer->sanitizeEmail($email))) {
        logs_webapp("invalid email format", getClientIP(), "password_recovery.log");

        http_response_code(400);
        echo json_encode(["status" => "error", 'message' => 'Invalid mail']);
        exit;
    }

    // Controlla se l'email è presente nel database
    $query = "SELECT id, username, complete FROM us3rs WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result === false || $result['complete'] === 0) {
        // Email non trovata, risposta generica
        logs_webapp("tried to recover the password using a non-existent/non-active email", getClientIP(), "password_recovery.log");

        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Something gone wrong. Try later"]);
        exit;
    }

    // L'email esiste, genera un link unico
    $userId = $result['id'];

    // Elimina eventuali token precedenti per lo stesso utente
    $deleteQuery = "DELETE FROM password_resets WHERE user_id = :user_id";
    $deleteStmt = $pdo->prepare($deleteQuery);
    $deleteStmt->bindParam(':user_id', $userId);
    $deleteStmt->execute();

    // Genera un nuovo token
    $token = bin2hex(random_bytes(32)); // Token sicuro
    $expiry = time() + 1800; // Scadenza in 30 minuti

    // Salva il nuovo token nel database
    $pdo->beginTransaction();
    $insertQuery = "INSERT INTO password_resets (user_id, token, expiry) VALUES (:user_id, :token, :expiry)";
    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->bindParam(':user_id', $userId);
    $insertStmt->bindParam(':token', $token);
    $insertStmt->bindParam(':expiry', $expiry);
    $insertStmt->execute();

    // Crea il link di recupero con data di scadenza
    $resetLink = "https://localhost/password_recovery/reset_password.php?token=$token";

    // Create the SMTP connection
    $emailService = new EmailService();
    
    // SMTP variables
    $subject = 'Password Recovery - Novelist Space';
    $body = realpath('/var/www/smtp/recover_password.html');

    try {

        // Connection test
        if ($emailService->testConnection()) {
            
            $emailService->sendEmail(
                $email,
                $subject, 
                $body,
                null,
                $resetLink
            );
            
            // If the registration was successful, commit to the DB
            $pdo->commit();

            // Write logs about registration
            logs_webapp("requested password recovery (user ID: $userid)", getClientIP(), "password_recovery.log");

            http_response_code(200);
            echo json_encode(['status' => 'success']);
            exit;
        }

    } catch (Exception $e) {
        $pdo->rollback();
        logs_webapp("something gone wrong during password recovery request (user ID: $userid)", getClientIP(), "password_recovery.log");

        http_response_code(500);
        // Gestione errori PHPMailer
        echo json_encode(["status" => "error", 'message' => 'Something gone wrong. Try later']); 
    }
?>