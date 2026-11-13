<?php

require_once __DIR__ . '/audit.php';

function crud_fmt($v, ?string $fmt = null): string
{
    if ($v === null || $v === '') {
        return '';
    }
    return match ($fmt) {
        'date'  => date('M j, Y', strtotime($v)),
        'datetime' => date('M j, Y g:i A', strtotime($v)),
        'num'   => number_format((float) $v, 2),
        'money' => '₱' . number_format((float) $v, 2),
        'int'   => number_format((int) $v),
        default => (string) $v,
    };
}

function crud_url(array $p): string
{
    return '?' . http_build_query(array_filter($p, fn($v) => $v !== '' && $v !== null));
}

function crud_options(PDO $pdo, string $sql): array
{
    $o = [];
    foreach ($pdo->query($sql)->fetchAll() as $r) {
        $o[(string) $r['id']] = $r['label'];
    }
    return $o;
}

function crud_flash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function crud_row(PDO $pdo, string $table, string $pk, int $id): array
{
    $st = $pdo->prepare("SELECT * FROM $table WHERE $pk = ?");
    $st->execute([$id]);
    return $st->fetch() ?: [];
}

function crud_label(PDO $pdo, array $cfg, int $id): ?string
{
    try {
        $st = $pdo->prepare("SELECT {$cfg['select']} FROM {$cfg['from']} WHERE " . ($cfg['pk_sql'] ?? $cfg['pk']) . ' = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ? (string) $row[$cfg['label_col']] : null;
    } catch (Throwable $e) {
        return null;
    }
}

function crud_display(array $f, $v): string
{
    if ($v === null || $v === '') {
        return '';
    }
    if ($f['type'] === 'select') {
        return (string) ($f['opts'][(string) $v] ?? $v);
    }
    if ($f['type'] === 'date') {
        return date('M j, Y', strtotime($v));
    }
    return (string) $v;
}

function crud_changes(array $fields, array $old, array $new): array
{
    $out = [];
    foreach ($fields as $f) {
        if (!empty($f['skip_audit'])) {
            continue;
        }
        $n = $f['name'];
        $o = $old[$n] ?? '';
        $v = $new[$n] ?? '';
        if (!empty($f['secret'])) {
            if ($v !== '') {
                $out[] = [$f['label'], '', $old ? '(changed)' : '(set)'];
            }
            continue;
        }
        $same = ($f['type'] === 'number' && is_numeric($o) && is_numeric($v))
            ? ((float) $o === (float) $v)
            : ((string) $o === (string) $v);
        if (!$same) {
            $out[] = [$f['label'], crud_display($f, $o), crud_display($f, $v)];
        }
    }
    return $out;
}

function crud_validate(array $fields, array $d, array $cfg, PDO $pdo, int $id = 0): array
{
    $err = [];
    foreach ($fields as $f) {
        $n = $f['name'];
        $v = $d[$n];
        $lb = $f['label'];
        if ($v === '') {
            if (!empty($f['required'])) {
                $err[$n] = "$lb is required.";
            }
            continue;
        }
        switch ($f['type']) {
            case 'select':
                if (!isset($f['opts'][$v])) {
                    $err[$n] = "Choose a valid option for $lb.";
                }
                break;
            case 'number':
                if (!is_numeric($v)) {
                    $err[$n] = "$lb must be a number.";
                } elseif (isset($f['min']) && $v < $f['min']) {
                    $err[$n] = "$lb must be at least {$f['min']}.";
                } elseif (isset($f['maxv']) && $v > $f['maxv']) {
                    $err[$n] = "$lb must be at most {$f['maxv']}.";
                } elseif (($f['step'] ?? '') === '1' && floor((float) $v) != (float) $v) {
                    $err[$n] = "$lb must be a whole number.";
                }
                break;
            case 'date':
                $dt = DateTime::createFromFormat('Y-m-d', $v);
                if (!$dt || $dt->format('Y-m-d') !== $v) {
                    $err[$n] = "$lb must be a valid date.";
                }
                break;
            case 'tel':
                if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $v)) {
                    $err[$n] = "$lb must be a valid phone number.";
                }
                break;
        }
        if (!isset($err[$n]) && isset($f['max']) && mb_strlen($v) > $f['max']) {
            $err[$n] = "$lb must be at most {$f['max']} characters.";
        }
    }
    if (!$err && isset($cfg['validate'])) {
        $err = $cfg['validate']($d, $pdo, $id);
    }
    return $err;
}

function crud_is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

function crud_fragment(string $view, array $vars, int $status = 200): void
{
    extract($vars, EXTR_SKIP);
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    header('Vary: X-Requested-With');
    require __DIR__ . '/views/' . $view . '.php';
}

function crud_notice(bool $modal, array $base, string $type, string $message, int $status = 200): void
{
    if ($modal) {
        http_response_code($status);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store');
        header('Vary: X-Requested-With');
        echo $message;
        return;
    }
    crud_view(null, $base + ['type' => $type, 'message' => $message]);
}

