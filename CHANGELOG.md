# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](./README.md#updating-and-versioning).

## Unreleased

- feat: Add `CoreImageAttributes.imageMaxWidth` field to expose `big_image_size_threshold` or `theme-defined wide size`.

## [0.2.2] - 2025-03-31

This _minor_ release adds support for the `generalSettings.siteIcon` field in the schema, and updates the EnvGenerator variables to reflect the latest requirements by the SnapWP Framework.

**Note**: While there are _no breaking changes_ in this release, the generated `.env` file is only compatible with the latest version of the SnapWP Framework. If you are using an older version, you will need to set your `.env` variables manually.

- feat: Expose `generalSettings.siteIcon` field to the schema.
- refactor: Update EnvGenerator variables and handling.
- dev: Deprecate the  `snapwp_helper_get_env_content()` and `snapwp_helper_get_env_variables()` functions, in favor of using the `VariableRegistry` and `Generator` classes directly.
- chore: Update Composer / NPM dependencies to their latest (SemVer-compatible) versions.
- chore: Fix readme.txt link syntax.
- tests: Use `IntegrationTestCase` for Integration tests.
- chore: Remove `npm install` from admin screen steps since it is no longer required.

## [0.2.1] - 2025-03-10

This _patch_ release fixes a regression where an unset `flat` query arg passed to `templateByUri.editorBlocks` would return unflattened blocks.

- fix: Ensure blocks are flattened if no `flat` query arg is passed to `templateByUri.editorBlocks`.
- chore: Update Composer and NPM dev-dependencies to their latest (SemVer-compatible) versions.

## [0.2.0] - 2025-03-01

This _major_ release aligns the WPGraphQL schema with changes backported upstream to WPGraphQL. Additionally, it fixes an issue when querying for nested `editorBlocks` data.

### Breaking
- feat!: Change `ScriptModuleDependency.importType` from type `String` to `ScriptModuleImportTypeEnum`.
- feat!: Remove `EnqueuedScript.location` field in favor of `EnqueuedScript.groupLocation`.

### Fixed

- fix: Ensure `templateByUri.editorBlocks` respects the `flat` query arg.

### Misc
- ci: Unmute `WP_DEBUG_DISPLAY` during env creation.
- chore: Remove unnecessary WordPress version checks.
- chore: Update Composer and NPM dev-dependencies to their latest (SemVer-compatible) versions.

## [0.1.0] - 2025-02-19

This release represents the first 0.X release of SnapWP Helper, allowing for future _patch_ releases to be semantically versioned without breaking changes.

There are **no breaking changes** in this release.

### Changed
- chore: Update Admin screen links and latest steps.

### Misc
- chore: Update Composer dev-dependencies to their latest versions.

## [0.0.2] - 2025-02-18

### Breaking
- chore!: Bump minimum required WPGraphQL Content Blocks version to 4.6.0.

### Added
- feat: Add compatibility with WPGraphQL v2.0 and WPGraphQL Content Blocks v4.8.
- feat: make generated `NEXT_PUBLIC_URL` and `NODE_TLS_REJECT_UNAUTHORIZED` environment variables uncommented by default.

### Changed
- dev: Add new `snapwp_helper/admin/capability` filter.

### Fixed
- fix: Ensure `WPGraphQL` exists before using in `SchemaFilters`.
- fix: Use `manage_options` for default admin screen capability.

### Docs
- docs: Misc cleanup.
- docs: Add usage docs on querying for `globalStyles` data.
- docs: Add Quick Install instructions for `wp-cli` and `composer`.

### Misc
- ci: Enable CodeClimate and Coveralls reporting.
- chore: Update Composer and NPM dependencies to their latest (SemVer-compatible) versions.
- chore: Update PHPStan to v2.0 and remediate new smells.
- chore: lint JS files with ESLint.

## [0.0.1] - 2025-01-30

- Initial (public) release.
