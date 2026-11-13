<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/audit.php';

[$script, $username, $password, $roleName] = $argv + [null, null, null, null];
if (!$username || !$password || !$roleName) {
    fwrite(STDERR, "Usage: php create_user.php <username> <password> <Administrator|Staff>\n");
    exit(1);
}
if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
    fwrite(STDERR, "Username must be 3 to 50 letters, numbers, dots, dashes or underscores.\n");
    exit(1);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}

try {
    $pdo = getConnection();

    $st = $pdo->prepare('SELECT role_id FROM role WHERE role_name = ?');
    $st->execute([$roleName]);
    $roleId = $st->fetchColumn();
    if ($roleId === false) {
        fwrite(STDERR, "Role must be exactly Administrator or Staff.\n");
        exit(1);
    }

    $st = $pdo->prepare('SELECT 1 FROM users WHERE lower(username) = lower(?)');
    $st->execute([$username]);
    if ($st->fetchColumn()) {
        fwrite(STDERR, "The username '$username' already exists.\n");
        exit(1);
    }

    $st = $pdo->prepare('INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?) RETURNING user_id');
    $st->execute([$username, password_hash($password, PASSWORD_DEFAULT), (int) $roleId]);
    $newId = (int) $st->fetchColumn();

    audit_log($pdo, 'INSERT', 'users', $newId, $username, [
        ['Username', '', $username],
        ['Role', '', $roleName],
        ['Password', '', '(set)'],
    ], 'system');

    echo "Created $roleName account '$username' (id $newId).\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Could not create the account: ' . $e->getMessage() . "\n");
    exit(1);
}