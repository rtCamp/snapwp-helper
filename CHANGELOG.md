# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](./README.md#updating-and-versioning).

## [0.0.2] - 2025-02-18

### Breaking
- chore!: Bump minimum required WPGraphQL Content Blocks version to 4.6.0.

### Added
- feat: Add compatibility with WPGraphQL v2.0 and WPGraphQL Content Blocks v4.8.
- feat: make generated `NEXT_PUBLIC_URL` and `NODE_TLS_REJECT_UNAUTHORIZED` environment variables uncommented by default.

### Changed
- dev: Add new `snapwp_helper/admin/capability` filter.
- chore: Update Composer and NPM dependencies to their latest (SemVer-compatible) versions.
- chore: Update PHPStan to v2.0 and remediate new smells.

### Fixed
- fix: Ensure `WPGraphQL` exists before using in `SchemaFilters`.
- fix: Use `manage_options` for default admin screen capability.

### Misc
- docs: Misc cleanup.
- ci: Enable CodeClimate and Coveralls reporting.

## [0.0.1] - 2025-01-30

- Initial (public) release.
