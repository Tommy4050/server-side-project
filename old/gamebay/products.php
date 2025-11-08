<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$host = 'localhost';
$db   = 'gamebay';
$user = 'root';
$pass = ''; // XAMPP alapértelmezett root jelszó
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $stmt = $pdo->query("SELECT * FROM games ORDER BY id ASC");
    $games = $stmt->fetchAll();
    echo json_encode($games);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
}
?>
