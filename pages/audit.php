<?php
$basePath = '../';
require __DIR__ . '/../includes/auth.php';
require_admin();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/crud.php';

$self = basename($_SERVER['SCRIPT_NAME']);
$pageTitle = 'Audit trail';
$activePage = 'audit';
$perPage = 20;

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

$base = compact('basePath', 'currentUser', 'pageTitle', 'activePage', 'self', 'csrf');

try {
    $pdo = getConnection();
} catch (Throwable $e) {
    error_log($e->getMessage());
    crud_view(null, $base + ['type' => 'danger', 'message' => 'Could not connect to the database. Check your .env settings.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        crud_flash('danger', 'Your session expired. Please try again.');
    } elseif (($_POST['op'] ?? '') === 'clear') {
        $types = is_array($_POST['types'] ?? null) ? $_POST['types'] : [];
        $module = is_string($_POST['module'] ?? null) ? $_POST['module'] : '';
        $age = (int) ($_POST['age'] ?? 0);
        try {
            $removed = audit_clear($pdo, $types, $module, $age);
            crud_flash('success', $removed > 0
                ? "Removed $removed audit " . ($removed === 1 ? 'entry.' : 'entries.')
                : 'No entries matched, so nothing was removed.');
        } catch (InvalidArgumentException $e) {
            crud_flash('danger', $e->getMessage());
        } catch (Throwable $e) {
            error_log('audit_clear: ' . $e->getMessage());
            crud_flash('danger', 'The audit trail could not be cleared.');
        }
    }
    header('Location: ' . $self);
    exit;
}

$base['flash'] = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$filters = [];
foreach (['user', 'action', 'module', 'from', 'to'] as $k) {
    $v = $_GET[$k] ?? '';
    $filters[$k] = is_string($v) ? trim($v) : '';
}
if (!isset(AUDIT_ACTIONS[$filters['action']])) {
    $filters['action'] = '';
}
foreach (['from', 'to'] as $k) {
    $d = DateTime::createFromFormat('Y-m-d', $filters[$k]);
    if (!$d || $d->format('Y-m-d') !== $filters[$k]) {
        $filters[$k] = '';
    }
}

$loadError = false;
$rows = [];
$total = 0;
$pages = 1;
$page = max(1, (int) ($_GET['page'] ?? 1));
$opts = ['users' => [], 'modules' => []];
try {
    $opts = audit_filter_options($pdo);
    [$rows, $total, $page, $pages] = audit_fetch($pdo, $filters, $page, $perPage);
} catch (Throwable $e) {
    error_log('audit page: ' . $e->getMessage());
    $loadError = true;
}

crud_view('audit', $base + compact('filters', 'opts', 'rows', 'total', 'page', 'pages', 'perPage', 'loadError'));