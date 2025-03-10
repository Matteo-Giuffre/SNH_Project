<?php
$host = '127.0.0.1';
$dbname = 'sn_project';
$username = 'root';
$password = 'SN2425';
$port = 3306;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", 'message' => 'Db connection Failed']);
    exit;
}
?>
