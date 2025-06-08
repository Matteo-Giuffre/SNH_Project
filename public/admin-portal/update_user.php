<?php
    session_start([
        'cookie_lifetime' => 0,
        'cookie_httponly' => true,
        'cookie_secure' => true, // Assicurati che il sito usi HTTPS
        'cookie_samesite' => 'Strict'
    ]);

    ob_clean();

    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        header("Location: /admin-portal/");
        exit;
    }

    // To prevent session hijacking
    if ($_SESSION['IP'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['User-Agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        header("Location: /admin-portal/");
        exit;
    }

    // 15 minuti
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
        session_unset();
        session_destroy();
        header("Location: /admin-portal/");
        exit;
    }

    // Set autoload
    require '/var/www/app/vendor/autoload.php';
    // Set logger
    require '/var/www/app/logger.php';
    // Set database
    require_once '/var/www/mysql_client/config_db.php';

    $_SESSION['last_activity'] = time(); // Aggiorna il timer

    // Ottieni l'ID dell'utente
    if (isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];

        // Inverti direttamente il valore di ispremium
        $changeStatus = "UPDATE us3rs SET ispremium = NOT ispremium WHERE id = :id";
        $stmt = $pdo->prepare($changeStatus);
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            logs_webapp("changed the status of user $user_id", "Admin (ID: " . $_SESSION['admin_id']. ")", 'admin_panel.log');

            http_response_code(200);
            echo json_encode(["status" => "success"]);
            exit;
        }

        // If an error occures
        logs_webapp("failed to update status of user $user_id", 'Admin (ID: ' . $_SESSION['admin_id']. ')', 'admin_panel.log');
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to update"]);
        exit;

    }
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid parameter"]);
?>