<?php

declare(strict_types=1);

// ======================
// CONFIG
// ======================
ini_set('display_errors', '0');
error_reporting(E_ERROR);

const SESSION_NAME = 'CINEVEO_SECURE_V2';
const SESSION_LIFETIME = 2592000;

const DB_HOST = '127.0.0.1';
const DB_CHARSET = 'utf8mb4';

const DB_CINE = [
    'name' => 'cineveo',
    'user' => 'cineveo',
    'pass' => '986307236M'
];

const DB_PIPO = [
    'name' => 'pipcine',
    'user_primary' => 'pipcine',
    'user_fallback' => 'pipcine',
    'pass' => 'pipocine12mt'
];

// ======================
// SESSION INIT
// ======================
function initSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);

        ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);

        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_start();
    }
}

// ======================
// RATE LIMIT
// ======================
function applyRateLimit(): void
{
    $_SESSION['req_cnt'] = $_SESSION['req_cnt'] ?? 0;
    $_SESSION['req_time'] = $_SESSION['req_time'] ?? time();

    if (time() - $_SESSION['req_time'] < 2) {
        $_SESSION['req_cnt']++;

        if ($_SESSION['req_cnt'] > 20) {
            http_response_code(429);
            exit;
        }
    } else {
        $_SESSION['req_cnt'] = 0;
        $_SESSION['req_time'] = time();
    }
}

// ======================
// DATABASE
// ======================
function createPDO(string $db, string $user, string $pass): ?PDO
{
    try {
        return new PDO(
            "mysql:host=" . DB_HOST . ";dbname={$db};charset=" . DB_CHARSET,
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ]
        );
    } catch (PDOException $e) {
        return null;
    }
}

// ======================
// AUTH
// ======================
function authenticateFromRememberMe(PDO $pdo): void
{
    if (!isset($_COOKIE['remember_me'])) return;

    try {
        [$selector, $token] = explode(':', $_COOKIE['remember_me'], 2);

        $stmt = $pdo->prepare("
            SELECT * FROM auth_tokens 
            WHERE selector = ? AND expires_at > NOW()
        ");
        $stmt->execute([$selector]);

        $row = $stmt->fetch();

        if ($row && hash_equals($row['token_hash'], hash('sha256', $token))) {
            setUserSession($pdo, $row['user_id']);
        }

    } catch (Throwable $e) {}
}

function authenticateFromSessionToken(PDO $pdo): void
{
    if (!isset($_COOKIE['cineveo_token'])) return;

    try {
        $hash = hash('sha256', $_COOKIE['cineveo_token']);

        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.full_name, u.name, u.profile_pic_url
            FROM user_sessions s
            JOIN users u ON u.id = s.user_id
            WHERE s.token_hash = ? AND s.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$hash]);

        if ($user = $stmt->fetch()) {
            applyUserToSession($user);
        }

    } catch (Throwable $e) {}
}

function setUserSession(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare("
        SELECT id, username, full_name, name, profile_pic_url
        FROM users WHERE id = ?
    ");
    $stmt->execute([$userId]);

    if ($user = $stmt->fetch()) {
        applyUserToSession($user);
    }
}

function applyUserToSession(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'] ?: $user['name'];
    $_SESSION['profile_pic_url'] = $user['profile_pic_url'];
}

// ======================
// BOOTSTRAP
// ======================
initSession();
applyRateLimit();

// DB connections
$pdoCineveo = createPDO(DB_CINE['name'], DB_CINE['user'], DB_CINE['pass']);

if (!$pdoCineveo) {
    http_response_code(503);
    exit;
}

$pdo = createPDO(DB_PIPO['name'], DB_PIPO['user_primary'], DB_PIPO['pass'])
    ?? createPDO(DB_PIPO['name'], DB_PIPO['user_fallback'], DB_PIPO['pass']);

// AUTH
if (!isset($_SESSION['user_id'])) {
    authenticateFromRememberMe($pdoCineveo);
    authenticateFromSessionToken($pdoCineveo);
}