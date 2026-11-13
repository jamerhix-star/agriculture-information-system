<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$id = (int) in_get('id');
$st = db()->prepare(
    'SELECT c.*, cc.category_name, s.season_name, cu.full_name AS created_by_name, uu.full_name AS updated_by_name
       FROM crop c
       JOIN crop_categories cc ON cc.crop_category_id = c.crop_category_id
       JOIN seasons s ON s.season_id = c.season_id
       LEFT JOIN users cu ON cu.user_id = c.created_by
       LEFT JOIN users uu ON uu.user_id = c.updated_by
      WHERE c.crop_id = ?'
);
$st->execute([$id]);
$c = $st->fetch();
if (!$c) {
    flash('danger', 'Crop not found.');
    redirect('/crops/index.php');
}

$st = db()->prepare(
    'SELECT p.planting_id, p.planting_date, p.area_planted, f.farm_name, ps.status_name
       FROM planting p
       JOIN farm f ON f.farm_id = p.farm_id
       JOIN planting_statuses ps ON ps.status_id = p.status_id
      WHERE p.crop_id = ? ORDER BY p.planting_date DESC'
);
$st->execute([$id]);
$plantings = $st->fetchAll();

page_header('Crop details', 'crops');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e($c['crop_name'] . ' - ' . $c['variety']) ?></h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="/crops/form.php?id=<?= $id ?>">Edit</a>
        <a class="btn btn-outline-dark" href="/crops/index.php">Back to list</a>
    </div>
</div>
<div class="card card-body mb-4">
    <?php detail_list(array_merge([
        'Crop ID'            => $c['crop_id'],
        'Category'           => $c['category_name'],
        'Growing period'     => $c['growing_period_days'] . ' days',
        'Recommended season' => $c['season_name'],
    ], audit_pairs($c))); ?>
</div>
<h2 class="h5">Planting records for this crop</h2>
<div class="table-responsive bg-white border rounded">
    <table class="table table-striped mb-0">
        <thead class="table-light"><tr><th>ID</th><th>Farm</th><th>Planted</th><th>Area (ha)</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($plantings as $r): ?>
            <tr>
                <td><?= (int) $r['planting_id'] ?></td><td><?= e($r['farm_name']) ?></td><td><?= e($r['planting_date']) ?></td>
                <td><?= e(money($r['area_planted'])) ?></td><td><?= e($r['status_name']) ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="/plantings/view.php?id=<?= (int) $r['planting_id'] ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$plantings): ?><tr><td colspan="6" class="text-center text-muted">This crop has not been planted yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php page_footer();
