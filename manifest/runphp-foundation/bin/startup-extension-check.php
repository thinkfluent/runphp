<?php
/**
 * This script is executed once (per container) at startup.
 *
 * To enable any required extensions that are not already enabled
 *
 * @author Tom Walder <tom@thinkfluent.co.uk>
 */

namespace ThinkFluent\RunPHP;

require_once __DIR__ . '/../src/Extensions.php';
require_once __DIR__ . '/../src/ComposerExtensionSync.php';

try {
    (new ComposerExtensionSync())->run();
} catch (\Throwable $obj_thrown) {
    error_log('Problem doing composer PHP extension warmup. You may be able to ignore this. ' . $obj_thrown->getMessage());
}
