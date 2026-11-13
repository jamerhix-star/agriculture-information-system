<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

if (current_user()) {
    redirect('/index.php');
}

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = in_post('username');
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $st = db()->prepare(
            'SELECT u.user_id, u.username, u.full_name, u.password_hash, u.role_id, r.role_name
               FROM users u JOIN roles r ON r.role_id = u.role_id
              WHERE lower(u.username) = lower(?) AND u.is_active'
        );
        $st->execute([$username]);
        $u = $st->fetch();

        if ($u && password_verify($password, $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'        => (int) $u['user_id'],
                'username'  => $u['username'],
                'full_name' => $u['full_name'],
                'role_id'   => (int) $u['role_id'],
                'role_name' => $u['role_name'],
            ];
            redirect('/index.php');
        }
        $error = 'Invalid username or password.';
    }
}

page_header('Login', '', false);
?>
<div class="row justify-content-center mt-5">
    <div class="col-12 col-sm-8 col-md-5 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 text-center mb-1">Agriculture Information System</h1>
                <p class="text-center text-muted">Please sign in</p>
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <input class="form-control" id="username" name="username" value="<?= e($username) ?>" autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" type="password" id="password" name="password">
                    </div>
                    <button class="btn btn-success w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php page_footer();
