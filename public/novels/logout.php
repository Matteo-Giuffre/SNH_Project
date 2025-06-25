<?php
    session_start([
            'cookie_lifetime' => 0,
            'cookie_httponly' => true,
            'cookie_secure' => true, // Assicurati che il sito usi HTTPS
            'cookie_samesite' => 'Strict'
    ]);

    ob_clean();

    // Set logger
    require_once '/var/www/app/logger.php';

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

    logs_webapp('logged out', $_SESSION['username'], 'login.log');

    // To prevent session fixation attacks
    session_regenerate_id(true);

    // Distruggi tutte le variabili di sessione
    $_SESSION = array();

    // Remove session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, 
            $params["path"], 
            $params["domain"], 
            $params["secure"], 
            $params["httponly"]
        );
    }

    // Distruggi la sessione
    session_destroy();

    // Reindirizza alla pagina di login (o home page)
    header("Location: /");
    exit;
?>