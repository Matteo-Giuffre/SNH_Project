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

// 15 minuti
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();
    session_destroy();
    header("Location: ../login/index.html");
    exit;
}
$_SESSION['last_activity'] = time(); // Aggiorna il timer



// Configurazione del database
require_once '../config_db.php';

// Recupera il valore della ricerca, se presente
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
// Controlla che la search_query non contenga caratteri pericolosi
if (preg_match('/[^a-zA-Z0-9\s]/', $search_query)) {
    die("Invalid search query");
}


// Filtri
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$valid_filters = ['all', 'short', 'long', 'free', 'premium', 'my_novels'];
if (!in_array($filter, $valid_filters)) {
    die("Invalid filter value");
}
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 8;
$offset = ($page - 1) * $items_per_page;

$query = "SELECT * FROM novels";
$conditions = [];

// Se è stata fatta una ricerca, aggiungere il filtro sulla query
if (!empty($search_query)) {
    $conditions[] = "title LIKE :search";
}

// Applicazione filtri
if ($filter === 'short') {
    $conditions[] = "novel_type = 0";
} elseif ($filter === 'long') {
    $conditions[] = "novel_type = 1";
} elseif ($filter === 'free') {
    $conditions[] = "free = 0";
} elseif ($filter === 'premium') {
    $conditions[] = "free = 1";
} elseif ($filter === 'my_novels') {
    // Aggiungi il filtro per "My Novels" usando l'uploader_id dell'utente
    $conditions[] = "uploader_id = :user_id";
}

// Se ci sono condizioni, aggiungiamo WHERE
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " LIMIT $items_per_page OFFSET $offset";

$stmt = $pdo->prepare($query);

// Se c'è una ricerca, esegui la query con il parametro di ricerca
$params = [];
if (!empty($search_query)) {
    $params['search'] = "%$search_query%";
}
if ($filter === 'my_novels') {
    $params['user_id'] = $_SESSION['id'];
}

$stmt->execute($params);

$novels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Conteggio totale per la paginazione
$count_query = "SELECT COUNT(*) FROM novels";
if (!empty($conditions)) {
    $count_query .= " WHERE " . implode(" AND ", $conditions);
}

$total_stmt = $pdo->prepare($count_query);
$total_params = [];

if (!empty($search_query)) {
    $total_params['search'] = "%$search_query%";
}

if ($filter === 'my_novels') {
    $total_params['user_id'] = $_SESSION['id']; // L'ID dell'utente dalla sessione
}

$total_stmt->execute($total_params);

$total_novels = $total_stmt->fetchColumn();
$total_pages = ceil($total_novels / $items_per_page);

$username = $_SESSION['username'];
$user_type = $_SESSION['user-type'];
$user_id = $_SESSION['id'];
?>


<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wannabe Novelist - Discover Your Next Story</title>
    <link rel="stylesheet" href="../Styles/home_style.css">
</head>
<body>
    <header class="header">
        <a href="" class="logo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                </path>
            </svg>
            <span class="logo-text">Novelist Space - Home</span>
            <img src="Resources/user-icon.png" alt="User Icon" class="user-icon" id="user-icon" style="cursor: pointer;max-height: 40px;max-width: 40px; margin-left: 15px;">
            <span class="user-info"><?php echo htmlspecialchars($username); ?></span>- 
            <span class="account-status"><?php echo ucfirst(htmlspecialchars($user_type)); ?></span>
        </a>
        <div class="nav-links">
            <a href="upload.php">Upload Novel</a>
        </div>
        <form action="logout.php" method="post" style="float: right; margin-top: 10px;">
            <button type="submit" class="logout-button">
                <img src="../Resources/logout.png" alt="Logout Icon" class="logout-icon">
                Log out
            </button>
        </form>
    </header>

    <div class="search-container">
        <form method="GET" action="index.php">
            <div class="search-box">
                <input type="text" name="search" id="search-bar" class="search-bar"
                    placeholder="Search novels by title..." value="<?= htmlspecialchars($search_query) ?>">
                
                <!-- Mantiene il filtro attivo -->
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">

                <button type="submit" id="search-button" class="search-button">🔍</button>
            </div>
        </form>
    </div>

    <div class="filters">
    <h2 style="margin-left: 2.2rem; margin-bottom: 0rem; font-size: 1.5rem;">Filter by:</h2>
        <button class="filter-tag <?= ($filter === 'all') ? 'active' : '' ?>" onclick="window.location.href='?filter=all'">All</button>
        <button class="filter-tag <?= ($filter === 'short') ? 'active' : '' ?>" onclick="window.location.href='?filter=short'">Short</button>
        <button class="filter-tag <?= ($filter === 'long') ? 'active' : '' ?>" onclick="window.location.href='?filter=long'">Long</button>
        <button class="filter-tag <?= ($filter === 'free') ? 'active' : '' ?>" onclick="window.location.href='?filter=free'">Free</button>
        <button class="filter-tag <?= ($filter === 'premium') ? 'active' : '' ?>" onclick="window.location.href='?filter=premium'">Premium</button>
        <button class="filter-tag <?= ($filter === 'my_novels') ? 'active' : '' ?>" onclick="window.location.href='?filter=my_novels'">My Novels</button>
    </div>

    <main class="novels-container">
    <?php if (empty($novels)): ?>
        <div class="no-results">
            <p>No results found.</p>
        </div>
    <?php else: ?>
        <?php foreach ($novels as $novel) : ?>
            <div class="novel-card <?= $novel['free'] ? 'premium' : 'free' ?>">
                <h3 class="novel-title">Title:  <?= htmlspecialchars($novel['title']) ?></h3>
                <p class="novel-info">✍️ Author:   <b><?= htmlspecialchars($novel['author_name']) ?></b></p>
                <p class="novel-info">📚 Genre:  <b><?= htmlspecialchars($novel['genre']) ?></b></p>
                <p class="novel-info"><?= $novel['novel_type'] ? '📖 Novel Type: <b>Long</b>' : '📝 Novel Type: <b>Short</b>' ?></p>
                
                <?php if ($novel['free'] && $user_type !== 'premium') : ?>
                    <?php if ($novel['uploader_id'] === $user_id) : ?>
                        <a href="novel_details.php?id=<?= $novel['id'] ?>" class="details-button unlocked">Read</a>
                    <?php else: ?>
                        <a class="details-button locked">🔒 Premium</a>
                    <?php endif; ?>
                <?php elseif ($novel['free'] && $user_type === 'premium') : ?>
                    <a href="novel_details.php?id=<?= $novel['id'] ?>" class="details-button unlocked">Read</a>
                <?php else: ?>
                    <a href="novel_details.php?id=<?= $novel['id'] ?>" class="details-button">Read</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </main>

    <!-- Paginazione -->
    <?php if ($total_novels > 0):?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?filter=<?= $filter ?>&search=<?= urlencode($search_query) ?>&page=<?= $page - 1 ?>" class="page-arrow">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5M12 19l-7-7 7-7"></path>
                    </svg>
                </a>
            <?php endif; ?>

            <span class="page-info">Page <?= $page ?> of <?= $total_pages ?></span>

            <?php if ($page < $total_pages): ?>
                <a href="?filter=<?= $filter ?>&search=<?= urlencode($search_query) ?>&page=<?= $page + 1 ?>" class="page-arrow">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>


    <footer>© 2025 Wannabe Novelist. All rights reserved.</footer>
</body>
</html>