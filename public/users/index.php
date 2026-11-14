<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_permission('users', 'view');

$q = in_get('q');
$role = in_get('role');
$active = in_get('active');
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(u.username ILIKE ? OR u.full_name ILIKE ?)';
    array_push($params, like($q), like($q));
}
if (ctype_digit($role)) {
    $where[] = 'u.role_id = ?';
    $params[] = $role;
}
if ($active === '1' || $active === '0') {
    $where[] = 'u.is_active = ?';
    $params[] = $active;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

[$sort, $dir, $orderBy] = sort_state([
    'id' => 'u.user_id', 'username' => 'u.username', 'name' => 'u.full_name',
    'role' => 'r.role_name', 'status' => 'u.is_active', 'created' => 'u.created_at',
], 'id', 'u.user_id');

$data = paginate(
    'users u JOIN roles r ON r.role_id = u.role_id', $whereSql, $params,
    'u.user_id, u.username, u.full_name, u.is_active, u.created_at, r.role_name',
    $orderBy
);
$roles = db()->query('SELECT role_id AS id, role_name AS label FROM roles ORDER BY role_id')->fetchAll();
$states = [['id' => '1', 'label' => 'Active'], ['id' => '0', 'label' => 'Inactive']];

page_header('Users', 'users');
list_title('User accounts', '/users/form.php', 'Add user');
?>
<form class="row g-2 filter-bar" method="get">
    <div class="col-12 col-md-4"><input class="form-control" name="q" placeholder="Search username or name" value="<?= e($q) ?>"></div>
    <div class="col-6 col-md-3"><?= filter_select('role', $roles, $role, 'All roles') ?></div>
    <div class="col-6 col-md-3"><?= filter_select('active', $states, $active, 'Any status') ?></div>
    <?= filter_actions('/users/index.php') ?>
</form>
<div class="table-responsive bg-white">
    <table class="table table-striped table-hover align-middle">
        <thead><tr>
            <?= sort_th('ID', 'id', $sort, $dir) ?>
            <?= sort_th('Username', 'username', $sort, $dir) ?>
            <?= sort_th('Full name', 'name', $sort, $dir) ?>
            <?= sort_th('Role', 'role', $sort, $dir) ?>
            <?= sort_th('Status', 'status', $sort, $dir) ?>
            <?= sort_th('Created', 'created', $sort, $dir) ?>
            <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($data['rows'] as $r): ?>
            <tr>
                <td><?= (int) $r['user_id'] ?></td>
                <td><?= e($r['username']) ?></td>
                <td><?= e($r['full_name']) ?></td>
                <td><?= badge($r['role_name'], $r['role_name'] === 'Administrator' ? 'success' : 'secondary') ?></td>
                <td><?= $r['is_active'] ? badge('Active', 'success') : badge('Inactive', 'danger') ?></td>
                <td><?= e(substr((string) $r['created_at'], 0, 10)) ?></td>
                <td><a class="btn btn-sm btn-outline-secondary" href="/users/form.php?id=<?= (int) $r['user_id'] ?>">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$data['rows']): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<p class="text-muted small mt-2">Accounts are deactivated instead of deleted, so the history of who changed which record is kept.</p>
<div class="mt-2"><?= pager($data) ?></div>
<?php page_footer();
