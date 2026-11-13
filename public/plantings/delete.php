<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

delete_flow(
    'planting', 'planting_id', '/plantings', 'planting record',
    "SELECT '#' || p.planting_id || ' - ' || f.farm_name || ' / ' || c.crop_name || ' (' || p.planting_date || ')' AS summary
       FROM planting p JOIN farm f ON f.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id WHERE p.planting_id = ?",
    [['SELECT COUNT(*) FROM harvest WHERE planting_id = ?', 'harvest record(s)']]
);
