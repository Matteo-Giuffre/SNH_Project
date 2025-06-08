<?php
    session_start([
        'cookie_lifetime' => 0, 
        'cookie_httponly' => true, 
        'cookie_secure' => true, 
        'cookie_samesite' => 'Strict'
    ]);

    // Clean undesired outputs
    ob_clean();

    require '/var/www/app/vendor/autoload.php';

    // Configurazione del database
    require_once '/var/www/mysql_client/config_db.php';

    // Configurazione del server SMTP
    require_once '/var/www/smtp/smtp_connection.php';

    // Sanitizer and logger configs
    require_once '/var/www/app/sanitizer.php';
    require_once '/var/www/app/logger.php';

    $sanitizer = new InputSanitizer();

    // Validate username
    $username = $_POST['username'];
    if (!($username = $sanitizer->sanitizeUsername($username))) {
        http_response_code(400);
        echo json_encode(["status" => "error", 'message' => 'Invalid username']);
        exit; // Esci dallo script
    }
    
    // Validate password
    $password = $_POST['password'];
    if (!$sanitizer->validatePassword($password)) {
        http_response_code(400);
        echo json_encode(["status" => "error", 'message' => 'Invalid password']);
        exit; // Esci dallo script
    }

    $query = "SELECT id, email, password, access_attempt, locked FROM us3r_admin WHERE username = :username";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se lo username non esiste
    if ($result === false) {
        logs_webapp("tried to access admin pannel using this username: $username", getClientIP(), 'admin_login.log');
        
        http_response_code(403);
        echo json_encode(["status" => "error", 'message' => 'Wrong credentials']);
        exit;
    }

    $userid = $result['id'];
    $hashedPassword = $result['password'];

    // if locked
    if ((int)$result['locked'] === 1) {
        logs_webapp("tried to access a locked account (ADMIN ID: $userid)", getClientIP(), 'admin_login.log');

        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Wrong credentials"]);
        exit;
    }
    
    // Verify password
    if (password_verify($password, $hashedPassword)) {//se anche la password è corretta allora il login viene eseguito con successo
        
        // Se la password è corretta, resetto i tentativi di accesso
        $pdo->beginTransaction();
        $access_stmt = $pdo->prepare("UPDATE us3r_admin SET access_attempt = 0 WHERE id = :userid");
        $access_stmt->bindParam(':userid', $userid);
        $access_stmt->execute();
        $pdo->commit();

        session_regenerate_id(true); // Rigenera il session ID
        $_SESSION['admin_id'] = $userid;
        $_SESSION['is_admin'] = true;
        $_SESSION['IP'] = $_SERVER['REMOTE_ADDR']; // Per prevenire session hijacking
        $_SESSION['User-Agent'] = $_SERVER['HTTP_USER_AGENT']; // Per prevenire session hijacking
        $_SESSION['last_activity'] = time(); // Per timeout della sessione

        // Logs successful login
        logs_webapp("accessed the account", "$username (ID: $userid)", 'admin_login.log');

        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Success login"]);
        exit;

    }

    // Se la password non è corretta, incremento il contatore dei tentativi
    $newAttemptCount = $result['access_attempt'] + 1;
    $pdo->beginTransaction();
    $attempt_stmt = $pdo->prepare("UPDATE us3r_admin SET access_attempt = :access_attempt WHERE id = :userid");
    $attempt_stmt->bindParam(':access_attempt', $newAttemptCount);
    $attempt_stmt->bindParam(':userid', $userid);
    $attempt_stmt->execute();
    $pdo->commit();

    // Logs login failed
    logs_webapp("wrong credentials (ADMIN ID: $userid)", getClientIP(), 'admin_login.log');

    // Se il numero di tentativi arriva a 5, blocco l'account e mando la mail
    if ($newAttemptCount >= 5) {
        $pdo->beginTransaction();
        $lock_stmt = $pdo->prepare("UPDATE us3r_admin SET locked = 1 WHERE id = :userid");
        $lock_stmt->bindParam(':userid', $userid);
        $lock_stmt->execute();
        $pdo->commit();

        // Logs the account lock
        logs_webapp('account locked', $username, 'admin_login.log');

        $email = $result['email'];

        // Create the SMTP connection
        $emailService = new EmailService();
            
        // SMTP variables
        $subject = 'Suspicious access attempt (Admin Panel) - Novelist Space';
        $body = '/var/www/smtp/suspicious_attempt_admin.html';

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
            echo json_encode(["status" => "error", 'message' => 'Wrong credentials']); 
            exit;
        }
    }
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Wrong credentials']);
    exit;
?>