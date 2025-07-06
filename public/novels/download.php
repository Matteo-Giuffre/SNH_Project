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
    
    // Recupera il percorso del file dal database (questo è solo un esempio, devi farlo tu in base alla tua struttura)
    require_once '/var/www/mysql_client/config_db.php';
    require_once '/var/www/app/logger.php';

    // Verifica che sia stato inviato un file ID valido tramite POST
    if (!isset($_POST['download-id'])) {
        logs_webapp('bad request (novel ID missing)', $_SESSION['username'], 'novels.log');

        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid novel ID"]);
        exit;
    }

    if ($_SESSION['user-type'] !== "premium") {
        logs_webapp("tried to download a novel without right priviledges", $_SESSION['username'], 'novels.log');

        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Unauthorized"]);
        exit;
    }
    
    // Recupera l'ID del file (assicurati che sia un valore valido)
    $id = intval($_POST['download-id']); // Usando un ID numerico per sicurezza

    $stmt = $pdo->prepare("SELECT title, novel_type, file_path FROM novels WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $novel = $stmt->fetch();

    if (!$novel) {
        logs_webapp("novel not found in DB (novel ID: $id)", $_SESSION['username'], 'novels.log');

        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Novel not found"]);
        exit;
    }
    
    if ((int)$novel['novel_type'] !== 1) {
        logs_webapp("tried to download a short novel (novel ID: $id)", $_SESSION['username'], 'novels.log');

        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Cannot download a short novel"]);
        exit;
    }

    $filePath = $novel['file_path'];
    // Verifica che il file esista
    if (file_exists($filePath)) {
        // Imposta gli header per il download
        $downloadName = $novel['title'] . ".pdf";
        logs_webapp("downloaded a novel (novel ID: $id)", $_SESSION['username'], 'novels.log');

        http_response_code(200);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
        readfile($filePath);
        exit;
    }

    logs_webapp("novel file not found (novel ID: $id)", $_SESSION['username'], 'novels.log');

    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Novel not found"]);
?>