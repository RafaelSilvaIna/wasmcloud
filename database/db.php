<?php
ini_set('display_errors', 0);
error_reporting(E_ERROR);

if (session_status() === PHP_SESSION_NONE) {
    session_name("CINEVEO_SECURE_V2");
    ini_set('session.gc_maxlifetime', 2592000);
    session_set_cookie_params([
        'lifetime' => 2592000,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if (!isset($_SESSION['req_cnt'])) $_SESSION['req_cnt'] = 0;
if (!isset($_SESSION['req_time'])) $_SESSION['req_time'] = time();

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

$dbHost = '127.0.0.1';
$dbCharset = 'utf8mb4';

$dbNameCine = 'cineveo';
$dbUserCine = 'cineveo';
$dbPassCine = '986307236M';

$dbNamePipo = 'pipocine';
$dbUserPipoPrimary = 'pipocine';
$dbUserPipoFallback = 'pipcine';
$dbPassPipo = 'pipocine12mt';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 5,
];

try {
    $pdoCineveo = new PDO("mysql:host={$dbHost};dbname={$dbNameCine};charset={$dbCharset}", $dbUserCine, $dbPassCine, $options);
} catch (\PDOException $e) {
    http_response_code(503);
    exit;
}

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbNamePipo};charset={$dbCharset}", $dbUserPipoPrimary, $dbPassPipo, $options);
} catch (\PDOException $e) {
    try {
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbNamePipo};charset={$dbCharset}", $dbUserPipoFallback, $dbPassPipo, $options);
    } catch (\PDOException $e) {
        $pdo = null;
    }
}

if (!isset($_SESSION['user_id'])) {
    if (isset($_COOKIE['remember_me'])) {
        try {
            $cookieParts = explode(':', $_COOKIE['remember_me'], 2);
            if (count($cookieParts) === 2) {
                $stmt = $pdoCineveo->prepare("SELECT * FROM auth_tokens WHERE selector = ? AND expires_at > NOW()");
                $stmt->execute([$cookieParts[0]]);
                $tokenRow = $stmt->fetch();
                
                if ($tokenRow && hash_equals($tokenRow['token_hash'], hash('sha256', $cookieParts[1]))) {
                    $uStmt = $pdoCineveo->prepare("SELECT id, username, full_name, name, profile_pic_url FROM users WHERE id = ?");
                    $uStmt->execute([$tokenRow['user_id']]);
                    if ($user = $uStmt->fetch()) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'] ?: $user['name'];
                        $_SESSION['profile_pic_url'] = $user['profile_pic_url'];
                    }
                }
            }
        } catch (Exception $e) {}
    } elseif (isset($_COOKIE['cineveo_token'])) {
        try {
            $tokenHash = hash('sha256', $_COOKIE['cineveo_token']);
            $stmtSess = $pdoCineveo->prepare("SELECT u.id, u.username, u.full_name, u.name, u.profile_pic_url FROM user_sessions s JOIN users u ON u.id = s.user_id WHERE s.token_hash = ? AND s.expires_at > NOW() LIMIT 1");
            $stmtSess->execute([$tokenHash]);
            if ($sessionUser = $stmtSess->fetch()) {
                $_SESSION['user_id'] = $sessionUser['id'];
                $_SESSION['username'] = $sessionUser['username'];
                $_SESSION['full_name'] = $sessionUser['full_name'] ?: $sessionUser['name'];
                $_SESSION['profile_pic_url'] = $sessionUser['profile_pic_url'];
            }
        } catch (Exception $e) {}
    }
}