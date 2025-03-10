<?php

// Connessione al database
require_once '../config_db.php';

// Verifica se è stato passato il token tramite la query string
if (!isset($_GET['token']) || empty($_GET['token'])) {
    echo json_encode(["status" => "error", "message" => "Missing Token!"]);
    exit;
}

$token = $_GET['token'];

// Recupero il token dalla tabella 'registration_tokens'
$query = "SELECT * FROM registration_tokens WHERE token = :token LIMIT 1";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':token', $token);
$stmt->execute();

$activation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activation) {
    // Token non trovato
    echo json_encode(["status" => "error", "message" => "Invalid Token!"]);
    exit;
}

// Verifica se il token è scaduto
$currentTime = new DateTime();
$expiryTime = new DateTime($activation['expiry']);

if ($currentTime > $expiryTime) {
    // Il token è scaduto
    echo json_encode(["status" => "error", "message" => "Expired Token."]);
    exit;
}

// Recupero l'ID dell'utente
$user_id = $activation['user_id'];

// Attivazione dell'account dell'utente
$query_update = "UPDATE us3rs SET complete = 1 WHERE id = :user_id";
$stmt_update = $pdo->prepare($query_update);
$stmt_update->bindParam(':user_id', $user_id);

if ($stmt_update->execute()) {
    // Cancelliamo il token (per evitare che venga usato più di una volta)
    $query_delete = "DELETE FROM registration_tokens WHERE token = :token";
    $stmt_delete = $pdo->prepare($query_delete);
    $stmt_delete->bindParam(':token', $token);
    $stmt_delete->execute();

    header("Location: activation.html");
    exit;

} else {
    echo json_encode(["status" => "error", "message" => "Error during account activation."]);
}
?>
