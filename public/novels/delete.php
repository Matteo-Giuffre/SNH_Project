<?php
    session_start([
        'cookie_lifetime' => 0,
        'cookie_httponly' => true,
        'cookie_secure' => true, // Assicurati che il sito usi HTTPS
        'cookie_samesite' => 'Strict'
    ]);

    ob_clean();
    header("Content-Type: application/json");

    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header("Location: /login/");
        exit;
    }

    if ($_SESSION['IP'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['User-Agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        header("Location: /login/");
        exit;
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {//15 minuti
        session_unset();
        session_destroy();
        header("Location: /login/");
        exit;
    }
    $_SESSION['last_activity'] = time(); // Aggiorna il timer

    //Configurazione db
    require_once '/var/www/mysql_client/config_db.php';

    if (!isset($_POST['novel_id'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid novel ID"]);
        exit;
    }

    $novel_id = intval($_POST['novel_id']);
    $user_id = $_SESSION['id'];

    // Controllo se l'utente è il proprietario della novella
    $stmt = $pdo->prepare("SELECT file_path FROM novels WHERE id = :id AND uploader_id = :user_id");
    $stmt->bindParam(":id", $novel_id, PDO::PARAM_INT);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $novel = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$novel) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Novel not found"]);
        exit;
    }

    // Elimina il file dal server
    $filePath = $novel['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Elimina la novella dal database
    $stmt = $pdo->prepare("DELETE FROM novels WHERE id = :id");
    $stmt->execute(['id' => $novel_id]);

    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Novel deleted successfully"]);
?>