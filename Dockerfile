ARG TAG_NAME="dev-master"

################################################################################################################
FROM fluentthinking/runphp-foundation:v0.5.1-dev
ARG TAG_NAME

# Install our code, then switch from foundation to our runphp site
COPY ./manifest /
RUN a2dissite 001-runphp-foundation && a2ensite 002-runphp

# So we can handle signals properly (Cloud Run will send a SIGTERM)
# Re-map SIGTERM to SIGWINCH for graceful apache shutdown
# https://cloud.google.com/blog/topics/developers-practitioners/graceful-shutdowns-cloud-run-deep-dive
ENTRYPOINT ["/usr/bin/dumb-init", "--rewrite", "15:28", "docker-runphp-entrypoint"]

# Run apache on startup
CMD [ "apache2-foreground" ]

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

# Optional
# ENV RUNPHP_SKIP_COMPOSER_EXTENSION_CHECKS="true"
# ENV RUNPHP_ALLOW_PRODUCTION_ADMIN="true"
# ENV RUNPHP_EXTRA_PREPEND="/some/prepend.php"