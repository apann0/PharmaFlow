<?php
/**
 * Database Configuration & Connection
 * 
 * Uses PDO with prepared statements for SQL injection prevention.
 * All queries throughout the application MUST use this connection.
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// ─── Database Credentials ───────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'pharmacy_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ─── PDO Connection ─────────────────────────────────────────────
function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error in production; show generic message
            error_log('Database Connection Error: ' . $e->getMessage());
            die('Database connection failed. Please check your configuration.');
        }
    }

    return $pdo;
}

// ─── Helper: Generate Unique Transaction ID ─────────────────────
function generateTransactionId(string $prefix = 'TXN'): string
{
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
}

// ─── Helper: Log Action ─────────────────────────────────────────
function logAction(int $userId, string $action, string $detail = ''): void
{
    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO logs (user_id, action, detail, ip_address) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        $userId,
        $action,
        $detail,
        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
    ]);
}

// ─── Helper: CSRF Token ─────────────────────────────────────────
function generateCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ─── Helper: Sanitize Output (XSS Prevention) ──────────────────
function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ─── Helper: Flash Messages ─────────────────────────────────────
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ─── Helper: Format Currency ────────────────────────────────────
function formatCurrency(float $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
