# Default build versions
ARG BUILD_FRANKENPHP_VER="1.9.1"
ARG BUILD_PHP_VER="8.4.12"
ARG TAG_NAME="dev-master"

################################################################################################################
FROM dunglas/frankenphp:${BUILD_FRANKENPHP_VER}-php${BUILD_PHP_VER} as foundation
# Extensions we'd like to add by default
ARG PHP_EXT_ESSENTIAL="bcmath opcache mysqli pdo_mysql bz2 soap sockets zip gd intl yaml apcu protobuf memcached redis xhprof grpc"
ARG TAG_NAME

# Workaround for noisy pecl E_STRICT
RUN sed -i '1 s/^.*$/<?php error_reporting(E_ALL ^ (E_DEPRECATED));/' /usr/local/lib/php/pearcmd.php

# Additional extensions here:
# frankenphp provides
# https://github.com/mlocati/docker-php-extension-installer
# Which ensures extensions are stripped of strings (small) and should not carry any extra 'dev' package weight
RUN install-php-extensions ${PHP_EXT_ESSENTIAL}
ENV IPE_DONT_ENABLE=1
RUN install-php-extensions xdebug

# TODO - GD with jpeg, webp, freetype

ENV RUNPHP_FOUNDATION_VERSION=${TAG_NAME}

################################################################################################################
FROM foundation
ARG TAG_NAME

# Let's get up to date
RUN apt-get update && apt-get -y upgrade

# Setup the XHprof output dir, install additional libs
ENV XHPROF_OUTPUT="/tmp/xhprof"
#     chown www-data:www-data /tmp/xhprof && \
RUN mkdir -p /tmp/xhprof && \
    apt-get install -y graphviz && \
    apt-get clean

# Install our manifest
COPY ./manifest /

# Install profile viewer
RUN curl https://github.com/thinkfluent/xhprof/archive/feature/frankenphp.tar.gz --silent --location --output /tmp/xhprof.tgz && \
    tar xfz /tmp/xhprof.tgz -C /tmp/ && \
    mv /tmp/xhprof-feature-frankenphp/* /runphp-foundation/admin/xhprof && \
    rm -rf /tmp/xhprof.tgz

# Use our custom entrypoint (which does some dev vs prod config changes & extracts Google Cloud context)
ENTRYPOINT ["docker-runphp-entrypoint"]

# Run frankenphp on startup using the runphp Caddyfile
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