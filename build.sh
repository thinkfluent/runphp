#!/usr/bin/env bash

# build.sh -v 7.4.33 -f v0.8.5 -t dev

# Args
while getopts ":v:t:f:" opt; do
  case $opt in
    v) BUILD_PHP_VER="$OPTARG"
    ;;
    f) BUILD_FOUNDATION_SUFFIX="$OPTARG"
    ;;
    t) BUILD_TAG="$OPTARG"
    ;;
    \?) echo "Invalid option -$OPTARG" >&2
    exit 1
    ;;
  esac
done

# And some defaults
: ${BUILD_PHP_VER:=7.4.33}
: ${BUILD_FOUNDATION_SUFFIX:=v0.8.6}
: ${BUILD_TAG:=dev}

# Fully qualified tag
RUNPHP_REV="${BUILD_PHP_VER}-${BUILD_TAG}"
export RUNPHP_REV

# Use the current folder context for the build
SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

echo "Building ${RUNPHP_REV}..."
docker build \
  --build-arg TAG_NAME=${RUNPHP_REV} \
  --build-arg BUILD_PHP_VER=${BUILD_PHP_VER} \
  --build-arg BUILD_FOUNDATION_SUFFIX=${BUILD_FOUNDATION_SUFFIX} \
  -t runphp:${RUNPHP_REV} ${SCRIPT_DIR}
