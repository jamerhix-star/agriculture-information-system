<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$id = (int) in_get('id');
$editing = $id > 0;
require_permission('plantings', $editing ? 'edit' : 'create');
$row = ['farm_id' => '', 'crop_id' => '', 'planting_date' => date('Y-m-d'), 'area_planted' => '', 'seed_quantity' => '', 'expected_harvest_date' => '', 'status_id' => '1'];

if ($editing) {
    $st = db()->prepare('SELECT * FROM planting WHERE planting_id = ?');
    $st->execute([$id]);
    $found = $st->fetch();
    if (!$found) {
        flash('danger', 'Planting record not found.');
        redirect('/plantings/index.php');
    }
    $row = array_merge($row, $found);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row = [
        'farm_id'               => in_post('farm_id'),
        'crop_id'               => in_post('crop_id'),
        'planting_date'         => in_post('planting_date'),
        'area_planted'          => in_post('area_planted'),
        'seed_quantity'         => in_post('seed_quantity'),
        'expected_harvest_date' => in_post('expected_harvest_date'),
        'status_id'             => in_post('status_id'),
    ];

    $farmArea = null;
    if (!valid_fk('farm', 'farm_id', $row['farm_id'])) {
        $errors['farm_id'] = 'Select an existing farm.';
    } else {
        $st = db()->prepare('SELECT area_hectares FROM farm WHERE farm_id = ?');
        $st->execute([(int) $row['farm_id']]);
        $farmArea = (float) $st->fetchColumn();
    }
    if (!valid_fk('crop', 'crop_id', $row['crop_id'])) {
        $errors['crop_id'] = 'Select an existing crop.';
    }
    if (!valid_date($row['planting_date'])) {
        $errors['planting_date'] = 'Enter a valid planting date.';
    }
    if (!num_ok($row['area_planted'], 0.01, 999999.99)) {
        $errors['area_planted'] = 'Enter a number from 0.01 to 999999.99 (max. 2 decimals).';
    } elseif ($farmArea !== null && (float) $row['area_planted'] > $farmArea) {
        $errors['area_planted'] = 'Area planted cannot be larger than the farm area (' . money($farmArea) . ' ha).';
    }
    if (!num_ok($row['seed_quantity'], 0.01, 999999.99)) {
        $errors['seed_quantity'] = 'Enter a number from 0.01 to 999999.99 (max. 2 decimals).';
    }
    if (!valid_date($row['expected_harvest_date'])) {
        $errors['expected_harvest_date'] = 'Enter a valid expected harvest date.';
    } elseif (!isset($errors['planting_date']) && $row['expected_harvest_date'] <= $row['planting_date']) {
        $errors['expected_harvest_date'] = 'Expected harvest date must be after the planting date.';
    }
    if (!valid_fk('planting_statuses', 'status_id', $row['status_id'])) {
        $errors['status_id'] = 'Select a status.';
    }

    if (!$errors) {
        try {
            $vals = [(int) $row['farm_id'], (int) $row['crop_id'], $row['planting_date'], $row['area_planted'],
                     $row['seed_quantity'], $row['expected_harvest_date'], (int) $row['status_id'], user_id()];
            if ($editing) {
                $sql = 'UPDATE planting SET farm_id = ?, crop_id = ?, planting_date = ?, area_planted = ?, seed_quantity = ?, expected_harvest_date = ?, status_id = ?, updated_by = ? WHERE planting_id = ?';
                db()->prepare($sql)->execute(array_merge($vals, [$id]));
                flash('success', 'Planting record updated successfully.');
                redirect('/plantings/view.php?id=' . $id);
            }
            $sql = 'INSERT INTO planting (farm_id, crop_id, planting_date, area_planted, seed_quantity, expected_harvest_date, status_id, updated_by, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING planting_id';
            $st = db()->prepare($sql);
            $st->execute(array_merge($vals, [user_id()]));
            flash('success', 'Planting record added successfully.');
            redirect('/plantings/view.php?id=' . (int) $st->fetchColumn());
        } catch (PDOException $ex) {
            save_failed($ex, $errors);
        }
    }
}

$farms = db()->query("SELECT fm.farm_id AS id, fm.farm_name || ' (' || fr.last_name || ')' AS label FROM farm fm JOIN farmer fr ON fr.farmer_id = fm.farmer_id ORDER BY fm.farm_name")->fetchAll();
$crops = db()->query("SELECT crop_id AS id, crop_name || ' - ' || variety AS label FROM crop ORDER BY crop_name, variety")->fetchAll();
$cropDays = [];
foreach (db()->query('SELECT crop_id, growing_period_days FROM crop')->fetchAll() as $c) {
    $cropDays[(int) $c['crop_id']] = (int) $c['growing_period_days'];
}

page_header($editing ? 'Edit planting' : 'Add planting', 'plantings');
?>
<h1 class="h3 mb-3"><?= $editing ? 'Edit planting record' : 'Add planting record' ?></h1>
<?php if (isset($errors['_form'])): ?><div class="alert alert-danger"><?= e($errors['_form']) ?></div><?php endif; ?>
<form method="post" novalidate class="card card-body" style="max-width:640px">
    <?= csrf_field() ?>
    <?= form_select('Farm', 'farm_id', options($farms, $row['farm_id'], '-- Select farm --'), $errors) ?>
    <?= form_select('Crop', 'crop_id', options($crops, $row['crop_id'], '-- Select crop --'), $errors) ?>
    <?= form_input('Planting date', 'planting_date', $row, $errors, 'date') ?>
    <?= form_input('Area planted (hectares)', 'area_planted', $row, $errors, 'number', 'step="0.01" min="0.01"') ?>
    <?= form_input('Seed quantity', 'seed_quantity', $row, $errors, 'number', 'step="0.01" min="0.01"') ?>
    <?= form_input('Expected harvest date', 'expected_harvest_date', $row, $errors, 'date') ?>
    <?= form_select('Status', 'status_id', options(lookup('status'), $row['status_id']), $errors) ?>
    <div class="d-flex gap-2">
        <button class="btn btn-success">Save</button>
        <a class="btn btn-outline-secondary" href="/plantings/index.php">Cancel</a>
    </div>
</form>
<script>
// Convenience only: suggests the expected harvest date (planting date + crop growing days). The server validates again.
(function () {
    var days = <?= json_encode($cropDays, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var crop = document.getElementById('crop_id'), pd = document.getElementById('planting_date'), ex = document.getElementById('expected_harvest_date');
    if (!crop || !pd || !ex) { return; }
    var auto = ex.value === '';
    ex.addEventListener('input', function () { auto = false; });
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function fill() {
        var d = days[crop.value];
        if (!auto || !d || !pd.value) { return; }
        var dt = new Date(pd.value + 'T00:00:00');
        dt.setDate(dt.getDate() + d);
        ex.value = dt.getFullYear() + '-' + pad(dt.getMonth() + 1) + '-' + pad(dt.getDate());
    }
    crop.addEventListener('change', fill);
    pd.addEventListener('change', fill);
})();
</script>
<?php page_footer();
