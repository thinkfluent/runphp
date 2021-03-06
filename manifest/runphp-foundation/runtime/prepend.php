<?php
/**
 * runphp auto-prepend file, included by php.ini
 *
 * - Register error, exception handlers
 * - Verify we've disabled Xdebug in production
 * - Check for (and execute) admin requests
 * - Enable profiling
 *
 * @author Tom Walder <tom@thinkfluent.co.uk>
 */

namespace ThinkFluent\RunPHP;

require_once __DIR__ . '/../src/Runtime.php';
require_once __DIR__ . '/../src/Google/ReportedErrorHandler.php';

$obj_runtime = Runtime::get();

// Disable Xdebug if we find ourselves in production mode with it on
if (Runtime::MODE_PROD === $obj_runtime->getMode()) {
    if(function_exists('xdebug_disable')) {
        xdebug_disable();
    }
}

// This auto-detects if we're running in Google Cloud (using Runtime, metadata server)
if ($obj_runtime->isGoogleCloud()) {
    (new Google\ReportedErrorHandler())->register();
}

// CLI mode, we're done here...
if ('cli' === PHP_SAPI) {
    return;
}

// In DEV mode, if this is a request for an admin page, load, run & exit.
if ('/_runphp' === substr($_SERVER['REQUEST_URI'] ?? '', 0, 8) && $obj_runtime->allowAdmin()) {
    require_once __DIR__ . '/../admin/admin.php';
    exit();
}

// Optional custom prepend file (we do some work ahead of profile start to keep profile clean)
$bol_include_prepend = false;
$str_optional_prepend = $obj_runtime->getAdditionalPrependFile();
if (is_readable($str_optional_prepend)) {
    $bol_include_prepend = true;
}

// Profiling
if ($obj_runtime->shouldProfile()) {
    if (extension_loaded('tideways_xhprof') && function_exists('tideways_xhprof_enable')) {
        register_shutdown_function([$obj_runtime, 'profileRequestShutdown']);
        // @todo Consider enable, sampling only?
        tideways_xhprof_enable(TIDEWAYS_XHPROF_FLAGS_MEMORY | TIDEWAYS_XHPROF_FLAGS_CPU);
    }
}

// Optional custom prepend file
if ($bol_include_prepend) {
    include_once $str_optional_prepend;
}
