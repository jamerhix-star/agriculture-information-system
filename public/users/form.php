<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$id = (int) in_get('id');
$editing = $id > 0;
require_permission('users', $editing ? 'edit' : 'create');

$row = ['username' => '', 'full_name' => '', 'role_id' => '2', 'is_active' => '1'];
if ($editing) {
    $found = model('user')->find($id);
    if (!$found) {
        flash('danger', 'User not found.');
        redirect('/users/index.php');
    }
    $row = array_merge($row, $found);
    $row['is_active'] = $found['is_active'] ? '1' : '0';
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row = [
        'username'  => in_post('username'),
        'full_name' => in_post('full_name'),
        'role_id'   => in_post('role_id'),
        'is_active' => in_post('is_active'),
    ];
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $confirm = isset($_POST['password_confirm']) && is_string($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

    if ($row['username'] === '') {
        $errors['username'] = 'Username is required.';
    } elseif (strlen($row['username']) < 3 || strlen($row['username']) > 50) {
        $errors['username'] = 'Username must be 3 to 50 characters.';
    } elseif (!preg_match('/^[A-Za-z0-9._@+-]+$/', $row['username'])) {
        $errors['username'] = 'Use only letters, numbers and . _ @ + -';
    }
    if ($row['full_name'] === '') {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($row['full_name']) > 100) {
        $errors['full_name'] = 'Full name must be 100 characters or fewer.';
    }
    if (!valid_fk('roles', 'role_id', $row['role_id'])) {
        $errors['role_id'] = 'Select a role.';
    }
    if ($row['is_active'] !== '1' && $row['is_active'] !== '0') {
        $errors['is_active'] = 'Select a status.';
    }
    if ($editing && $id === user_id()) {
        // protect against locking yourself out
        if ($row['is_active'] !== '1') {
            $errors['is_active'] = 'You cannot deactivate your own account.';
        }
        if ($row['role_id'] !== '1') {
            $errors['role_id'] = 'You cannot remove your own administrator role.';
        }
    }
    if (!$editing || $password !== '' || $confirm !== '') {
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors['password'] = 'Password must be 8 to 72 characters.';
        } elseif ($password !== $confirm) {
            $errors['password_confirm'] = 'The passwords do not match.';
        }
    }

    if (!$errors) {
        try {
            if ($editing) {
                $data = [
                    'username'  => $row['username'],
                    'full_name' => $row['full_name'],
                    'role_id'   => (int) $row['role_id'],
                    'is_active' => $row['is_active'] === '1',
                ];
                if ($password !== '') {
                    $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                }
                model('user')->update($id, $data);
                if ($id === user_id()) {
                    $_SESSION['user']['full_name'] = $row['full_name'];
                    $_SESSION['user']['username'] = $row['username'];
                }
                flash('success', 'User updated successfully.');
            } else {
                $data = [
                    'username'      => $row['username'],
                    'full_name'     => $row['full_name'],
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role_id'       => (int) $row['role_id'],
                    'is_active'     => $row['is_active'] === '1',
                ];
                model('user')->create($data);
                flash('success', 'User added successfully.');
            }
            redirect('/users/index.php');
        } catch (PDOException $ex) {
            save_failed($ex, $errors, 'username', 'This username is already taken.');
        }
    }
}

$roles = db()->query('SELECT role_id AS id, role_name AS label FROM roles ORDER BY role_id')->fetchAll();
$states = [['id' => '1', 'label' => 'Active'], ['id' => '0', 'label' => 'Inactive']];

page_header($editing ? 'Edit user' : 'Add user', 'users');
?>
<div class="page-head"><h1><?= $editing ? 'Edit user' : 'Add user' ?></h1></div>
<?php if (isset($errors['_form'])): ?><div class="alert alert-danger"><?= e($errors['_form']) ?></div><?php endif; ?>
<form method="post" novalidate class="card card-body" style="max-width:640px">
    <?= csrf_field() ?>
    <?= form_input('Full name', 'full_name', $row, $errors) ?>
    <?= form_input('Username', 'username', $row, $errors) ?>
    <?= form_select('Role', 'role_id', options($roles, $row['role_id']), $errors) ?>
    <?= form_select('Status', 'is_active', options($states, $row['is_active']), $errors) ?>
    <?= form_input($editing ? 'New password (leave blank to keep the current one)' : 'Password', 'password', [], $errors, 'password', 'autocomplete="new-password"') ?>
    <?= form_input('Confirm password', 'password_confirm', [], $errors, 'password', 'autocomplete="new-password"') ?>
    <div class="d-flex gap-2">
        <button class="btn btn-success">Save</button>
        <a class="btn btn-outline-secondary" href="/users/index.php">Cancel</a>
    </div>
</form>
<?php page_footer();
