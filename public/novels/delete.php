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

//Configurazione db
require_once '/var/www/mysql_client/config_db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['novel_id'])) {
    $novel_id = intval($_POST['novel_id']);
    $user_id = $_SESSION['id'];

    // Controllo se l'utente è il proprietario della novella
    $stmt = $pdo->prepare("SELECT file_path FROM novels WHERE id = :id AND uploader_id = :user_id");
    $stmt->execute(['id' => $novel_id, 'user_id' => $user_id]);
    $novel = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$novel) {
        die("Unauthorized action.");
    }

    // Elimina il file dal server
    $filePath = './' . $novel['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Elimina la novella dal database
    $stmt = $pdo->prepare("DELETE FROM novels WHERE id = :id");
    $stmt->execute(['id' => $novel_id]);

    echo "<script>
        alert('Novel deleted successfully!');
        window.location.href = 'index.php?filter=my_novels';
    </script>";
    exit;
} else {
    die("Invalid request.");
}
