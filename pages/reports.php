<?php
$basePath = '../';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/crud.php';

$hj = 'harvest h JOIN planting p ON p.planting_id = h.planting_id JOIN farm f ON f.farm_id = p.farm_id'
    . ' JOIN farmer fr ON fr.farmer_id = f.farmer_id JOIN crop c ON c.crop_id = p.crop_id';
$cropLabel = "c.crop_name || ' - ' || c.variety";
$valueSql = 'h.quantity_kg * h.selling_price_per_kg';

$H = 'Harvest Reports';
$O = 'Farm and Planting Reports';

$reports = [
    'records' => [
        'title' => 'Harvest Records', 'section' => $H, 'harvest' => true,
        'date' => 'h.harvest_date', 'dateLabel' => 'harvest date',
        'select' => "h.harvest_date, f.farm_name, $cropLabel AS crop, h.quantity_kg, qg.quality_grade_name, hr.remark_description",
        'from' => $hj . ' JOIN quality_grade qg ON qg.quality_grade_id = h.quality_grade_id JOIN harvest_remark hr ON hr.remark_id = h.remark_id',
        'groupby' => '', 'order' => 'h.harvest_date DESC, h.harvest_id DESC',
        'cols' => ['harvest_date' => ['Date', 'date'], 'farm_name' => ['Farm'], 'crop' => ['Crop'], 'quantity_kg' => ['Quantity (kg)', 'num'], 'quality_grade_name' => ['Grade'], 'remark_description' => ['Remarks']],
        'totals' => ['quantity_kg' => 'num'],
    ],
    'value' => [
        'title' => 'Estimated Harvest Value', 'section' => $H, 'harvest' => true,
        'date' => 'h.harvest_date', 'dateLabel' => 'harvest date',
        'select' => "h.harvest_date, f.farm_name, $cropLabel AS crop, h.quantity_kg, h.selling_price_per_kg, $valueSql AS est_value",
        'from' => $hj,
        'groupby' => '', 'order' => 'h.harvest_date DESC, h.harvest_id DESC',
        'cols' => ['harvest_date' => ['Date', 'date'], 'farm_name' => ['Farm'], 'crop' => ['Crop'], 'quantity_kg' => ['Quantity (kg)', 'num'], 'selling_price_per_kg' => ['Price per kg', 'money'], 'est_value' => ['Estimated value', 'money']],
        'totals' => ['quantity_kg' => 'num', 'est_value' => 'money'],
    ],
    'harvest' => [
        'title' => 'Total Harvested Quantity by Crop', 'section' => $H, 'harvest' => true,
        'date' => 'h.harvest_date', 'dateLabel' => 'harvest date',
        'select' => "$cropLabel AS crop, COUNT(h.harvest_id) AS harvests, SUM(h.quantity_kg) AS total_kg, ROUND(AVG(h.quantity_kg), 2) AS avg_kg, SUM($valueSql) AS est_value",
        'from' => $hj,
        'groupby' => 'GROUP BY c.crop_id, c.crop_name, c.variety', 'order' => 'total_kg DESC, crop',
        'cols' => ['crop' => ['Crop'], 'harvests' => ['Harvests', 'int'], 'total_kg' => ['Total (kg)', 'num'], 'avg_kg' => ['Avg. per harvest (kg)', 'num'], 'est_value' => ['Estimated value', 'money']],
        'totals' => ['harvests' => 'int', 'total_kg' => 'num', 'est_value' => 'money'],
        'share' => 'total_kg',
    ],
    'monthly' => [
        'title' => 'Monthly Harvest Summary', 'section' => $H, 'harvest' => true,
        'date' => 'h.harvest_date', 'dateLabel' => 'harvest date',
        'select' => "to_char(date_trunc('month', h.harvest_date), 'Mon YYYY') AS month_label, COUNT(h.harvest_id) AS harvests, SUM(h.quantity_kg) AS total_kg, SUM($valueSql) AS est_value",
        'from' => $hj,
        'groupby' => "GROUP BY date_trunc('month', h.harvest_date)", 'order' => "date_trunc('month', h.harvest_date)",
        'cols' => ['month_label' => ['Month'], 'harvests' => ['Harvests', 'int'], 'total_kg' => ['Total (kg)', 'num'], 'est_value' => ['Estimated value', 'money']],
        'totals' => ['harvests' => 'int', 'total_kg' => 'num', 'est_value' => 'money'],
        'share' => 'total_kg',
    ],
    'performance' => [
        'title' => 'Harvest Performance', 'section' => $H, 'harvest' => true,
        'date' => 'h.harvest_date', 'dateLabel' => 'harvest date',
        'select' => "f.farm_name, $cropLabel AS crop, p.expected_harvest_date, h.harvest_date, (h.harvest_date - p.expected_harvest_date) AS days_diff, p.area_planted, h.quantity_kg, ROUND(h.quantity_kg / p.area_planted, 2) AS yield_per_ha",
        'from' => $hj,
        'groupby' => '', 'order' => 'h.harvest_date DESC, h.harvest_id DESC',
        'cols' => ['farm_name' => ['Farm'], 'crop' => ['Crop'], 'expected_harvest_date' => ['Expected', 'date'], 'harvest_date' => ['Harvested', 'date'], 'days_diff' => ['Vs. expected', 'days'], 'area_planted' => ['Area (ha)', 'num'], 'quantity_kg' => ['Quantity (kg)', 'num'], 'yield_per_ha' => ['Yield (kg/ha)', 'num']],
        'totals' => ['quantity_kg' => 'num'],
    ],

    'farmers' => [
        'title' => 'Farmers and Their Farms', 'section' => $O,
        'date' => 'fr.date_registered', 'dateLabel' => 'registration date',
        'select' => "fr.first_name || ' ' || fr.last_name AS farmer, f.farm_name, f.location, f.area_hectares, st.soil_type_name, it.irrigation_type_name",
        'from' => 'farmer fr LEFT JOIN farm f ON f.farmer_id = fr.farmer_id LEFT JOIN soil_type st ON st.soil_type_id = f.soil_type_id LEFT JOIN irrigation_type it ON it.irrigation_type_id = f.irrigation_type_id',
        'groupby' => '', 'order' => 'fr.last_name, fr.first_name, f.farm_name',
        'cols' => ['farmer' => ['Farmer'], 'farm_name' => ['Farm'], 'location' => ['Location'], 'area_hectares' => ['Area (ha)', 'num'], 'soil_type_name' => ['Soil'], 'irrigation_type_name' => ['Irrigation']],
        'totals' => ['area_hectares' => 'num'],
    ],
    'crops' => [
        'title' => 'Crops Planted Per Farm', 'section' => $O,
        'date' => 'p.planting_date', 'dateLabel' => 'planting date',
        'select' => "f.farm_name, $cropLabel AS crop, COUNT(p.planting_id) AS plantings, SUM(p.area_planted) AS total_area",
        'from' => 'planting p JOIN farm f ON f.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id',
        'groupby' => 'GROUP BY f.farm_id, f.farm_name, c.crop_id, c.crop_name, c.variety', 'order' => 'f.farm_name, c.crop_name, c.variety',
        'cols' => ['farm_name' => ['Farm'], 'crop' => ['Crop'], 'plantings' => ['Plantings', 'int'], 'total_area' => ['Total area (ha)', 'num']],
        'totals' => ['plantings' => 'int', 'total_area' => 'num'],
    ],
    'status' => [
        'title' => 'Plantings by Status', 'section' => $O,
        'date' => 'p.planting_date', 'dateLabel' => 'planting date',
        'select' => 's.status_name, COUNT(p.planting_id) AS plantings, SUM(p.area_planted) AS total_area',
        'from' => 'planting p JOIN planting_status s ON s.status_id = p.status_id',
        'groupby' => 'GROUP BY s.status_id, s.status_name', 'order' => 's.status_id',
        'cols' => ['status_name' => ['Status'], 'plantings' => ['Plantings', 'int'], 'total_area' => ['Total area (ha)', 'num']],
        'totals' => ['plantings' => 'int', 'total_area' => 'num'],
    ],
];

