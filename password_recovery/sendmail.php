<?php
// Includi i file principali di PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configurazione del database
require_once '../config_db.php';

// Ricevi l'email dal POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    // Controlla se l'email è presente nel database
    $query = "SELECT id,complete FROM us3rs WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $result = $stmt->fetch();

    if ($result === false || $result['complete'] === 0) {
        // Email non trovata, risposta generica
        http_response_code(200);
    } 
    else {
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
        $insertQuery = "INSERT INTO password_resets (user_id, email, token, expiry) VALUES (:user_id, :email, :token, :expiry)";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->bindParam(':user_id', $userId);
        $insertStmt->bindParam(':email', $email);
        $insertStmt->bindParam(':token', $token);
        $insertStmt->bindParam(':expiry', $expiry);
        $insertStmt->execute();

        // Crea il link di recupero con data di scadenza
        $resetLink = "https://localhost/password_recovery/reset_password.php?token=$token";

        // Leggi il contenuto del file mail.html
        $mailContent = file_get_contents('./PHPMailer/email.html');
        $mailContent = str_replace('{{resetLink}}', $resetLink, $mailContent);

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
            http_response_code(200); // Email inviata correttamente
        } 
        catch (Exception $e) {
            // Gestione errori PHPMailer
            error_log("Errore nell'invio dell'email: " . $mail->ErrorInfo);
            http_response_code(500); // Errore nell'invio dell'email
            exit;
        }
    }
} else {
    http_response_code(400); // Richiesta non valida
    exit;
}
?>