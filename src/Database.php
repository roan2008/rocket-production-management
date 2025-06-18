<?php
class Database
{
    private static $pdo;

    public static function connect()
    {
        if (!self::$pdo) {
            $config = include __DIR__ . '/../config.php';
            $db = $config['db'];
            $dsn = "mysql:host={$db['host']};dbname={$db['database']};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $db['username'], $db['password']);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$pdo;
    }

    /**
     * Sanitize input string to prevent XSS and trim whitespace.
     * This should be used for display purposes only. Database
     * queries rely on prepared statements for security.
     */
    public static function sanitizeString($value)
    {
        return htmlspecialchars(trim((string) $value), ENT_QUOTES, 'UTF-8');
    }
}
?>
