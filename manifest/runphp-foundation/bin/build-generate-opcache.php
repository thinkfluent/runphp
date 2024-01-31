<?php
/**
 * This script is intended to run at build-time, to pre-compile the OPCache to disk for production
 *
 * Specifically for disk-based files cache where we are going to run in a CLI environment.
 *
 * @author Tom Walder <tom@thinkfluent.co.uk>
 */

declare(strict_types=1);

namespace ThinkFluent\RunPHP;

// Just in case opcache is not available
if (!function_exists('opcache_compile_file')) {
    echo 'OPCache extension is not installed/enabled', PHP_EOL;
    return;
}

if ('cli' !== PHP_SAPI) {
    echo 'OPCache build-stage compiling is only available in CLI mode', PHP_EOL;
    return;
}

// Check the opcache config is enabled and disk persistence enabled
$opcacheStatus = opcache_get_status(false);
if (empty($opcacheStatus['opcache_enabled'])) {
    echo 'OPCache is not enabled for CLI', PHP_EOL;
    return;
}

// Check for disk persistence, target folder
$str_opcache_target = rtrim($opcacheStatus['file_cache'] ?? '', '/');
if (empty($str_opcache_target)) {
    echo 'OPCache is not configured for disk persistence', PHP_EOL;
    return;
}

// Only in production...
require_once __DIR__ . '/../src/Runtime.php';
$obj_runtime = Runtime::get();
if (Runtime::MODE_PROD === $obj_runtime->getMode()) {
    require_once __DIR__ . '/../src/PreloadConfig.php';
    require_once __DIR__ . '/../src/Preloader.php';
    echo 'Running build-stage OPCache compiler', PHP_EOL;
    $obj_preloader = new Preloader(PreloadConfig::fromEnv());
    $obj_preloader->run();

    // Output for the build stage
    $str_cache_size_on_disk = trim(shell_exec(sprintf('du -hs %s', $str_opcache_target)));
    echo sprintf(
        'OPCache compiled file count: %d, size on disk: %s, cache location: %s',
        $obj_preloader->getProcessedFileCount(),
        $str_cache_size_on_disk,
        $str_opcache_target
    ), PHP_EOL;

    // Record some stats in the built image
    file_put_contents(
        sprintf('%s/compile.json', $str_opcache_target),
        json_encode([
            'dtm' => (new \DateTime())->format('c'),
            'compiled_files' => $obj_preloader->getProcessedFileCount(),
            'size_on_disk' => $str_cache_size_on_disk,
            'status_after' => opcache_get_status(false)
        ], JSON_PRETTY_PRINT)
    );
} else {
    echo 'OPCache build-stage compilation is ONLY available in PRODUCTION mode, skipping.', PHP_EOL;
    return;
}
