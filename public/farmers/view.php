<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$id = (int) in_get('id');
$st = db()->prepare(
    'SELECT f.*, cu.full_name AS created_by_name, uu.full_name AS updated_by_name
       FROM farmer f
       LEFT JOIN users cu ON cu.user_id = f.created_by
       LEFT JOIN users uu ON uu.user_id = f.updated_by
      WHERE f.farmer_id = ?'
);
$st->execute([$id]);
$f = $st->fetch();
if (!$f) {
    flash('danger', 'Farmer not found.');
    redirect('/farmers/index.php');
}

$st = db()->prepare(
    'SELECT fm.farm_id, fm.farm_name, fm.location, fm.area_hectares, s.soil_type_name, i.irrigation_type_name
       FROM farm fm
       JOIN soil_types s ON s.soil_type_id = fm.soil_type_id
       JOIN irrigation_types i ON i.irrigation_type_id = fm.irrigation_type_id
      WHERE fm.farmer_id = ? ORDER BY fm.farm_name'
);
$st->execute([$id]);
$farms = $st->fetchAll();

page_header('Farmer details', 'farmers');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e($f['first_name'] . ' ' . $f['last_name']) ?></h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="/farmers/form.php?id=<?= $id ?>">Edit</a>
        <a class="btn btn-outline-dark" href="/farmers/index.php">Back to list</a>
    </div>
</div>
<div class="card card-body mb-4">
    <?php detail_list(array_merge([
        'Farmer ID'       => $f['farmer_id'],
        'Contact number'  => $f['contact_number'],
        'Address'         => $f['address'],
        'Date registered' => $f['date_registered'],
    ], audit_pairs($f))); ?>
</div>
<h2 class="h5">Farms owned</h2>
<div class="table-responsive bg-white border rounded">
    <table class="table table-striped mb-0">
        <thead class="table-light"><tr><th>Farm</th><th>Location</th><th>Area (ha)</th><th>Soil</th><th>Irrigation</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($farms as $r): ?>
            <tr>
                <td><?= e($r['farm_name']) ?></td><td><?= e($r['location']) ?></td><td><?= e(money($r['area_hectares'])) ?></td>
                <td><?= e($r['soil_type_name']) ?></td><td><?= e($r['irrigation_type_name']) ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="/farms/view.php?id=<?= (int) $r['farm_id'] ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$farms): ?><tr><td colspan="6" class="text-center text-muted">This farmer has no farms yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php page_footer();