function report_fmt($v, ?string $fmt): string
{
    if ($fmt === 'days') {
        if ($v === null || $v === '') {
            return '';
        }
        $n = (int) $v;
        return $n === 0 ? 'On time' : sprintf('%+d day%s', $n, abs($n) === 1 ? '' : 's');
    }
    return crud_fmt($v, $fmt);
}

$key = $_GET['r'] ?? 'records';
$key = is_string($key) && isset($reports[$key]) ? $key : 'records';
$rep = $reports[$key];
$isHarvest = !empty($rep['harvest']);
$share = $rep['share'] ?? '';

$dateOk = function ($v): bool {
    $d = is_string($v) ? DateTime::createFromFormat('Y-m-d', $v) : false;
    return $d !== false && $d->format('Y-m-d') === $v;
};
$from = $dateOk($_GET['from'] ?? '') ? $_GET['from'] : '';
$to = $dateOk($_GET['to'] ?? '') ? $_GET['to'] : '';

$notice = '';
if (($_GET['from'] ?? '') !== '' && $from === '') {
    $notice = 'The From date is not a valid date.';
} elseif (($_GET['to'] ?? '') !== '' && $to === '') {
    $notice = 'The To date is not a valid date.';
} elseif ($from !== '' && $to !== '' && $from > $to) {
    $notice = 'The From date cannot be after the To date.';
}

