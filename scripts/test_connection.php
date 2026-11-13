<?php
// Usage: php scripts/test_connection.php
require_once __DIR__ . '/../src/bootstrap.php';

$row = db()->query('SELECT (SELECT COUNT(*) FROM farmer) AS farmers, (SELECT COUNT(*) FROM harvest) AS harvests, (SELECT COUNT(*) FROM users) AS users')->fetch();
echo "Connected to Supabase OK. farmers={$row['farmers']}, harvests={$row['harvests']}, users={$row['users']}\n";
