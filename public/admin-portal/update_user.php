<?php
session_start([
    'cookie_lifetime' => 0,
    'cookie_httponly' => true,
    'cookie_secure' => true, // Assicurati che il sito usi HTTPS
    'cookie_samesite' => 'Strict'
]);


if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.html");
    exit;
}

if ($_SESSION['IP'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['User-Agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    header("Location: ../login/index.html");
    exit;
}

// 15 minuti
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();
    session_destroy();
    header("Location: ../login/index.html");
    exit;
}
$_SESSION['last_activity'] = time(); // Aggiorna il timer

// Configurazione del database
require_once '/var/www/mysql_client/config_db.php';

// Ottieni l'ID dell'utente
if (isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);

    // Inverti direttamente il valore di ispremium
    $stmt = $pdo->prepare("UPDATE us3rs SET ispremium = NOT ispremium WHERE id = :id");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);

    if ($stmt->execute()) {

        // Logging della modifica di stato di un utente
        $logFile = '../logs/logs.txt';
        $timestamp = date('Y-m-d H:i:s');
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $logEntry = "USER_STATUS_UPDATE  USER_ID: $user_id '$timestamp' '$ipAddress'\n";
        
        try {
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", 'message' => 'Error during login']);
            exit;
        }

        // Restituisce la risposta di successo
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update"]);
    }
}
else{
    http_response_code(400);
    exit;
}
?>