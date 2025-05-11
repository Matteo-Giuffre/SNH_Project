<?php
    $HOST = 'novelist_database';
    $DB_NAME = 'sn_project';
    $DB_USER = 'novelist_user';
    $DB_PASSWORD = trim(file_get_contents("/run/www-data_secrets/mysql_password"));

    $ssl_ca = "/etc/mysql_client/ssl/ca.pem";
    $ssl_cert = "/etc/mysql_client/ssl/client-cert.pem";
    $ssl_key = "/etc/mysql_client/ssl/client-key.pem";

    try {
        $pdo = new PDO("mysql:host=$HOST;dbname=$DB_NAME", 
            $DB_USER, 
            $DB_PASSWORD,
            # Use ca cert, client-cert and client-key to enable mutual authentication between webserver and db
            [
                PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
                PDO::MYSQL_ATTR_SSL_CERT => $ssl_cert,
                PDO::MYSQL_ATTR_SSL_KEY => $ssl_key,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
    } catch (PDOException $e) {
        exit;
    }
?>
