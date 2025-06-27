<?php
    session_start([
        'cookie_lifetime' => 0, 
        'cookie_httponly' => true, 
        'cookie_secure' => true, 
        'cookie_samesite' => 'Strict'
    ]);
    
    // Clean undesired outputs
    ob_clean();

    // Set content type
    header('Content-Type: application/json');

    require '/var/www/app/vendor/autoload.php';

    // Configurazione del database
    require_once '/var/www/mysql_client/config_db.php';

    // Configurazione del server SMTP
    require_once '/var/www/smtp/smtp_connection.php';

    // Sanitizer and logger configs
    require_once '/var/www/app/sanitizer.php';
    require_once '/var/www/app/logger.php';

    // Initialize Sanitizer
    $sanitizer = new InputSanitizer();

    // Validate username
    $username = $_POST['username'];
    if (!($username = $sanitizer->sanitizeUsername($username))) {
        logs_webapp("tried to use an invalid username format", getClientIP(), 'login.log');
        
        http_response_code(400);
        echo json_encode(["status" => "error", 'message' => 'Invalid username']);
        exit; // Esci dallo script
    }
    
    // Validate password
    $password = $_POST['password'];
    if (!$sanitizer->validatePassword($password)) {
        logs_webapp("tried to use an invalid password format", getClientIP(), 'login.log');
        
        http_response_code(400);
        echo json_encode(["status" => "error", 'message' => 'Invalid password']);
        exit; // Esci dallo script
    }

    $query = "SELECT * FROM us3rs WHERE username = :username";

    // Retrieve user info
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se lo username non esiste o l'account non è ancora abilitato
    if ($result === false || (int)$result['complete'] === 0) {
        logs_webapp("tried to access a non-existent/uncomplete account", getClientIP(), 'login.log');
        
        http_response_code(403);
        echo json_encode(["status" => "error", 'message' => 'Wrong credentials or too many failed attempts']);
        exit;
    }
    
    $userid = $result['id'];

    // If account is locked, logs access attempt (change password due to many failed attempts)
    if ((int)$result['locked'] === 1) {
        logs_webapp("tried to access a locked account (user ID: $userid)", getClientIP(), 'login.log');

        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Wrong credentials or too many failed attempts"]);
        exit;
    }

    $hashedPassword = $result['password'];
    if (password_verify($password, $hashedPassword)) {   //se anche la password è corretta allora il login viene eseguito con successo

        // Se la password è corretta, resetto i tentativi di accesso
        $pdo->beginTransaction();
        $access_stmt = $pdo->prepare("UPDATE us3rs SET access_attemp = 0 WHERE id = :userid");
        $access_stmt->bindParam(':userid', $userid);
        $access_stmt->execute();
        $pdo->commit();
        
        session_regenerate_id(true); // Rigenera il session ID
        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $userid; // Salva l'id nella sessione
        $_SESSION['username'] = $username; // Salva lo username nella sessione
        $_SESSION['user-type'] = ((int)$result['ispremium'] === 0) ? "free" : "premium";
        $_SESSION['IP'] = $_SERVER['REMOTE_ADDR']; // Per prevenire session hijacking
        $_SESSION['User-Agent'] = $_SERVER['HTTP_USER_AGENT']; // Per prevenire session hijacking
        $_SESSION['last_activity'] = time(); // Per timeout della sessione

        // Logs access
        logs_webapp('accessed the account', $username, 'login.log');

        http_response_code(200);
        echo json_encode(["status" => "success"]);
        exit;
    } 

    // Se la password non è corretta, incremento il contatore dei tentativi
    $newAttemptCount = $result['access_attemp'] + 1;
    $pdo->beginTransaction();
    $attempt_stmt = $pdo->prepare("UPDATE us3rs SET access_attemp = :access_attemp WHERE id = :userid");
    $attempt_stmt->bindParam(':access_attemp', $newAttemptCount);
    $attempt_stmt->bindParam(':userid', $userid);
    $attempt_stmt->execute();
    $pdo->commit();

    // Logs the failed attempt
    logs_webapp("wrong credentials (user ID: $userid)", getClientIP(), 'login.log');

    // Se il numero di tentativi arriva a 5, blocco l'account e mando la mail
    if ($newAttemptCount >= 5) {
        $pdo->beginTransaction();
        $lock_stmt = $pdo->prepare("UPDATE us3rs SET locked = 1 WHERE id = :userid");
        $lock_stmt->bindParam(':userid', $userid);
        $lock_stmt->execute();
        $pdo->commit();

        // Logs the account lock
        logs_webapp("account locked (user ID: $userid)", getClientIP(), 'login.log');

        $email = $result['email'];

        // Create the SMTP connection
        $emailService = new EmailService();
            
        // SMTP variables
        $subject = 'Suspicious access attempt - Novelist Space';
        $body = '/var/www/smtp/suspicious_attempt.html';

        try {
    
            // Connection test
            if ($emailService->testConnection()) {
                
                $result = $emailService->sendEmail(
                    $email,
                    $subject, 
                    $body,
                    null,
                    null
                );
            }

        } catch (Exception $e) {
            http_response_code(500);
            // Gestione errori PHPMailer
            echo json_encode(["status" => "error", 'message' => 'Wrong credentials or too many failed attempts']); 
            exit;
        }
    }
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Wrong credentials or too many failed attempts']);
    exit;
?>