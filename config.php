```php
<?php
declare(strict_types=1);

/*
 * CONFIGURAÇÃO DO BANCO DE DADOS
 * XAMPP padrão: usuário root e senha vazia.
 * Altere conforme os dados da sua hospedagem.
 */
$host = "localhost";
$dbname = "usuariovizan_v2";
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
    error_log("Vizan DB: " . $e->getMessage());
    http_response_code(500);
    exit("Não foi possível conectar ao banco. Confira as configurações do config.php e importe o SQL.");
}
