<?php
$basePath = '../';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/crud.php';

crud_run([
    'key'       => 'crops',
    'title'     => 'Crops',
    'singular'  => 'crop',
    'table'     => 'crop',
    'pk'        => 'crop_id',
    'pk_sql'    => 'c.crop_id',
    'label_col' => 'crop_name',
    'select'    => 'c.crop_id, c.crop_name, c.variety, cc.crop_category_name, c.growing_period_days, s.season_name',
    'from'      => 'crop c
                    JOIN crop_category cc ON cc.crop_category_id = c.crop_category_id
                    JOIN season s ON s.season_id = c.season_id',
    'columns'   => [
        'crop_name'           => ['Crop', 'c.crop_name'],
        'variety'             => ['Variety', 'c.variety'],
        'crop_category_name'  => ['Category', 'cc.crop_category_name'],
        'growing_period_days' => ['Growing period (days)', 'c.growing_period_days', 'int'],
        'season_name'         => ['Season', 's.season_name'],
    ],
    'search'    => [
        'c.crop_name',
        'c.variety',
        'cc.crop_category_name',
    ],
    'search_numeric' => ['c.growing_period_days'],
    'filters'   => [
        'category' => [
            'label'   => 'Category',
            'where'   => 'c.crop_category_id = ?',
            'options' => 'SELECT crop_category_id AS id, crop_category_name AS label FROM crop_category ORDER BY crop_category_id',
        ],
        'season' => [
            'label'   => 'Season',
            'where'   => 'c.season_id = ?',
            'options' => 'SELECT season_id AS id, season_name AS label FROM season ORDER BY season_id',
        ],
    ],
    'fields'    => [
        ['name' => 'crop_name',           'label' => 'Crop name', 'type' => 'text', 'required' => true, 'max' => 80],
        ['name' => 'variety',             'label' => 'Variety',   'type' => 'text', 'required' => true, 'max' => 80],
        ['name' => 'crop_category_id',    'label' => 'Category',  'type' => 'select', 'required' => true,
         'options' => 'SELECT crop_category_id AS id, crop_category_name AS label FROM crop_category ORDER BY crop_category_id'],
        ['name' => 'growing_period_days', 'label' => 'Growing period (days)', 'type' => 'number', 'required' => true, 'min' => 1, 'maxv' => 1000, 'step' => '1'],
        ['name' => 'season_id',           'label' => 'Season',    'type' => 'select', 'required' => true,
         'options' => 'SELECT season_id AS id, season_name AS label FROM season ORDER BY season_id'],
    ],
]);