<?php
ini_set('display_errors', 0);
error_reporting(E_ERROR);

$skipPublicSessionBootstrap = defined('ADMIN_SESSION_CONTEXT') && ADMIN_SESSION_CONTEXT === true;

if (session_status() === PHP_SESSION_NONE && !defined('STREAM_PROXY_CONTEXT') && !$skipPublicSessionBootstrap) {
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

define('IMGBB_API_KEY', '2039ae608a9e563946472995aeb0e672');
define('DOWNLOAD_SECRET', 'CineVEO_Secure_Download_2025_KEY');

$dbHost = '127.0.0.1';
$dbCharset = 'utf8mb4';

$dbNamePipo = 'pipocine';
$dbUserPipoPrimary = 'pipocine';
$dbUserPipoFallback = 'pipcine';
$dbPassPipo = 'pipocine12mt';

$dbNameCine = 'cineveo';
$dbUserCine = 'cineveo';
$dbPassCine = '986307236M';

$dsnPipo = "mysql:host={$dbHost};dbname={$dbNamePipo};charset={$dbCharset}";
$dsnCine = "mysql:host={$dbHost};dbname={$dbNameCine};charset={$dbCharset}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 5,
];

$connectionError = null;

try {
    $pdoCineveo = new PDO($dsnCine, $dbUserCine, $dbPassCine, $options);
} catch (\PDOException $e) {
    $connectionError = 'Falha critica no banco central (Cineveo).';
}

if (!$connectionError) {
    try {
        try {
            $pdo = new PDO($dsnPipo, $dbUserPipoPrimary, $dbPassPipo, $options);
        } catch (\PDOException $e) {
            $pdo = new PDO($dsnPipo, $dbUserPipoFallback, $dbPassPipo, $options);
        }
    } catch (\PDOException $e) {
        $connectionError = 'Falha no banco Pipocine. Verifique se o DB, o usuario e a senha estao criados corretamente no aaPanel.';
    }
}

if ($connectionError) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $isApiCall = (
        strpos($requestUri, '/api/') === 0 ||
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
        (isset($_GET['fetchMode'])) ||
        (isset($_POST['ajaxAction']))
    );

    if ($isApiCall) {
        header('Content-Type: application/json');
        http_response_code(503);
        echo json_encode([
            'isAuthenticated' => false,
            'user' => null,
            'error' => $connectionError
        ]);
        exit;
    } else {
        http_response_code(503);
        die('<h2 style="font-family:sans-serif;color:#c00;text-align:center;margin-top:50px;">' . $connectionError . '</h2>');
    }
}

if (!defined('STREAM_PROXY_CONTEXT') && !$skipPublicSessionBootstrap) {
    if (!isset($_SESSION['userId']) && isset($_COOKIE['remember_me'])) {
        try {
            $cookieParts = explode(':', $_COOKIE['remember_me'], 2);
            $selector = $cookieParts[0] ?? null;
            $token = $cookieParts[1] ?? null;

            if ($selector && $token) {
                $stmtAuth = $pdoCineveo->prepare("SELECT * FROM auth_tokens WHERE selector = ? AND expires_at > NOW()");
                $stmtAuth->execute([$selector]);
                $authToken = $stmtAuth->fetch();

                if ($authToken && hash_equals($authToken['token_hash'], hash('sha256', $token))) {
                    $stmtUser = $pdoCineveo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmtUser->execute([$authToken['user_id']]);
                    $user = $stmtUser->fetch();

                    if ($user) {
                        $_SESSION['userId'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['fullName'] = $user['full_name'];
                        $_SESSION['profilePicUrl'] = $user['profile_pic_url'];
                    }
                }
            }
        } catch (Exception $e) {}
    }

    if (!isset($_SESSION['userId']) && isset($_COOKIE['cineveo_token'])) {
        try {
            $tokenHash = hash('sha256', $_COOKIE['cineveo_token']);
            $stmtSess = $pdoCineveo->prepare(
                "SELECT u.id, u.username, u.full_name, u.name, u.profile_pic_url
                 FROM user_sessions s
                 JOIN users u ON u.id = s.user_id
                 WHERE s.token_hash = ? AND s.expires_at > NOW()
                 LIMIT 1"
            );
            $stmtSess->execute([$tokenHash]);
            $sessionUser = $stmtSess->fetch();

            if ($sessionUser) {
                $_SESSION['userId'] = $sessionUser['id'];
                $_SESSION['username'] = $sessionUser['username'];
                $_SESSION['fullName'] = $sessionUser['full_name'] ?: $sessionUser['name'];
                $_SESSION['profilePicUrl'] = $sessionUser['profile_pic_url'];
            }
        } catch (Exception $e) {}
    }
}