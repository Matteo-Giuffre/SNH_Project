<?php

session_start([
    'cookie_lifetime' => 0,
    'cookie_httponly' => true,
    'cookie_secure' => true, // Assicurati che il sito usi HTTPS
    'cookie_samesite' => 'Strict'
]);


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die("Unauthorized access.");
}

if ($_SESSION['IP'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['User-Agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    header("Location: ../login/index.html");
    exit;
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {//15 minuti
    session_unset();
    session_destroy();
    header("Location: ../login/index.html");
    exit;
}
$_SESSION['last_activity'] = time(); // Aggiorna il timer
// Verifica che sia stato inviato un file ID valido tramite POST
if (isset($_POST['id'])) {
    // Recupera l'ID del file (assicurati che sia un valore valido)
    $Id = intval($_POST['id']); // Usando un ID numerico per sicurezza

    // Recupera il percorso del file dal database (questo è solo un esempio, devi farlo tu in base alla tua struttura)
    require_once '../config_db.php';
    $stmt = $pdo->prepare("SELECT title,file_path FROM novels WHERE id = :Id");
    $stmt->bindParam(':Id', $Id, PDO::PARAM_INT);
    $stmt->execute();
    $novel = $stmt->fetch();
    $filePath = "./" . $novel['file_path'];
    // Verifica che il file esista
    if (file_exists($filePath)) {
        // Imposta gli header per il download
        $downloadName = $novel['title'] . ".pdf";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
        readfile($filePath);
        exit;
    } else {
        die("File not found.");
    }
} else {
    die("Missing File ID");
}
?>
