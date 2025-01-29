# Serverless PHP Toolkit for Google Cloud Run

The `thinkfluent/runphp` toolkit enables rapid application development and serverless hosting on [Google Cloud Run](https://cloud.google.com/run).

Docker images can be found here: https://hub.docker.com/r/fluentthinking/runphp

| PHP Version | Latest Image                          |
|-------------|---------------------------------------|
| PHP 8.4.3   | `fluentthinking/runphp:8.4.3-v0.22.2` `latest` |
| PHP 8.3.16  | `fluentthinking/runphp:8.3.16-v0.22.2` |
| PHP 8.2.27  | `fluentthinking/runphp:8.2.27-v0.22.2` |
| PHP 8.1.31  | `fluentthinking/runphp:8.1.31-v0.22.2` |
| PHP 8.0.30  | `fluentthinking/runphp:8.0.30-v0.22.2` |
| PHP 7.4.33  | `fluentthinking/runphp:7.4.33-v0.22.2` |

#### Some Benefits of Cloud Run with runphp

* Auto-scaling, scale-to-zero serverless hosting on Google infrastructure
* [Free tier](https://cloud.google.com/run#section-14) (2m req per month), then pay-as-you-go
* [Continuous Deployment](https://cloud.google.com/blog/products/application-development/cloud-run-integrates-with-continuous-deployment) - automated build & deployment integrations via GitHub & more
* Custom domains with free, automatically renewing SSL certificates

## Getting Started

### The Vanilla Build

This should start a local instance of the default runphp image.
```bash
docker run --rm -e "RUNPHP_MODE=development" -e "PORT=80" -p 8080:80 fluentthinking/runphp:latest
```

You should be able to access the default home page and admin interfaces as follows:
* http://localhost:8080
* http://localhost:8080/_runphp

### Building Your Own Application

You will most likely need a `Dockerfile`; here is a simple example, which uses the baked in apache configs.

```Dockerfile
FROM fluentthinking/runphp:latest

RUN mkdir /app
COPY . /app

ENV RUNPHP_COMPOSER_PATH="/app"
ENV RUNPHP_DOC_ROOT="/app/public"
ENV RUNPHP_INDEX_FILE="index.php"
```

```bash
docker build -t myapp .
```
```bash
docker run --rm -e "RUNPHP_MODE=development" -e "PORT=80" -p 8080:80 myapp:latest
```

#### Pushing to Cloud Run

These examples assume the "latest" tag, but in reality, you should use a semver or equivalent tag.

```bash
docker tag myapp:latest eu.gcr.io/<google-project>/myapp:latest
```
```bash
docker push eu.gcr.io/<google-project>/myapp:latest
```
```bash
gcloud run deploy <cloud-run-service-name> \
    --image=eu.gcr.io/<google-project>/myapp:latest \
    --platform managed \
    --allow-unauthenticated \
    --set-env-vars "RUNPHP_MODE=development" \
    --region europe-west1 \
    --project <google-project>
```

## Components
runphp has the following key areas of concern:

* **Foundation Docker Image**
  * Based on upstream `php:?.?-apache`
  * Apache configurations tweaks including remote IP fixes for `X-Forwarded-For`, security options etc.
  * A useful default set of PHP extensions
  * Extensible (Docker!) if you need to run custom images or add further extension
  * https://github.com/thinkfluent/runphp-foundation 
* **Google Cloud Integrations**
  * Google-centric PHP extensions built-in, for high performance Google API calls with `grpc` and `protobuf`
  * Automatic integration with Google [Cloud Error Reporting](https://cloud.google.com/error-reporting)
  * Google trace-linked logging (request-grouped log messages in the GCP log viewer). PHP memory and latency data by default.
    * See https://github.com/thinkfluent/runphp-monolog-formatter
* **Composer-oriented Project Tooling**
  * (coming soon) Rapid creation of new projects with `composer create-project`
  * PHP extension detection & automatic enable via `ext-*`
  * PHP preloading from Composer class map (or other sources) for high performance in production
* **Getting-started Admin Interface**
  * Simple admin UI, with phpinfo, opcache inspection
* **Request Profiling**
  * [XHProf-based profiling](#xhprof-profiling)

## Customisation

### Docker Entrypoint

In our Docker entrypoint, we set up some important ENV variables to help the runphp stack operate correctly.

If you want to do additional work on container startup, you can 

* Define `RUNPHP_EXTRA_ENTRYPOINT_CMD="<your command here>"` in your environment, and we'll execute your script after ours
  * This means you get access to `RUNPHP_GOOGLE_CLOUD` and other environment values
* Replace the entrypoint, but make sure you execute `docker-runphp-entrypoint` at the end of your script.
  * We've included an example entrypoint script at [manifest/usr/local/bin/docker-custom-entrypoint](/runphp/thinkfluent/runphp/blob/master/manifest/usr/local/bin/docker-custom-entrypoint)

### Apache

If you want to roll your own apache configs, you can disable the runphp sites in your Dockerfile with
```
RUN a2dissite 002-runphp
``` 

### PHP Prepend

runphp takes advantage of the PHP `auto_prepend_file` ini control to provide some of its features.

If you want to provide an additional prepend file, without losing the runphp stack, you can 
define `RUNPHP_EXTRA_PREPEND="/some/prepend.php"` in your environment.

If you also enable profiling (see below on how to do this), your prepend file is included in the profile.

### PHP Preloading

runphp supports a few PHP preloading strategies, as no one-solution fits all. 
They are controlled via environment variables as follows:

* `RUNPHP_COMPOSER_PATH="/app"`
* `RUNPHP_PRELOAD_STRATEGY="src"` - "none", "composer-classmap" or "src"
* `RUNPHP_PRELOAD_ACTION="include"` - "include" or "compile"

### Startup Messages
runphp can produce a few useful startup messages, such as whether it has detected itself as running on Google Cloud.

However, startup output to STDERR can be noisy, so is disabled by default.

You can enable noisy startup with `RUNPHP_QUIET=no` as an environment variable.

## Cloud Run Jobs
The runphp stack can be used with Cloud Run Jobs, as well as Cloud Run services.

### Logging & Tracing
To help with log tracing when running as a cli job, we provide support for the following ENV variables:

`RUNPHP_TRACE_CONTEXT_HINT`

Which, if supplied during job execution, will be used as the trace context for the job in any logs produced with
https://github.com/thinkfluent/runphp-monolog-formatter

## Automated Build & Deploy
This is the **recommended method** - [Continuous Deployment using Cloud Build](https://cloud.google.com/run/docs/continuous-deployment-with-cloud-build). Which can be very easily set up in the Cloud Run interface when creating a service, or manually afterwards.

### XHProf Profiling

By adding the following ENV variable, we turn on xhprof profiling for PHP requests
* `RUNPHP_XHPROF_PROFILING="yes"`

The XHProf GUI can be accessed at `/xhprof`

Please note: profiling data files are transient in Cloud Run, as instances are stopped & started.

## Building runphp

If you need to build your own base images (this repo)...

```bash
docker build \
  --platform linux/amd64 \
  --build-arg TAG_NAME=dev \
  --build-arg BUILD_PHP_VER=2 \
  --build-arg BUILD_FOUNDATION_SUFFIX=v0.20.0 \
  -t runphp:dev .
```