<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$q = in_get('q');
$address = in_get('address');
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(f.first_name ILIKE ? OR f.last_name ILIKE ? OR f.contact_number ILIKE ?)';
    array_push($params, like($q), like($q), like($q));
}
if ($address !== '') {
    $where[] = 'f.address = ?';
    $params[] = $address;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

[$sort, $dir, $orderBy] = sort_state([
    'id' => 'f.farmer_id', 'name' => 'f.last_name', 'contact' => 'f.contact_number',
    'address' => 'f.address', 'registered' => 'f.date_registered', 'farms' => 'farm_count',
], 'id', 'f.farmer_id');

$data = paginate(
    'farmer f', $whereSql, $params,
    'f.*, (SELECT COUNT(*) FROM farm x WHERE x.farmer_id = f.farmer_id) AS farm_count',
    $orderBy
);

$addresses = [];
foreach (db()->query('SELECT DISTINCT address FROM farmer ORDER BY address')->fetchAll(PDO::FETCH_COLUMN) as $a) {
    $addresses[] = ['id' => $a, 'label' => $a];
}

page_header('Farmers', 'farmers');
list_title('Farmers', '/farmers/form.php', 'Add farmer');
?>
<form class="row g-2 mb-3" method="get">
    <div class="col-12 col-md-5"><input class="form-control" name="q" placeholder="Search name or contact number" value="<?= e($q) ?>"></div>
    <div class="col-12 col-md-4"><?= filter_select('address', $addresses, $address, 'All addresses') ?></div>
    <?= filter_actions('/farmers/index.php') ?>
</form>
<div class="table-responsive bg-white border rounded">
    <table class="table table-striped table-hover align-middle mb-0">
        <thead class="table-light"><tr>
            <?= sort_th('ID', 'id', $sort, $dir) ?>
            <?= sort_th('Name', 'name', $sort, $dir) ?>
            <?= sort_th('Contact', 'contact', $sort, $dir) ?>
            <?= sort_th('Address', 'address', $sort, $dir) ?>
            <?= sort_th('Registered', 'registered', $sort, $dir) ?>
            <?= sort_th('Farms', 'farms', $sort, $dir) ?>
            <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($data['rows'] as $r): ?>
            <tr>
                <td><?= (int) $r['farmer_id'] ?></td>
                <td><?= e($r['last_name'] . ', ' . $r['first_name']) ?></td>
                <td><?= e($r['contact_number']) ?></td>
                <td><?= e($r['address']) ?></td>
                <td><?= e($r['date_registered']) ?></td>
                <td><?= (int) $r['farm_count'] ?></td>
                <?= row_actions('/farmers', (int) $r['farmer_id']) ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$data['rows']): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No farmers found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-3"><?= pager($data) ?></div>
<?php page_footer();
