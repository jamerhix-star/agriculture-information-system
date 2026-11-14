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
<div class="login-bg" style="position:fixed; inset:0; z-index:2000; overflow:auto;">
    <div class="card login-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <h1 class="h4 mb-1">Agriculture Information System</h1>
                <p class="text-muted mb-0">Sign in to continue</p>
            </div>
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger py-2"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input class="form-control form-control-lg" id="username" name="username" value="<?= e($username) ?>" autocomplete="username" autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control form-control-lg" type="password" id="password" name="password" autocomplete="current-password">
                </div>
                <button class="btn btn-success btn-lg w-100">Login</button>
            </form>
        </div>
    </div>
</div>
<?php page_footer();
