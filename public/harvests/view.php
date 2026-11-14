<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$id = (int) in_get('id');
$st = db()->prepare(
    'SELECT h.*, p.planting_date, fm.farm_name, c.crop_name, c.variety, g.grade_name, r.remark_description,
            (h.quantity_kg * h.selling_price_per_kg) AS est_value,
            cu.full_name AS created_by_name, uu.full_name AS updated_by_name
       FROM harvest h
       JOIN planting p ON p.planting_id = h.planting_id
       JOIN farm fm ON fm.farm_id = p.farm_id
       JOIN crop c ON c.crop_id = p.crop_id
       JOIN quality_grades g ON g.grade_id = h.grade_id
       JOIN harvest_remarks r ON r.remark_id = h.remark_id
       LEFT JOIN users cu ON cu.user_id = h.created_by
       LEFT JOIN users uu ON uu.user_id = h.updated_by
      WHERE h.harvest_id = ?'
);
$st->execute([$id]);
$h = $st->fetch();
if (!$h) {
    flash('danger', 'Harvest record not found.');
    redirect('/harvests/index.php');
}

page_header('Harvest details', 'harvests');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Harvest #<?= (int) $h['harvest_id'] ?></h1>
    <div class="d-flex gap-2">
        <?php if (can('harvests', 'edit')): ?><a class="btn btn-outline-secondary" href="/harvests/form.php?id=<?= $id ?>">Edit</a><?php endif; ?>
        <a class="btn btn-outline-dark" href="/harvests/index.php">Back to list</a>
    </div>
</div>
<div class="card card-body">
    <?php detail_list(array_merge([
        'Planting record'      => '#' . $h['planting_id'] . ' - ' . $h['farm_name'] . ' / ' . $h['crop_name'] . ' - ' . $h['variety'] . ' (planted ' . $h['planting_date'] . ')',
        'Harvest date'         => $h['harvest_date'],
        'Quantity (kg)'        => money($h['quantity_kg']),
        'Selling price per kg' => money($h['selling_price_per_kg']),
        'Estimated value'      => money($h['est_value']),
        'Quality grade'        => $h['grade_name'],
        'Remarks'              => $h['remark_description'],
    ], audit_pairs($h))); ?>
</div>
<p class="mt-3"><a href="/plantings/view.php?id=<?= (int) $h['planting_id'] ?>">View the planting record</a></p>
<?php page_footer();
