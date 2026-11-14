<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$id = (int) in_get('id');
$st = db()->prepare(
    'SELECT p.*, fm.farm_name, fr.first_name, fr.last_name, c.crop_name, c.variety, ps.status_name,
            cu.full_name AS created_by_name, uu.full_name AS updated_by_name
       FROM planting p
       JOIN farm fm ON fm.farm_id = p.farm_id
       JOIN farmer fr ON fr.farmer_id = fm.farmer_id
       JOIN crop c ON c.crop_id = p.crop_id
       JOIN planting_statuses ps ON ps.status_id = p.status_id
       LEFT JOIN users cu ON cu.user_id = p.created_by
       LEFT JOIN users uu ON uu.user_id = p.updated_by
      WHERE p.planting_id = ?'
);
$st->execute([$id]);
$p = $st->fetch();
if (!$p) {
    flash('danger', 'Planting record not found.');
    redirect('/plantings/index.php');
}

$st = db()->prepare(
    'SELECT h.harvest_id, h.harvest_date, h.quantity_kg, h.selling_price_per_kg, g.grade_name, r.remark_description
       FROM harvest h
       JOIN quality_grades g ON g.grade_id = h.grade_id
       JOIN harvest_remarks r ON r.remark_id = h.remark_id
      WHERE h.planting_id = ? ORDER BY h.harvest_date'
);
$st->execute([$id]);
$harvests = $st->fetchAll();

page_header('Planting details', 'plantings');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Planting #<?= (int) $p['planting_id'] ?></h1>
    <div class="d-flex gap-2">
        <?php if (can('plantings', 'edit')): ?><a class="btn btn-outline-secondary" href="/plantings/form.php?id=<?= $id ?>">Edit</a><?php endif; ?>
        <a class="btn btn-outline-dark" href="/plantings/index.php">Back to list</a>
    </div>
</div>
<div class="card card-body mb-4">
    <?php detail_list(array_merge([
        'Farm'                  => $p['farm_name'] . ' (' . $p['first_name'] . ' ' . $p['last_name'] . ')',
        'Crop'                  => $p['crop_name'] . ' - ' . $p['variety'],
        'Planting date'         => $p['planting_date'],
        'Area planted (ha)'     => money($p['area_planted']),
        'Seed quantity'         => money($p['seed_quantity']),
        'Expected harvest date' => $p['expected_harvest_date'],
        'Status'                => $p['status_name'],
    ], audit_pairs($p))); ?>
</div>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h5 mb-0">Harvest records</h2>
    <?php if (can('harvests', 'create')): ?><a class="btn btn-sm btn-success" href="/harvests/form.php?planting_id=<?= $id ?>">+ Add harvest</a><?php endif; ?>
</div>
<div class="table-responsive bg-white border rounded">
    <table class="table table-striped mb-0">
        <thead class="table-light"><tr><th>ID</th><th>Date</th><th>Quantity (kg)</th><th>Price / kg</th><th>Grade</th><th>Remarks</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($harvests as $r): ?>
            <tr>
                <td><?= (int) $r['harvest_id'] ?></td><td><?= e($r['harvest_date']) ?></td><td><?= e(money($r['quantity_kg'])) ?></td>
                <td><?= e(money($r['selling_price_per_kg'])) ?></td><td><?= grade_badge($r['grade_name']) ?></td><td><?= e($r['remark_description']) ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="/harvests/view.php?id=<?= (int) $r['harvest_id'] ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$harvests): ?><tr><td colspan="7" class="text-center text-muted">No harvests recorded for this planting.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php page_footer();
