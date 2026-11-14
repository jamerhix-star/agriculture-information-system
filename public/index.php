<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';
require_login();

$pdo = db();
$counts = $pdo->query(
    'SELECT (SELECT COUNT(*) FROM farmer)   AS farmers,
            (SELECT COUNT(*) FROM farm)     AS farms,
            (SELECT COUNT(*) FROM crop)     AS crops,
            (SELECT COUNT(*) FROM planting) AS plantings,
            (SELECT COUNT(*) FROM harvest)  AS harvests'
)->fetch();

$totals = $pdo->query(
    'SELECT COALESCE(SUM(quantity_kg), 0) AS qty, COALESCE(SUM(quantity_kg * selling_price_per_kg), 0) AS value FROM harvest'
)->fetch();

$byStatus = $pdo->query(
    'SELECT s.status_name, COUNT(p.planting_id) AS total
       FROM planting_statuses s LEFT JOIN planting p ON p.status_id = s.status_id
      GROUP BY s.status_id, s.status_name ORDER BY s.status_id'
)->fetchAll();
$statusSum = 0;
foreach ($byStatus as $s) {
    $statusSum += (int) $s['total'];
}

$topCrops = $pdo->query(
    'SELECT c.crop_name, SUM(h.quantity_kg) AS qty
       FROM crop c JOIN planting p ON p.crop_id = c.crop_id JOIN harvest h ON h.planting_id = p.planting_id
      GROUP BY c.crop_id, c.crop_name ORDER BY qty DESC LIMIT 5'
)->fetchAll();
$maxQty = 0;
foreach ($topCrops as $t) {
    $maxQty = max($maxQty, (float) $t['qty']);
}

$recentPlantings = $pdo->query(
    'SELECT p.planting_id, p.planting_date, fm.farm_name, c.crop_name, ps.status_name
       FROM planting p JOIN farm fm ON fm.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id
       JOIN planting_statuses ps ON ps.status_id = p.status_id
      ORDER BY p.planting_date DESC, p.planting_id DESC LIMIT 5'
)->fetchAll();

$recentHarvests = $pdo->query(
    'SELECT h.harvest_id, h.harvest_date, h.quantity_kg, fm.farm_name, c.crop_name, g.grade_name
       FROM harvest h JOIN planting p ON p.planting_id = h.planting_id JOIN farm fm ON fm.farm_id = p.farm_id
       JOIN crop c ON c.crop_id = p.crop_id JOIN quality_grades g ON g.grade_id = h.grade_id
      ORDER BY h.harvest_date DESC, h.harvest_id DESC LIMIT 5'
)->fetchAll();

// label, count, link
$cards = [
    ['Farmers', $counts['farmers'], '/farmers/index.php'],
    ['Farms', $counts['farms'], '/farms/index.php'],
    ['Crops', $counts['crops'], '/crops/index.php'],
    ['Plantings', $counts['plantings'], '/plantings/index.php'],
    ['Harvests', $counts['harvests'], '/harvests/index.php'],
];

page_header('Dashboard', 'dashboard');
?>
<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <div class="text-muted">Welcome back, <?= e(current_user()['full_name']) ?> &middot; <?= e(current_user()['role_name']) ?></div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (can('plantings', 'create')): ?><a class="btn btn-success" href="/plantings/form.php">+ New planting</a><?php endif; ?>
        <?php if (can('harvests', 'create')): ?><a class="btn btn-outline-success" href="/harvests/form.php">+ New harvest</a><?php endif; ?>
        <a class="btn btn-outline-secondary" href="/reports.php">Reports</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <?php foreach ($cards as [$label, $count, $url]): ?>
        <div class="col-6 col-md-4 col-xl">
            <a class="stat-link" href="<?= e($url) ?>">
                <div class="card h-100"><div class="stat">
                    <div class="lbl"><?= e($label) ?></div>
                    <div class="num"><?= (int) $count ?></div>
                </div></div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100"><div class="stat">
            <div class="lbl">Total harvested quantity</div>
            <div class="num"><?= e(number_format((float) $totals['qty'], 2)) ?> <small class="fs-6 text-muted">kg</small></div>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="stat">
            <div class="lbl">Estimated harvest value</div>
            <div class="num">&#8369; <?= e(money($totals['value'])) ?></div>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Plantings by status</div>
            <div class="card-body">
                <?php foreach ($byStatus as $s):
                    $pct = $statusSum > 0 ? round(((int) $s['total'] / $statusSum) * 100) : 0; ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span><?= status_badge($s['status_name']) ?></span>
                        <span class="small text-muted"><?= (int) $s['total'] ?> &middot; <?= $pct ?>%</span>
                    </div>
                    <div class="bar-track mb-3"><div class="bar-fill" style="width: <?= $pct ?>%"></div></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Top crops by harvested quantity</div>
            <div class="card-body">
                <?php foreach ($topCrops as $t):
                    $pct = $maxQty > 0 ? round(((float) $t['qty'] / $maxQty) * 100) : 0; ?>
                    <div class="d-flex justify-content-between mb-1"><span><?= e($t['crop_name']) ?></span><span class="small text-muted"><?= e(number_format((float) $t['qty'], 2)) ?> kg</span></div>
                    <div class="bar-track mb-3"><div class="bar-fill" style="width: <?= $pct ?>%"></div></div>
                <?php endforeach; ?>
                <?php if (!$topCrops): ?><div class="text-muted">No harvest data yet.</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span>Recent planting records</span><a class="small" href="/plantings/index.php">View all</a></div>
            <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                <thead><tr><th>ID</th><th>Date</th><th>Farm</th><th>Crop</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($recentPlantings as $r): ?>
                    <tr><td><a href="/plantings/view.php?id=<?= (int) $r['planting_id'] ?>"><?= (int) $r['planting_id'] ?></a></td>
                        <td><?= e($r['planting_date']) ?></td><td><?= e($r['farm_name']) ?></td><td><?= e($r['crop_name']) ?></td>
                        <td><?= status_badge($r['status_name']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$recentPlantings): ?><tr><td colspan="5" class="text-center text-muted">No planting records yet.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span>Recent harvest records</span><a class="small" href="/harvests/index.php">View all</a></div>
            <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                <thead><tr><th>ID</th><th>Date</th><th>Farm</th><th>Crop</th><th>Kg</th><th>Grade</th></tr></thead>
                <tbody>
                <?php foreach ($recentHarvests as $r): ?>
                    <tr><td><a href="/harvests/view.php?id=<?= (int) $r['harvest_id'] ?>"><?= (int) $r['harvest_id'] ?></a></td>
                        <td><?= e($r['harvest_date']) ?></td><td><?= e($r['farm_name']) ?></td><td><?= e($r['crop_name']) ?></td>
                        <td><?= e(money($r['quantity_kg'])) ?></td><td><?= grade_badge($r['grade_name']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$recentHarvests): ?><tr><td colspan="6" class="text-center text-muted">No harvest records yet.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>
<?php page_footer();
