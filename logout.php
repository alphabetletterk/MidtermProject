<?php
session_start();

if (isset($_SESSION['user'])) {
    try {
        require __DIR__ . '/config/database.php';
        require_once __DIR__ . '/includes/audit.php';
        $u = $_SESSION['user'];
        audit_log(
            getConnection(), 'LOGOUT', 'auth',
            isset($u['id']) ? (int) $u['id'] : null,
            $u['username'] ?? null
        );
    } catch (Throwable $e) {
        error_log('logout: ' . $e->getMessage());
    }
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: index.php');
exit;