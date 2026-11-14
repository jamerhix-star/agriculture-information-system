<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$id = (int) in_get('id');
$editing = $id > 0;
require_permission('farmers', $editing ? 'edit' : 'create');
$row = ['first_name' => '', 'last_name' => '', 'contact_number' => '', 'address' => '', 'date_registered' => date('Y-m-d')];

if ($editing) {
    $found = model('farmer')->find($id);
    if (!$found) {
        flash('danger', 'Farmer not found.');
        redirect('/farmers/index.php');
    }
    $row = array_merge($row, $found);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row = [
        'first_name'      => in_post('first_name'),
        'last_name'       => in_post('last_name'),
        'contact_number'  => in_post('contact_number'),
        'address'         => in_post('address'),
        'date_registered' => in_post('date_registered'),
    ];

    if ($row['first_name'] === '') {
        $errors['first_name'] = 'First name is required.';
    } elseif (strlen($row['first_name']) > 50) {
        $errors['first_name'] = 'First name must be 50 characters or fewer.';
    }
    if ($row['last_name'] === '') {
        $errors['last_name'] = 'Last name is required.';
    } elseif (strlen($row['last_name']) > 50) {
        $errors['last_name'] = 'Last name must be 50 characters or fewer.';
    }
    if ($row['contact_number'] === '') {
        $errors['contact_number'] = 'Contact number is required.';
    } elseif (!preg_match('/^[0-9+() -]{7,20}$/', $row['contact_number'])) {
        $errors['contact_number'] = 'Use 7 to 20 characters: digits, spaces, + ( ) or -.';
    }
    if ($row['address'] === '') {
        $errors['address'] = 'Address is required.';
    } elseif (strlen($row['address']) > 150) {
        $errors['address'] = 'Address must be 150 characters or fewer.';
    }
    if (!valid_date($row['date_registered'])) {
        $errors['date_registered'] = 'Enter a valid date.';
    } elseif ($row['date_registered'] > date('Y-m-d')) {
        $errors['date_registered'] = 'Date registered cannot be in the future.';
    }

    if (!$errors) {
        try {
            $data = [
                'first_name'      => $row['first_name'],
                'last_name'       => $row['last_name'],
                'contact_number'  => $row['contact_number'],
                'address'         => $row['address'],
                'date_registered' => $row['date_registered'],
                'updated_by'      => user_id(),
            ];
            if ($editing) {
                model('farmer')->update($id, $data);
                flash('success', 'Farmer updated successfully.');
                redirect('/farmers/view.php?id=' . $id);
            }
            $data['created_by'] = user_id();
            $created = model('farmer')->create($data);
            flash('success', 'Farmer added successfully.');
            redirect('/farmers/view.php?id=' . (int) ($created['farmer_id'] ?? 0));
        } catch (PDOException $ex) {
            save_failed($ex, $errors, 'contact_number', 'A farmer with this name and contact number already exists.');
        }
    }
}

page_header($editing ? 'Edit farmer' : 'Add farmer', 'farmers');
?>
<h1 class="h3 mb-3"><?= $editing ? 'Edit farmer' : 'Add farmer' ?></h1>
<?php if (isset($errors['_form'])): ?><div class="alert alert-danger"><?= e($errors['_form']) ?></div><?php endif; ?>
<form method="post" novalidate class="card card-body" style="max-width:640px">
    <?= csrf_field() ?>
    <?= form_input('First name', 'first_name', $row, $errors) ?>
    <?= form_input('Last name', 'last_name', $row, $errors) ?>
    <?= form_input('Contact number', 'contact_number', $row, $errors) ?>
    <?= form_input('Address', 'address', $row, $errors) ?>
    <?= form_input('Date registered', 'date_registered', $row, $errors, 'date') ?>
    <div class="d-flex gap-2">
        <button class="btn btn-success">Save</button>
        <a class="btn btn-outline-secondary" href="/farmers/index.php">Cancel</a>
    </div>
</form>
<?php page_footer();
