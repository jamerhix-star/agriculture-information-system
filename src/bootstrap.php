<?php
/**
 * Core file: environment, database, security helpers, list/form helpers.
 * Every page starts with: require_once ... '/src/bootstrap.php';
 */
define('ROOT', dirname(__DIR__));

/* ------------------------------------------------------------------ */
/* 1. Environment (.env locally, real environment variables on a host) */
/* ------------------------------------------------------------------ */
function load_env(string $file): void
{
    if (!is_readable($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim(trim($v), "\"'");
        if (getenv($k) === false) {
            putenv($k . '=' . $v);
        }
    }
}
load_env(ROOT . '/.env');

function env(string $key, string $default = ''): string
{
    $v = getenv($key);
    return $v === false ? $default : $v;
}

/* ------------------------------------------------------------------ */
/* 2. Logging and friendly errors (technical details never shown)      */
/* ------------------------------------------------------------------ */
if (!is_dir(ROOT . '/logs')) {
    @mkdir(ROOT . '/logs', 0775, true);
}
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (is_dir(ROOT . '/logs') && is_writable(ROOT . '/logs')) {
    ini_set('error_log', ROOT . '/logs/php-error.log');
}
error_reporting(E_ALL);

function log_error($e): void
{
    $msg = ($e instanceof Throwable)
        ? get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine()
        : (string) $e;
    $line = '[' . date('c') . '] ' . $msg . PHP_EOL;
    @file_put_contents(ROOT . '/logs/app.log', $line, FILE_APPEND); // local file (if writable)
    @file_put_contents('php://stderr', $line);                       // Render / Docker "Logs" tab
}

set_exception_handler(function (Throwable $e): void {
    log_error($e);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
        return;
    }
    http_response_code(500);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>Error</title>'
        . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
        . '<body class="bg-light"><div class="container py-5"><div class="alert alert-danger">'
        . '<h4 class="alert-heading">Something went wrong</h4>'
        . '<p class="mb-0">We could not complete your request. Please try again later or contact the administrator.</p>'
        . '</div><a class="btn btn-secondary" href="/index.php">Back to dashboard</a></div></body></html>';
});

/* ------------------------------------------------------------------ */
/* 3. Session                                                          */
/* ------------------------------------------------------------------ */
if (PHP_SAPI !== 'cli') {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/* ------------------------------------------------------------------ */
/* 4. Database (PDO + pdo_pgsql, Supabase Session pooler, port 5432)  */
/* ------------------------------------------------------------------ */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        env('DB_HOST'),
        env('DB_PORT', '5432'),
        env('DB_NAME', 'postgres')
    );
    $pdo = new PDO($dsn, env('DB_USER'), env('DB_PASS'), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}

/* ------------------------------------------------------------------ */
/* 5. Small helpers: output escaping, redirects, flash, CSRF           */
/* ------------------------------------------------------------------ */
function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $t = $_POST['csrf_token'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(419);
        exit('Your session expired or the form was not valid. Please go back, reload the page and try again.');
    }
}

function in_post(string $k): string
{
    $v = $_POST[$k] ?? '';
    return is_string($v) ? trim($v) : '';
}

function in_get(string $k): string
{
    $v = $_GET[$k] ?? '';
    return is_string($v) ? trim($v) : '';
}

/* ------------------------------------------------------------------ */
/* 6. Authentication and roles (1 = Administrator, 2 = Staff)          */
/* ------------------------------------------------------------------ */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return (int) (current_user()['role_id'] ?? 0) === 1;
}

function user_id(): int
{
    return (int) (current_user()['id'] ?? 0);
}

