<?php
/**
 * Quick and dirty "admin" page with some context data and useful links
 *
 * @author Tom Walder <tom@thinkfluent.co.uk>
 */

namespace ThinkFluent\RunPHP;

// Early-out for phpinfo requests
if ('/_runphp/phpinfo' === substr($_SERVER['REQUEST_URI'] ?? '', 0, 16)) {
    phpinfo();
    exit();
}

// Early-out for opcache status requests
if ('/_runphp/opcache' === substr($_SERVER['REQUEST_URI'] ?? '', 0, 16)) {
    echo '<pre>', json_encode(opcache_get_status(false), JSON_PRETTY_PRINT), '</pre>';
    exit();
}

// Gather data
$obj_runtime = Runtime::get();
if ($obj_runtime->isGoogleCloud()) {
    $obj_metadata = $obj_runtime->fetchMetadata();
    $str_project = $obj_metadata->computeMetadata->v1->project->projectId;
    $arr_zone_parts = explode('/', $obj_metadata->computeMetadata->v1->instance->region);
    $str_running_location = array_pop($arr_zone_parts);
    $str_service = $obj_runtime->env()['K_SERVICE'] ?? 'unknown';
    $str_disable_gcloud = '';
} else {
    $obj_metadata = (object)['running_in_google_cloud' => false];
    $str_project = 'project';
    $str_running_location = 'local';
    $str_service = 'local';
    $str_disable_gcloud = 'disabled';
}

// Early-out for metadata requests
if ('/_runphp/metadata' === substr($_SERVER['REQUEST_URI'] ?? '', 0, 17)) {
    echo '<pre>', json_encode($obj_metadata, JSON_PRETTY_PRINT), '</pre>';
    exit();
}

// Extension data
require_once __DIR__ . '/../src/Extensions.php';
$obj_ext = new Extensions();
$arr_installed = $obj_ext->fetchInstalledExtList();
$arr_ext_enabled = $obj_ext->fetchEnabledExtensions();
$arr_ext_installed_not_enabled = array_diff($arr_installed, $arr_ext_enabled);

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">
    <title>runphp admin</title>
</head>
<body>
<div class="container d-flex w-100 h-100 p-3 mx-auto flex-column">
    <header class="mb-auto"></header>
    <main class="inner text-center">
        <h1 class="display-4 mb-3">thinkfluent/runphp</h1>
        <h3 class="display-5 mb-4">Serverless FrankenPHP for Google Cloud Run</h3>
        <div class="mb-3">
            <span class="badge badge-pill badge-primary">project: <?php echo $str_project; ?></span>
            <span class="badge badge-pill badge-primary">location: <?php echo $str_running_location; ?></span>
            <span class="badge badge-pill badge-secondary">mode: <?php echo $obj_runtime->getMode(); ?></span>
            <span class="badge badge-pill badge-secondary">runphp: <?php echo $obj_runtime->getVersion(); ?></span>
            <span class="badge badge-pill badge-secondary">sapi: <?php echo PHP_SAPI; ?></span>
            <span class="badge badge-pill badge-secondary"><?php echo shell_exec('frankenphp -v'); ?></span>
        </div>
        <div class="row mb-3">
            <div class="col">
                <div class="card-deck">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Error Reporting</h5>
                            <p class="card-text">By default, runphp will capture errors and exceptions, and log then to the Google Cloud Error Console.</p>
                            <p class="card-text">
                                <a target="_blank" class="btn btn-outline-primary <?php echo $str_disable_gcloud; ?>" href="https://console.cloud.google.com/errors?project=<?php echo $str_project; ?>">Error Reporting</a>
                            </p>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Environment, phpinfo</h5>
                            <p class="card-text">If you want to take a look at the output of <code>phpinfo()</code>, which includes all available environment variables, click the link below.</p>
                            <p class="card-text">
                                <a class="btn btn-outline-primary" href="/_runphp/phpinfo">phpinfo()</a>
                                <a class="btn btn-outline-primary" href="/_runphp/opcache">opcache status</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col">
                <div class="card-deck">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Google Metadata</h5>
                            <p class="card-text">Google provides Cloud Run applications with access to <a href="https://cloud.google.com/run/docs/reference/container-contract#metadata-server" target="_blank">metadata</a> about their running environment and project. This includes default service account, project, location etc.</p>
                            <p class="card-text">
                                <a class="btn btn-outline-primary <?php echo $str_disable_gcloud; ?>" href="/_runphp/metadata">Show Metadata</a>
                            </p>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Google Cloud Console</h5>
                            <p class="card-text">Apart from error reporting, the Google Cloud Console gives you access to all sorts of useful features, like logs, performance traces and more.</p>
                            <p class="card-text">
                                <a target="_blank" class="btn btn-outline-primary <?php echo $str_disable_gcloud; ?>" href="https://console.cloud.google.com/?project=<?php echo $str_project; ?>">Console</a>
                                <a target="_blank" class="btn btn-outline-primary <?php echo $str_disable_gcloud; ?>" href="https://console.cloud.google.com/traces/overview?project=<?php echo $str_project; ?>">Traces</a>
                                <a target="_blank" class="btn btn-outline-primary <?php echo $str_disable_gcloud; ?>" href="https://console.cloud.google.com/logs/query;query=resource.type%20%3D%20%22cloud_run_revision%22%0Aresource.labels.service_name%20%3D%20%22<?php echo $str_service; ?>%22?project=<?php echo $str_project; ?>">Logs</a>
                            </p>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Xdebug, Profiling</h5>
                            <p class="card-text">In dev mode, we enable Xdebug for you. Control this via the <code>RUNPHP_MODE</code> environment variable.</p>
                            <p class="card-text">Performance profiling with XHProf</p>
                            <?php if ($obj_runtime->shouldProfile()) { ?>
                                <a class="btn btn-outline-primary" href="/xhprof/">Show Profiles</a>
                            <?php } else { ?>
                                    Disabled. Enable with env: <code><?php echo Runtime::ENV_PROFILING;?>=yes</code>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="card-deck">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">PHP Extensions</h5>
                            <p class="card-text">runphp comes with a bunch of common PHP extensions compiled. Some (especially Google-friendly ones, like grpc and protobuf are enabled by default.</p>
                            <h6 class="card-title">Enabled</h6>
                            <p class="card-text">
                                <code><?php echo implode(', ', $arr_ext_enabled); ?></code>
                            </p>
                            <h6 class="card-title">Installed but not enabled</h6>
                            <p class="card-text">
                                <code><?php echo implode(', ', $arr_ext_installed_not_enabled); ?></code>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer class="mt-auto">
        <p class="text-center text-muted">
            <small>
                <a href="https://github.com/thinkfluent/runphp">runphp</a> by <a href="https://x.com/tom_walder">Tom Walder</a>, <a href="https://thinkfluent.co.uk">Fluent Thinking Limited</a>
            </small>
        </p>
        <p class="text-center text-muted">
            <small>
                <a href="https://frankenphp.dev/">FrankenPHP</a> by <a href="https://dunglas.dev/">Kévin Dunglas</a>
            </small>
    </footer>
</div>
</body>
<style>
    html, body {
        height: 100%;
    }
    body {
        display: -ms-flexbox;
        display: flex;
    }
    .display-5 {
        font-size: 2.5rem;
        font-weight: 200;
        line-height: 1.2;
    }
    @media screen and (max-width: 450px) {
        .display-4 {
            font-size: 2.0rem;
        }
        .display-5 {
            font-size: 1.5rem;
        }
    }
</style>
</html>