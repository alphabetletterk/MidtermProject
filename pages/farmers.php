<?php
$basePath = '../';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/crud.php';

crud_run([
    'key'       => 'farmers',
    'title'     => 'Farmers',
    'singular'  => 'farmer',
    'table'     => 'farmer',
    'pk'        => 'farmer_id',
    'label_col' => 'full_name',
    'select'    => "farmer_id, last_name || ', ' || first_name AS full_name, contact_number, address, date_registered",
    'from'      => 'farmer',
    'columns'   => [
        'full_name'       => ['Name', 'last_name'],
        'contact_number'  => ['Contact number', 'contact_number'],
        'address'         => ['Address', 'address'],
        'date_registered' => ['Registered', 'date_registered', 'date'],
    ],
    'search'    => [
        'first_name',
        'last_name',
        "first_name || ' ' || last_name",
        'contact_number',
        'address',
    ],
    'filters'   => [
        'address' => [
            'label'   => 'Address',
            'text'    => true,
            'where'   => 'address = ?',
            'options' => "SELECT DISTINCT address AS id, address AS label FROM farmer ORDER BY address",
        ],
    ],
    'fields'    => [
        ['name' => 'first_name',      'label' => 'First name',      'type' => 'text', 'required' => true, 'max' => 50],
        ['name' => 'last_name',       'label' => 'Last name',       'type' => 'text', 'required' => true, 'max' => 50],
        ['name' => 'contact_number',  'label' => 'Contact number',  'type' => 'tel',  'required' => true, 'max' => 20],
        ['name' => 'address',         'label' => 'Address',         'type' => 'text', 'required' => true, 'max' => 150],
        ['name' => 'date_registered', 'label' => 'Date registered', 'type' => 'date', 'required' => true, 'default' => 'today', 'maxv' => date('Y-m-d')],
    ],
    'validate'  => function (array $d, PDO $pdo): array {
        $err = [];
        if ($d['date_registered'] > date('Y-m-d')) {
            $err['date_registered'] = 'Date registered cannot be in the future.';
        }
        return $err;
    },
]);