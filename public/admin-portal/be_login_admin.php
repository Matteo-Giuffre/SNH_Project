<?php
session_start([
    'cookie_lifetime' => 0, 
    'cookie_httponly' => true, 
    'cookie_secure' => true, 
    'cookie_samesite' => 'Strict'
]);

// Configurazione del database
require_once '/var/www/mysql_client/config_db.php';

// Ricezione dei dati da POST
$username = $_POST['username'];
$password = $_POST['password'];

$query = "SELECT salt,password FROM us3r_admin WHERE username = :username";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->execute();
$result = $stmt->fetch();

if ($result === false) {
    // Se lo username non esiste
    echo json_encode(["status" => "error", 'message' => 'Wrong Username or Password!']);
    exit;
}
else{ // se lo username è corretto verifico la password
    $salt = $result['salt'];
    $hashedPassword = $result['password'];
    $newHashedPassword = hash('sha256', $salt . $password); 
    if ($hashedPassword === $newHashedPassword){//se anche la password è corretta allora il login viene eseguito con successo
        
        session_regenerate_id(true); // Rigenera il session ID
        $_SESSION['is_admin'] = true;
        $_SESSION['IP'] = $_SERVER['REMOTE_ADDR']; // Per prevenire session hijacking
        $_SESSION['User-Agent'] = $_SERVER['HTTP_USER_AGENT']; // Per prevenire session hijacking
        $_SESSION['last_activity'] = time(); // Per timeout della sessione

        
        // Logging del login dell'admin con try-catch
        $logFile = '../logs/logs.txt';
        $timestamp = date('Y-m-d H:i:s');
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $logEntry = "SUCCESS_ADMIN_LOGIN $username '$timestamp' '$ipAddress'\n";
        
        try {
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", 'message' => 'Error during login']);
            exit;
        }

        echo json_encode(["status" => "success"]);
        exit;
    }
    else{
        echo json_encode(["status" => "error", 'message' => 'Wrong Username or Password!']);
        exit;
    }
}

?>