function crud_view(?string $view, array $vars): void
{
    extract($vars, EXTR_SKIP);
    require __DIR__ . '/header.php';
    require __DIR__ . '/views/alerts.php';
    if ($view !== null) {
        require __DIR__ . '/views/' . $view . '.php';
    }
    require __DIR__ . '/footer.php';
}

function crud_run(array $cfg): void
{
    $basePath = $GLOBALS['basePath'];
    $currentUser = $GLOBALS['currentUser'];
    $isAdmin = $currentUser['role'] === 'Administrator';
    $table = $cfg['table'];
    $pk = $cfg['pk'];
    $one = $cfg['singular'];
    $self = basename($_SERVER['SCRIPT_NAME']);
    $pageTitle = $cfg['title'];
    $activePage = $cfg['key'];
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    $csrf = $_SESSION['csrf'];
    $modal = crud_is_ajax();

    $base = compact('cfg', 'basePath', 'currentUser', 'isAdmin', 'pageTitle', 'activePage', 'one', 'pk', 'self', 'csrf', 'modal');

    try {
        $pdo = getConnection();
        $fields = $cfg['fields'];
        foreach ($fields as &$f) {
            if ($f['type'] === 'select') {
                $f['opts'] = crud_options($pdo, $f['options']);
            }
        }
        unset($f);
    } catch (Throwable $e) {
        error_log($e->getMessage());
        crud_notice($modal, $base, 'danger', 'Could not connect to the database. Check your .env settings.', 500);
        return;
    }

    $errors = [];
    $action = $_GET['action'] ?? 'list';
    $id = (int) ($_GET['id'] ?? 0);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
            crud_flash('danger', 'Your session expired. Please try again.');
            if ($modal) {
                http_response_code(403);
                exit;
            }
            header('Location: ' . $self);
            exit;
        }
        $op = $_POST['op'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);

        if ($op === 'delete') {
            if (!$isAdmin) {
                crud_flash('danger', 'Only an administrator can delete records.');
            } else {
                $blocked = isset($cfg['before_delete']) ? $cfg['before_delete']($id, $pdo) : null;
                if ($blocked) {
                    crud_flash('danger', $blocked);
                } else {
                    try {
                        $label = crud_label($pdo, $cfg, $id);
                        $old = crud_row($pdo, $table, $pk, $id);
                        $st = $pdo->prepare("DELETE FROM $table WHERE $pk = ?");
                        $st->execute([$id]);
                        if ($st->rowCount() > 0) {
                            audit_log($pdo, 'DELETE', $table, $id, $label, crud_changes($fields, $old, []));
                            crud_flash('success', ucfirst($one) . ' deleted.');
                        } else {
                            crud_flash('warning', 'That record was not found.');
                        }
                    } catch (PDOException $e) {
                        crud_flash('danger', $e->getCode() === '23503'
                            ? "Cannot delete this $one because other records use it."
                            : "The $one could not be deleted.");
                    }
                }
            }
            header('Location: ' . $self);
            exit;
        }

        if ($op === 'save') {
            $formData = [];
            foreach ($fields as $f) {
                $v = $_POST[$f['name']] ?? '';
                $formData[$f['name']] = is_string($v) ? ($f['type'] === 'password' ? $v : trim($v)) : '';
            }
            $errors = crud_validate($fields, $formData, $cfg, $pdo, $id);
            if (!$errors) {
                $data = isset($cfg['prepare']) ? $cfg['prepare']($formData, $id) : $formData;
                $vals = array_map(fn($v) => $v === '' ? null : $v, array_values($data));
                $names = array_keys($data);
                try {
                    if ($id) {
                        $old = crud_row($pdo, $table, $pk, $id);
                        $set = implode(', ', array_map(fn($n) => "$n = ?", $names));
                        $pdo->prepare("UPDATE $table SET $set WHERE $pk = ?")->execute([...$vals, $id]);
                        $changes = crud_changes($fields, $old, $formData);
                        if ($changes) {
                            audit_log($pdo, 'UPDATE', $table, $id, crud_label($pdo, $cfg, $id), $changes);
                        }
                        crud_flash('success', ucfirst($one) . ' updated.');
                    } else {
                        $marks = implode(', ', array_fill(0, count($names), '?'));
                        $st = $pdo->prepare("INSERT INTO $table (" . implode(', ', $names) . ") VALUES ($marks) RETURNING $pk");
                        $st->execute($vals);
                        $newId = (int) $st->fetchColumn();
                        audit_log($pdo, 'INSERT', $table, $newId, crud_label($pdo, $cfg, $newId), crud_changes($fields, [], $formData));
                        crud_flash('success', ucfirst($one) . ' added.');
                    }
                    if ($modal) {
                        header('Content-Type: application/json');
                        echo '{"ok":true}';
                        exit;
                    }
                    header('Location: ' . $self);
                    exit;
                } catch (PDOException $e) {
                    error_log($e->getMessage());
                    $errors['_form'] = match ($e->getCode()) {
                        '23505' => "This $one already exists.",
                        '23514', '22003' => 'One of the values is outside the allowed range.',
                        '23503' => 'A selected option no longer exists.',
                        default => "The $one could not be saved.",
                    };
                }
            }
            $action = $id ? 'edit' : 'add';
        }
    }

    if (!$modal) {
        $base['flash'] = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
    }

    if ($action === 'add' || $action === 'edit') {
        if (!isset($formData)) {
            $formData = [];
            if ($action === 'edit') {
                $st = $pdo->prepare("SELECT * FROM $table WHERE $pk = ?");
                $st->execute([$id]);
                $formData = $st->fetch() ?: [];
                if (!$formData) {
                    crud_notice($modal, $base, 'warning', 'That record was not found.', 404);
                    return;
                }
            } else {
                foreach ($fields as $f) {
                    $formData[$f['name']] = ($f['default'] ?? '') === 'today' ? date('Y-m-d') : '';
                }
            }
        }
        $vars = $base + compact('action', 'id', 'fields', 'formData', 'errors');
        if ($modal) {
            crud_fragment('form', $vars, $errors ? 422 : 200);
        } else {
            crud_view('form', $vars);
        }
        return;
    }

    if ($action === 'view') {
        try {
            $st = $pdo->prepare("SELECT {$cfg['select']} FROM {$cfg['from']} WHERE " . ($cfg['pk_sql'] ?? $pk) . ' = ?');
            $st->execute([$id]);
            $record = $st->fetch() ?: [];
            $stamps = $record ? crud_row($pdo, $table, $pk, $id) : [];
        } catch (Throwable $e) {
            error_log($e->getMessage());
            crud_notice($modal, $base, 'danger', 'The record could not be loaded.', 500);
            return;
        }
        if (!$record) {
            crud_notice($modal, $base, 'warning', 'That record was not found.', 404);
            return;
        }
        $cols = $cfg['columns'];
        $vars = $base + compact('id', 'record', 'stamps', 'cols');
        if ($modal) {
            crud_fragment('view', $vars);
        } else {
            crud_view('view', $vars);
        }
        return;
    }

    $q = $_GET['q'] ?? '';
    $q = is_string($q) ? trim($q) : '';
    $conds = [];
    $params = [];
    if ($q !== '') {
        $parts = [];
        foreach ($cfg['search'] ?? [] as $c) {
            $parts[] = "$c ILIKE ?";
            $params[] = "%$q%";
        }
        $num = str_replace([',', ' ', '₱'], '', $q);
        if (!empty($cfg['search_numeric']) && preg_match('/^\d{1,12}(\.\d{1,4})?$/', $num)) {
            foreach ($cfg['search_numeric'] as $c) {
                $parts[] = "CAST($c AS numeric) = CAST(? AS numeric)";
                $params[] = $num;
            }
        }
        if ($parts) {
            $conds[] = '(' . implode(' OR ', $parts) . ')';
        }
    }
    $keep = ['q' => $q];
    $fopts = [];
    $loadError = false;
    $rows = [];
    $total = 0;
    $cols = $cfg['columns'];
    $sort = in_array($_GET['sort'] ?? '', array_keys($cols), true) ? $_GET['sort'] : ($cfg['sort'] ?? array_key_first($cols));
    $dir = ($_GET['dir'] ?? ($cfg['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
    $perPage = 10;
    $page = 1;
    $pages = 1;

    try {
        foreach ($cfg['filters'] ?? [] as $k => $flt) {
            $fopts[$k] = crud_options($pdo, $flt['options']);
            $val = $_GET[$k] ?? '';
            $val = is_string($val) ? $val : '';
            $keep[$k] = $val;
            $isText = !empty($flt['text']);
            if ($val !== '' && ($isText ? isset($fopts[$k][$val]) : ctype_digit($val))) {
                $conds[] = $flt['where'];
                $params[] = $isText ? $val : (int) $val;
            }
        }
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $st = $pdo->prepare("SELECT COUNT(*) FROM {$cfg['from']} $where");
        $st->execute($params);
        $total = (int) $st->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
        $offset = ($page - 1) * $perPage;
        $order = $cols[$sort][1] . ' ' . strtoupper($dir) . ', ' . ($cfg['pk_sql'] ?? $pk);
        $st = $pdo->prepare("SELECT {$cfg['select']} FROM {$cfg['from']} $where ORDER BY $order LIMIT $perPage OFFSET $offset");
        $st->execute($params);
        $rows = $st->fetchAll();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $loadError = true;
    }

    crud_view('list', $base + compact('q', 'keep', 'fopts', 'cols', 'sort', 'dir', 'rows', 'total', 'page', 'pages', 'perPage', 'loadError'));
}