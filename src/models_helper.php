<?php
// src/models_helper.php - autoload models and factory
require_once ROOT . '/src/Database.php';
foreach (glob(ROOT . '/models/*.php') as $f) require_once $f;

function model(string $name): object {
    $cls = ucfirst($name);
    return new $cls(new Database(db()));
}
