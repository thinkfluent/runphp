<?php
/**
 * runphp auto-prepend file, included by php.ini
 *
 * - Register error, exception handlers
 * - Verify we've disabled Xdebug in production
 * - Check for (and execute) admin requests
 * - Enable profiling
 * - Send PHP memory usage to apache (for inclusion in logs)
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

// Memory & trace data to apache
register_shutdown_function(function (){
    $int_peak = memory_get_peak_usage(true);
    apache_note('php_mem_peak', $int_peak);
    apache_note('php_mem_peak_mb', sprintf('%.2f', $int_peak / 1024 / 1024));
    apache_note('gcp_trace_context', Runtime::get()->getTraceContext());
});

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
    if (extension_loaded('xhprof') && function_exists('xhprof_enable')) {
        register_shutdown_function([$obj_runtime, 'profileRequestShutdown']);
        // @todo Consider enable, sampling only?
        xhprof_enable(XHPROF_FLAGS_MEMORY | XHPROF_FLAGS_CPU);
    }
}

// Optional custom prepend file
if ($bol_include_prepend) {
    include_once $str_optional_prepend;
}
