# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [v0.11.0] - 2023-12-13

### Added
- Support for trace hinting (especially useful for CLI/Cloud Run Jobs) via `RUNPHP_TRACE_CONTEXT_HINT`

### Changed
- Quiet startup (i.e. no STDOUT/STDERR output) by default