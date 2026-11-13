<?php
/** Shared page layout: Bootstrap 5 from the CDN, navbar, flash messages. */

function page_header(string $title, string $active = '', bool $nav = true): void
{
    $links = [
        'dashboard' => ['/index.php', 'Dashboard'],
        'farmers'   => ['/farmers/index.php', 'Farmers'],
        'farms'     => ['/farms/index.php', 'Farms'],
        'crops'     => ['/crops/index.php', 'Crops'],
        'plantings' => ['/plantings/index.php', 'Plantings'],
        'harvests'  => ['/harvests/index.php', 'Harvests'],
        'reports'   => ['/reports.php', 'Reports'],
    ];
    $user = current_user();
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | Agriculture Information System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>@media print { .no-print { display: none !important; } }</style>
</head>
<body class="bg-light">
<?php if ($nav && $user): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4 no-print">
    <div class="container">
        <a class="navbar-brand" href="/index.php">AgriInfo</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
                aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <?php foreach ($links as $key => [$url, $label]): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $key === $active ? ' active fw-bold' : '' ?>" href="<?= e($url) ?>"><?= e($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <form method="post" action="/logout.php" class="d-flex align-items-center gap-2 text-white">
                <?= csrf_field() ?>
                <span class="small"><?= e($user['full_name']) ?> (<?= e($user['role_name']) ?>)</span>
                <button class="btn btn-sm btn-outline-light">Logout</button>
            </form>
        </div>
    </div>
</nav>
<?php endif; ?>
<main class="container pb-5">
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
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
