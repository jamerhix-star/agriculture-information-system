<?php
/** Shared page layout: Bootstrap 5 (CDN) + a small custom theme, sidebar navigation, flash messages. */

function page_header(string $title, string $active = '', bool $nav = true): void
{
    $items = [
        'dashboard' => ['/index.php', 'Dashboard', '', ''],
        'farmers'   => ['/farmers/index.php', 'Farmers', '', 'farmers'],
        'farms'     => ['/farms/index.php', 'Farms', '', 'farms'],
        'crops'     => ['/crops/index.php', 'Crops', '', 'crops'],
        'plantings' => ['/plantings/index.php', 'Plantings', '', 'plantings'],
        'harvests'  => ['/harvests/index.php', 'Harvests', '', 'harvests'],
        'reports'   => ['/reports.php', 'Reports', '', 'reports'],
        'users'     => ['/users/index.php', 'Users', '', 'users'],
    ];
    $user = current_user();
    $showNav = $nav && $user;
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | Agriculture Information System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --g900:#14532d; --g800:#166534; --g700:#15803d; --g600:#16a34a; --g100:#dcfce7; --g50:#f0fdf4; --ink:#1f2937; --muted:#6b7280; --bg:#f3f6f4; }
        body { background: var(--bg); color: var(--ink); font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        a { color: var(--g700); }
        .btn-success { --bs-btn-bg:#16a34a; --bs-btn-border-color:#16a34a; --bs-btn-hover-bg:#15803d; --bs-btn-hover-border-color:#15803d; --bs-btn-active-bg:#166534; --bs-btn-active-border-color:#166534; }
        .btn, .form-control, .form-select { border-radius: 10px; }
        .form-control:focus, .form-select:focus { border-color: var(--g600); box-shadow: 0 0 0 .2rem rgba(22,163,74,.18); }
        .form-label { font-weight: 600; font-size: .9rem; }

        /* sidebar */
        .sidebar { position: fixed; top: 0; bottom: 0; left: 0; width: 250px; z-index: 1040; display: flex; flex-direction: column;
                   background: linear-gradient(180deg, var(--g900), var(--g800)); color: #fff; transform: translateX(-100%); transition: transform .25s ease; }
        .sidebar.open { transform: none; }
        .sidebar .brand { padding: 1.4rem 1.25rem 1rem; font-weight: 700; font-size: 1.2rem; letter-spacing: .02em; }
        .sidebar .brand small { display: block; font-weight: 400; font-size: .75rem; color: #bbf7d0; }
        .sidebar .menu { flex: 1; overflow-y: auto; padding-bottom: 1rem; }
        .sidebar a.nav-item { display: flex; align-items: center; padding: .62rem 1.1rem; margin: .12rem .75rem; border-radius: 10px;
                              color: #d1fae5; text-decoration: none; font-size: .95rem; }
        .sidebar a.nav-item:hover { background: rgba(255,255,255,.12); color: #fff; }
        .sidebar a.nav-item.active { background: #fff; color: var(--g900); font-weight: 600; box-shadow: inset 4px 0 0 var(--g600); }
        .sidebar .userbox { padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,.15); font-size: .85rem; }
        .sidebar .userbox .name { font-weight: 600; }
        .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 1030; }
        .overlay.show { display: block; }
        .topbar { position: sticky; top: 0; z-index: 1020; display: flex; align-items: center; gap: .75rem; padding: .6rem 1rem; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        @media (min-width: 992px) { .sidebar { transform: none; } .has-nav .content { margin-left: 250px; } }
        .content-inner { padding: 1.25rem; max-width: 1400px; margin: 0 auto; }
        @media (min-width: 992px) { .content-inner { padding: 2rem 2.25rem; } }

        /* page header, cards, tables */
        .page-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
        .page-head h1, h1.h3 { font-size: 1.55rem; font-weight: 700; color: var(--g900); margin: 0; }
        .card { border: 0; border-radius: 14px; box-shadow: 0 1px 3px rgba(16,24,40,.06), 0 6px 18px rgba(16,24,40,.05); }
        .card-header { background: transparent; border-bottom: 1px solid #eef2ef; font-weight: 600; padding: .9rem 1.1rem; }
        .table-responsive.bg-white { border: 0 !important; border-radius: 14px !important; box-shadow: 0 1px 3px rgba(16,24,40,.06), 0 6px 18px rgba(16,24,40,.05); }
        .table { --bs-table-striped-bg: #f8fbf9; --bs-table-hover-bg: #eefaf1; margin-bottom: 0; }
        .table thead th { background: var(--g50); color: var(--g900); font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--g100); white-space: nowrap; padding: .85rem .9rem; }
        .table thead th a { color: inherit !important; }
        .table td { padding: .75rem .9rem; vertical-align: middle; }
        .filter-bar { background: #fff; border-radius: 14px; padding: .85rem; box-shadow: 0 1px 3px rgba(16,24,40,.06); margin-bottom: 1rem; }
        .pagination .page-link { color: var(--g700); border-radius: 8px; margin: 0 2px; }
        .pagination .active .page-link { background: var(--g600); border-color: var(--g600); color: #fff; }
        .alert { border: 0; border-radius: 12px; }
        dt { color: var(--muted); font-weight: 600; }
        dd { margin-bottom: .6rem; }

        /* dashboard */
        .stat { display: block; padding: 1.1rem 1.25rem; }
        .stat .ico { width: 52px; height: 52px; border-radius: 14px; display: grid; place-items: center; font-size: 1.6rem; flex: none; }
        .stat .num { font-size: 1.9rem; font-weight: 700; line-height: 1; }
        .stat .lbl { color: var(--muted); font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; }
        a.stat-link { text-decoration: none; color: inherit; display: block; transition: transform .15s; }
        a.stat-link:hover { transform: translateY(-2px); }
        .bar-track { background: #e8efe9; border-radius: 99px; height: 10px; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--g600), #4ade80); }

        /* login */
        .login-bg { min-height: 100vh; display: grid; place-items: center; padding: 1rem; background: linear-gradient(135deg, #14532d 0%, #16a34a 60%, #86efac 100%); }
        .login-card { width: 100%; max-width: 410px; border-radius: 18px; }

        @media print { .sidebar, .topbar, .no-print, .overlay { display: none !important; } .has-nav .content { margin-left: 0 !important; } body { background: #fff; } }
    </style>
</head>
<body class="<?= $showNav ? 'has-nav' : '' ?>">
<?php if ($showNav): ?>
    <div class="overlay" id="overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="brand">AgriInfo<small>Agriculture Information System</small></div>
        <nav class="menu">
            <?php foreach ($items as $key => [$url, $label, $icon, $module]): ?>
                <?php if ($module === '' || can($module, 'view')): ?>
                    <a class="nav-item<?= $key === $active ? ' active' : '' ?>" href="<?= e($url) ?>"><?= e($label) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <form method="post" action="/logout.php" class="userbox">
            <?= csrf_field() ?>
            <div class="name"><?= e($user['full_name']) ?></div>
            <div class="mb-2" style="color:#bbf7d0"><?= e($user['role_name']) ?></div>
            <button class="btn btn-sm btn-light w-100">Logout</button>
        </form>
    </aside>
    <div class="content">
        <div class="topbar d-lg-none">
            <button type="button" class="btn btn-light border" id="menuBtn" aria-label="Open menu">&#9776;</button>
            <strong class="text-success">AgriInfo</strong>
        </div>
        <div class="content-inner">
<?php else: ?>
    <div class="content"><div class="content-inner">
<?php endif; ?>
<?php
    foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) {
        echo '<div class="alert alert-' . e($type) . ' alert-dismissible fade show" role="alert">' . e($msg)
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    unset($_SESSION['flash']);
}

function page_footer(): void
{
    ?>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var btn = document.getElementById('menuBtn'), sb = document.getElementById('sidebar'), ov = document.getElementById('overlay');
    if (!btn || !sb || !ov) { return; }
    btn.addEventListener('click', function () { sb.classList.add('open'); ov.classList.add('show'); });
    ov.addEventListener('click', function () { sb.classList.remove('open'); ov.classList.remove('show'); });
})();
</script>
</body>
</html>
<?php
}
