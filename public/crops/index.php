<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$q = in_get('q');
$cat = in_get('category');
$season = in_get('season');
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(c.crop_name ILIKE ? OR c.variety ILIKE ?)';
    array_push($params, like($q), like($q));
}
if (ctype_digit($cat)) {
    $where[] = 'c.crop_category_id = ?';
    $params[] = $cat;
}
if (ctype_digit($season)) {
    $where[] = 'c.season_id = ?';
    $params[] = $season;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

[$sort, $dir, $orderBy] = sort_state([
    'id' => 'c.crop_id', 'name' => 'c.crop_name', 'variety' => 'c.variety', 'category' => 'cc.category_name',
    'days' => 'c.growing_period_days', 'season' => 's.season_name',
], 'id', 'c.crop_id');

$data = paginate(
    'crop c JOIN crop_categories cc ON cc.crop_category_id = c.crop_category_id JOIN seasons s ON s.season_id = c.season_id',
    $whereSql, $params,
    'c.crop_id, c.crop_name, c.variety, c.growing_period_days, cc.category_name, s.season_name',
    $orderBy
);

page_header('Crops', 'crops');
list_title('Crops', '/crops/form.php', 'Add crop');
?>
<form class="row g-2 filter-bar" method="get">
    <div class="col-12 col-md-4"><input class="form-control" name="q" placeholder="Search crop or variety" value="<?= e($q) ?>"></div>
    <div class="col-6 col-md-3"><?= filter_select('category', lookup('category'), $cat, 'All categories') ?></div>
    <div class="col-6 col-md-3"><?= filter_select('season', lookup('season'), $season, 'All seasons') ?></div>
    <?= filter_actions('/crops/index.php') ?>
</form>
<div class="table-responsive bg-white border rounded">
    <table class="table table-striped table-hover align-middle mb-0">
        <thead class="table-light"><tr>
            <?= sort_th('ID', 'id', $sort, $dir) ?>
            <?= sort_th('Crop', 'name', $sort, $dir) ?>
            <?= sort_th('Variety', 'variety', $sort, $dir) ?>
            <?= sort_th('Category', 'category', $sort, $dir) ?>
            <?= sort_th('Growing days', 'days', $sort, $dir) ?>
            <?= sort_th('Season', 'season', $sort, $dir) ?>
            <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($data['rows'] as $r): ?>
            <tr>
                <td><?= (int) $r['crop_id'] ?></td>
                <td><?= e($r['crop_name']) ?></td>
                <td><?= e($r['variety']) ?></td>
                <td><?= e($r['category_name']) ?></td>
                <td><?= (int) $r['growing_period_days'] ?></td>
                <td><?= e($r['season_name']) ?></td>
                <?= row_actions('/crops', (int) $r['crop_id']) ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$data['rows']): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No crops found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-3"><?= pager($data) ?></div>
<?php page_footer();
