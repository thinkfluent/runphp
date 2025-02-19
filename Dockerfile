ARG TAG_NAME="dev-master"

ARG BUILD_FRANKENPHP_VER="1.4.2"
ARG BUILD_PHP_VER="8.4.3"
ARG BUILD_FOUNDATION_SUFFIX="v0.22.0"

################################################################################################################
FROM fluentthinking/runphp-foundation:${BUILD_FOUNDATION_SUFFIX}-frankenphp${BUILD_FRANKENPHP_VER}-php${BUILD_PHP_VER}
ARG TAG_NAME

# Let's get up to date
RUN apt-get update && apt-get -y upgrade

# Setup the XHprof output dir, install additional libs
ENV XHPROF_OUTPUT="/tmp/xhprof"
#     chown www-data:www-data /tmp/xhprof && \
RUN mkdir -p /tmp/xhprof && \
    apt-get install -y graphviz && \
    apt-get clean



# Install our code, then switch from foundation to our runphp site
COPY ./manifest /
#RUN a2dissite 001-runphp-foundation && a2ensite 002-runphp && a2disconf other-vhosts-access-log


# TODO log format update \
#       NOT for access logs (Cloud Run will take care of that)
#       BUT for "other" logs (e.g. startup logs/messages)
# https://caddy.community/t/google-cloud-structured-logging-format/12678/8


# Install profile viewer
RUN curl https://github.com/thinkfluent/xhprof/archive/master.tar.gz --silent --location --output /tmp/xhprof.tgz && \
    tar xfz /tmp/xhprof.tgz -C /tmp/ && \
    mv /tmp/xhprof-master/* /runphp-foundation/admin/xhprof && \
    rm -rf /tmp/xhprof.tgz

# So we can handle signals properly (Cloud Run will send a SIGTERM)
# Re-map SIGTERM to SIGWINCH for graceful apache shutdown
# https://cloud.google.com/blog/topics/developers-practitioners/graceful-shutdowns-cloud-run-deep-dive
# ENTRYPOINT ["/usr/bin/dumb-init", "--rewrite", "15:28", "docker-runphp-entrypoint"]

# Use our custom entrypoint (which does some dev vs prod config changes & extracts Google Cloud context)
ENTRYPOINT ["docker-runphp-entrypoint"]

# Run frankenphp on startup
CMD ["--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]

# Do some baseline setup for runphp
ENV RUNPHP_MODE="production"
ENV RUNPHP_COMPOSER_PATH="/runphp"
ENV RUNPHP_DOC_ROOT="/runphp"
ENV RUNPHP_INDEX_FILE="index.php"
ENV RUNPHP_VERSION=${TAG_NAME}

# PHP Preloading - "none", "composer-classmap" or "src"
ENV RUNPHP_PRELOAD_STRATEGY="src"
# PHP Preloading - "include" or "compile"
ENV RUNPHP_PRELOAD_ACTION="include"

EXPOSE 8080

# Optional
# ENV RUNPHP_SKIP_COMPOSER_EXTENSION_CHECKS="true"
# ENV RUNPHP_ALLOW_PRODUCTION_ADMIN="yes"
# ENV RUNPHP_XHPROF_PROFILING="yes"
# ENV RUNPHP_EXTRA_PREPEND="/some/prepend.php"
# ENV RUNPHP_EXTRA_ENTRYPOINT_CMD="php /runphp/hello.php"