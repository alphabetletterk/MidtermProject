<?php
$basePath = '../';
require __DIR__ . '/../includes/auth.php';
require_admin();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/crud.php';

function accounts_admin_count(PDO $pdo): int
{
    return (int) $pdo->query(
        "SELECT COUNT(*) FROM users u JOIN role r ON r.role_id = u.role_id WHERE r.role_name = 'Administrator'"
    )->fetchColumn();
}

function accounts_role_of(PDO $pdo, int $userId): ?string
{
    $st = $pdo->prepare('SELECT r.role_name FROM users u JOIN role r ON r.role_id = u.role_id WHERE u.user_id = ?');
    $st->execute([$userId]);
    $name = $st->fetchColumn();
    return $name === false ? null : $name;
}

crud_run([
    'key'       => 'users',
    'title'     => 'Accounts',
    'singular'  => 'account',
    'table'     => 'users',
    'pk'        => 'user_id',
    'pk_sql'    => 'u.user_id',
    'label_col' => 'username',
    'select'    => 'u.user_id, u.username, r.role_name, u.created_at, u.updated_at',
    'from'      => 'users u JOIN role r ON r.role_id = u.role_id',
    'columns'   => [
        'username'   => ['Username', 'u.username'],
        'role_name'  => ['Role', 'r.role_name'],
        'created_at' => ['Created', 'u.created_at', 'datetime'],
        'updated_at' => ['Last updated', 'u.updated_at', 'datetime'],
    ],
    'search'    => ['u.username', 'r.role_name'],
    'filters'   => [
        'role' => [
            'label'   => 'Role',
            'where'   => 'u.role_id = ?',
            'options' => 'SELECT role_id AS id, role_name AS label FROM role ORDER BY role_id',
        ],
    ],
    'fields'    => [
        ['name' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true, 'max' => 50],
        ['name' => 'role_id',  'label' => 'Role',     'type' => 'select', 'required' => true,
         'options' => 'SELECT role_id AS id, role_name AS label FROM role ORDER BY role_id'],
        ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'max' => 72, 'secret' => true,
         'hint' => 'At least 8 characters. When editing, leave blank to keep the current password.'],
        ['name' => 'password_confirm', 'label' => 'Confirm password', 'type' => 'password', 'max' => 72, 'skip_audit' => true],
    ],

    'prepare'   => function (array $d, int $id): array {
        $row = ['username' => $d['username'], 'role_id' => $d['role_id']];
        if ($d['password'] !== '') {
            $row['password_hash'] = password_hash($d['password'], PASSWORD_DEFAULT);
        }
        return $row;
    },

    'validate'  => function (array $d, PDO $pdo, int $id): array {
        $err = [];

        if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $d['username'])) {
            $err['username'] = 'Use 3 to 50 letters, numbers, dots, dashes or underscores.';
        } else {
            $st = $pdo->prepare('SELECT 1 FROM users WHERE lower(username) = lower(?) AND user_id <> ?');
            $st->execute([$d['username'], $id]);
            if ($st->fetchColumn()) {
                $err['username'] = 'That username is already taken.';
            }
        }

        $pw = $d['password'];
        if ($id === 0 && $pw === '') {
            $err['password'] = 'A password is required for a new account.';
        } elseif ($pw !== '') {
            if (strlen($pw) < 8) {
                $err['password'] = 'Password must be at least 8 characters.';
            } elseif ($pw !== $d['password_confirm']) {
                $err['password_confirm'] = 'The passwords do not match.';
            }
        }

        if ($id > 0) {
            $st = $pdo->prepare('SELECT role_name FROM role WHERE role_id = ?');
            $st->execute([(int) $d['role_id']]);
            $newRole = $st->fetchColumn();
            if (accounts_role_of($pdo, $id) === 'Administrator' && $newRole !== 'Administrator'
                && accounts_admin_count($pdo) <= 1) {
                $err['role_id'] = 'There must be at least one administrator.';
            }
        }
        return $err;
    },

    'before_delete' => function (int $id, PDO $pdo): ?string {
        if ($id === (int) ($_SESSION['user']['id'] ?? 0)) {
            return 'You cannot delete your own account.';
        }
        if (accounts_role_of($pdo, $id) === 'Administrator' && accounts_admin_count($pdo) <= 1) {
            return 'You cannot delete the last administrator.';
        }
        return null;
    },
]);