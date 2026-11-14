<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$id = (int) in_get('id');
$editing = $id > 0;
require_permission('crops', $editing ? 'edit' : 'create');
$row = ['crop_name' => '', 'variety' => '', 'crop_category_id' => '', 'growing_period_days' => '', 'season_id' => ''];

if ($editing) {
    $found = model('crop')->find($id);
    if (!$found) {
        flash('danger', 'Crop not found.');
        redirect('/crops/index.php');
    }
    $row = array_merge($row, $found);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row = [
        'crop_name'           => in_post('crop_name'),
        'variety'             => in_post('variety'),
        'crop_category_id'    => in_post('crop_category_id'),
        'growing_period_days' => in_post('growing_period_days'),
        'season_id'           => in_post('season_id'),
    ];

    if ($row['crop_name'] === '') {
        $errors['crop_name'] = 'Crop name is required.';
    } elseif (strlen($row['crop_name']) > 80) {
        $errors['crop_name'] = 'Crop name must be 80 characters or fewer.';
    }
    if ($row['variety'] === '') {
        $errors['variety'] = 'Variety is required.';
    } elseif (strlen($row['variety']) > 80) {
        $errors['variety'] = 'Variety must be 80 characters or fewer.';
    }
    if (!valid_fk('crop_categories', 'crop_category_id', $row['crop_category_id'])) {
        $errors['crop_category_id'] = 'Select a crop category.';
    }
    if (!ctype_digit($row['growing_period_days']) || (int) $row['growing_period_days'] < 1 || (int) $row['growing_period_days'] > 1000) {
        $errors['growing_period_days'] = 'Enter a whole number of days from 1 to 1000.';
    }
    if (!valid_fk('seasons', 'season_id', $row['season_id'])) {
        $errors['season_id'] = 'Select a recommended season.';
    }

    if (!$errors) {
        try {
            $data = [
                'crop_name'           => $row['crop_name'],
                'variety'             => $row['variety'],
                'crop_category_id'    => (int) $row['crop_category_id'],
                'growing_period_days' => (int) $row['growing_period_days'],
                'season_id'           => (int) $row['season_id'],
                'updated_by'          => user_id(),
            ];
            if ($editing) {
                model('crop')->update($id, $data);
                flash('success', 'Crop updated successfully.');
                redirect('/crops/view.php?id=' . $id);
            }
            $data['created_by'] = user_id();
            $created = model('crop')->create($data);
            flash('success', 'Crop added successfully.');
            redirect('/crops/view.php?id=' . (int) ($created['crop_id'] ?? 0));
        } catch (PDOException $ex) {
            save_failed($ex, $errors, 'variety', 'This crop and variety already exist.');
        }
    }
}

page_header($editing ? 'Edit crop' : 'Add crop', 'crops');
?>
<h1 class="h3 mb-3"><?= $editing ? 'Edit crop' : 'Add crop' ?></h1>
<?php if (isset($errors['_form'])): ?><div class="alert alert-danger"><?= e($errors['_form']) ?></div><?php endif; ?>
<form method="post" novalidate class="card card-body" style="max-width:640px">
    <?= csrf_field() ?>
    <?= form_input('Crop name', 'crop_name', $row, $errors) ?>
    <?= form_input('Variety', 'variety', $row, $errors) ?>
    <?= form_select('Category', 'crop_category_id', options(lookup('category'), $row['crop_category_id']), $errors) ?>
    <?= form_input('Growing period (days)', 'growing_period_days', $row, $errors, 'number', 'step="1" min="1" max="1000"') ?>
    <?= form_select('Recommended season', 'season_id', options(lookup('season'), $row['season_id']), $errors) ?>
    <div class="d-flex gap-2">
        <button class="btn btn-success">Save</button>
        <a class="btn btn-outline-secondary" href="/crops/index.php">Cancel</a>
    </div>
</form>
<?php page_footer();
