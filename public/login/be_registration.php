<?php
    // Clean undesired outputs
    ob_clean();

    // Set content type
    header('Content-Type: application/json');

    require '/var/www/app/vendor/autoload.php';

    // Configurazione del database
    require_once '/var/www/mysql_client/config_db.php';

    // Configurazione del server SMTP
    require_once '/var/www/smtp/smtp_connection.php';

    // Sanitizer config and logging functions
    require_once '/var/www/app/sanitizer.php';
    require_once '/var/www/app/logger.php';

    // Initialize sanitizer
    $sanitizer = new InputSanitizer();

    // Sanitize username
    $username = $_POST['username']; 
    if (!($username = $sanitizer->sanitizeUsername($username))) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid username"]);
        exit;
    }

    // Check if username already exists
    $pdo->beginTransaction();
    $checkUser = "SELECT COUNT(*) FROM us3rs WHERE username = :username";
    $checkStmt = $pdo->prepare($checkUser);
    $checkStmt->bindParam(":username", $username);
    $checkStmt->execute();

    if ($checkStmt->fetchColumn() > 0) {
        http_response_code(409);
        // Username già utilizzati
        echo json_encode(["status" => "error", 'message' => 'Username already exists']);
        exit; // Esci dallo script
    }

    // Sanitize email
    $email = $_POST['email'];
    if (!($email = $sanitizer->sanitizeEmail($email))) {
        http_response_code(400);
        echo json_encode(["status" => "error", 'message' => 'Invalid mail']);
        exit;
    }
    
    // Validate password
    $password = trim($_POST['password']);
    if (!$sanitizer->validatePassword($password)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid password"]);
    }

    // Controllo se l'username o l'email esistono già nel database
    $query = "SELECT COUNT(*) FROM us3rs WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $count = $stmt->fetchColumn();

    // Inviare la mail all'utente nel caso provano a fare una registrazione con la sua email
    if ($count > 0) {
        // Write logs
        logs_webapp("tried to register using an existing email address", getClientIP(), "registration.log");

        // Create the SMTP connection
        $alertService = new EmailService();
            
        // SMTP variables
        $subject = 'Alert - Novelist Space';
        $body = '/var/www/smtp/alert_registration.html';

        try {
    
            // Connection test
            if ($alertService->testConnection()) {
                
                $result = $alertService->sendEmail(
                    $email,
                    $subject, 
                    $body,
                    null,
                    null
                );

                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'An email has been sent to confirm registration! Please check your inbox.']);
                exit;
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Something gone wrong during registration']);
            exit;
        }
    } 

    // Generate salted hash of password using bcrypt (just one field in db)
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // inserimento del nuovo utente nel db
    $query = "INSERT INTO us3rs (email, username, password) VALUES (:email, :username, :password)";
    $stmt = $pdo->prepare($query);
    // Binding dei parametri per l'inserimento
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashedPassword);

    // Eseguiamo la query per inserire l'utente
    if ($stmt->execute()) {
        // 2. Otteniamo l'ID dell'utente appena registrato
        $user_id = $pdo->lastInsertId();  // Recupera l'ID dell'utente appena inserito

        // 3. Generazione del token per l'attivazione
        $token = bin2hex(random_bytes(32));  // Token univoco di 64 caratteri
        $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));  // Scadenza del token (24 ore)

        // Inserimento del token nella tabella `activation_tokens`
        $query_token = "INSERT INTO registration_tokens (user_id, token, expiry) VALUES (:user_id, :token, :expiry)";
        $stmt_token = $pdo->prepare($query_token);
        $stmt_token->bindParam(':user_id', $user_id);
        $stmt_token->bindParam(':token', $token);
        $stmt_token->bindParam(':expiry', $expiry);

        // Eseguiamo la query per inserire il token
        if ($stmt_token->execute()) {

            // Crea il link di recupero con data di scadenza
            $confirmLink = "https://localhost/login/activate.php?token=$token";

            // Create the SMTP connection
            $emailService = new EmailService();
            
            // SMTP variables
            $subject = 'Confirm registration - Novelist Space';
            $body = realpath('/var/www/smtp/confirm.html');

            try {
        
                // Connection test
                if ($emailService->testConnection()) {
                    
                    $result = $emailService->sendEmail(
                        $email,
                        $subject, 
                        $body,
                        null,
                        $confirmLink
                    );
                    
                    // If the registration was successful, commit to the DB
                    $pdo->commit();

                    // Write logs about registration
                    logs_webapp("created an account", $username, "registration.log");

                    http_response_code(200);
                    echo json_encode(['status' => 'success', 'message' => 'An email has been sent to confirm registration! Please check your inbox.']);
                    exit;
                }
        
            } catch (Exception $e) {
                $pdo->rollback();
                http_response_code(500);
                // Gestione errori PHPMailer
                echo json_encode(["status" => "error", 'message' => 'Something gone wrong during registration']); 
                exit;
            }
        }
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Something gone wrong during registration']);
        exit;
    }
?>