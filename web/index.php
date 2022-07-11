<?php
/**
 * This file is the production entry point, and is designed to be faster
 */

use DigraphCMS\Cache\CachedInitializer;
use DigraphCMS\Digraph;

// Check if .maintenance exists, and if so only show maintenance page
if (is_file(__DIR__ . '/../.maintenance')) {
    include __DIR__ . '/../maintenance.php';
    exit();
}

// load autoloader after maintenance check
require_once __DIR__ . "/../vendor/autoload.php";

// configure initialization cache to have indefinite ttl
// this works on staging/production where cache is cleared on deploy
CachedInitializer::configureCache(__DIR__ . '/../cache', -1);

// load initialization
require_once __DIR__ . '/../initialize.php';

// build and render response
Digraph::renderActualRequest();