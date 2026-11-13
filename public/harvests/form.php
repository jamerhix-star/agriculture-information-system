<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$id = (int) in_get('id');
$editing = $id > 0;
$row = ['planting_id' => in_get('planting_id'), 'harvest_date' => date('Y-m-d'), 'quantity_kg' => '', 'selling_price_per_kg' => '', 'grade_id' => '', 'remark_id' => ''];

if ($editing) {
    $st = db()->prepare('SELECT * FROM harvest WHERE harvest_id = ?');
    $st->execute([$id]);
    $found = $st->fetch();
    if (!$found) {
        flash('danger', 'Harvest record not found.');
        redirect('/harvests/index.php');
    }
    $row = array_merge($row, $found);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row = [
        'planting_id'          => in_post('planting_id'),
        'harvest_date'         => in_post('harvest_date'),
        'quantity_kg'          => in_post('quantity_kg'),
        'selling_price_per_kg' => in_post('selling_price_per_kg'),
        'grade_id'             => in_post('grade_id'),
        'remark_id'            => in_post('remark_id'),
    ];

    $plantingDate = null;
    if (!valid_fk('planting', 'planting_id', $row['planting_id'])) {
        $errors['planting_id'] = 'Select an existing planting record.';
    } else {
        $st = db()->prepare('SELECT planting_date FROM planting WHERE planting_id = ?');
        $st->execute([(int) $row['planting_id']]);
        $plantingDate = (string) $st->fetchColumn();
    }
    if (!valid_date($row['harvest_date'])) {
        $errors['harvest_date'] = 'Enter a valid harvest date.';
    } elseif ($plantingDate !== null && $row['harvest_date'] < $plantingDate) {
        $errors['harvest_date'] = 'Harvest date cannot be before the planting date (' . $plantingDate . ').';
    }
    if (!num_ok($row['quantity_kg'], 0.01, 99999999.99)) {
        $errors['quantity_kg'] = 'Enter a quantity greater than 0 (max. 2 decimals).';
    }
    if (!num_ok($row['selling_price_per_kg'], 0, 99999999.99)) {
        $errors['selling_price_per_kg'] = 'Enter a price of 0 or more (max. 2 decimals).';
    }
    if (!valid_fk('quality_grades', 'grade_id', $row['grade_id'])) {
        $errors['grade_id'] = 'Select a quality grade.';
    }
    if (!valid_fk('harvest_remarks', 'remark_id', $row['remark_id'])) {
        $errors['remark_id'] = 'Select a remark.';
    }

    if (!$errors) {
        try {
            $vals = [(int) $row['planting_id'], $row['harvest_date'], $row['quantity_kg'], $row['selling_price_per_kg'],
                     (int) $row['grade_id'], (int) $row['remark_id'], user_id()];
            if ($editing) {
                $sql = 'UPDATE harvest SET planting_id = ?, harvest_date = ?, quantity_kg = ?, selling_price_per_kg = ?, grade_id = ?, remark_id = ?, updated_by = ? WHERE harvest_id = ?';
                db()->prepare($sql)->execute(array_merge($vals, [$id]));
                flash('success', 'Harvest record updated successfully.');
                redirect('/harvests/view.php?id=' . $id);
            }
            $sql = 'INSERT INTO harvest (planting_id, harvest_date, quantity_kg, selling_price_per_kg, grade_id, remark_id, updated_by, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING harvest_id';
            $st = db()->prepare($sql);
            $st->execute(array_merge($vals, [user_id()]));
            flash('success', 'Harvest record added successfully.');
            redirect('/harvests/view.php?id=' . (int) $st->fetchColumn());
        } catch (PDOException $ex) {
            save_failed($ex, $errors);
        }
    }
}

$plantings = db()->query(
    "SELECT p.planting_id AS id,
            '#' || p.planting_id || ' - ' || fm.farm_name || ' / ' || c.crop_name || ' (planted ' || p.planting_date || ')' AS label
       FROM planting p JOIN farm fm ON fm.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id
      ORDER BY p.planting_id DESC"
)->fetchAll();

page_header($editing ? 'Edit harvest' : 'Add harvest', 'harvests');
?>
<h1 class="h3 mb-3"><?= $editing ? 'Edit harvest record' : 'Add harvest record' ?></h1>
<?php if (isset($errors['_form'])): ?><div class="alert alert-danger"><?= e($errors['_form']) ?></div><?php endif; ?>
<form method="post" novalidate class="card card-body" style="max-width:640px">
    <?= csrf_field() ?>
    <?= form_select('Planting record', 'planting_id', options($plantings, $row['planting_id'], '-- Select planting --'), $errors) ?>
    <?= form_input('Harvest date', 'harvest_date', $row, $errors, 'date') ?>
    <?= form_input('Quantity (kg)', 'quantity_kg', $row, $errors, 'number', 'step="0.01" min="0.01"') ?>
    <?= form_input('Selling price per kg', 'selling_price_per_kg', $row, $errors, 'number', 'step="0.01" min="0"') ?>
    <?= form_select('Quality grade', 'grade_id', options(lookup('grade'), $row['grade_id']), $errors) ?>
    <?= form_select('Remarks', 'remark_id', options(lookup('remark'), $row['remark_id']), $errors) ?>
    <div class="d-flex gap-2">
        <button class="btn btn-success">Save</button>
        <a class="btn btn-outline-secondary" href="/harvests/index.php">Cancel</a>
    </div>
</form>
<?php page_footer();
