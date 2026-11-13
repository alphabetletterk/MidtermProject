<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/audit.php';

function fail(string $message): void
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function ask(string $question, string $default = ''): string
{
    echo $question . ($default !== '' ? " [$default]" : '') . ': ';
    $line = fgets(STDIN);
    if ($line === false) {
        fail("\nNo input available. Run this in an interactive terminal: php install.php");
    }
    $line = trim($line);
    return $line === '' ? $default : $line;
}

$schemaFile = __DIR__ . '/database/schema.sql';
$sampleFile = __DIR__ . '/database/sample_data.sql';
if (!is_readable($schemaFile)) {
    fail('Missing file: database/schema.sql');
}

try {
    $pdo = getConnection();
    $existing = (int) $pdo->query(
        "SELECT count(*) FROM information_schema.tables
         WHERE table_schema = 'public' AND table_name IN ('farmer', 'farm', 'crop', 'planting', 'harvest', 'users')"
    )->fetchColumn();
} catch (Throwable $e) {
    fail('Could not connect to the database. Check the DB_* values in your .env file.' . "\n" . $e->getMessage());
}
if ($existing > 0) {
    fail("This database already has tables, so install.php stopped without changing anything.\n"
       . "It only sets up an EMPTY database. To add an account later, run:\n"
       . '  php create_user.php <username> "<password>" <Administrator|Staff>');
}

echo "Agriculture Information System: setup\n\n";

$loadSample = in_array(strtolower(ask('Load sample data (12 farmers, 12 farms, 11 crops, 15 plantings, 11 harvests)? y/n', 'n')), ['y', 'yes'], true);
if ($loadSample && !is_readable($sampleFile)) {
    fail('Missing file: database/sample_data.sql');
}

echo "\nCreate accounts. The first one is an Administrator. Typing is visible here, so clear the screen afterwards (cls).\n";
$accounts = [];
$taken = [];
while (true) {
    $first = $accounts === [];
    $username = ask($first ? 'Administrator username' : 'Another username (press Enter to finish)', $first ? 'admin' : '');
    if ($username === '') {
        break;
    }
    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        echo "  Use 3 to 50 letters, numbers, dots, dashes or underscores.\n";
        continue;
    }
    if (isset($taken[strtolower($username)])) {
        echo "  You already entered that username.\n";
        continue;
    }
    $password = ask('  Password (at least 8 characters)');
    if (mb_strlen($password) < 8) {
        echo "  That password is too short.\n";
        continue;
    }
    $role = 'Administrator';
    if (!$first) {
        $role = ucfirst(strtolower(ask('  Role (Administrator or Staff)', 'Staff')));
        if (!in_array($role, ['Administrator', 'Staff'], true)) {
            echo "  The role must be Administrator or Staff.\n";
            continue;
        }
    }
    $accounts[] = [$username, $password, $role];
    $taken[strtolower($username)] = true;
}

echo "\nCreating tables and lookup values...\n";
try {
    $pdo->exec(file_get_contents($schemaFile));
} catch (Throwable $e) {
    fail('schema.sql failed, so nothing was created: ' . $e->getMessage());
}

if ($loadSample) {
    echo "Loading sample data...\n";
    try {
        $pdo->exec(file_get_contents($sampleFile));
    } catch (Throwable $e) {
        fail('Tables were created, but the sample data failed: ' . $e->getMessage()
           . "\nYou can retry it by running database/sample_data.sql in the Supabase SQL editor or pgAdmin.");
    }
}

foreach ($accounts as [$username, $password, $role]) {
    try {
        $st = $pdo->prepare('SELECT role_id FROM role WHERE role_name = ?');
        $st->execute([$role]);
        $roleId = (int) $st->fetchColumn();

        $st = $pdo->prepare('INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?) RETURNING user_id');
        $st->execute([$username, password_hash($password, PASSWORD_DEFAULT), $roleId]);
        $newId = (int) $st->fetchColumn();

        audit_log($pdo, 'INSERT', 'users', $newId, $username, [
            ['Username', '', $username],
            ['Role', '', $role],
            ['Password', '', '(set)'],
        ], 'system');
        echo "Created $role account '$username'.\n";
    } catch (Throwable $e) {
        echo "Could not create '$username': " . $e->getMessage() . "\n";
    }
}

echo "\nDone. Start the app with:  php -S localhost:8000\n";
if (!$accounts) {
    echo "No account was created. Create one with:  php create_user.php admin \"YourPassword\" Administrator\n";
}
