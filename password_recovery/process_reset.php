<?php
// Configurazione del database
require_once '../config_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['user_id'], $_POST['token'], $_POST['newpassword'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    $userId = $_POST['user_id'];
    $token = $_POST['token'];
    $password = $_POST['newpassword'];

    // Verifica che il token sia ancora valido
    $query = "SELECT user_id FROM password_resets WHERE token = :token AND user_id = :user_id AND expiry > :current_time";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':token', $token, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':current_time', time(), PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        exit;
    }

    //prendo salt, e password
    $query2 = "SELECT salt, password FROM us3rs WHERE id = :id";
    $stmt2 = $pdo->prepare($query2);
    $stmt2->bindParam(':id', $userId);
    $stmt2->execute();
    $result2 = $stmt2->fetch();
    $salt = $result2['salt'];
    $oldpassword = $result2['password'];

    // Hash della nuova password
    $hashedNewPassword = hash('sha256', $salt . $password);

    if ($hashedNewPassword === $oldpassword){
        echo json_encode(['success' => false, 'message' => 'Invalid Password']);
        exit;
    }

    // Aggiorna la password nel database e resetto gli access_Attempt e il locked_at nel caso in cui l'account fosse bloccato
    $updateQuery = "UPDATE us3rs SET password = :password, access_attemp = 0, locked_at = NULL WHERE id = :id";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->bindParam(':password', $hashedNewPassword, PDO::PARAM_STR);
    $updateStmt->bindParam(':id', $userId, PDO::PARAM_INT);

    if ($updateStmt->execute()) {
        // Elimina il token usato per evitare riutilizzi
        $deleteQuery = "DELETE FROM password_resets WHERE token = :token";
        $deleteStmt = $pdo->prepare($deleteQuery);
        $deleteStmt->bindParam(':token', $token, PDO::PARAM_STR);
        $deleteStmt->execute();

        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Password update error']);
        exit;
    }
}
else{
    http_response_code(400); // Richiesta non valida
    exit;
}
?>