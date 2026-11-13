<?php
// Usage:  php scripts/create_users.php <admin_password> <staff_password>
// Creates (or resets the password of) the users "admin" and "staff".
// Passwords are passed on the command line, so no password is ever committed to Git.
require_once __DIR__ . '/../src/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit('Run this script from the command line.');
}
$adminPw = $argv[1] ?? '';
$staffPw = $argv[2] ?? '';
if (strlen($adminPw) < 8 || strlen($staffPw) < 8) {
    fwrite(STDERR, "Usage: php scripts/create_users.php <admin_password> <staff_password>  (min. 8 characters each)\n");
    exit(1);
}

$sql = 'INSERT INTO users (username, full_name, password_hash, role_id) VALUES (?, ?, ?, ?)
        ON CONFLICT (lower(username)) DO UPDATE SET password_hash = EXCLUDED.password_hash, full_name = EXCLUDED.full_name, role_id = EXCLUDED.role_id';
$st = db()->prepare($sql);
$st->execute(['admin', 'System Administrator', password_hash($adminPw, PASSWORD_DEFAULT), 1]);
$st->execute(['staff', 'Staff User', password_hash($staffPw, PASSWORD_DEFAULT), 2]);
echo "Users ready: admin (Administrator) and staff (Staff).\n";
