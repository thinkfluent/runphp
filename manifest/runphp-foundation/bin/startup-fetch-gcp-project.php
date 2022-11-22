<?php
/**
 * This script is executed once (per container) at startup.
 *
 * Outputs the current GCP project ID so the entrypoint can update the
 * container Environment variables
 *
 * @author Tom Walder <tom@thinkfluent.co.uk>
 */

namespace ThinkFluent\RunPHP;

use ThinkFluent\RunPHP\Google\Metadata;

require_once __DIR__ . '/../src/Google/Metadata.php';
$str_metadata = (new Metadata())->fetch()->getData();
$obj_metadata = \json_decode($str_metadata);
echo $obj_metadata->computeMetadata->v1->project->projectId ?? 'unknown';