function require_login(): void
{
    if (!current_user()) {
        redirect('/login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        flash('danger', 'Only an administrator can do that.');
        redirect('/index.php');
    }
}

/**
 * ROLE PERMISSION MATRIX - the single place that decides who can do what.
 * Role ids: 1 = Administrator, 2 = Staff.
 * Administrator: everything. Staff: view everything, add/edit planting and harvest
 * records only. Master data (farmers, farms, crops), deleting, and user accounts are admin-only.
 */
const PERMISSIONS = [
    'farmers'   => ['view' => [1, 2], 'create' => [1],    'edit' => [1],    'delete' => [1]],
    'farms'     => ['view' => [1, 2], 'create' => [1],    'edit' => [1],    'delete' => [1]],
    'crops'     => ['view' => [1, 2], 'create' => [1],    'edit' => [1],    'delete' => [1]],
    'plantings' => ['view' => [1, 2], 'create' => [1, 2], 'edit' => [1, 2], 'delete' => [1]],
    'harvests'  => ['view' => [1, 2], 'create' => [1, 2], 'edit' => [1, 2], 'delete' => [1]],
    'reports'   => ['view' => [1, 2]],
    'users'     => ['view' => [1],    'create' => [1],    'edit' => [1],    'delete' => []],
];

function can(string $module, string $action): bool
{
    $role = (int) (current_user()['role_id'] ?? 0);
    return in_array($role, PERMISSIONS[$module][$action] ?? [], true);
}

/** Server-side check. Hiding a button is not enough, every protected page calls this. */
function require_permission(string $module, string $action): void
{
    require_login();
    if (!can($module, $action)) {
        flash('danger', 'You do not have permission to do that. This action is for administrators only.');
        redirect('/index.php');
    }
}

function badge(string $text, string $color = 'secondary'): string
{
    return '<span class="badge rounded-pill text-bg-' . e($color) . '">' . e($text) . '</span>';
}

function status_badge(string $name): string
{
    $map = ['Planted' => 'info', 'Growing' => 'warning', 'Harvested' => 'success', 'Failed' => 'danger'];
    return badge($name, $map[$name] ?? 'secondary');
}

function grade_badge(string $name): string
{
    $map = ['Grade A' => 'success', 'Grade B' => 'primary', 'Grade C' => 'warning'];
    return badge($name, $map[$name] ?? 'secondary');
}

/* ------------------------------------------------------------------ */
/* 7. Validation helpers                                               */
/* ------------------------------------------------------------------ */
function valid_date(string $d): bool
{
    $x = DateTime::createFromFormat('Y-m-d', $d);
    return $x !== false && $x->format('Y-m-d') === $d;
}

/** Positive/zero decimal with at most $dec decimals and a min/max range. */
function num_ok(string $v, float $min, float $max, int $dec = 2): bool
{
    if (!preg_match('/^\d+(\.\d{1,' . $dec . '})?$/', $v)) {
        return false;
    }
    return (float) $v >= $min && (float) $v <= $max;
}

function row_exists(string $table, string $col, int $id): bool
{
    static $allowed = [
        'farmer' => 'farmer_id', 'farm' => 'farm_id', 'crop' => 'crop_id', 'planting' => 'planting_id',
        'soil_types' => 'soil_type_id', 'irrigation_types' => 'irrigation_type_id',
        'crop_categories' => 'crop_category_id', 'seasons' => 'season_id',
        'planting_statuses' => 'status_id', 'quality_grades' => 'grade_id', 'harvest_remarks' => 'remark_id',
        'roles' => 'role_id', 'users' => 'user_id',
    ];
    if (($allowed[$table] ?? null) !== $col) {
        throw new InvalidArgumentException('Table/column not allowed');
    }
    $st = db()->prepare("SELECT 1 FROM $table WHERE $col = ?");
    $st->execute([$id]);
    return (bool) $st->fetchColumn();
}

/** A foreign-key value must be digits only AND exist in the related table. */
function valid_fk(string $table, string $col, string $v): bool
{
    return ctype_digit($v) && row_exists($table, $col, (int) $v);
}

function save_failed(PDOException $ex, array &$errors, string $uniqueField = '', string $uniqueMsg = ''): void
{
    log_error($ex);
    $code = (string) $ex->getCode();
    if ($code === '23505' && $uniqueField !== '') {
        $errors[$uniqueField] = $uniqueMsg;
    } elseif ($code === '23503') {
        $errors['_form'] = 'A selected related record no longer exists. Reload the page and try again.';
    } else {
        $errors['_form'] = 'The record could not be saved. Please check your entries and try again.';
    }
}

/* ------------------------------------------------------------------ */
/* 8. Lookup (dropdown) helpers: show label, store numeric id          */
/* ------------------------------------------------------------------ */
const LOOKUPS = [
    'soil'       => ['soil_types', 'soil_type_id', 'soil_type_name'],
    'irrigation' => ['irrigation_types', 'irrigation_type_id', 'irrigation_type_name'],
    'category'   => ['crop_categories', 'crop_category_id', 'category_name'],
    'season'     => ['seasons', 'season_id', 'season_name'],
    'status'     => ['planting_statuses', 'status_id', 'status_name'],
    'grade'      => ['quality_grades', 'grade_id', 'grade_name'],
    'remark'     => ['harvest_remarks', 'remark_id', 'remark_description'],
];

/** Returns rows of ['id' => ..., 'label' => ...] for a lookup table. */
function lookup(string $key): array
{
    [$t, $i, $n] = LOOKUPS[$key];
    return db()->query("SELECT $i AS id, $n AS label FROM $t ORDER BY $i")->fetchAll();
}

function options(array $rows, $selected, string $placeholder = '-- Select --'): string
{
    $h = '<option value="">' . e($placeholder) . '</option>';
    foreach ($rows as $r) {
        $sel = ((string) $selected === (string) $r['id']) ? ' selected' : '';
        $h .= '<option value="' . e($r['id']) . '"' . $sel . '>' . e($r['label']) . '</option>';
    }
    return $h;
}

function filter_select(string $name, array $rows, string $current, string $allLabel): string
{
    return '<select class="form-select" name="' . e($name) . '">' . options($rows, $current, $allLabel) . '</select>';
}

/* ------------------------------------------------------------------ */
/* 9. Form helpers (message shown under the field)                     */
/* ------------------------------------------------------------------ */
function is_invalid(array $errors, string $f): string
{
    return isset($errors[$f]) ? ' is-invalid' : '';
}

function field_error(array $errors, string $f): string
{
    return isset($errors[$f]) ? '<div class="invalid-feedback">' . e($errors[$f]) . '</div>' : '';
}

function form_input(string $label, string $name, array $row, array $errors, string $type = 'text', string $extra = ''): string
{
    return '<div class="mb-3"><label class="form-label" for="' . e($name) . '">' . e($label) . '</label>'
        . '<input class="form-control' . is_invalid($errors, $name) . '" type="' . e($type) . '" id="' . e($name)
        . '" name="' . e($name) . '" value="' . e($row[$name] ?? '') . '" ' . $extra . '>'
        . field_error($errors, $name) . '</div>';
}

function form_select(string $label, string $name, string $optionsHtml, array $errors): string
{
    return '<div class="mb-3"><label class="form-label" for="' . e($name) . '">' . e($label) . '</label>'
        . '<select class="form-select' . is_invalid($errors, $name) . '" id="' . e($name) . '" name="' . e($name) . '">'
        . $optionsHtml . '</select>' . field_error($errors, $name) . '</div>';
}

/* ------------------------------------------------------------------ */
/* 10. List helpers: search/filter LIKE, sorting, pagination           */
/* ------------------------------------------------------------------ */
function like(string $q): string
{
    return '%' . addcslashes($q, '%_\\') . '%';
}

/** Returns [sortKey, 'ASC'|'DESC', 'ORDER BY sql'] using a whitelist map. */
function sort_state(array $map, string $default, string $tie): array
{
    $sort = (isset($_GET['sort']) && is_string($_GET['sort'])) ? $_GET['sort'] : $default;
    if (!isset($map[$sort])) {
        $sort = $default;
    }
    $dir = (($_GET['dir'] ?? 'asc') === 'desc') ? 'DESC' : 'ASC';
    return [$sort, $dir, $map[$sort] . ' ' . $dir . ', ' . $tie];
}

/** Current URL query string with some values replaced. */
function qs(array $override = []): string
{
    $q = array_merge($_GET, $override);
    foreach ($q as $k => $v) {
        if ($v === '' || $v === null) {
            unset($q[$k]);
        }
    }
    return '?' . http_build_query($q);
}

function sort_th(string $label, string $key, string $cur, string $dir): string
{
    $newDir = ($cur === $key && $dir === 'ASC') ? 'desc' : 'asc';
    $arrow = $cur === $key ? ($dir === 'ASC' ? ' &#9650;' : ' &#9660;') : '';
    return '<th><a class="text-decoration-none text-dark" href="'
        . e(qs(['sort' => $key, 'dir' => $newDir, 'page' => 1])) . '">' . e($label) . $arrow . '</a></th>';
}

function paginate(string $from, string $whereSql, array $params, string $select, string $orderBy, int $per = 10): array
{
    $st = db()->prepare("SELECT COUNT(*) FROM $from $whereSql");
    $st->execute($params);
    $total = (int) $st->fetchColumn();
    $pages = max(1, (int) ceil($total / $per));
    $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);
    $offset = ($page - 1) * $per;
    $st = db()->prepare("SELECT $select FROM $from $whereSql ORDER BY $orderBy LIMIT $per OFFSET $offset");
    $st->execute($params);
    return ['rows' => $st->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
}

function pager(array $data): string
{
    $note = '<div class="text-muted small mb-1">' . (int) $data['total'] . ' record(s) &middot; page '
        . (int) $data['page'] . ' of ' . (int) $data['pages'] . '</div>';
    if ($data['pages'] <= 1) {
        return $note;
    }
    $page = $data['page'];
    $pages = $data['pages'];
    $item = function (int $p, string $label, bool $disabled = false, bool $active = false): string {
        return '<li class="page-item' . ($disabled ? ' disabled' : '') . ($active ? ' active' : '') . '">'
            . '<a class="page-link" href="' . e(qs(['page' => $p])) . '">' . $label . '</a></li>';
    };
    $h = $note . '<nav><ul class="pagination pagination-sm flex-wrap">';
    $h .= $item(max(1, $page - 1), '&laquo;', $page <= 1);
    for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++) {
        $h .= $item($i, (string) $i, false, $i === $page);
    }
    $h .= $item(min($pages, $page + 1), '&raquo;', $page >= $pages);
    return $h . '</ul></nav>';
}

