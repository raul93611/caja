<?php
// ============================================================
// config.php — Conexión a la base de datos con PDO
// ============================================================

// Cargar .env
$_envFile = __DIR__ . '/.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if (str_starts_with(trim($_line), '#') || !str_contains($_line, '=')) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        $_ENV[trim($_k)] = trim($_v);
    }
}

define('DB_HOST',    $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME',    $_ENV['DB_NAME'] ?? 'caja');
define('DB_USER',    $_ENV['DB_USER'] ?? 'root');
define('DB_PASS',    $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<p style="color:red;font-family:sans-serif;padding:2rem;">
                Error de conexión a la base de datos. Verifica config.php.<br>
                <small>' . htmlspecialchars($e->getMessage()) . '</small>
            </p>');
        }
    }
    return $pdo;
}

// Función auxiliar: verificar sesión activa
function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Función auxiliar: escapar para HTML
function h(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

// Función auxiliar: formatear moneda BOB
function moneda(float $monto): string {
    return 'Bs ' . number_format($monto, 2);
}
