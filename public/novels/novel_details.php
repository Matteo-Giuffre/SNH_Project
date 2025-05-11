<?php
session_start([
    'cookie_lifetime' => 0,
    'cookie_httponly' => true,
    'cookie_secure' => true, // Assicurati che il sito usi HTTPS
    'cookie_samesite' => 'Strict'
]);


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login/index.html");
    exit;
}

if ($_SESSION['IP'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['User-Agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    header("Location: ../login/index.html");
    exit;
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) { //15 minuti
    session_unset();
    session_destroy();
    header("Location: ../login/index.html");
    exit;
}
$_SESSION['last_activity'] = time(); // Aggiorna il timer

$user_type = $_SESSION['user-type'];
$user_id = $_SESSION['id'];

if (!isset($_GET['id'])) {
    die("Novel not found.");
}

// Configurazione del database
require_once '/var/www/mysql_client/config_db.php';


$novel_id = intval($_GET['id']); // Assicuriamoci che sia un intero
if (!is_int($novel_id)) {
    die(json_encode(["status" => "error", "message" => "Novel not found."]));
}
$query = "SELECT * FROM novels WHERE id = :id";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':id', $novel_id, PDO::PARAM_INT);
$stmt->execute();

$novel = $stmt->fetch(PDO::FETCH_ASSOC); // Ottieni i dati come array associativo

if (!$novel) {
    die(json_encode(["status" => "error", "message" => "Novel not found."]));
}

// Protezione per le novelle Premium
if ($novel['free'] && $user_type !== 'premium') {
    
    if ($novel['uploader_id'] === $user_id){
        ;
    }
    else{
        die("This is a premium content!");
    }
}

# Check path traversal
$filePath = './' . $novel['file_path']; 

if (!file_exists($filePath)) {
    die("Server Error: File not Found");
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($novel['title']) ?> - Details</title>
    <link rel="stylesheet" href="../Styles/novel_details_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

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
        <h1><?= htmlspecialchars($novel['title']) ?></h1>
        <p><strong>Author:</strong> <?= htmlspecialchars($novel['author_name']) ?></p>
        <p><strong>Genre:</strong> <?= htmlspecialchars($novel['genre']) ?></p>

        <?php if ($novel['novel_type'] == 0): ?>
            <div class="novel-content">
                <?php echo nl2br(htmlspecialchars(file_get_contents($novel['file_path']))); ?>
            </div>
        <?php else: ?>
            <form action="download.php" method="POST">
                <input type="hidden" name="id" value="<?= htmlspecialchars($novel['id']) ?>">
                <button type="submit" class="download-btn">Download <?= htmlspecialchars($novel['title']) ?>.pdf</button>
            </form>
        <?php endif; ?>
    </main>

    <?php if ($user_id === $novel['uploader_id']) : ?>
        <form action="delete.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this novel?');">
            <input type="hidden" name="novel_id" value="<?= htmlspecialchars($novel['id']) ?>">
            <button type="submit" class="delete-btn">
                <i class="fas fa-trash"></i> Delete Novel
            </button>
        </form>
    <?php endif; ?>

    <footer>© 2025 Wannabe Novelist. All rights reserved.</footer>
</body>
</html>
