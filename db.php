<?php
/* Path: db.php
 * 說明：讀取專案根目錄 .env，建立 PDO 連線-測試
 */

declare(strict_types=1);

function env_load(string $file): array
{
    if (!is_file($file)) {
        throw new RuntimeException('.env 不存在：' . $file);
    }

    $vars = parse_ini_file($file, false, INI_SCANNER_RAW);
    if ($vars === false) {
        throw new RuntimeException('.env 解析失敗');
    }

    return $vars;
}

try {
    $env = env_load(__DIR__ . '/.env');

    $host     = $env['DB_HOST'] ?? 'localhost';
    $port     = $env['DB_PORT'] ?? '3306';
    $dbname   = $env['DB_DATABASE'] ?? '';
    $username = $env['DB_USERNAME'] ?? '';
    $password = $env['DB_PASSWORD'] ?? '';
    $charset  = $env['DB_CHARSET'] ?? 'utf8mb4';

    if ($dbname === '' || $username === '') {
        throw new RuntimeException('.env 缺少 DB_DATABASE 或 DB_USERNAME');
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    exit('DB ERROR: ' . $e->getMessage());
}