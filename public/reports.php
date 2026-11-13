<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';
require_login();

$titles = [
    'farmers_farms'    => 'Farmers and their farms',
    'crops_per_farm'   => 'Crops planted per farm',
    'plantings_status' => 'Planting records by status',
    'harvests'         => 'Harvest records',
    'total_quantity'   => 'Total harvested quantity (per crop)',
    'harvest_value'    => 'Estimated harvest value (per farm)',
];
$type = in_get('type');
if (!isset($titles[$type])) {
    $type = 'farmers_farms';
}

$from = in_get('from');
$to = in_get('to');
$q = in_get('q');
$grade = in_get('grade');
$problems = [];
if ($from !== '' && !valid_date($from)) {
    $problems[] = 'The "From" date is not a valid date and was ignored.';
    $from = '';
}
if ($to !== '' && !valid_date($to)) {
    $problems[] = 'The "To" date is not a valid date and was ignored.';
    $to = '';
}
if ($from !== '' && $to !== '' && $to < $from) {
    $problems[] = 'The "To" date cannot be before the "From" date. The date range was ignored.';
    $from = '';
    $to = '';
}

$usesPlantingDate = in_array($type, ['crops_per_farm', 'plantings_status'], true);
$usesHarvestDate = in_array($type, ['harvests', 'total_quantity', 'harvest_value'], true);
$dateCol = $usesPlantingDate ? 'p.planting_date' : 'h.harvest_date';

$cond = [];
$params = [];
if ($usesPlantingDate || $usesHarvestDate) {
    if ($from !== '') {
        $cond[] = "$dateCol >= ?";
        $params[] = $from;
    }
    if ($to !== '') {
        $cond[] = "$dateCol <= ?";
        $params[] = $to;
    }
}
if ($usesHarvestDate && ctype_digit($grade)) {
    $cond[] = 'h.grade_id = ?';
    $params[] = $grade;
}
if ($type === 'farmers_farms' && $q !== '') {
    $cond[] = '(fr.first_name ILIKE ? OR fr.last_name ILIKE ? OR fm.farm_name ILIKE ?)';
    array_push($params, like($q), like($q), like($q));
}
$and = implode(' AND ', $cond);
$where = $cond ? 'WHERE ' . $and : '';

// column spec: key => [label, kind] ; kind: text | int | dec | dec* (dec* = show a total)
switch ($type) {
    case 'crops_per_farm':
        $cols = ['farm' => ['Farm', 'text'], 'crop' => ['Crop', 'text'], 'variety' => ['Variety', 'text'], 'plantings' => ['Plantings', 'int*'], 'total_area' => ['Total area (ha)', 'dec*']];
        $sql = "SELECT fm.farm_name AS farm, c.crop_name AS crop, c.variety, COUNT(*) AS plantings, SUM(p.area_planted) AS total_area
                  FROM planting p JOIN farm fm ON fm.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id
                  $where
                 GROUP BY fm.farm_id, fm.farm_name, c.crop_id, c.crop_name, c.variety
                 ORDER BY fm.farm_name, c.crop_name";
        break;
    case 'plantings_status':
        $cols = ['status' => ['Status', 'text'], 'plantings' => ['Planting records', 'int*'], 'total_area' => ['Total area (ha)', 'dec*']];
        $sql = 'SELECT s.status_name AS status, COUNT(p.planting_id) AS plantings, COALESCE(SUM(p.area_planted), 0) AS total_area
                  FROM planting_statuses s LEFT JOIN planting p ON p.status_id = s.status_id' . ($and !== '' ? ' AND ' . $and : '') . '
                 GROUP BY s.status_id, s.status_name ORDER BY s.status_id';
        break;
    case 'harvests':
        $cols = ['id' => ['ID', 'text'], 'harvest_date' => ['Date', 'text'], 'farm' => ['Farm', 'text'], 'crop' => ['Crop', 'text'],
                 'quantity_kg' => ['Quantity (kg)', 'dec*'], 'price' => ['Price / kg', 'dec'], 'grade' => ['Grade', 'text'], 'remarks' => ['Remarks', 'text']];
        $sql = "SELECT h.harvest_id AS id, h.harvest_date, fm.farm_name AS farm, c.crop_name AS crop, h.quantity_kg,
                       h.selling_price_per_kg AS price, g.grade_name AS grade, r.remark_description AS remarks
                  FROM harvest h JOIN planting p ON p.planting_id = h.planting_id JOIN farm fm ON fm.farm_id = p.farm_id
                  JOIN crop c ON c.crop_id = p.crop_id JOIN quality_grades g ON g.grade_id = h.grade_id
                  JOIN harvest_remarks r ON r.remark_id = h.remark_id
                  $where ORDER BY h.harvest_date DESC, h.harvest_id DESC";
        break;
    case 'total_quantity':
        $cols = ['crop' => ['Crop', 'text'], 'variety' => ['Variety', 'text'], 'harvests' => ['Harvests', 'int*'], 'total_kg' => ['Total quantity (kg)', 'dec*']];
        $sql = "SELECT c.crop_name AS crop, c.variety, COUNT(*) AS harvests, SUM(h.quantity_kg) AS total_kg
                  FROM harvest h JOIN planting p ON p.planting_id = h.planting_id JOIN crop c ON c.crop_id = p.crop_id
                  $where GROUP BY c.crop_id, c.crop_name, c.variety ORDER BY total_kg DESC";
        break;
    case 'harvest_value':
        $cols = ['farm' => ['Farm', 'text'], 'harvests' => ['Harvests', 'int*'], 'total_kg' => ['Total quantity (kg)', 'dec*'], 'est_value' => ['Estimated value', 'dec*']];
        $sql = "SELECT fm.farm_name AS farm, COUNT(*) AS harvests, SUM(h.quantity_kg) AS total_kg,
                       SUM(h.quantity_kg * h.selling_price_per_kg) AS est_value
                  FROM harvest h JOIN planting p ON p.planting_id = h.planting_id JOIN farm fm ON fm.farm_id = p.farm_id
                  $where GROUP BY fm.farm_id, fm.farm_name ORDER BY est_value DESC";
        break;
    default: // farmers_farms
        $cols = ['farmer' => ['Farmer', 'text'], 'address' => ['Address', 'text'], 'farm' => ['Farm', 'text'], 'location' => ['Location', 'text'],
                 'area_hectares' => ['Area (ha)', 'dec*'], 'soil' => ['Soil', 'text'], 'irrigation' => ['Irrigation', 'text']];
        $sql = "SELECT fr.last_name || ', ' || fr.first_name AS farmer, fr.address, COALESCE(fm.farm_name, '(no farm)') AS farm,
                       fm.location, fm.area_hectares, so.soil_type_name AS soil, ir.irrigation_type_name AS irrigation
                  FROM farmer fr LEFT JOIN farm fm ON fm.farmer_id = fr.farmer_id
                  LEFT JOIN soil_types so ON so.soil_type_id = fm.soil_type_id
                  LEFT JOIN irrigation_types ir ON ir.irrigation_type_id = fm.irrigation_type_id
                  $where ORDER BY fr.last_name, fr.first_name, fm.farm_name";
}

