<?php
declare(strict_types=1);

/**
 * Local XAMPP defaults. Override with environment variables when needed:
 * RB_DB_HOST, RB_DB_PORT, RB_DB_NAME, RB_DB_USER, RB_DB_PASS
 */
$dbHost = getenv('RB_DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('RB_DB_PORT') ?: '3306';
$dbName = getenv('RB_DB_NAME') ?: 'rockbottom';
$dbUser = getenv('RB_DB_USER') ?: 'root';
$dbPass = getenv('RB_DB_PASS');
if ($dbPass === false) {
    $dbPass = '';
}

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]
    );
    $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    error_log('RockBottom database connection failed: ' . $e->getMessage());
    http_response_code(503);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Database connection failed. Check config/database.php and import database/rockbottom.sql.\n");
        exit(1);
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>RockBottom setup</title>';
    echo '<style>body{font-family:Georgia,serif;background:#30202D;color:#EDE5D8;padding:12vh 8vw;line-height:1.7}code{background:#453B3A;padding:2px 6px;border-radius:3px}</style>';
    echo '<h1>We are still below the surface.</h1>';
    echo '<p>The database is not reachable. Start MySQL in XAMPP, import <code>database/rockbottom.sql</code>, and check <code>config/database.php</code>.</p>';
    echo '</body></html>';
    exit;
}

return $pdo;