function money($v): string
{
    return number_format((float) $v, 2);
}

function list_title(string $title, string $addUrl, string $addLabel): void
{
    $module = trim(dirname($addUrl), '/');
    echo '<div class="page-head"><h1>' . e($title) . '</h1>';
    if (can($module, 'create')) {
        echo '<a class="btn btn-success" href="' . e($addUrl) . '">+ ' . e($addLabel) . '</a>';
    } else {
        echo '<span class="badge text-bg-light border">View only</span>';
    }
    echo '</div>';
}

function filter_actions(string $resetUrl): string
{
    return '<div class="col-12 col-md-auto d-flex gap-2"><button class="btn btn-primary">Filter</button>'
        . '<a class="btn btn-outline-secondary" href="' . e($resetUrl) . '">Reset</a></div>';
}

/** View / Edit / Delete buttons, shown only when the role is allowed (see PERMISSIONS). */
function row_actions(string $base, int $id): string
{
    $m = trim($base, '/');
    $h = '<td class="text-nowrap">'
        . '<a class="btn btn-sm btn-outline-primary" href="' . e($base) . '/view.php?id=' . $id . '">View</a> ';
    if (can($m, 'edit')) {
        $h .= '<a class="btn btn-sm btn-outline-secondary" href="' . e($base) . '/form.php?id=' . $id . '">Edit</a> ';
    }
    if (can($m, 'delete')) {
        $h .= '<a class="btn btn-sm btn-outline-danger" href="' . e($base) . '/delete.php?id=' . $id . '">Delete</a>';
    }
    return $h . '</td>';
}

