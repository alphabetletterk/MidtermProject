<?php
$basePath = '';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/database.php';

$counts = ['farmers' => 0, 'farms' => 0, 'crops' => 0, 'plantings' => 0, 'harvests' => 0, 'total_kg' => 0];
$statuses = [];
$recentPlantings = [];
$recentHarvests = [];
$dbError = false;

try {
    $pdo = getConnection();

    $counts = $pdo->query("
        SELECT
          (SELECT COUNT(*) FROM farmer)   AS farmers,
          (SELECT COUNT(*) FROM farm)     AS farms,
          (SELECT COUNT(*) FROM crop)     AS crops,
          (SELECT COUNT(*) FROM planting) AS plantings,
          (SELECT COUNT(*) FROM harvest)  AS harvests,
          (SELECT COALESCE(SUM(quantity_kg), 0) FROM harvest) AS total_kg
    ")->fetch();

    $statuses = $pdo->query("
        SELECT s.status_name, COUNT(p.planting_id) AS total
        FROM planting_status s
        LEFT JOIN planting p ON p.status_id = s.status_id
        GROUP BY s.status_id, s.status_name
        ORDER BY s.status_id
    ")->fetchAll();

    $recentPlantings = $pdo->query("
        SELECT p.planting_id, f.farm_name, c.crop_name, p.planting_date, s.status_name
        FROM planting p
        JOIN farm f ON f.farm_id = p.farm_id
        JOIN crop c ON c.crop_id = p.crop_id
        JOIN planting_status s ON s.status_id = p.status_id
        ORDER BY p.planting_date DESC, p.planting_id DESC
        LIMIT 5
    ")->fetchAll();

    $recentHarvests = $pdo->query("
        SELECT h.harvest_id, f.farm_name, c.crop_name, h.harvest_date, h.quantity_kg, r.remark_description
        FROM harvest h
        JOIN planting p ON p.planting_id = h.planting_id
        JOIN farm f ON f.farm_id = p.farm_id
        JOIN crop c ON c.crop_id = p.crop_id
        JOIN harvest_remark r ON r.remark_id = h.remark_id
        ORDER BY h.harvest_date DESC, h.harvest_id DESC
        LIMIT 5
    ")->fetchAll();
} catch (Throwable $e) {
    $dbError = true;
    error_log($e->getMessage());
}

$cards = [
    ['Farmers',          number_format((int) $counts['farmers']),   'pages/farmers.php'],
    ['Farms',            number_format((int) $counts['farms']),     'pages/farms.php'],
    ['Crops',            number_format((int) $counts['crops']),     'pages/crops.php'],
    ['Plantings',        number_format((int) $counts['plantings']), 'pages/planting.php'],
    ['Harvests',         number_format((int) $counts['harvests']),  'pages/harvest.php'],
    ['Total harvested',  number_format((float) $counts['total_kg'], 2), 'pages/reports.php?r=harvest', 'kg'],
];
$statusSum = max(1, array_sum(array_column($statuses, 'total')));

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-1">Dashboard</h1>
<p class="text-secondary mb-4">Overview of farmers, farms, crops, plantings, and harvests.</p>

<?php if ($dbError): ?>
  <div class="alert alert-danger" role="alert">
    The dashboard could not load data right now. Please try again, or ask an administrator to check the database connection.
  </div>
<?php endif; ?>

<div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3 mb-4">
  <?php foreach ($cards as $card):
      [$label, $value, $url] = $card;
      $unit = $card[3] ?? ''; ?>
    <div class="col">
      <a href="<?= e($url) ?>" class="panel stat stat-link d-block h-100 text-decoration-none">
        <div class="stat-label"><?= e($label) ?></div>
        <div class="stat-value text-nowrap"><?= e($value) ?><?php if ($unit !== ''): ?> <span class="stat-unit"><?= e($unit) ?></span><?php endif; ?></div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-4">
    <section class="panel h-100">
      <h2 class="h6 mb-3">Plantings by status</h2>
      <?php if (!$statuses): ?>
        <p class="text-secondary small mb-0">No status data yet.</p>
      <?php endif; ?>
      <?php foreach ($statuses as $s): ?>
        <?php $pct = round(((int) $s['total']) / $statusSum * 100); ?>
        <div class="d-flex justify-content-between small mb-1">
          <span><?= e($s['status_name']) ?></span>
          <span><?= e($s['total']) ?></span>
        </div>
        <div class="progress mb-3" role="progressbar" aria-label="<?= e($s['status_name']) ?>"
             aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
          <div class="progress-bar" style="width: <?= $pct ?>%"></div>
        </div>
      <?php endforeach; ?>
    </section>
  </div>

  <div class="col-lg-8">
    <section class="panel h-100">
      <h2 class="h6 mb-3">Recent plantings</h2>
      <?php if (!$recentPlantings): ?>
        <p class="text-secondary small mb-0">No plantings recorded yet.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr><th>Farm</th><th>Crop</th><th>Date planted</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php foreach ($recentPlantings as $row): ?>
                <tr>
                  <td><?= e($row['farm_name']) ?></td>
                  <td><?= e($row['crop_name']) ?></td>
                  <td><?= e(date('M j, Y', strtotime($row['planting_date']))) ?></td>
                  <td><?= e($row['status_name']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>

<section class="panel">
  <h2 class="h6 mb-3">Recent harvests</h2>
  <?php if (!$recentHarvests): ?>
    <p class="text-secondary small mb-0">No harvests recorded yet.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr><th>Farm</th><th>Crop</th><th>Date harvested</th><th class="text-end">Quantity (kg)</th><th>Remarks</th></tr>
        </thead>
        <tbody>
          <?php foreach ($recentHarvests as $row): ?>
            <tr>
              <td><?= e($row['farm_name']) ?></td>
              <td><?= e($row['crop_name']) ?></td>
              <td><?= e(date('M j, Y', strtotime($row['harvest_date']))) ?></td>
              <td class="text-end"><?= e(number_format((float) $row['quantity_kg'], 2)) ?></td>
              <td><?= e($row['remark_description']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>