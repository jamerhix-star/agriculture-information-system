<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$q = in_get('q');
$status = in_get('status');
$cropId = in_get('crop');
$from = in_get('from');
$to = in_get('to');
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(fm.farm_name ILIKE ? OR c.crop_name ILIKE ? OR c.variety ILIKE ?)';
    array_push($params, like($q), like($q), like($q));
}
if (ctype_digit($status)) {
    $where[] = 'p.status_id = ?';
    $params[] = $status;
}
if (ctype_digit($cropId)) {
    $where[] = 'p.crop_id = ?';
    $params[] = $cropId;
}
if ($from !== '' && valid_date($from)) {
    $where[] = 'p.planting_date >= ?';
    $params[] = $from;
}
if ($to !== '' && valid_date($to)) {
    $where[] = 'p.planting_date <= ?';
    $params[] = $to;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

[$sort, $dir, $orderBy] = sort_state([
    'id' => 'p.planting_id', 'farm' => 'fm.farm_name', 'crop' => 'c.crop_name', 'planted' => 'p.planting_date',
    'area' => 'p.area_planted', 'expected' => 'p.expected_harvest_date', 'status' => 'ps.status_name',
], 'id', 'p.planting_id');

$data = paginate(
    'planting p JOIN farm fm ON fm.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id
     JOIN planting_statuses ps ON ps.status_id = p.status_id',
    $whereSql, $params,
    'p.planting_id, p.planting_date, p.area_planted, p.expected_harvest_date, fm.farm_name, c.crop_name, c.variety, ps.status_name',
    $orderBy
);
$crops = db()->query("SELECT crop_id AS id, crop_name || ' - ' || variety AS label FROM crop ORDER BY crop_name, variety")->fetchAll();

page_header('Planting records', 'plantings');
list_title('Planting records', '/plantings/form.php', 'Add planting');
?>
<form class="row g-2 filter-bar" method="get">
    <div class="col-12 col-md-3"><input class="form-control" name="q" placeholder="Search farm or crop" value="<?= e($q) ?>"></div>
    <div class="col-6 col-md-2"><?= filter_select('status', lookup('status'), $status, 'All statuses') ?></div>
    <div class="col-6 col-md-2"><?= filter_select('crop', $crops, $cropId, 'All crops') ?></div>
    <div class="col-6 col-md-2"><input type="date" class="form-control" name="from" value="<?= e($from) ?>" title="Planted from"></div>
    <div class="col-6 col-md-2"><input type="date" class="form-control" name="to" value="<?= e($to) ?>" title="Planted to"></div>
    <?= filter_actions('/plantings/index.php') ?>
</form>
<div class="table-responsive bg-white border rounded">
    <table class="table table-striped table-hover align-middle mb-0">
        <thead class="table-light"><tr>
            <?= sort_th('ID', 'id', $sort, $dir) ?>
            <?= sort_th('Farm', 'farm', $sort, $dir) ?>
            <?= sort_th('Crop', 'crop', $sort, $dir) ?>
            <?= sort_th('Planted', 'planted', $sort, $dir) ?>
            <?= sort_th('Area (ha)', 'area', $sort, $dir) ?>
            <?= sort_th('Expected harvest', 'expected', $sort, $dir) ?>
            <?= sort_th('Status', 'status', $sort, $dir) ?>
            <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($data['rows'] as $r): ?>
            <tr>
                <td><?= (int) $r['planting_id'] ?></td>
                <td><?= e($r['farm_name']) ?></td>
                <td><?= e($r['crop_name'] . ' - ' . $r['variety']) ?></td>
                <td><?= e($r['planting_date']) ?></td>
                <td><?= e(money($r['area_planted'])) ?></td>
                <td><?= e($r['expected_harvest_date']) ?></td>
                <td><?= status_badge($r['status_name']) ?></td>
                <?= row_actions('/plantings', (int) $r['planting_id']) ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$data['rows']): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No planting records found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-3"><?= pager($data) ?></div>
<?php page_footer();
