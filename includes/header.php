<?php
$navItems = [
    'dashboard' => ['Dashboard', 'dashboard.php'],
    'farmers'   => ['Farmers',   'pages/farmers.php'],
    'farms'     => ['Farms',     'pages/farms.php'],
    'crops'     => ['Crops',     'pages/crops.php'],
    'planting'  => ['Planting',  'pages/planting.php'],
    'harvest'   => ['Harvest',   'pages/harvest.php'],
    'reports'   => ['Reports',   'pages/reports.php'],
];
$adminItems = [
    'users' => ['Accounts',    'pages/users.php'],
    'audit' => ['Audit trail', 'pages/audit.php'],
];
$isAdmin = ($currentUser['role'] ?? '') === 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | Agriculture Information System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:wght@600&display=swap" rel="stylesheet">
  <link href="<?= $basePath ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<header class="app-header">
  <nav class="navbar navbar-expand-lg app-nav">
    <div class="container">
      <a class="app-brand" href="<?= $basePath ?>dashboard.php">
        <svg width="30" height="30" viewBox="0 0 30 30" aria-hidden="true">
          <rect width="30" height="30" rx="7" fill="#1f4d3a"/>
          <path d="M15 24 V14" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
          <path d="M15 15 C15 10 11 8 7 9 C7 13 10 15 15 15Z" fill="#fff"/>
          <path d="M15 12 C15 8 18 6 22 7 C22 11 19 12 15 12Z" fill="#e3c565"/>
        </svg>
        <span>Agriculture Information System</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
              aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav mx-auto">
          <?php foreach ($navItems as $key => [$label, $url]): ?>
            <li class="nav-item">
              <a class="nav-link<?= $key === $activePage ? ' active' : '' ?>"
                 href="<?= $basePath . $url ?>"><?= e($label) ?></a>
            </li>
          <?php endforeach; ?>
          <?php if ($isAdmin): ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle<?= isset($adminItems[$activePage]) ? ' active' : '' ?>" href="#"
                 role="button" data-bs-toggle="dropdown" aria-expanded="false">Admin</a>
              <ul class="dropdown-menu">
                <?php foreach ($adminItems as $key => [$label, $url]): ?>
                  <li><a class="dropdown-item<?= $key === $activePage ? ' active' : '' ?>"
                         href="<?= $basePath . $url ?>"><?= e($label) ?></a></li>
                <?php endforeach; ?>
              </ul>
            </li>
          <?php endif; ?>
        </ul>
        <a class="btn btn-signout mt-3 mt-lg-0" href="<?= $basePath ?>logout.php">Sign out</a>
      </div>
    </div>
  </nav>
  <div class="app-strip">
    Signed in as <strong><?= e($currentUser['username']) ?></strong> (<?= e($currentUser['role']) ?>)
  </div>
</header>
<main class="container py-5">