<?php
session_start();

require __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/audit.php';

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = 'Your session expired. Reload the page and try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Enter your username and password.';
    } else {
        try {
            $pdo = getConnection();
            $st = $pdo->prepare(
                'SELECT u.user_id, u.username, u.password_hash, r.role_name
                 FROM users u JOIN role r ON r.role_id = u.role_id
                 WHERE u.username = ?'
            );
            $st->execute([$username]);
            $account = $st->fetch();

            $hash = $account['password_hash'] ?? password_hash('not-a-real-password', PASSWORD_DEFAULT);
            $ok = password_verify($password, $hash) && $account;

            if ($ok) {
                if (password_needs_rehash($account['password_hash'], PASSWORD_DEFAULT)) {
                    $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
                        ->execute([password_hash($password, PASSWORD_DEFAULT), $account['user_id']]);
                }
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'       => (int) $account['user_id'],
                    'username' => $account['username'],
                    'role'     => $account['role_name'],
                ];
                audit_log($pdo, 'LOGIN', 'auth', (int) $account['user_id'], $account['username']);
                header('Location: dashboard.php');
                exit;
            }

            if ($account) {
                audit_log($pdo, 'LOGIN_FAILED', 'auth', (int) $account['user_id'], $account['username'], null, $account['username']);
            }            
            $error = 'Incorrect username or password.';
        } catch (Throwable $e) {
            error_log('login: ' . $e->getMessage());
            $error = 'Could not sign you in right now. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-bg">
<main class="login-frame">

  <section class="login-card">
    <p class="kicker">Agriculture Information System</p>
    <h1 class="h3 mb-1">Login</h1>
    <br>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger py-2 small" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="index.php" novalidate>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username"
               placeholder="Enter your username" value="<?= htmlspecialchars($username) ?>"
               autocomplete="username" required autofocus>
      </div>

      <div class="mb-4">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password"
               placeholder="Enter your password" autocomplete="current-password" required>
      </div>

      <button type="submit" class="btn btn-field w-100 py-2">Login</button>
    </form>

  </section>

  <section class="login-art d-none d-lg-flex" aria-hidden="true">
    <svg viewBox="0 0 440 340" xmlns="http://www.w3.org/2000/svg">
      <ellipse cx="225" cy="318" rx="190" ry="12" fill="#e3efe8"/>

      <g stroke="#8a9a5b" stroke-width="3" fill="none" stroke-linecap="round">
        <path d="M40 318 C40 260 36 220 44 170"/>
        <path d="M70 318 C70 270 74 235 66 195"/>
      </g>
      <g fill="#e3c565">
        <ellipse cx="44" cy="160" rx="7" ry="14"/><ellipse cx="36" cy="178" rx="6" ry="12" transform="rotate(-20 36 178)"/><ellipse cx="52" cy="178" rx="6" ry="12" transform="rotate(20 52 178)"/>
        <ellipse cx="66" cy="185" rx="7" ry="14"/><ellipse cx="58" cy="203" rx="6" ry="12" transform="rotate(-20 58 203)"/><ellipse cx="74" cy="203" rx="6" ry="12" transform="rotate(20 74 203)"/>
      </g>

      <rect x="110" y="236" width="270" height="10" rx="4" fill="#5fae85"/>
      <path d="M130 246 L118 318" stroke="#5fae85" stroke-width="8" stroke-linecap="round"/>
      <path d="M360 246 L372 318" stroke="#5fae85" stroke-width="8" stroke-linecap="round"/>

      <rect x="150" y="84" width="186" height="130" rx="10" fill="#cfe9da"/>
      <rect x="160" y="94" width="166" height="110" rx="6" fill="#f3faf6"/>
      <rect x="208" y="104" width="70" height="90" rx="6" fill="#fff" stroke="#d6e6dc"/>
      <circle cx="243" cy="128" r="13" fill="#1f7f4b"/>
      <circle cx="243" cy="124" r="4" fill="#fff"/><path d="M235 135 Q243 128 251 135" fill="#fff"/>
      <rect x="222" y="152" width="42" height="5" rx="2.5" fill="#d6e6dc"/>
      <rect x="226" y="164" width="34" height="5" rx="2.5" fill="#d6e6dc"/>
      <rect x="228" y="178" width="30" height="8" rx="4" fill="#1f7f4b"/>
      <rect x="232" y="214" width="22" height="22" fill="#a9d6bf"/>

      <rect x="338" y="196" width="12" height="40" rx="2" fill="#f2c94c"/>
      <rect x="352" y="190" width="12" height="46" rx="2" fill="#56b87f"/>
      <rect x="366" y="196" width="12" height="40" rx="2" fill="#2f9aa0"/>

      <g>
        <circle cx="168" cy="58" r="14" fill="#f2994a"/><rect x="162" y="58" width="12" height="9" rx="2" fill="#fff"/><path d="M164.5 58 V54.5 a3.5 3.5 0 0 1 7 0 V58" fill="none" stroke="#fff" stroke-width="2"/>
        <circle cx="214" cy="44" r="14" fill="#f4a6b8"/><rect x="207" y="39" width="14" height="10" rx="2" fill="#fff"/><path d="M207 40 L214 46 L221 40" fill="none" stroke="#f4a6b8" stroke-width="1.5"/>
        <circle cx="262" cy="32" r="11" fill="#6fcf3a"/><path d="M257 32 L261 36 L268 28" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="300" cy="56" r="13" fill="#2d9cdb"/><circle cx="300" cy="52" r="4" fill="#fff"/><path d="M292 63 Q300 54 308 63" fill="#fff"/>
      </g>

      <path d="M404 290 C392 262 394 236 408 214 C420 238 418 266 404 290Z" fill="#2f8f5b"/>
      <path d="M404 290 C384 276 376 258 380 240 C398 250 406 270 404 290Z" fill="#7cc79b"/>
      <path d="M404 290 C424 280 432 262 428 246 C412 254 402 270 404 290Z" fill="#1f6b43"/>
      <path d="M384 288 H424 L419 326 H389 Z" fill="#7a4e3a"/>

      <rect x="150" y="286" width="86" height="34" rx="3" fill="#c98f52"/>
      <path d="M150 297 H236 M150 309 H236" stroke="#a97238" stroke-width="2"/>
      <circle cx="172" cy="283" r="9" fill="#e0533d"/><circle cx="192" cy="281" r="9" fill="#f2994a"/><circle cx="212" cy="283" r="9" fill="#e0533d"/>
    </svg>
  </section>

</main>
</body>
</html>