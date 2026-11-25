<?php
// Ajuste estas informações conforme seu MySQL/XAMPP.
$host = "localhost";
$dbname = "usuariovizan";
$dbuser = "root";
$dbpass = "";

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $dbuser,
        $dbpass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit("Não foi possível conectar ao banco de dados. Verifique o config.php.");
}
