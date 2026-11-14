<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$q = in_get('q');
$grade = in_get('grade');
$remark = in_get('remark');
$from = in_get('from');
$to = in_get('to');
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(fm.farm_name ILIKE ? OR c.crop_name ILIKE ? OR c.variety ILIKE ?)';
    array_push($params, like($q), like($q), like($q));
}
if (ctype_digit($grade)) {
    $where[] = 'h.grade_id = ?';
    $params[] = $grade;
}
if (ctype_digit($remark)) {
    $where[] = 'h.remark_id = ?';
    $params[] = $remark;
}
if ($from !== '' && valid_date($from)) {
    $where[] = 'h.harvest_date >= ?';
    $params[] = $from;
}
if ($to !== '' && valid_date($to)) {
    $where[] = 'h.harvest_date <= ?';
    $params[] = $to;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

[$sort, $dir, $orderBy] = sort_state([
    'id' => 'h.harvest_id', 'date' => 'h.harvest_date', 'farm' => 'fm.farm_name', 'crop' => 'c.crop_name',
    'qty' => 'h.quantity_kg', 'price' => 'h.selling_price_per_kg', 'value' => 'est_value',
    'grade' => 'g.grade_name', 'remark' => 'r.remark_description',
], 'date', 'h.harvest_id');
if (!isset($_GET['sort'])) {
    $dir = 'DESC';
    $orderBy = 'h.harvest_date DESC, h.harvest_id DESC';
}

$data = paginate(
    'harvest h JOIN planting p ON p.planting_id = h.planting_id JOIN farm fm ON fm.farm_id = p.farm_id
     JOIN crop c ON c.crop_id = p.crop_id JOIN quality_grades g ON g.grade_id = h.grade_id
     JOIN harvest_remarks r ON r.remark_id = h.remark_id',
    $whereSql, $params,
    'h.harvest_id, h.planting_id, h.harvest_date, h.quantity_kg, h.selling_price_per_kg,
     (h.quantity_kg * h.selling_price_per_kg) AS est_value, fm.farm_name, c.crop_name, g.grade_name, r.remark_description',
    $orderBy
);

page_header('Harvest records', 'harvests');
list_title('Harvest records', '/harvests/form.php', 'Add harvest');
?>
<form class="row g-2 filter-bar" method="get">
    <div class="col-12 col-md-3"><input class="form-control" name="q" placeholder="Search farm or crop" value="<?= e($q) ?>"></div>
    <div class="col-6 col-md-2"><?= filter_select('grade', lookup('grade'), $grade, 'All grades') ?></div>
    <div class="col-6 col-md-2"><?= filter_select('remark', lookup('remark'), $remark, 'All remarks') ?></div>
    <div class="col-6 col-md-2"><input type="date" class="form-control" name="from" value="<?= e($from) ?>" title="Harvested from"></div>
    <div class="col-6 col-md-2"><input type="date" class="form-control" name="to" value="<?= e($to) ?>" title="Harvested to"></div>
    <?= filter_actions('/harvests/index.php') ?>
</form>
<div class="table-responsive bg-white border rounded">
    <table class="table table-striped table-hover align-middle mb-0">
        <thead class="table-light"><tr>
            <?= sort_th('ID', 'id', $sort, $dir) ?>
            <?= sort_th('Date', 'date', $sort, $dir) ?>
            <?= sort_th('Farm', 'farm', $sort, $dir) ?>
            <?= sort_th('Crop', 'crop', $sort, $dir) ?>
            <?= sort_th('Qty (kg)', 'qty', $sort, $dir) ?>
            <?= sort_th('Price / kg', 'price', $sort, $dir) ?>
            <?= sort_th('Est. value', 'value', $sort, $dir) ?>
            <?= sort_th('Grade', 'grade', $sort, $dir) ?>
            <?= sort_th('Remarks', 'remark', $sort, $dir) ?>
            <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($data['rows'] as $r): ?>
            <tr>
                <td><?= (int) $r['harvest_id'] ?></td>
                <td><?= e($r['harvest_date']) ?></td>
                <td><?= e($r['farm_name']) ?></td>
                <td><?= e($r['crop_name']) ?></td>
                <td><?= e(money($r['quantity_kg'])) ?></td>
                <td><?= e(money($r['selling_price_per_kg'])) ?></td>
                <td><?= e(money($r['est_value'])) ?></td>
                <td><?= grade_badge($r['grade_name']) ?></td>
                <td><?= e($r['remark_description']) ?></td>
                <?= row_actions('/harvests', (int) $r['harvest_id']) ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$data['rows']): ?>
            <tr><td colspan="10" class="text-center text-muted py-4">No harvest records found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-3"><?= pager($data) ?></div>
<?php page_footer();
