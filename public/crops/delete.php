<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

delete_flow(
    'crop', 'crop_id', '/crops', 'crop',
    "SELECT crop_name || ' - ' || variety AS summary FROM crop WHERE crop_id = ?",
    [['SELECT COUNT(*) FROM planting WHERE crop_id = ?', 'planting record(s)']]
);