$st = db()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

$sums = [];
foreach ($cols as $key => [$label, $kind]) {
    if (substr($kind, -1) === '*') {
        $sums[$key] = 0.0;
        foreach ($rows as $r) {
            $sums[$key] += (float) $r[$key];
        }
    }
}

function fmt_cell($v, string $kind): string
{
    if ($v === null) {
        return '';
    }
    $base = rtrim($kind, '*');
    if ($base === 'int') {
        return number_format((float) $v, 0);
    }
    if ($base === 'dec') {
        return number_format((float) $v, 2);
    }
    return (string) $v;
}

page_header('Reports', 'reports');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Reports</h1>
    <button class="btn btn-outline-dark no-print" onclick="window.print()">Print</button>
</div>

<ul class="nav nav-pills mb-3 no-print">
    <?php foreach ($titles as $key => $label): ?>
        <li class="nav-item"><a class="nav-link<?= $key === $type ? ' active' : '' ?>" href="/reports.php?type=<?= e($key) ?>"><?= e($label) ?></a></li>
    <?php endforeach; ?>
</ul>

<?php foreach ($problems as $p): ?><div class="alert alert-warning"><?= e($p) ?></div><?php endforeach; ?>

<form class="row g-2 mb-3 no-print" method="get">
    <input type="hidden" name="type" value="<?= e($type) ?>">
    <?php if ($type === 'farmers_farms'): ?>
        <div class="col-12 col-md-5"><input class="form-control" name="q" placeholder="Search farmer or farm" value="<?= e($q) ?>"></div>
    <?php else: ?>
        <div class="col-6 col-md-3"><label class="form-label small mb-0">From date</label><input type="date" class="form-control" name="from" value="<?= e($from) ?>"></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-0">To date</label><input type="date" class="form-control" name="to" value="<?= e($to) ?>"></div>
    <?php endif; ?>
    <?php if ($usesHarvestDate): ?>
        <div class="col-12 col-md-3"><label class="form-label small mb-0">Quality grade</label><?= filter_select('grade', lookup('grade'), $grade, 'All grades') ?></div>
    <?php endif; ?>
    <div class="col-12 col-md-auto d-flex gap-2 align-items-end">
        <button class="btn btn-primary">Apply</button>
        <a class="btn btn-outline-secondary" href="/reports.php?type=<?= e($type) ?>">Reset</a>
    </div>
</form>

<h2 class="h5"><?= e($titles[$type]) ?></h2>
<div class="table-responsive bg-white border rounded">
    <table class="table table-striped mb-0">
        <thead class="table-light"><tr>
            <?php foreach ($cols as [$label, $kind]): ?>
                <th class="<?= $kind === 'text' ? '' : 'text-end' ?>"><?= e($label) ?></th>
            <?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <?php foreach ($cols as $key => [$label, $kind]): ?>
                    <td class="<?= $kind === 'text' ? '' : 'text-end' ?>"><?= e(fmt_cell($r[$key], $kind)) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="<?= count($cols) ?>" class="text-center text-muted py-4">No data found for the selected filters.</td></tr>
        <?php endif; ?>
        </tbody>
        <?php if ($rows && $sums): ?>
        <tfoot class="table-light fw-bold"><tr>
            <?php $first = true; foreach ($cols as $key => [$label, $kind]): ?>
                <td class="<?= $kind === 'text' ? '' : 'text-end' ?>">
                    <?php if (isset($sums[$key])): ?><?= e(fmt_cell($sums[$key], $kind)) ?>
                    <?php elseif ($first): ?>Total<?php endif; ?>
                </td>
            <?php $first = false; endforeach; ?>
        </tr></tfoot>
        <?php endif; ?>
    </table>
</div>
<p class="text-muted small mt-2"><?= count($rows) ?> row(s)</p>
<?php page_footer();
