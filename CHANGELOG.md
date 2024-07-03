# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [v0.15.0] - 2024-07-03

### Changed
- Bump PHP version, grpc/protobuf extensions

## [v0.14.0] - 2024-06-19

### Changed
- Bump PHP version, base image

## [v0.13.0] - 2024-02-01

### Changed
- Bump PHP version, base image
- Respect `error_reporting()` when push GoogleReportedError events to Stackdriver

### Fixed
- Ensure long URLs or file paths are shortened & hashed for XHProf output filenames
- Support profiling of CLI scripts

## [v0.12.0] - 2023-12-28

### Changed
- Bump PHP version, base image
- Improve default trace reference when not running in Google Cloud

## [v0.11.0] - 2023-12-13

### Added
- Support for trace hinting (especially useful for CLI/Cloud Run Jobs) via `RUNPHP_TRACE_CONTEXT_HINT`

### Changed
- Quiet startup (i.e. no STDOUT/STDERR output) by default