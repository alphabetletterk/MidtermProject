<?php
$basePath = '../';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/crud.php';

crud_run([
    'key'       => 'harvest',
    'title'     => 'Harvest',
    'singular'  => 'harvest record',
    'table'     => 'harvest',
    'pk'        => 'harvest_id',
    'pk_sql'    => 'h.harvest_id',
    'label_col' => 'label',
    'select'    => "h.harvest_id, f.farm_name, c.crop_name, h.harvest_date, h.quantity_kg, h.selling_price_per_kg,
                    h.quantity_kg * h.selling_price_per_kg AS est_value, qg.quality_grade_name, hr.remark_description,
                    f.farm_name || ' - ' || c.crop_name || ' (' || to_char(h.harvest_date, 'YYYY-MM-DD') || ')' AS label",
    'from'      => 'harvest h
                    JOIN planting p ON p.planting_id = h.planting_id
                    JOIN farm f ON f.farm_id = p.farm_id
                    JOIN crop c ON c.crop_id = p.crop_id
                    JOIN quality_grade qg ON qg.quality_grade_id = h.quality_grade_id
                    JOIN harvest_remark hr ON hr.remark_id = h.remark_id',
    'columns'   => [
        'farm_name'            => ['Farm', 'f.farm_name'],
        'crop_name'            => ['Crop', 'c.crop_name'],
        'harvest_date'         => ['Harvested', 'h.harvest_date', 'date'],
        'quantity_kg'          => ['Quantity (kg)', 'h.quantity_kg', 'num'],
        'selling_price_per_kg' => ['Price Per kg', 'h.selling_price_per_kg', 'money'],
        'est_value'            => ['Est. Value', 'h.quantity_kg * h.selling_price_per_kg', 'money'],
        'quality_grade_name'   => ['Grade', 'qg.quality_grade_name'],
        'remark_description'   => ['Remarks', 'hr.remark_description'],
    ],
    'sort'      => 'harvest_date',
    'dir'       => 'desc',
    'search'    => [
        'f.farm_name',
        'c.crop_name',
        'hr.remark_description',
    ],
    'search_numeric' => [
        'h.quantity_kg',
        'h.selling_price_per_kg',
        'h.quantity_kg * h.selling_price_per_kg',
    ],
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

        'grade' => [
            'label'   => 'Quality Grade',
            'where'   => 'h.quality_grade_id = ?',
            'options' => 'SELECT quality_grade_id AS id, quality_grade_name AS label FROM quality_grade ORDER BY quality_grade_id',
        ],
        'remark' => [
            'label'   => 'Remarks',
            'where'   => 'h.remark_id = ?',
            'options' => 'SELECT remark_id AS id, remark_description AS label FROM harvest_remark ORDER BY remark_id',
        ],
    ],
    'fields'    => [
        ['name' => 'planting_id', 'label' => 'Planting record', 'type' => 'select', 'required' => true,
         'options' => "SELECT p.planting_id AS id, f.farm_name || ' - ' || c.crop_name || ' (planted ' || to_char(p.planting_date, 'YYYY-MM-DD') || ')' AS label
                       FROM planting p JOIN farm f ON f.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id
                       ORDER BY p.planting_date DESC, p.planting_id DESC"],
        ['name' => 'harvest_date',  'label' => 'Harvest Date', 'type' => 'date', 'required' => true, 'default' => 'today', 'maxv' => date('Y-m-d')],
        ['name' => 'quantity_kg',   'label' => 'Quantity (kg)', 'type' => 'number', 'required' => true, 'min' => 0.01, 'maxv' => 99999999.99, 'step' => '0.01'],
        ['name' => 'selling_price_per_kg', 'label' => 'Selling Price Per kg', 'type' => 'number', 'required' => true, 'min' => 0, 'maxv' => 99999999.99, 'step' => '0.01'],
        ['name' => 'quality_grade_id', 'label' => 'Quality Grade', 'type' => 'select', 'required' => true,
         'options' => 'SELECT quality_grade_id AS id, quality_grade_name AS label FROM quality_grade ORDER BY quality_grade_id'],
        ['name' => 'remark_id', 'label' => 'Harvest Remarks', 'type' => 'select', 'required' => true,
         'options' => 'SELECT remark_id AS id, remark_description AS label FROM harvest_remark ORDER BY remark_id'],
    ],
    'validate'  => function (array $d, PDO $pdo): array {
        $err = [];
        $st = $pdo->prepare('SELECT planting_date FROM planting WHERE planting_id = ?');
        $st->execute([(int) $d['planting_id']]);
        $plantedOn = $st->fetchColumn();
        if ($d['harvest_date'] > date('Y-m-d')) {
            $err['harvest_date'] = 'Harvest date cannot be in the future.';
        } elseif ($plantedOn !== false && $d['harvest_date'] < $plantedOn) {
            $err['harvest_date'] = 'Harvest date cannot be before the planting date (' . date('M j, Y', strtotime($plantedOn)) . ').';
        }
        return $err;
    },
]);