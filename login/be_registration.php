<?php

// Includi i file principali di PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configurazione del database
require_once '../config_db.php';

$username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", 'message' => 'Error invalid mail']);
    exit; // Esci dallo script
}
$password = $_POST['password'];

// Controllo se l'username o l'email esistono già nel database
$query = "SELECT COUNT(*) FROM us3rs WHERE email = :email OR username = :username";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->bindParam(':email', $email);
$stmt->execute();

$count = $stmt->fetchColumn();

if ($count > 0) {
    // Username o email già utilizzati
    echo json_encode(["status" => "success", 'message' => 'An email has been sent to confirm your registration']);
    exit; // Esci dallo script
}

//Se è tutto ok procedo con la registrazione
$salt = bin2hex(random_bytes(16)); // Salt di 16 byte (32 caratteri esadecimali)
$hashedPassword = hash('sha256', $salt . $password); 

// // inserimento del nuovo utente nel db
$query = "INSERT INTO us3rs (email, username, salt, password) VALUES (:email, :username, :salt, :password)";
$stmt = $pdo->prepare($query);
// Binding dei parametri per l'inserimento
$stmt->bindParam(':email', $email);
$stmt->bindParam(':username', $username);
$stmt->bindParam(':salt', $salt);
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

        // Leggi il contenuto del file mail.html
        $mailContent = file_get_contents('./PHPMailer/email.html');
        $mailContent = str_replace('{{confirmLink}}', $confirmLink, $mailContent);

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
            $mail->Subject = 'Password Recovery - Novelist Space';
            $mail->Body = $mailContent;

            // Invia l'email
            $mail->send();
            echo json_encode(["status" => "success", 'message' => 'An email has been sent to confirm your registration']);
            exit; // Esci dallo script
        } 
        catch (Exception $e) {
            // Gestione errori PHPMailer
            echo json_encode(["status" => "error", 'message' => "Error during mail sending: " . $mail->ErrorInfo]);
            exit;
        }

    } else {
        echo json_encode(["status" => "error", 'message' => 'Error during Registration']);
        exit; // Esci dallo script
    }
} else {
    echo json_encode(["status" => "error", 'message' => 'Error during Registration']);
    exit; // Esci dallo script
}
?>