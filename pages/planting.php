<?php
$basePath = '../';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/crud.php';

crud_run([
    'key'       => 'planting',
    'title'     => 'Planting',
    'singular'  => 'planting record',
    'table'     => 'planting',
    'pk'        => 'planting_id',
    'pk_sql'    => 'p.planting_id',
    'label_col' => 'label',
    'select'    => "p.planting_id, f.farm_name, c.crop_name, p.planting_date, p.area_planted, p.seed_quantity, p.expected_harvest_date, s.status_name,
                    f.farm_name || ' - ' || c.crop_name || ' (' || to_char(p.planting_date, 'YYYY-MM-DD') || ')' AS label",
    'from'      => 'planting p
                    JOIN farm f ON f.farm_id = p.farm_id
                    JOIN crop c ON c.crop_id = p.crop_id
                    JOIN planting_status s ON s.status_id = p.status_id',
    'columns'   => [
        'farm_name'             => ['Farm', 'f.farm_name'],
        'crop_name'             => ['Crop', 'c.crop_name'],
        'planting_date'         => ['Planted', 'p.planting_date', 'date'],
        'area_planted'          => ['Area (ha)', 'p.area_planted', 'num'],
        'seed_quantity'         => ['Seed Quantity', 'p.seed_quantity', 'num'],
        'expected_harvest_date' => ['Expected Harvest Date', 'p.expected_harvest_date', 'date'],
        'status_name'           => ['Status', 's.status_name'],
    ],
    'sort'      => 'planting_date',
    'dir'       => 'desc',
    'search'    => [
        'f.farm_name',
        'c.crop_name',
    ],
    'search_numeric' => ['p.area_planted', 'p.seed_quantity'],
    'filters'   => [
        'farm' => [
            'label'   => 'Farm',
            'where'   => 'p.farm_id = ?',
            'options' => 'SELECT farm_id AS id, farm_name AS label FROM farm ORDER BY farm_name',
        ],
        'crop' => [
            'label'   => 'Crop',
            'where'   => 'p.crop_id = ?',
            'options' => "SELECT crop_id AS id, crop_name || ' - ' || variety AS label FROM crop ORDER BY crop_name, variety",
        ],        
        'status' => [
            'label'   => 'Status',
            'where'   => 'p.status_id = ?',
            'options' => 'SELECT status_id AS id, status_name AS label FROM planting_status ORDER BY status_id',
        ],
    ],
    'fields'    => [
        ['name' => 'farm_id', 'label' => 'Farm', 'type' => 'select', 'required' => true,
         'options' => "SELECT f.farm_id AS id, f.farm_name || ' (' || fr.last_name || ')' AS label FROM farm f JOIN farmer fr ON fr.farmer_id = f.farmer_id ORDER BY f.farm_name"],
        ['name' => 'crop_id', 'label' => 'Crop', 'type' => 'select', 'required' => true,
         'options' => "SELECT crop_id AS id, crop_name || ' - ' || variety AS label FROM crop ORDER BY crop_name, variety"],
        ['name' => 'planting_date',         'label' => 'Planting Date',          'type' => 'date', 'required' => true, 'default' => 'today'],
        ['name' => 'expected_harvest_date', 'label' => 'Expected Harvest Date',  'type' => 'date', 'required' => true],
        ['name' => 'area_planted',  'label' => 'Area Planted (ha)', 'type' => 'number', 'required' => true, 'min' => 0.01, 'maxv' => 999999.99, 'step' => '0.01'],
        ['name' => 'seed_quantity', 'label' => 'Seed Quantity',      'type' => 'number', 'required' => true, 'min' => 0.01, 'maxv' => 999999.99, 'step' => '0.01'],
        ['name' => 'status_id', 'label' => 'Status', 'type' => 'select', 'required' => true,
         'options' => 'SELECT status_id AS id, status_name AS label FROM planting_status ORDER BY status_id'],
    ],
    'validate'  => function (array $d, PDO $pdo): array {
        $err = [];
        if ($d['expected_harvest_date'] < $d['planting_date']) {
            $err['expected_harvest_date'] = 'Expected harvest date cannot be before the planting date.';
        }
        $st = $pdo->prepare('SELECT area_hectares FROM farm WHERE farm_id = ?');
        $st->execute([(int) $d['farm_id']]);
        $farmArea = $st->fetchColumn();
        if ($farmArea !== false && (float) $d['area_planted'] > (float) $farmArea) {
            $err['area_planted'] = 'Area planted cannot exceed the farm area (' . number_format((float) $farmArea, 2) . ' ha).';
        }
        return $err;
    },
]);