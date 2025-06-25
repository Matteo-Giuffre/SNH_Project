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

    // 15 minuti
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
        session_unset();
        session_destroy();
        header("Location: /login/");
        exit;
    }
    $_SESSION['last_activity'] = time(); // Aggiorna il timer

    // Configurazione del database
    require_once '/var/www/mysql_client/config_db.php';
    require_once '/var/www/app/sanitizer.php';

    $sanitizer = new InputSanitizer();

    // Recupera il valore della ricerca, se presente
    $search_query = isset($_GET['search']) ? $sanitizer->sanitizeString($_GET['search']) : null;

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
    $params = [];

    // Se è stata fatta una ricerca, aggiungere il filtro sulla query
    if ($search_query && !empty($search_query)) {
        $conditions[] = "title LIKE :search";
        $params['search'] = "%$search_query%";
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
        $params['user_id'] = $_SESSION['id'];
    }

    // Se ci sono condizioni, aggiungiamo WHERE
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " LIMIT $items_per_page OFFSET $offset";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $novels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Conteggio totale per la paginazione
    $count_query = "SELECT COUNT(*) FROM novels";
    if (!empty($conditions)) {
        $count_query .= " WHERE " . implode(" AND ", $conditions);
    }

    $total_stmt = $pdo->prepare($count_query);

    $total_stmt->execute($params);

    $total_novels = $total_stmt->fetchColumn();
    $total_pages = ceil($total_novels / $items_per_page);

    // Retrieve user data from current session 
    $user_type = $_SESSION['user-type'];
    $username = $_SESSION['username'];
    $user_id = $_SESSION['id'];
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Novelist Space - Discover Your Next Story</title>
        <link rel="stylesheet" href="/Styles/home_style.css">
    </head>
    <body>
        <header class="header">
            <a id="reload-section" href="index.php" class="logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                    </path>
                </svg>
                <img src="/Resources/user-icon.png" alt="User Icon" class="user-icon">
                <span class="user-info"><?= htmlspecialchars($username, ENT_QUOTES) ?></span> - 
                <span class="account-status"><?= htmlspecialchars($user_type, ENT_QUOTES) ?></span>
            </a>
            <div class="nav-links">
                <a href="upload.php">Upload Novel</a>
            </div>
            <form action="logout.php" method="post" class="logout-form">
                <button type="submit" class="logout-button">
                    <img src="/Resources/logout.png" alt="Logout Icon" class="logout-icon">
                    Log out
                </button>
            </form>
        </header>

        <div class="search-container">
            <form method="GET" id="search-form" action="index.php">
                <div class="search-box">
                    <input type="text" id="search-bar" class="search-bar"
                        placeholder="Search novels by title..." value="<?= htmlspecialchars($search_query, ENT_QUOTES) ?>">
                    
                    <!-- Mantiene il filtro attivo -->
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter, ENT_QUOTES) ?>">

                    <button type="submit" id="search-button" class="search-button">🔍</button>
                </div>
            </form>
        </div>

        <div class="filters">
            <h2 class="filter-title">Filter by:</h2>
            <button class="filter-tag <?= ($filter === 'all') ? 'active' : '' ?>" data-filter="all">All</button>
            <button class="filter-tag <?= ($filter === 'short') ? 'active' : '' ?>" data-filter="short">Short</button>
            <button class="filter-tag <?= ($filter === 'long') ? 'active' : '' ?>" data-filter="long">Long</button>
            <button class="filter-tag <?= ($filter === 'free') ? 'active' : '' ?>" data-filter="free">Free</button>
            <button class="filter-tag <?= ($filter === 'premium') ? 'active' : '' ?>" data-filter="premium">Premium</button>
            <button class="filter-tag <?= ($filter === 'my_novels') ? 'active' : '' ?>" data-filter="my_novels">My Novels</button>
        </div>

        <main class="novels-container">
        <?php if (empty($novels)): ?>
            <div class="no-results">
                <p>No results found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($novels as $novel) : ?>
                <div class="novel-card <?= $novel['free'] ? 'premium' : 'free' ?>">
                    <h3 class="novel-title">Title:  <?= htmlspecialchars($novel['title'], ENT_QUOTES) ?></h3>
                    <p class="novel-info">✍️ Author:   <b><?= htmlspecialchars($novel['author_name'], ENT_QUOTES) ?></b></p>
                    <p class="novel-info">📚 Genre:  <b><?= htmlspecialchars($novel['genre'], ENT_QUOTES) ?></b></p>
                    <p class="novel-info"><?= $novel['novel_type'] ? '📖 Novel Type: <b>Long</b>' : '📝 Novel Type: <b>Short</b>' ?></p>
                    
                    <?php if ($novel['free'] && $user_type !== 'premium') : ?>
                        <?php if ($novel['uploader_id'] === $user_id) : ?>
                            <a href="novel_details.php?id=<?= urlencode($novel['id']) ?>" class="details-button unlocked">Read</a>
                        <?php else: ?>
                            <a class="details-button locked">🔒 Premium</a>
                        <?php endif; ?>
                    <?php elseif ($novel['free'] && $user_type === 'premium') : ?>
                        <a href="novel_details.php?id=<?= urlencode($novel['id']) ?>" class="details-button unlocked">Read</a>
                    <?php else: ?>
                        <a href="novel_details.php?id=<?= urlencode($novel['id']) ?>" class="details-button">Read</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </main>

        <!-- Paginazione -->
        <?php if ($total_novels > 0):?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?filter=<?= urlencode($filter) ?>&search=<?= urlencode($search_query) ?>&page=<?= urlencode($page - 1) ?>" class="page-arrow">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 12H5M12 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                <?php endif; ?>

                <span class="page-info">Page <?= $page ?> of <?= $total_pages ?></span>

                <?php if ($page < $total_pages): ?>
                    <a href="?filter=<?= urlencode($filter) ?>&search=<?= urlencode($search_query) ?>&page=<?= urlencode($page + 1) ?>" class="page-arrow">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <footer class="footer">
            <p id="p_footer"></p>
        </footer>

        <script src="/Scripts/copyright.js"></script>
        <script src="/Scripts/novels_index.js"></script>

    </body>
</html>