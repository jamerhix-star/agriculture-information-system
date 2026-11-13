<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

delete_flow(
    'farmer', 'farmer_id', '/farmers', 'farmer',
    "SELECT first_name || ' ' || last_name || ' (' || address || ')' AS summary FROM farmer WHERE farmer_id = ?",
    [['SELECT COUNT(*) FROM farm WHERE farmer_id = ?', 'farm record(s)']]
);
