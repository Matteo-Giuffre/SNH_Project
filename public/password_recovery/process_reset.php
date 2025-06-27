<?php
    ob_clean();
    header('Content-Type: application/json');

    // Configurazione del database
    require_once '/var/www/mysql_client/config_db.php';
    require_once '/var/www/app/sanitizer.php';
    require_once '/var/www/app/logger.php';

    if (!isset($_POST['token'], $_POST['newpassword'])) {
        logs_webapp("bad request (parameter(s) missing)", getClientIP(), 'password_recovery.log');

        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit;
    }

    $sanitizer = new InputSanitizer();
    
    $token = $_POST['token'];
    if (!$sanitizer->validateResetToken($token)) {
        logs_webapp("invalid token format", getClientIP(), "password_recovery.log");

        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token']);
        exit;
    }

    $password = $_POST['newpassword'];
    if (!$sanitizer->validatePassword($password)) {
        logs_webapp("invalid password format", getClientIP(), "password_recovery.log");

        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
        exit;
    }

    // Verifica che il token sia ancora valido
    $query = "SELECT user_id FROM password_resets WHERE token = :token AND expiry > :current_time";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':token', $token, PDO::PARAM_STR);
    $stmt->bindValue(':current_time', time(), PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        logs_webapp("used an invalid/expired token", getClientIP(), "password_recovery.log");

        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token']);
        exit;
    }

    // Retrieve user_id
    $userId = $result['user_id'];

    // Retrieve password
    $query2 = "SELECT password FROM us3rs WHERE id = :id";
    $stmt2 = $pdo->prepare($query2);
    $stmt2->bindParam(':id', $userId);
    $stmt2->execute();
    $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);

    // Retrieve old password, and check that new password is different from old password
    $oldpassword = $result2['password'];
    if (password_verify($password, $oldpassword)){
        logs_webapp("tried to reuse the old password during password recovery (user ID: $userId)", getClientIP(), "password_recovery.log");

        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'New password must be different from the old password']);
        exit;
    }

    // Hash della nuova password
    $hashedNewPassword = password_hash($password, PASSWORD_DEFAULT);

    // Aggiorna la password nel database e resetto gli access_Attempt e il locked_at nel caso in cui l'account fosse bloccato
    $pdo->beginTransaction();
    $updateQuery = "UPDATE us3rs SET password = :password, access_attemp = 0, locked = 0 WHERE id = :id";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->bindParam(':password', $hashedNewPassword, PDO::PARAM_STR);
    $updateStmt->bindParam(':id', $userId, PDO::PARAM_INT);

    if ($updateStmt->execute()) {
        // Elimina il token usato per evitare riutilizzi
        $deleteQuery = "DELETE FROM password_resets WHERE token = :token";
        $deleteStmt = $pdo->prepare($deleteQuery);
        $deleteStmt->bindParam(':token', $token, PDO::PARAM_STR);
        
        if ($deleteStmt->execute()) {
            $pdo->commit();
            logs_webapp("changed password (user ID: $userId)", getClientIP(), "password_recovery.log");

            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
            exit;
        }

        $pdo->rollback();
        logs_webapp("something gone wrong during password update (user ID: $userId)", getClientIP(), "password_recovery.log");

        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Password update error']);
        exit;
    }

    $pdo->rollback();
    logs_webapp("something gone wrong during password update (user ID: $userId)", getClientIP(), "password_recovery.log");
    
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Password update error']);
?>