<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

delete_flow(
    'farm', 'farm_id', '/farms', 'farm',
    "SELECT farm_name || ' (' || location || ')' AS summary FROM farm WHERE farm_id = ?",
    [['SELECT COUNT(*) FROM planting WHERE farm_id = ?', 'planting record(s)']]
);