function detail_list(array $pairs): void
{
    echo '<dl class="row mb-0">';
    foreach ($pairs as $label => $value) {
        echo '<dt class="col-sm-4 col-lg-3">' . e($label) . '</dt><dd class="col-sm-8 col-lg-9">' . e($value) . '</dd>';
    }
    echo '</dl>';
}

function audit_pairs(array $r): array
{
    return [
        'Created'      => substr((string) $r['created_at'], 0, 19) . ' by ' . ($r['created_by_name'] ?? 'n/a'),
        'Last updated' => substr((string) $r['updated_at'], 0, 19) . ' by ' . ($r['updated_by_name'] ?? 'n/a'),
    ];
}

/**
 * Confirmation page + safe delete. GET shows the confirmation, POST deletes.
 * Blocked while other records still use the record (also enforced by FKs).
 * $dependents = list of [count SQL taking the id, 'label of dependent records'].
 */
function delete_flow(string $table, string $pk, string $base, string $singular, string $summarySql, array $dependents): void
{
    require_permission(trim($base, '/'), 'delete');
    $id = (int) (isset($_POST['id']) ? $_POST['id'] : in_get('id'));

    $st = db()->prepare($summarySql);
    $st->execute([$id]);
    $rec = $st->fetch();
    if (!$rec) {
        flash('danger', ucfirst($singular) . ' not found.');
        redirect($base . '/index.php');
    }

    $blocks = [];
    foreach ($dependents as [$sql, $label]) {
        $c = db()->prepare($sql);
        $c->execute([$id]);
        $n = (int) $c->fetchColumn();
        if ($n > 0) {
            $blocks[] = $n . ' ' . $label;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        if ($blocks) {
            flash('danger', 'Cannot delete: this ' . $singular . ' is still used by ' . implode(', ', $blocks) . '.');
            redirect($base . '/index.php');
        }
        try {
            db()->prepare("DELETE FROM $table WHERE $pk = ?")->execute([$id]);
            flash('success', ucfirst($singular) . ' deleted successfully.');
        } catch (PDOException $ex) {
            log_error($ex);
            flash('danger', 'This ' . $singular . ' cannot be deleted because other records still use it.');
        }
        redirect($base . '/index.php');
    }

    page_header('Delete ' . $singular, trim($base, '/'));
    echo '<h1 class="h3 mb-3">Delete ' . e($singular) . '</h1>';
    echo '<div class="card card-body" style="max-width:640px">';
    echo '<p class="mb-2">You are about to permanently delete:</p>';
    echo '<p class="fw-bold">' . e($rec['summary']) . '</p>';
    if ($blocks) {
        echo '<div class="alert alert-warning">This ' . e($singular) . ' cannot be deleted because it is still used by '
            . e(implode(', ', $blocks)) . '. Remove or reassign those records first.</div>';
        echo '<a class="btn btn-secondary" href="' . e($base) . '/index.php">Back to list</a>';
    } else {
        echo '<div class="alert alert-danger">This action cannot be undone. Are you sure?</div>';
        echo '<form method="post" class="d-flex gap-2">' . csrf_field()
            . '<input type="hidden" name="id" value="' . $id . '">'
            . '<button class="btn btn-danger">Yes, delete</button>'
            . '<a class="btn btn-secondary" href="' . e($base) . '/index.php">Cancel</a></form>';
    }
    echo '</div>';
    page_footer();
}

require __DIR__ . '/layout.php';
