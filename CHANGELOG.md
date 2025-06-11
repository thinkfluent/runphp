# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [v0.27.0] - 2025-06-11

### Added
- Bump PHP versions. 8.4.8 latest

## [v0.26.0] - 2025-06-05

### Added
- Bump PHP versions. 8.4.7 latest

## [v0.25.0] - 2025-04-17

### Added
- Bump PHP versions. 8.4.6 latest

## [v0.24.0] - 2025-03-19

### Added
- Bump PHP version. 8.4.5 latest

## [v0.23.1] - 2025-03-02

### Fixed
- Pin grpc to v1.64.1

## [v0.23.0] - 2025-02-19

### Added
- Bump PHP version. 8.4.4 latest

## [v0.19.0] - 2024-10-31

### Changed
- Bump PHP version. 8.3.13 latest
- [Foundation changes](https://github.com/thinkfluent/runphp-foundation/compare/v0.17.0...v0.18.0)

## [v0.18.0] - 2024-10-24

### Changed
- Bump PHP version. 8.3.12 latest
- Bump yaml & grpc extensions
- [Foundation changes](https://github.com/thinkfluent/runphp-foundation/compare/v0.16.0...v0.17.0)

## [v0.17.0] - 2024-09-25

### Changed
- Bump PHP version. 8.3.11 latest
- [Foundation changes](https://github.com/thinkfluent/runphp-foundation/compare/v0.15.0...v0.16.0)

## [v0.16.0] - 2024-08-05

### Changed
- Bump PHP version, grpc/protobuf extensions

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