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
        :root { --bg:#f6f4ee; --surface:#ffffff; --line:#e6e1d4; --ink:#26261f; --muted:#7a7868; --accent:#3d6a4e; --accent-dark:#2f533d; --accent-soft:#e6efe8;
                --serif: ui-serif, Georgia, Cambria, "Times New Roman", serif; }
        html { font-size: 15.5px; }
        body { background: var(--bg); color: var(--ink); line-height: 1.55;
               font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        a { color: var(--accent); } a:hover { color: var(--accent-dark); }
        h1, h2, h3, .h3, .h4, .h5 { font-family: var(--serif); color: var(--ink); font-weight: 600; }

        /* buttons and inputs: calm, one accent color */
        .btn { border-radius: 8px; font-weight: 500; }
        .btn-success, .btn-primary { --bs-btn-bg: var(--accent); --bs-btn-border-color: var(--accent); --bs-btn-color: #fff;
            --bs-btn-hover-bg: var(--accent-dark); --bs-btn-hover-border-color: var(--accent-dark); --bs-btn-hover-color: #fff;
            --bs-btn-active-bg: var(--accent-dark); --bs-btn-active-border-color: var(--accent-dark); --bs-btn-active-color: #fff; }
        .btn-outline-success, .btn-outline-primary { --bs-btn-color: var(--accent); --bs-btn-border-color: #cdd8cf;
            --bs-btn-hover-bg: var(--accent-soft); --bs-btn-hover-color: var(--accent-dark); --bs-btn-hover-border-color: #b5c6ba;
            --bs-btn-active-bg: var(--accent-soft); --bs-btn-active-color: var(--accent-dark); --bs-btn-active-border-color: #b5c6ba; }
        .btn-outline-secondary, .btn-outline-dark { --bs-btn-color: #55554a; --bs-btn-border-color: #d8d3c6;
            --bs-btn-hover-bg: #efece3; --bs-btn-hover-color: var(--ink); --bs-btn-hover-border-color: #c9c3b3;
            --bs-btn-active-bg: #efece3; --bs-btn-active-color: var(--ink); --bs-btn-active-border-color: #c9c3b3; }
        .btn-outline-danger { --bs-btn-color: #a2392d; --bs-btn-border-color: #e3c9c5; --bs-btn-hover-bg: #f8e9e6; --bs-btn-hover-color: #8a2f25; --bs-btn-hover-border-color: #d9b3ad; }
        .form-control, .form-select { border-radius: 8px; border-color: #d9d4c7; background-color: #fff; }
        .form-control:focus, .form-select:focus { border-color: var(--accent); box-shadow: 0 0 0 .2rem rgba(61,106,78,.15); }
        .form-label { font-weight: 500; font-size: .88rem; color: #4a4a40; }
        .invalid-feedback { font-size: .84rem; }

        /* sidebar: light, quiet */
        .sidebar { position: fixed; top: 0; bottom: 0; left: 0; width: 244px; z-index: 1040; display: flex; flex-direction: column;
                   background: #efebe1; border-right: 1px solid var(--line); color: var(--ink);
                   transform: translateX(-100%); transition: transform .25s ease; }
        .sidebar.open { transform: none; }
        .sidebar .brand { padding: 1.6rem 1.4rem 1.2rem; font-family: var(--serif); font-weight: 600; font-size: 1.35rem; color: var(--accent-dark); }
        .sidebar .brand small { display: block; margin-top: .15rem; font-family: system-ui, sans-serif; font-weight: 400; font-size: .74rem; color: var(--muted); letter-spacing: .02em; }
        .sidebar .menu { flex: 1; overflow-y: auto; padding: .25rem 0 1rem; }
        .sidebar a.nav-item { display: block; padding: .55rem 1rem; margin: .1rem .8rem; border-radius: 8px; border: 1px solid transparent;
                              color: #55554a; text-decoration: none; }
        .sidebar a.nav-item:hover { background: rgba(0,0,0,.04); color: var(--ink); }
        .sidebar a.nav-item.active { background: #fff; border-color: var(--line); color: var(--accent-dark); font-weight: 600; }
        .sidebar .userbox { padding: 1rem 1.4rem 1.2rem; border-top: 1px solid var(--line); font-size: .85rem; }
        .sidebar .userbox .name { font-weight: 600; }
        .sidebar .userbox .role { color: var(--muted); margin-bottom: .7rem; }
        .overlay { display: none; position: fixed; inset: 0; background: rgba(38,38,31,.35); z-index: 1030; }
        .overlay.show { display: block; }
        .topbar { position: sticky; top: 0; z-index: 1020; display: flex; align-items: center; gap: .75rem; padding: .6rem 1rem; background: #fff; border-bottom: 1px solid var(--line); }
        @media (min-width: 992px) { .sidebar { transform: none; } .has-nav .content { margin-left: 244px; } }
        .content-inner { padding: 1.25rem; max-width: 1320px; margin: 0 auto; }
        @media (min-width: 992px) { .content-inner { padding: 2.4rem 2.75rem; } }

        /* page header, cards, tables */
        .page-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
        .page-head h1, h1.h3 { font-size: 1.75rem; margin: 0; letter-spacing: -.01em; }
        .card { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; box-shadow: none; }
        .card-header { background: transparent; border-bottom: 1px solid var(--line); font-family: var(--serif); font-weight: 600; font-size: 1.05rem; padding: 1rem 1.25rem; }
        .card-body { padding: 1.25rem; }
        .table-responsive.bg-white { background: var(--surface) !important; border: 1px solid var(--line) !important; border-radius: 12px !important; box-shadow: none; }
        .table { --bs-table-bg: transparent; --bs-table-striped-bg: transparent; --bs-table-hover-bg: #faf8f3; --bs-table-border-color: #eee9dc; margin-bottom: 0; }
        .table thead th { background: transparent; color: var(--muted); font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .07em;
                          border-bottom: 1px solid var(--line); white-space: nowrap; padding: .95rem 1rem; }
        .table thead th a { color: inherit !important; }
        .table td { padding: .85rem 1rem; vertical-align: middle; }
        .table tbody tr:last-child td { border-bottom: 0; }
        .filter-bar { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: .8rem; margin: 0 0 1.1rem; }
        .pagination .page-link { color: var(--accent); border: 1px solid var(--line); border-radius: 8px; margin: 0 2px; background: #fff; }
        .pagination .page-item.disabled .page-link { color: #b0ad9f; }
        .pagination .active .page-link { background: var(--accent); border-color: var(--accent); color: #fff; }
        .alert { border: 1px solid transparent; border-radius: 10px; }
        .alert-success { background: #e8f1ea; color: #2f5d3f; border-color: #cfe0d4; }
        .alert-danger { background: #f8e9e6; color: #8a2f25; border-color: #ecc9c3; }
        .alert-warning { background: #f8efd9; color: #7a5a12; border-color: #ecdcb0; }
        dt { color: var(--muted); font-weight: 500; }
        dd { margin-bottom: .65rem; }

        /* soft badges */
        .badge { font-weight: 500; padding: .38em .7em; }
        .badge.text-bg-success   { background: #e3eee6 !important; color: #2f5d3f !important; }
        .badge.text-bg-info      { background: #e1ecf0 !important; color: #2f5a6a !important; }
        .badge.text-bg-warning   { background: #f6ecd2 !important; color: #7a5a12 !important; }
        .badge.text-bg-danger    { background: #f5e1de !important; color: #8a2f25 !important; }
        .badge.text-bg-primary   { background: #e2e8f2 !important; color: #34508a !important; }
        .badge.text-bg-secondary { background: #ece9e0 !important; color: #5c5a4e !important; }
        .badge.text-bg-light     { background: #fff !important; color: var(--muted) !important; }

        /* dashboard: plain figures, no colored edges */
        .stat { padding: 1.15rem 1.3rem; }
        .stat .lbl { color: var(--muted); font-size: .76rem; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; margin-bottom: .35rem; }
        .stat .num { font-family: var(--serif); font-size: 2.1rem; font-weight: 600; line-height: 1.1; color: var(--ink); }
        a.stat-link { text-decoration: none; color: inherit; display: block; }
        a.stat-link .card { transition: border-color .15s, background-color .15s; }
        a.stat-link:hover .card { border-color: #c9c3b3; background: #fdfcf9; }
        .bar-track { background: #ece8dc; border-radius: 99px; height: 8px; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 99px; background: var(--accent); }

        /* login */
        .login-bg { min-height: 100vh; display: grid; place-items: center; padding: 1rem; background: var(--bg); }
        .login-card { width: 100%; max-width: 400px; border-radius: 14px; }

        @media print { .sidebar, .topbar, .no-print, .overlay { display: none !important; } .has-nav .content { margin-left: 0 !important; } body { background: #fff; } }

        /* all links and tabs in green (no blue) */
        :root { --bs-link-color: #3d6a4e; --bs-link-hover-color: #2f533d; --bs-link-color-rgb: 61,106,78; --bs-link-hover-color-rgb: 47,83,61; }
        a { color: #3d6a4e; }
        a:hover { color: #2f533d; }
        .nav-link { color: #3d6a4e; }
        .nav-link:hover, .nav-link:focus { color: #2f533d; }
        .nav-pills { --bs-nav-link-color: #3d6a4e; --bs-nav-link-hover-color: #2f533d; --bs-nav-pills-link-active-bg: #3d6a4e; --bs-nav-pills-link-active-color: #ffffff; }
        .nav-pills .nav-link.active, .nav-pills .show > .nav-link { background-color: #3d6a4e; color: #ffffff; }
        .btn-link { --bs-btn-color: #3d6a4e; --bs-btn-hover-color: #2f533d; }
        .form-check-input:checked { background-color: #3d6a4e; border-color: #3d6a4e; }
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
            <div class="role"><?= e($user['role_name']) ?></div>
            <button class="btn btn-sm btn-outline-secondary w-100">Logout</button>
        </form>
    </aside>
    <div class="content">
        <div class="topbar d-lg-none">
            <button type="button" class="btn btn-light border" id="menuBtn" aria-label="Open menu">&#9776;</button>
            <strong style="font-family:var(--serif); color:var(--accent-dark)">AgriInfo</strong>
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
