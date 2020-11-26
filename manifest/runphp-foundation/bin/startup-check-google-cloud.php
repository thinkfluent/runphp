<?php
/**
 * This script is executed once (per container) at startup.
 *
 * Determines if we're running on Google Cloud or not (e.g. locally), outputs yes/no so the entrypoint can update the
 * container Environment variables
 *
 * @author Tom Walder <tom@thinkfluent.co.uk>
 */

namespace ThinkFluent\RunPHP;

use ThinkFluent\RunPHP\Google\Metadata;

require_once __DIR__ . '/../src/Google/Metadata.php';

echo ((new Metadata())->canResolve() ? "yes" : "no");
