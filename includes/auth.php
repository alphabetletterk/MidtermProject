<?php
require_once __DIR__ . '/../config/bootstrap.php';
session_start();
date_default_timezone_set('Asia/Manila');

const SESSION_IDLE_SECONDS = 30 * 60;

$sessionUser = $_SESSION['user'] ?? null;
$sessionValid = is_array($sessionUser)
    && isset($sessionUser['id'], $sessionUser['username'], $sessionUser['role'])
    && is_string($sessionUser['username']) && $sessionUser['username'] !== ''
    && in_array($sessionUser['role'], ['Administrator', 'Staff'], true)
    && (time() - (int) ($_SESSION['last_seen'] ?? time())) <= SESSION_IDLE_SECONDS;

if (!$sessionValid) {
    $_SESSION = [];
    header('Location: ' . ($basePath ?? '') . 'index.php');
    exit;
}

$_SESSION['last_seen'] = time();
$currentUser = $_SESSION['user'];

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function require_admin(): void
{
    global $currentUser, $basePath;
    if (($currentUser['role'] ?? '') === 'Administrator') {
        return;
    }
    http_response_code(403);
    $pageTitle = 'Access denied';
    $activePage = '';
    $type = 'danger';
    $message = 'Only an administrator can open this page.';
    require __DIR__ . '/header.php';
    require __DIR__ . '/views/alerts.php';
    require __DIR__ . '/footer.php';
    exit;
}