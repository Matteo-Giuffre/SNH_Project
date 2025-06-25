<?php
    session_start([
        'cookie_lifetime' => 0,
        'cookie_httponly' => true,
        'cookie_secure' => true, // Assicurati che il sito usi HTTPS
        'cookie_samesite' => 'Strict'
    ]);

    ob_clean();

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

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) { //15 minuti
        session_unset();
        session_destroy();
        header("Location: /login/");
        exit;
    }
    $_SESSION['last_activity'] = time(); // Aggiorna il timer

    $user_id = $_SESSION['id'];
    $user_type = $_SESSION['user-type'];

    if (!isset($_GET['id'])) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Novel not found']);
        exit;
    }

    // Configurazione del database
    require_once '/var/www/mysql_client/config_db.php';

    $novel_id = intval($_GET['id']); // Assicuriamoci che sia un intero
    if (!is_int($novel_id)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid id format"]);
        exit;
    }

    $query = "SELECT * FROM novels WHERE id = :id";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $novel_id, PDO::PARAM_INT);
    $stmt->execute();

    $novel = $stmt->fetch(PDO::FETCH_ASSOC); // Ottieni i dati come array associativo

    if (!$novel) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Novel not found"]);
        exit;
    }

    // Se la novella è premium e l'utente non lo è (e non è neanche l'uploader) blocca la richiesta
    if (($novel['free'] && $user_type !== 'premium') && $novel['uploader_id'] !== $user_id) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    # Check path traversal
    $filePath = $novel['file_path']; 

    if (!file_exists($filePath)) {
        http_response_code(404);
        die("Server Error: File not Found");
    }
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($novel['title']) ?> - Details</title>
        <link rel="stylesheet" href="/Styles/novel_details_style.css">

    </head>
    <body>

        <header class="header">
            <a href="" class="logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                    </path>
                </svg>
                <span class="logo-text">Novelist Space - Read</span>
            </a>
            <div class="nav-links">
                <a href="index.php">Back Home</a>
            </div>
        </header>

        <main>
            <input type="hidden" id="novel_id" value="<?= htmlspecialchars($novel['id'], ENT_QUOTES) ?>">
            <input type="hidden" id="novel_title" value="<?= htmlspecialchars($novel['title'], ENT_QUOTES) ?>">
            <h1><?= htmlspecialchars($novel['title'], ENT_QUOTES) ?></h1>
            <p><strong>Author:</strong> <?= htmlspecialchars($novel['author_name'], ENT_QUOTES) ?></p>
            <p><strong>Genre:</strong> <?= htmlspecialchars($novel['genre'], ENT_QUOTES) ?></p>

            <?php if ((int)$novel['novel_type'] === 0): ?>
                <div class="novel-content">
                    <?= nl2br(htmlspecialchars(file_get_contents($novel['file_path']), ENT_QUOTES)) ?>
                </div>
            <?php else: ?>
                <form id="download-form">
                    <button type="submit" class="download-btn">Download <?= htmlspecialchars($novel['title'], ENT_QUOTES) ?>.pdf</button>
                </form>
            <?php endif; ?>
        </main>

        <?php if ($user_id === $novel['uploader_id']) : ?>
            <form id="delete-form">
                <button type="submit" class="delete-btn">
                    <i class="fas fa-trash"></i> Delete Novel
                </button>
            </form>
        <?php endif; ?>

        <footer class="footer">
            <p id="p_footer"></p>
        </footer>

        <script src="/Scripts/copyright.js"></script>
        <script src="/Scripts/novels_detail.js"></script>

    </body>
</html>
