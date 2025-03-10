<?php

session_start([
    'cookie_lifetime' => 0, 
    'cookie_httponly' => true, 
    'cookie_secure' => true, 
    'cookie_samesite' => 'Strict'
]);

// Includi i file principali di PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configurazione del database
require_once '../config_db.php';

// Ricezione e sanitizzazione input
$username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
$password = $_POST['password'];

$query = "SELECT id,email,salt,password,ispremium,complete,access_attemp,locked_at FROM us3rs WHERE username = :username";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->execute();
$result = $stmt->fetch();


if ($result === false || $result['complete'] === 0) {
    // Se lo username non esiste o l'account non è ancora attivo oppure è bloccato
    echo json_encode(["status" => "error", 'message' => 'Wrong credentials or too many failed attempts']);
    exit;
}

// Se l'account è bloccato e il tempo di blocco NON è ancora scaduto, impedisci l'accesso
if ($result['locked_at'] !== null && (strtotime($result['locked_at']) + 900) > time()) {
    echo json_encode(["status" => "error", 'message' => 'Wrong credentials or too many failed attempts']);
    exit;
}

// Se l'account era bloccato ma il tempo è scaduto, resetta i tentativi e rimuovi il blocco
if ($result['locked_at'] !== null && (strtotime($result['locked_at']) + 900) <= time()) {
    $stmt2 = $pdo->prepare("UPDATE us3rs SET access_attemp = 0, locked_at = NULL WHERE username = :username");
    $stmt2->bindParam(':username', $username);
    $stmt2->execute();
}

else{// se lo username è corretto verifico la password
    $salt = $result['salt'];
    $hashedPassword = $result['password'];
    $newHashedPassword = hash('sha256', $salt . $password); 
    if ($hashedPassword === $newHashedPassword){//se anche la password è corretta allora il login viene eseguito con successo

        // Se la password è corretta, resetto i tentativi di accesso
        $stmt3 = $pdo->prepare("UPDATE us3rs SET access_attemp = 0 WHERE username = :username");
        $stmt3->bindParam(':username', $username);
        $stmt3->execute();
        
        session_regenerate_id(true); // Rigenera il session ID
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username; // Salva lo username nella sessione
        $_SESSION['id'] = $result['id']; // Salva l'id nella sessione
        $_SESSION['user-type'] = ($result['ispremium'] === 0) ? "free" : "premium";
        $_SESSION['IP'] = $_SERVER['REMOTE_ADDR']; // Per prevenire session hijacking
        $_SESSION['User-Agent'] = $_SERVER['HTTP_USER_AGENT']; // Per prevenire session hijacking
        $_SESSION['last_activity'] = time(); // Per timeout della sessione

        echo json_encode(["status" => "success"]);
        exit;
    }
    else{//se la password è sbagliata

        // Se la password non è corretta, incremento il contatore dei tentativi
        $newAttemptCount = $result['access_attemp'] + 1;
        $stmt4 = $pdo->prepare("UPDATE us3rs SET access_attemp = :access_attemp WHERE username = :username");
        $stmt4->bindParam(':access_attemp', $newAttemptCount);
        $stmt4->bindParam(':username', $username);
        $stmt4->execute();

        // Se il numero di tentativi arriva a 6, blocco l'account e mando la mail
        if ($newAttemptCount > 5) {
            $stmt5 = $pdo->prepare("UPDATE us3rs SET locked_at = NOW() WHERE username = :username");
            $stmt5->bindParam(':username', $username);
            $stmt5->execute();

            $email = $result['email'];

            // Leggi il contenuto del file mail.html
            $mailContent = file_get_contents('./PHPMailer/email2.html');

            // Configura PHPMailer
            $mail = new PHPMailer(true);

            try {
                // Configura il server SMTP (es. Gmail)
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'snh.project2425@gmail.com'; // Sostituisci con il tuo indirizzo Gmail
                $mail->Password = 'fwji avvu qesu njpx'; // Sostituisci con la tua password per app
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Impostazioni mittente e destinatario
                $mail->setFrom('noreply@novelistspace.com', 'Novelist Space'); 
                $mail->addAddress($email); // Destinatario

                // Contenuto email
                $mail->isHTML(true);
                $mail->Subject = 'Suspicious access - Novelist Space';
                $mail->Body = $mailContent;

                // Invia l'email
                $mail->send();
                echo json_encode(["status" => "error", 'message' => 'Wrong credentials or too many failed attempts']);
                exit; // Esci dallo script
            } 
            catch (Exception $e) {
                // Gestione errori PHPMailer
                echo json_encode(["status" => "error", 'message' => 'Wrong credentials or too many failed attempts']);
                exit;
            }
        }

        echo json_encode(["status" => "error", 'message' => 'Wrong credentials or too many failed attempts']);
        exit;
    }
}

?>