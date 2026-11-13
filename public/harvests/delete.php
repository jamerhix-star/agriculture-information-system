<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

delete_flow(
    'harvest', 'harvest_id', '/harvests', 'harvest record',
    "SELECT '#' || h.harvest_id || ' - ' || fm.farm_name || ' / ' || c.crop_name || ' (' || h.harvest_date || ', ' || h.quantity_kg || ' kg)' AS summary
       FROM harvest h JOIN planting p ON p.planting_id = h.planting_id
       JOIN farm fm ON fm.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id WHERE h.harvest_id = ?",
    []
);
