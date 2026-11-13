<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$id = (int) in_get('id');
$st = db()->prepare(
    'SELECT fm.*, fr.first_name, fr.last_name, s.soil_type_name, i.irrigation_type_name,
            cu.full_name AS created_by_name, uu.full_name AS updated_by_name
       FROM farm fm
       JOIN farmer fr ON fr.farmer_id = fm.farmer_id
       JOIN soil_types s ON s.soil_type_id = fm.soil_type_id
       JOIN irrigation_types i ON i.irrigation_type_id = fm.irrigation_type_id
       LEFT JOIN users cu ON cu.user_id = fm.created_by
       LEFT JOIN users uu ON uu.user_id = fm.updated_by
      WHERE fm.farm_id = ?'
);
$st->execute([$id]);
$f = $st->fetch();
if (!$f) {
    flash('danger', 'Farm not found.');
    redirect('/farms/index.php');
}

$st = db()->prepare(
    'SELECT p.planting_id, p.planting_date, p.area_planted, p.expected_harvest_date, c.crop_name, c.variety, ps.status_name
       FROM planting p
       JOIN crop c ON c.crop_id = p.crop_id
       JOIN planting_statuses ps ON ps.status_id = p.status_id
      WHERE p.farm_id = ? ORDER BY p.planting_date DESC'
);
$st->execute([$id]);
$plantings = $st->fetchAll();

page_header('Farm details', 'farms');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e($f['farm_name']) ?></h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="/farms/form.php?id=<?= $id ?>">Edit</a>
        <a class="btn btn-outline-dark" href="/farms/index.php">Back to list</a>
    </div>
</div>
<div class="card card-body mb-4">
    <?php detail_list(array_merge([
        'Farm ID'    => $f['farm_id'],
        'Owner'      => $f['first_name'] . ' ' . $f['last_name'],
        'Location'   => $f['location'],
        'Area (ha)'  => money($f['area_hectares']),
        'Soil type'  => $f['soil_type_name'],
        'Irrigation' => $f['irrigation_type_name'],
    ], audit_pairs($f))); ?>
</div>
<h2 class="h5">Planting records</h2>
<div class="table-responsive bg-white border rounded">
    <table class="table table-striped mb-0">
        <thead class="table-light"><tr><th>ID</th><th>Crop</th><th>Planted</th><th>Area (ha)</th><th>Expected harvest</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($plantings as $r): ?>
            <tr>
                <td><?= (int) $r['planting_id'] ?></td><td><?= e($r['crop_name'] . ' - ' . $r['variety']) ?></td>
                <td><?= e($r['planting_date']) ?></td><td><?= e(money($r['area_planted'])) ?></td>
                <td><?= e($r['expected_harvest_date']) ?></td><td><?= e($r['status_name']) ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="/plantings/view.php?id=<?= (int) $r['planting_id'] ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$plantings): ?><tr><td colspan="7" class="text-center text-muted">No planting records for this farm.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php page_footer();
