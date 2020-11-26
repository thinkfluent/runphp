<?php
/**
 * runphp preload file, included by opcache.ini
 *
 * - Check we're OK to preload
 * - Try and find the Composer classmap, and compile those files
 *
 * @author Tom Walder <tom@thinkfluent.co.uk>
 */
namespace ThinkFluent\RunPHP;

// Nothing to do in CLI mode
if ('cli' === PHP_SAPI) {
    return;
}

// Just in case opcache is not available
if (!function_exists('opcache_compile_file')) {
    return;
}

// Only in production...
require_once __DIR__ . '/../src/Runtime.php';
$obj_runtime = Runtime::get();
if (Runtime::MODE_PROD === $obj_runtime->getMode()) {
    require_once __DIR__ . '/../src/Preloader.php';
    (new Preloader())->run();
}

