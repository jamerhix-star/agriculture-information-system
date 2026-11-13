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

$cards = [
    ['Farmers', $counts['farmers'], '/farmers/index.php', 'success'],
    ['Farms', $counts['farms'], '/farms/index.php', 'primary'],
    ['Crops', $counts['crops'], '/crops/index.php', 'warning'],
    ['Plantings', $counts['plantings'], '/plantings/index.php', 'info'],
    ['Harvests', $counts['harvests'], '/harvests/index.php', 'danger'],
];

page_header('Dashboard', 'dashboard');
?>
<h1 class="h3 mb-3">Dashboard</h1>

<div class="row g-3 mb-4">
    <?php foreach ($cards as [$label, $count, $url, $color]): ?>
        <div class="col-6 col-md-4 col-lg">
            <a class="text-decoration-none" href="<?= e($url) ?>">
                <div class="card text-bg-<?= e($color) ?> h-100">
                    <div class="card-body">
                        <div class="small text-uppercase">Total <?= e($label) ?></div>
                        <div class="display-6 fw-bold"><?= (int) $count ?></div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small text-uppercase">Total harvested quantity</div>
            <div class="h2 mb-0"><?= e(money($totals['qty'])) ?> kg</div>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small text-uppercase">Estimated harvest value</div>
            <div class="h2 mb-0">&#8369; <?= e(money($totals['value'])) ?></div>
        </div></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Plantings by status</div>
    <div class="card-body">
        <?php foreach ($byStatus as $s):
            $pct = $statusSum > 0 ? round(((int) $s['total'] / $statusSum) * 100) : 0; ?>
            <div class="d-flex justify-content-between small"><span><?= e($s['status_name']) ?></span><span><?= (int) $s['total'] ?></span></div>
            <div class="progress mb-3" role="progressbar" aria-label="<?= e($s['status_name']) ?>" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-success" style="width: <?= $pct ?>%"><?= $pct ?>%</div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span>Recent planting records</span><a href="/plantings/index.php">View all</a></div>
            <div class="table-responsive"><table class="table table-sm table-striped mb-0">
                <thead><tr><th>ID</th><th>Date</th><th>Farm</th><th>Crop</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($recentPlantings as $r): ?>
                    <tr><td><a href="/plantings/view.php?id=<?= (int) $r['planting_id'] ?>"><?= (int) $r['planting_id'] ?></a></td>
                        <td><?= e($r['planting_date']) ?></td><td><?= e($r['farm_name']) ?></td><td><?= e($r['crop_name']) ?></td><td><?= e($r['status_name']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$recentPlantings): ?><tr><td colspan="5" class="text-center text-muted">No planting records yet.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span>Recent harvest records</span><a href="/harvests/index.php">View all</a></div>
            <div class="table-responsive"><table class="table table-sm table-striped mb-0">
                <thead><tr><th>ID</th><th>Date</th><th>Farm</th><th>Crop</th><th>Kg</th><th>Grade</th></tr></thead>
                <tbody>
                <?php foreach ($recentHarvests as $r): ?>
                    <tr><td><a href="/harvests/view.php?id=<?= (int) $r['harvest_id'] ?>"><?= (int) $r['harvest_id'] ?></a></td>
                        <td><?= e($r['harvest_date']) ?></td><td><?= e($r['farm_name']) ?></td><td><?= e($r['crop_name']) ?></td>
                        <td><?= e(money($r['quantity_kg'])) ?></td><td><?= e($r['grade_name']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$recentHarvests): ?><tr><td colspan="6" class="text-center text-muted">No harvest records yet.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>
<?php page_footer();
