<?php
$basePath = '../';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/crud.php';

crud_run([
    'key'       => 'farms',
    'title'     => 'Farms',
    'singular'  => 'farm',
    'table'     => 'farm',
    'pk'        => 'farm_id',
    'pk_sql'    => 'f.farm_id',
    'label_col' => 'farm_name',
    'select'    => "f.farm_id, f.farm_name, fr.last_name || ', ' || fr.first_name AS farmer, f.location, f.area_hectares, st.soil_type_name, it.irrigation_type_name",
    'from'      => 'farm f
                    JOIN farmer fr ON fr.farmer_id = f.farmer_id
                    JOIN soil_type st ON st.soil_type_id = f.soil_type_id
                    JOIN irrigation_type it ON it.irrigation_type_id = f.irrigation_type_id',
    'columns'   => [
        'farm_name'            => ['Farm', 'f.farm_name'],
        'farmer'               => ['Farmer', 'fr.last_name'],
        'location'             => ['Location', 'f.location'],
        'area_hectares'        => ['Area (ha)', 'f.area_hectares', 'num'],
        'soil_type_name'       => ['Soil', 'st.soil_type_name'],
        'irrigation_type_name' => ['Irrigation', 'it.irrigation_type_name'],
    ],
    'search'    => [
        'f.farm_name',
        'f.location',
        'fr.first_name',
        'fr.last_name',
        "fr.first_name || ' ' || fr.last_name",
    ],
    'search_numeric' => ['f.area_hectares'],
    'filters'   => [
        'irrigation' => [
            'label'   => 'Irrigation type',
            'where'   => 'f.irrigation_type_id = ?',
            'options' => 'SELECT irrigation_type_id AS id, irrigation_type_name AS label FROM irrigation_type ORDER BY irrigation_type_id',
        ],
        'soil' => [
            'label'   => 'Soil type',
            'where'   => 'f.soil_type_id = ?',
            'options' => 'SELECT soil_type_id AS id, soil_type_name AS label FROM soil_type ORDER BY soil_type_id',
        ],
    ],
    'fields'    => [
        ['name' => 'farmer_id',          'label' => 'Farmer',          'type' => 'select', 'required' => true,
         'options' => "SELECT farmer_id AS id, last_name || ', ' || first_name AS label FROM farmer ORDER BY last_name, first_name"],
        ['name' => 'farm_name',          'label' => 'Farm name',       'type' => 'text',   'required' => true, 'max' => 100],
        ['name' => 'location',           'label' => 'Location',        'type' => 'text',   'required' => true, 'max' => 150],
        ['name' => 'area_hectares',      'label' => 'Area (hectares)', 'type' => 'number', 'required' => true, 'min' => 0.01, 'maxv' => 999999.99, 'step' => '0.01'],
        ['name' => 'soil_type_id',       'label' => 'Soil type',       'type' => 'select', 'required' => true,
         'options' => 'SELECT soil_type_id AS id, soil_type_name AS label FROM soil_type ORDER BY soil_type_id'],
        ['name' => 'irrigation_type_id', 'label' => 'Irrigation type', 'type' => 'select', 'required' => true,
         'options' => 'SELECT irrigation_type_id AS id, irrigation_type_name AS label FROM irrigation_type ORDER BY irrigation_type_id'],
    ],
]);