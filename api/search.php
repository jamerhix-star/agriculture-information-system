<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';
header('Content-Type: application/json');

$module = in_get('module');
$q = in_get('q');
$limit = max(1, min(20, (int) (in_get('limit') ?: 8)));

$map = [
    'crops'    => ['table' => 'crop',    'pk' => 'crop_id',    'label' => "crop_name || ' - ' || variety", 'sub' => 'crop_category_id'],
    'farmers'  => ['table' => 'farmer',  'pk' => 'farmer_id',  'label' => "first_name || ' ' || last_name", 'sub' => 'address'],
    'farms'    => ['table' => 'farm',    'pk' => 'farm_id',    'label' => 'farm_name', 'sub' => 'location'],
    'planting' => ['table' => 'planting','pk' => 'planting_id','label' => "'#' || planting_id", 'sub' => 'planting_date'],
    'harvest'  => ['table' => 'harvest','pk' => 'harvest_id', 'label' => "'#' || harvest_id", 'sub' => 'harvest_date'],
];

if (!isset($map[$module]) || $q === '') {
    echo json_encode(['results' => []]);
    exit;
}

$m = $map[$module];
$like = '%' . addcslashes($q, '%_\\') . '%';
$sql = "SELECT {$m['pk']} AS id, {$m['label']} AS label, {$m['sub']} AS sub FROM {$m['table']} WHERE ({$m['label']} ILIKE ?) LIMIT $limit";
$st = db()->prepare($sql);
$st->execute([$like]);
echo json_encode(['results' => $st->fetchAll()]);
