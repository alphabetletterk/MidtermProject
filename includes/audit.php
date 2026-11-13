<?php
const AUDIT_KEEP_DAYS_FAILED = 30;
const AUDIT_KEEP_DAYS        = 180;

const AUDIT_KEEP_LATEST_CLEANUP =
    "(table_name <> 'audit_log' OR audit_id <> (SELECT max(audit_id) FROM audit_log WHERE table_name = 'audit_log'))";

const AUDIT_ACTIONS = [
    'INSERT'       => 'Added',
    'UPDATE'       => 'Edited',
    'DELETE'       => 'Deleted',
    'LOGIN'        => 'Signed in',
    'LOGOUT'       => 'Signed out',
    'LOGIN_FAILED' => 'Failed sign-in',
];

const AUDIT_MODULES = [
    'farmer'   => 'Farmers',
    'farm'     => 'Farms',
    'crop'     => 'Crops',
    'planting' => 'Planting',
    'harvest'  => 'Harvest',
    'users'    => 'Accounts',
    'auth'     => 'Sign-in',
    'audit_log' => 'Audit trail',
];

function audit_log(
    PDO $pdo,
    string $action,
    string $table,
    ?int $recordId = null,
    ?string $label = null,
    ?array $details = null,
    ?string $username = null
): void {
    try {
        $user = $_SESSION['user'] ?? [];
        $name = $username ?? ($user['username'] ?? 'system');
        $st = $pdo->prepare(
            'INSERT INTO audit_log (user_id, username, action, table_name, record_id, record_label, details)
             VALUES ((SELECT user_id FROM users WHERE user_id = ?), ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            isset($user['id']) ? (int) $user['id'] : null,
            mb_substr($name, 0, 50),
            $action,
            $table,
            $recordId,
            $label === null ? null : mb_substr($label, 0, 200),
            $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ]);
        if ($action === 'LOGIN') {
            audit_prune($pdo);
        }
    } catch (Throwable $e) {
        error_log('audit_log: ' . $e->getMessage());
    }
}

function audit_fetch(PDO $pdo, array $f, int $page, int $perPage): array
{
    $conds = [];
    $params = [];
    foreach (['user' => 'username', 'action' => 'action', 'module' => 'table_name'] as $key => $col) {
        if ($f[$key] !== '') {
            $conds[] = "$col = ?";
            $params[] = $f[$key];
        }
    }
    if ($f['from'] !== '') {
        $conds[] = "CAST(created_at AT TIME ZONE 'Asia/Manila' AS date) >= CAST(? AS date)";
        $params[] = $f['from'];
    }
    if ($f['to'] !== '') {
        $conds[] = "CAST(created_at AT TIME ZONE 'Asia/Manila' AS date) <= CAST(? AS date)";
        $params[] = $f['to'];
    }
    $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

    $st = $pdo->prepare("SELECT COUNT(*) FROM audit_log $where");
    $st->execute($params);
    $total = (int) $st->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($pages, max(1, $page));
    $offset = ($page - 1) * $perPage;

    $st = $pdo->prepare(
        "SELECT audit_id, username, action, table_name, record_id, record_label, details, created_at
         FROM audit_log $where
         ORDER BY created_at DESC, audit_id DESC
         LIMIT $perPage OFFSET $offset"
    );
    $st->execute($params);
    return [$st->fetchAll(), $total, $page, $pages];
}

function audit_filter_options(PDO $pdo): array
{
    return [
        'users'   => $pdo->query('SELECT DISTINCT username FROM audit_log ORDER BY username')->fetchAll(PDO::FETCH_COLUMN),
        'modules' => $pdo->query('SELECT DISTINCT table_name FROM audit_log ORDER BY table_name')->fetchAll(PDO::FETCH_COLUMN),
    ];
}

function audit_actions_for(string $module): array
{
    return match ($module) {
        ''          => array_keys(AUDIT_ACTIONS),
        'auth'      => ['LOGIN', 'LOGOUT', 'LOGIN_FAILED'],
        'audit_log' => ['DELETE'],
        default     => ['INSERT', 'UPDATE', 'DELETE'],
    };
}

function audit_prune(PDO $pdo): int
{
    try {
        $st = $pdo->prepare(
            "DELETE FROM audit_log
             WHERE " . AUDIT_KEEP_LATEST_CLEANUP . "
               AND (created_at < now() - CAST(? AS integer) * INTERVAL '1 day'
                    OR (action = 'LOGIN_FAILED'
                        AND created_at < now() - CAST(? AS integer) * INTERVAL '1 day'))"
        );
        $st->execute([AUDIT_KEEP_DAYS, AUDIT_KEEP_DAYS_FAILED]);
        return $st->rowCount();
    } catch (Throwable $e) {
        error_log('audit_prune: ' . $e->getMessage());
        return 0;
    }
}

function audit_clear(PDO $pdo, array $types, string $module = '', int $olderThanDays = 0): int
{
    if ($module !== '' && !isset(AUDIT_MODULES[$module])) {
        throw new InvalidArgumentException('Choose a valid module.');
    }
    $types = array_values(array_intersect(
        array_unique(array_filter($types, fn($t) => is_string($t) && isset(AUDIT_ACTIONS[$t]))),
        audit_actions_for($module)
    ));
    if (!$types) {
        throw new InvalidArgumentException('Choose at least one entry type to remove.');
    }
    if (!in_array($olderThanDays, [0, 30, 90, 180], true)) {
        throw new InvalidArgumentException('Choose a valid age.');
    }

    $conds = ['action IN (' . implode(', ', array_fill(0, count($types), '?')) . ')', AUDIT_KEEP_LATEST_CLEANUP];
    $params = $types;
    if ($module !== '') {
        $conds[] = 'table_name = ?';
        $params[] = $module;
    }
    if ($olderThanDays > 0) {
        $conds[] = "created_at < now() - CAST(? AS integer) * INTERVAL '1 day'";
        $params[] = $olderThanDays;
    }

    $st = $pdo->prepare('DELETE FROM audit_log WHERE ' . implode(' AND ', $conds));
    $st->execute($params);
    $removed = $st->rowCount();

    if ($removed > 0) {
        audit_log($pdo, 'DELETE', 'audit_log', null, "Cleared $removed " . ($removed === 1 ? 'entry' : 'entries'), [
            ['Entry types', implode(', ', array_map(fn($t) => AUDIT_ACTIONS[$t], $types)), ''],
            ['Module', $module === '' ? 'All modules' : AUDIT_MODULES[$module], ''],
            ['Age', $olderThanDays > 0 ? "Older than $olderThanDays days" : 'Any age', ''],
        ]);
    }
    return $removed;
}