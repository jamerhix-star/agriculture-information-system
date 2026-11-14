<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$q = in_get('q');
$irr = in_get('irrigation');
$soil = in_get('soil');
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(fm.farm_name ILIKE ? OR fm.location ILIKE ? OR fr.first_name ILIKE ? OR fr.last_name ILIKE ?)';
    array_push($params, like($q), like($q), like($q), like($q));
}
if (ctype_digit($irr)) {
    $where[] = 'fm.irrigation_type_id = ?';
    $params[] = $irr;
}
if (ctype_digit($soil)) {
    $where[] = 'fm.soil_type_id = ?';
    $params[] = $soil;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

[$sort, $dir, $orderBy] = sort_state([
    'id' => 'fm.farm_id', 'name' => 'fm.farm_name', 'farmer' => 'fr.last_name', 'location' => 'fm.location',
    'area' => 'fm.area_hectares', 'soil' => 's.soil_type_name', 'irrigation' => 'i.irrigation_type_name',
], 'id', 'fm.farm_id');

$data = paginate(
    'farm fm JOIN farmer fr ON fr.farmer_id = fm.farmer_id
     JOIN soil_types s ON s.soil_type_id = fm.soil_type_id
     JOIN irrigation_types i ON i.irrigation_type_id = fm.irrigation_type_id',
    $whereSql, $params,
    'fm.farm_id, fm.farm_name, fm.location, fm.area_hectares, fr.first_name, fr.last_name, s.soil_type_name, i.irrigation_type_name',
    $orderBy
);

page_header('Farms', 'farms');
list_title('Farms', '/farms/form.php', 'Add farm');
?>
<form class="row g-2 filter-bar" method="get">
    <div class="col-12 col-md-4"><input class="form-control" name="q" placeholder="Search farm, location or farmer" value="<?= e($q) ?>"></div>
    <div class="col-6 col-md-3"><?= filter_select('irrigation', lookup('irrigation'), $irr, 'All irrigation types') ?></div>
    <div class="col-6 col-md-3"><?= filter_select('soil', lookup('soil'), $soil, 'All soil types') ?></div>
    <?= filter_actions('/farms/index.php') ?>
</form>
<div class="table-responsive bg-white border rounded">
    <table class="table table-striped table-hover align-middle mb-0">
        <thead class="table-light"><tr>
            <?= sort_th('ID', 'id', $sort, $dir) ?>
            <?= sort_th('Farm', 'name', $sort, $dir) ?>
            <?= sort_th('Farmer', 'farmer', $sort, $dir) ?>
            <?= sort_th('Location', 'location', $sort, $dir) ?>
            <?= sort_th('Area (ha)', 'area', $sort, $dir) ?>
            <?= sort_th('Soil', 'soil', $sort, $dir) ?>
            <?= sort_th('Irrigation', 'irrigation', $sort, $dir) ?>
            <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($data['rows'] as $r): ?>
            <tr>
                <td><?= (int) $r['farm_id'] ?></td>
                <td><?= e($r['farm_name']) ?></td>
                <td><?= e($r['last_name'] . ', ' . $r['first_name']) ?></td>
                <td><?= e($r['location']) ?></td>
                <td><?= e(money($r['area_hectares'])) ?></td>
                <td><?= e($r['soil_type_name']) ?></td>
                <td><?= e($r['irrigation_type_name']) ?></td>
                <?= row_actions('/farms', (int) $r['farm_id']) ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$data['rows']): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No farms found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-3"><?= pager($data) ?></div>
<?php page_footer();
