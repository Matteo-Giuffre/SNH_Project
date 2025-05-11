<?php

session_start([
    'cookie_lifetime' => 0,
    'cookie_httponly' => true,
    'cookie_secure' => true, // Assicurati che il sito usi HTTPS
    'cookie_samesite' => 'Strict'
]);


if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.html");
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
require_once '/var/www/mysql_client/config_db.php';

$query = "SELECT id, email, username, ispremium FROM us3rs";
$stmt = $pdo->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="../Styles/admin_panel.css">
</head>
<body>
    <header class="header">
        <a href="" class="logo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                </path>
            </svg>
            <span class="logo-text">Novelist Space - Admin Panel</span>
        </a>
        <form action="logout.php" method="post" style="float: right; margin-top: 10px;">
            <button type="submit" class="logout-button">
                <img src="../Resources/logout.png" alt="Logout Icon" class="logout-icon">
                Log out
            </button>
        </form>
    </header>
    <div class="container">
        <header>
            <h1>Users</h1>
            <input type="text" id="searchBar" class="search-bar" placeholder="Search Username...">
        </header>
        <div class="scroll-container">
            <?php foreach ($results as $row): ?>
                <div class="user-card">
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars($row['username']); ?></h3>
                        <p>ID: <?php echo htmlspecialchars($row['id']); ?></p>
                        <p>Email: <?php echo htmlspecialchars($row['email']); ?></p>
                        <p>Status: 
                            <span class="status <?php echo $row['ispremium'] ? 'premium' : 'free'; ?>">
                                <?php echo $row['ispremium'] ? 'Premium' : 'Free'; ?>
                            </span>
                        </p>
                    </div>
                    <button class="button button-toggle <?php echo $row['ispremium'] ? 'button-red' : ''; ?>" onclick="togglePremium(<?php echo $row['id']; ?>)">
                        Switch to <?php echo $row['ispremium'] ? 'Free' : 'Premium'; ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
        function togglePremium(userId) {
            fetch('update_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'user_id=' + userId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert('Errore: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Si è verificato un errore.');
            });
        }

        document.addEventListener("DOMContentLoaded", function () {
            const searchBar = document.getElementById('searchBar');
            const userCards = document.querySelectorAll('.user-card');

            searchBar.addEventListener('input', function () {
                let searchValue = searchBar.value.toLowerCase().trim();

                userCards.forEach(card => {
                    let username = card.querySelector('h3').textContent.toLowerCase();
                    if (username.includes(searchValue)) {
                        card.style.display = "flex"; // Mostra la card
                    } else {
                        card.style.display = "none"; // Nasconde la card
                    }
                });
            });
        });
    </script>
</body>
</html>
