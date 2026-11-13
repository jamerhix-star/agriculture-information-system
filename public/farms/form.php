<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$id = (int) in_get('id');
$editing = $id > 0;
$row = ['farmer_id' => '', 'farm_name' => '', 'location' => '', 'area_hectares' => '', 'soil_type_id' => '', 'irrigation_type_id' => ''];

if ($editing) {
    $st = db()->prepare('SELECT * FROM farm WHERE farm_id = ?');
    $st->execute([$id]);
    $found = $st->fetch();
    if (!$found) {
        flash('danger', 'Farm not found.');
        redirect('/farms/index.php');
    }
    $row = array_merge($row, $found);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row = [
        'farmer_id'          => in_post('farmer_id'),
        'farm_name'          => in_post('farm_name'),
        'location'           => in_post('location'),
        'area_hectares'      => in_post('area_hectares'),
        'soil_type_id'       => in_post('soil_type_id'),
        'irrigation_type_id' => in_post('irrigation_type_id'),
    ];

    if (!valid_fk('farmer', 'farmer_id', $row['farmer_id'])) {
        $errors['farmer_id'] = 'Select an existing farmer.';
    }
    if ($row['farm_name'] === '') {
        $errors['farm_name'] = 'Farm name is required.';
    } elseif (strlen($row['farm_name']) > 100) {
        $errors['farm_name'] = 'Farm name must be 100 characters or fewer.';
    }
    if ($row['location'] === '') {
        $errors['location'] = 'Location is required.';
    } elseif (strlen($row['location']) > 150) {
        $errors['location'] = 'Location must be 150 characters or fewer.';
    }
    if (!num_ok($row['area_hectares'], 0.01, 999999.99)) {
        $errors['area_hectares'] = 'Enter a number from 0.01 to 999999.99 (max. 2 decimals).';
    }
    if (!valid_fk('soil_types', 'soil_type_id', $row['soil_type_id'])) {
        $errors['soil_type_id'] = 'Select a soil type.';
    }
    if (!valid_fk('irrigation_types', 'irrigation_type_id', $row['irrigation_type_id'])) {
        $errors['irrigation_type_id'] = 'Select an irrigation type.';
    }

    if (!$errors) {
        try {
            $vals = [(int) $row['farmer_id'], $row['farm_name'], $row['location'], $row['area_hectares'],
                     (int) $row['soil_type_id'], (int) $row['irrigation_type_id'], user_id()];
            if ($editing) {
                $sql = 'UPDATE farm SET farmer_id = ?, farm_name = ?, location = ?, area_hectares = ?, soil_type_id = ?, irrigation_type_id = ?, updated_by = ? WHERE farm_id = ?';
                db()->prepare($sql)->execute(array_merge($vals, [$id]));
                flash('success', 'Farm updated successfully.');
                redirect('/farms/view.php?id=' . $id);
            }
            $sql = 'INSERT INTO farm (farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id, updated_by, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING farm_id';
            $st = db()->prepare($sql);
            $st->execute(array_merge($vals, [user_id()]));
            flash('success', 'Farm added successfully.');
            redirect('/farms/view.php?id=' . (int) $st->fetchColumn());
        } catch (PDOException $ex) {
            save_failed($ex, $errors, 'farm_name', 'This farmer already has a farm with that name.');
        }
    }
}

$farmers = db()->query("SELECT farmer_id AS id, last_name || ', ' || first_name AS label FROM farmer ORDER BY last_name, first_name")->fetchAll();

page_header($editing ? 'Edit farm' : 'Add farm', 'farms');
?>
<h1 class="h3 mb-3"><?= $editing ? 'Edit farm' : 'Add farm' ?></h1>
<?php if (isset($errors['_form'])): ?><div class="alert alert-danger"><?= e($errors['_form']) ?></div><?php endif; ?>
<form method="post" novalidate class="card card-body" style="max-width:640px">
    <?= csrf_field() ?>
    <?= form_select('Farmer (owner)', 'farmer_id', options($farmers, $row['farmer_id'], '-- Select farmer --'), $errors) ?>
    <?= form_input('Farm name', 'farm_name', $row, $errors) ?>
    <?= form_input('Location', 'location', $row, $errors) ?>
    <?= form_input('Area (hectares)', 'area_hectares', $row, $errors, 'number', 'step="0.01" min="0.01"') ?>
    <?= form_select('Soil type', 'soil_type_id', options(lookup('soil'), $row['soil_type_id']), $errors) ?>
    <?= form_select('Irrigation type', 'irrigation_type_id', options(lookup('irrigation'), $row['irrigation_type_id']), $errors) ?>
    <div class="d-flex gap-2">
        <button class="btn btn-success">Save</button>
        <a class="btn btn-outline-secondary" href="/farms/index.php">Cancel</a>
    </div>
</form>
<?php page_footer();