$rows = [];
$kpi = ['harvests' => 0, 'total_kg' => 0, 'est_value' => 0];
$farmOpts = [];
$cropOpts = [];
$farmId = '';
$cropId = '';
$loadError = false;

try {
    $pdo = getConnection();

    if ($isHarvest) {
        $farmOpts = crud_options($pdo, 'SELECT farm_id AS id, farm_name AS label FROM farm ORDER BY farm_name');
        $cropOpts = crud_options($pdo, "SELECT crop_id AS id, crop_name || ' - ' || variety AS label FROM crop ORDER BY crop_name, variety");
        $f = $_GET['farm'] ?? '';
        $farmId = is_string($f) && isset($farmOpts[$f]) ? $f : '';
        $c = $_GET['crop'] ?? '';
        $cropId = is_string($c) && isset($cropOpts[$c]) ? $c : '';
    }

    $conds = [];
    $params = [];
    if ($from !== '') { $conds[] = $rep['date'] . ' >= ?'; $params[] = $from; }
    if ($to !== '')   { $conds[] = $rep['date'] . ' <= ?'; $params[] = $to; }
    if ($farmId !== '') { $conds[] = 'f.farm_id = ?'; $params[] = (int) $farmId; }
    if ($cropId !== '') { $conds[] = 'c.crop_id = ?'; $params[] = (int) $cropId; }
    $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

    if ($notice === '') {
        $st = $pdo->prepare("SELECT {$rep['select']} FROM {$rep['from']} $where {$rep['groupby']} ORDER BY {$rep['order']}");
        $st->execute($params);
        $rows = $st->fetchAll();

        if ($isHarvest) {
            $st = $pdo->prepare(
                "SELECT COUNT(h.harvest_id) AS harvests, COALESCE(SUM(h.quantity_kg), 0) AS total_kg,
                        COALESCE(SUM($valueSql), 0) AS est_value
                 FROM $hj $where"
            );
            $st->execute($params);
            $kpi = $st->fetch() ?: $kpi;
        }
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    $loadError = true;
}

$sums = [];
foreach ($rep['totals'] as $c => $_) {
    $sums[$c] = array_sum(array_map('floatval', array_column($rows, $c)));
}

$scope = [];
if ($from !== '' || $to !== '') {
    $scope[] = ($from ?: 'start') . ' to ' . ($to ?: 'today');
}
if ($farmId !== '') { $scope[] = 'Farm: ' . $farmOpts[$farmId]; }
if ($cropId !== '') { $scope[] = 'Crop: ' . $cropOpts[$cropId]; }

$sections = [];
foreach ($reports as $k => $rp) {
    $sections[$rp['section']][$k] = $rp['title'];
}

$pageTitle = 'Reports';
$activePage = 'reports';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">Reports</h1>
  <button type="button" class="btn btn-signout no-print" onclick="window.print()">Print</button>
</div>

<form method="get" class="panel mb-3 no-print">
  <div class="row g-2 align-items-end">
    <div class="col-lg-4">
      <label class="form-label small" for="r">Report</label>
      <select class="form-select" id="r" name="r" onchange="this.form.submit()">
        <?php foreach ($sections as $label => $items): ?>
          <optgroup label="<?= e($label) ?>">
            <?php foreach ($items as $k => $title): ?>
              <option value="<?= e($k) ?>"<?= $k === $key ? ' selected' : '' ?>><?= e($title) ?></option>
            <?php endforeach; ?>
          </optgroup>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-lg-3 col-6">
      <label class="form-label small" for="from">From (<?= e($rep['dateLabel']) ?>)</label>
      <input type="date" class="form-control" id="from" name="from" value="<?= e($from) ?>">
    </div>
    <div class="col-lg-3 col-6">
      <label class="form-label small" for="to">To</label>
      <input type="date" class="form-control" id="to" name="to" value="<?= e($to) ?>">
    </div>
    <div class="col-12 col-lg-auto d-flex gap-2">
      <button type="submit" class="btn btn-field">Generate</button>
      <a href="?r=<?= e($key) ?>" class="btn btn-signout">Reset</a>
    </div>
  </div>
  <?php if ($isHarvest): ?>
    <div class="row g-2 mt-1">
      <div class="col-lg-4 col-md-6">
        <label class="form-label small" for="farm">Farm</label>
        <select class="form-select" id="farm" name="farm">
          <option value="">All farms</option>
          <?php foreach ($farmOpts as $v => $l): ?>
            <option value="<?= e($v) ?>"<?= (string) $v === $farmId ? ' selected' : '' ?>><?= e($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-lg-4 col-md-6">
        <label class="form-label small" for="crop">Crop</label>
        <select class="form-select" id="crop" name="crop">
          <option value="">All crops</option>
          <?php foreach ($cropOpts as $v => $l): ?>
            <option value="<?= e($v) ?>"<?= (string) $v === $cropId ? ' selected' : '' ?>><?= e($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  <?php endif; ?>
</form>

<?php if ($loadError): ?>
  <div class="alert alert-danger">The report could not be generated. Please try again.</div>
<?php endif; ?>
<?php if ($notice !== ''): ?>
  <div class="alert alert-warning"><?= e($notice) ?></div>
<?php endif; ?>

<?php if ($isHarvest): ?>
  <?php
  $kg = (float) $kpi['total_kg'];
  $value = (float) $kpi['est_value'];
  $cards = [
      ['Harvest records',      crud_fmt((int) $kpi['harvests'], 'int')],
      ['Total harvested',      crud_fmt($kg, 'num') . ' kg'],
      ['Estimated value',      crud_fmt($value, 'money')],
      ['Average price per kg', $kg > 0 ? crud_fmt($value / $kg, 'money') : '—'],
  ]; ?>
  <div class="row row-cols-2 row-cols-lg-4 g-3 mb-3">
    <?php foreach ($cards as [$label, $text]): ?>
      <div class="col">
        <div class="panel stat">
          <div class="stat-label"><?= e($label) ?></div>
          <div class="stat-value"><?= e($text) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<section class="panel">
  <h2 class="h6 mb-3">
    <?= e($rep['title']) ?>
    <?php if ($scope): ?><span class="text-secondary fw-normal">(<?= e(implode(' · ', $scope)) ?>)</span><?php endif; ?>
  </h2>
  <?php if (!$rows): ?>
    <p class="text-secondary mb-0">No data for this report and these filters.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-fixed align-middle mb-0">
        <thead>
          <tr>
            <?php foreach ($rep['cols'] as $c => $meta): ?>
              <th><?= e($meta[0]) ?></th>
            <?php endforeach; ?>
            <?php if ($share !== ''): ?><th>Share of total</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <?php foreach ($rep['cols'] as $c => $meta): ?>
                <td><?= e(report_fmt($r[$c], $meta[1] ?? null)) ?></td>
              <?php endforeach; ?>
              <?php if ($share !== ''):
                  $part = $sums[$share] > 0 ? (float) $r[$share] / $sums[$share] * 100 : 0; ?>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" role="progressbar" aria-label="Share of total"
                         aria-valuenow="<?= (int) round($part) ?>" aria-valuemin="0" aria-valuemax="100">
                      <div class="progress-bar" style="width: <?= round($part, 1) ?>%"></div>
                    </div>
                    <span class="small text-nowrap"><?= e(number_format($part, 1)) ?>%</span>
                  </div>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="fw-bold">
            <?php $i = 0; foreach ($rep['cols'] as $c => $meta): ?>
              <td>
                <?= $i === 0 ? 'Total' : (isset($rep['totals'][$c]) ? e(report_fmt($sums[$c], $rep['totals'][$c])) : '') ?>
              </td>
            <?php $i++; endforeach; ?>
            <?php if ($share !== ''): ?><td><?= $sums[$share] > 0 ? '100%' : '' ?></td><?php endif; ?>
          </tr>
        </tfoot>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>