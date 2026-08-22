<?php
/**
 * Database Configuration
 * AI-Powered Student Collaboration Intelligence Platform
 */

// Supabase REST credentials (read from environment variables or local placeholders)
define('SUPABASE_URL',            getenv('SUPABASE_URL') ?: 'https://sbzecviaqezsbouymecf.supabase.co');
define('SUPABASE_PUBLISHABLE_KEY',getenv('SUPABASE_PUBLISHABLE_KEY') ?: 'sb_publishable_u6d3Nf4pSnpDtEISRJm24g_kyDP70a7');
define('SUPABASE_SECRET_KEY',     getenv('SUPABASE_SECRET_KEY') ?: '');
define('SUPABASE_JWKS_URL',       getenv('SUPABASE_JWKS_URL') ?: 'https://sbzecviaqezsbouymecf.supabase.co/auth/v1/.well-known/jwks.json');

// Database credentials (with environment variable support for cloud deployment)
define('DB_HOST',    getenv('DB_HOST') ?: 'db.sbzecviaqezsbouymecf.supabase.co');
define('DB_PORT',    getenv('DB_PORT') ?: '5432');
define('DB_NAME',    getenv('DB_NAME') ?: 'postgres');
define('DB_USER',    getenv('DB_USER') ?: 'postgres');
define('DB_PASS',    getenv('DB_PASS') ?: 'Govardhan@26');

// Dynamic BASE_URL detection:
// 1. Explicit env var if set (e.g. in cloud / custom subpath)
// 2. Auto-detects if running under /ipcapstone (XAMPP default) or root domain (Render/Railway)
if (getenv('BASE_URL') !== false) {
    define('BASE_URL', rtrim(getenv('BASE_URL'), '/'));
} elseif (isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], '/ipcapstone')) {
    define('BASE_URL', '/ipcapstone');
} elseif (isset($_SERVER['SCRIPT_NAME']) && str_starts_with($_SERVER['SCRIPT_NAME'], '/ipcapstone')) {
    define('BASE_URL', '/ipcapstone');
} else {
    define('BASE_URL', '');
}

define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB

/**
 * Returns a singleton PDO connection (Supabase PostgreSQL / Cloud DB or local MySQL).
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        // Step 1: Check for DATABASE_URL environment variable (common in Railway/Render/Heroku)
        $databaseUrl = getenv('DATABASE_URL');
        if ($databaseUrl) {
            try {
                $dbParts = parse_url($databaseUrl);
                $pgHost = $dbParts['host'] ?? DB_HOST;
                $pgPort = $dbParts['port'] ?? 5432;
                $pgUser = urldecode($dbParts['user'] ?? DB_USER);
                $pgPass = urldecode($dbParts['pass'] ?? DB_PASS);
                $pgName = ltrim($dbParts['path'] ?? 'postgres', '/');

                $dsnPg = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=require', $pgHost, $pgPort, $pgName);
                $pdo = new PDO($dsnPg, $pgUser, $pgPass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT         => false,
                    PDO::ATTR_TIMEOUT            => 10,
                ]);
                return $pdo;
            } catch (PDOException $eDbUrl) {
                error_log('DATABASE_URL connection failed: ' . $eDbUrl->getMessage());
            }
        }

        // Step 2: In cloud environments (Railway, Render) or when DB_HOST is explicitly configured
        $isCloud = getenv('RAILWAY_ENVIRONMENT') || getenv('RENDER') || (getenv('DB_HOST') && getenv('DB_HOST') !== '127.0.0.1');

        if ($isCloud) {
            try {
                $dsnPg = sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
                    DB_HOST, DB_PORT, DB_NAME
                );
                $pdo = new PDO($dsnPg, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT         => false,
                    PDO::ATTR_TIMEOUT            => 10,
                ]);
                return $pdo;
            } catch (PDOException $ePg) {
                error_log('Supabase DB connection failed: ' . $ePg->getMessage());
                http_response_code(500);
                die('<div style="font-family:sans-serif;padding:30px;max-width:600px;margin:auto;background:#fee2e2;border:1px solid #ef4444;border-radius:10px;color:#991b1b;">'
                  . '<h3 style="margin-top:0;">Database Connection Error</h3>'
                  . '<p>Could not connect to Supabase PostgreSQL at <code>' . htmlspecialchars(DB_HOST) . '</code>.</p>'
                  . '<p><strong>Details:</strong> ' . htmlspecialchars($ePg->getMessage()) . '</p>'
                  . '<p>Please check your Railway <strong>Variables</strong> tab for <code>DB_HOST</code>, <code>DB_USER</code>, and <code>DB_PASS</code>.</p>'
                  . '</div>');
            }
        }

        // Step 3: Local Development (Try local MySQL first, then Supabase PostgreSQL)
        $mysqlPorts = [3308, 3306];
        foreach ($mysqlPorts as $port) {
            try {
                $dsnMysql = "mysql:host=127.0.0.1;port={$port};dbname=ipcapstone_db;charset=utf8mb4";
                $pdo = new PDO($dsnMysql, 'root', '', [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT         => false,
                ]);
                return $pdo;
            } catch (PDOException $eMy) {
                // Try next port
            }
        }

        // Local fallback to Supabase PostgreSQL
        try {
            $dsnPg = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
                DB_HOST, DB_PORT, DB_NAME
            );
            $pdo = new PDO($dsnPg, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT         => false,
                PDO::ATTR_TIMEOUT            => 5,
            ]);
        } catch (PDOException $ePg) {
            error_log('DB Connection failed: ' . $ePg->getMessage());
            http_response_code(500);
            die('Database connection error. Please ensure local XAMPP MySQL is started or check Supabase credentials.');
        }
    }
    return $pdo;
}

// ─── Session helpers ──────────────────────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['student_id']);
}

function isFacultyLoggedIn(): bool {
    return isset($_SESSION['faculty_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

function requireFaculty(): void {
    if (!isFacultyLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php?role=faculty');
        exit;
    }
}

function currentStudent(): array {
    $s = $_SESSION['student'] ?? [];
    return is_array($s) ? $s : [];
}

function currentFaculty(): array {
    $f = $_SESSION['faculty'] ?? [];
    return is_array($f) ? $f : [];
}

function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateAvatar(string $name): string {
    $initials = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', trim($name)))));
    $initials  = substr($initials, 0, 2);
    $colors    = ['6366f1','06b6d4','f59e0b','10b981','ef4444','8b5cf6','ec4899'];
    $color     = $colors[abs(crc32($name)) % count($colors)];
    return "https://ui-avatars.com/api/?name=" . urlencode($name)
         . "&background={$color}&color=fff&size=128&bold=true";
}

// ─── CSRF Protection ──────────────────────────────────────────────────────────

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid CSRF token']));
    }
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}
