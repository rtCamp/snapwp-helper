# Development Guide

Code contributions, bug reports, and feature requests are welcome! The following document provides information about development processes and testing.

> [!TIP]
> To learn more about contributing to the project, please read our [Code of Conduct](./CODE_OF_CONDUCT.md) and [Contributing Guidelines](./CONTRIBUTING.md) guidelines.

## TOC

- [Directory Structure](#directory-structure)
- [Local Development](#local-development)
  - [Prerequisites](#prerequisites)
  - [Quick Start](#quick-start)
  - [Setup Plugin Locally](#setup-plugin-locally)
  - [Install WordPress Test Environment](#install-wordpress-test-environment)
  - [Building for Production](#building-for-production)
- [Code Contributions (Pull Requests)](#code-contributions-pull-requests)
  - [Workflow](#workflow)
  - [Code Quality / Code Standards](#code-quality--code-standards)
    - [PHP_CodeSniffer](#php_codesniffer)
    - [PHPStan](#phpstan)
    - [ESLint](#eslint)
  - [Testing](#testing)
    - [Codeception](#codeception)
    - [Playwright](#playwright)
  - [Versioning & Releasing](#versioning--releasing)

## Directory Structure

The plugin is organized as follows:

<details>
<summary> Click to expand </summary>

```log
snapwp-helper/
│
│   # The built assets, compiled via `npm run build:dist`. They are excluded from the repository and should not be edited directly.
├── build/
│
│   # The Frontend assets, including JavaScript, CSS, and images.
│   # Each package has its own directory, which is mapped in `webpack.config.js`.
├── packages/
│   └── admin/ # Runs on our plugin's admin pages.
│
│   # PHP classes and functions.
│   # Classes follow PSR-4, and are namespaced at `SnapWP\Helper`.
├── src/
│   ├── Interfaces/  # PHP interfaces.
│   │
│   │   # Individual features exist as co-located "Modules".
│   ├── Modules/
│   │  ├── Admin.php   # Registers the plugin's admin pages.
│   │  ├── Assets.php  # Registers WP scripts and styles.
│   │  │
│   │  │  # Manages WPGraphQL functionality
│   │  ├── GraphQL/
│   │  │  ├── Interfaces/ # Local PHP interfaces for the Module.
│   │  │  ├── Model/      # Custom WPGraphQL Models
│   │  │  ├── Type/       # Custom WPGraphQL types
│   │  │  │
│   │  │  ├── SchemaFilters.php     # Modifies existing WPGraphQL schema.
│   │  │  └── TypeRegistry.php      # Registers custom WPGraphQL types.
│   │  │
│   │  └── PluginUpdater/ # Plugin Updater Module
│   │     └── UpdateChecker.php  # Update Checker API
│   │
│   ├── Traits/ # Reusable PHP traits.
│   │
│   ├── Utils/  # Utility methods
│   │
│   ├── Autoloader.php   # The PSR-4 autoloader for the plugin.
│   ├── Dependencies.php # Manages plugin dependencies (e.g. WPGraphQL versions).
│   └── Main.php         # The main plugin class.
│
├── tests/ # Test files.
├── vendor/ # Composer dependencies
│
│   # Important root files.
├── access-functions.php  # Globally-available functions. External code should use these functions to access plugin functionality instead of directly calling individual class methods.
├── activation.php        # Runs when the plugin is activated.
└── snapwp-helper.php      # Main plugin file

```

</details>

## Local Development

To test or build the plugin locally, you must [clone the plugin from GitHub](https://github.com/rtCamp/snapwp-helper). Downloading from `Composer`, `WordPress.org`, or `Packagist` will not include the necessary development files.

### Prerequisites

- [Composer](https://getcomposer.org/) v2.0 or higher
- [Node.js](https://nodejs.org/) v20.0 or higher

To use the Docker-ized test environment and to run the tests, you will also need:

- [Docker](https://www.docker.com/) running Docker Compose v2.20 or higher.

### Quick Start

```bash
# Clone the repository
git clone

# Switch to the plugin directory
cd snapwp-helper

# Copy the .env.dist file to .env
# Make Sure to Update the .env file with the correct values
cp .env.dist .env

# Install the test environment
npm run install-test-env

### Commands

# Run lints
npm run lint:php
npm run lint:php:fix
npm run lint:phpstan

# Run Codeception tests
vendor/bin/codecept run <suite-name>
XDEBUG_MODE=coverage vendor/bin/codecept run <suite-name> --coverage --coverage-html

# Run Playwright tests
npm run test:e2e
npm run test:e2e:ui

# Build the plugin for production and generate a .zip file
npm run build:dist
npm run plugin-zip
```
### Setup Plugin Locally

For the plugin to work locally, you need to install both the NPM and Composer dependencies, and then build the Assets.

```bash
npm run install-local-deps
```

This command will install the NPM and Composer dependencies and build the assets.

### Install WordPress Test Environment

To run the tests, you will need a local WordPress environment. You can use any of the following methods.

#### Using a local WordPress Environment

1. Install the [Plugin dependencies](#setup-plugin-locally).

2. Copy the [`.env.dist`](tests/.env.dist) file to `.env` and [update the values](https://wpbrowser.wptestkit.dev/custom-configuration/#using-a-custom-configuration-to-run-tests) to match your local WordPress environment.

   **Note**: If you are using [LocalWP](https://localwp.com/), make sure the `.env` values match the _internal_ values used inside the container.

3. Set up the testing environment by running the following command: `npm run install-test-env`. This command will:
   - Download the latest version of WordPress (if not already downloaded).
   - Reset the database and install WordPress.
   - Install and activate the necessary plugins.
   - Symbolically link the plugin to the WordPress plugins directory.
   - Install the Composer and NPM dependencies for the plugin.

#### Using Docker

1. Copy the [`./.docker/.env.ci`](./.docker/.env.ci) file to the root of the plugin and rename to `.env`.

2. Build and spin up the docker image.

   ```bash
   npm run docker:start
   ```

3. Initialize the docker test environment inside of docker.

   ```bash
   docker exec -e COVERAGE=1 $(docker compose ps -q wordpress) init-docker.sh
   ```


### Building for Production

To build the plugin for production, run the following command:

```bash
npm run build:dist
```

This will clean up the dev-dependencies and build the assets.

You can then generate the production `.zip` file by with the following command:

```bash
npm run plugin-zip

```

## Code Contributions (Pull Requests)

### Workflow

The `develop` branch is used for active development, while `main` contains a snapshot the current stable release. Always create a new branch from `develop` when working on a new feature or bug fix.

Branches should be prefixed with the type of change (e.g. `feat`, `chore`, `tests`, `fix`, etc.) followed by a short description of the change. For example, a branch for a new feature called "Add new feature" could be named `feat/add-new-feature`.

### Code Quality / Code Standards

This project uses several tools to ensure code quality and standards are maintained:

#### PHP_CodeSniffer

This project uses [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer/) to enforce WordPress Coding Standards. We use the [WPGraphQL Coding Standards ruleset](https://github.com/AxeWP/WPGraphQL-Coding-Standards), which is a superset of [WPCS](https://github.com/WordPress/WordPress-Coding-Standards), [VIPCS](https://github.com/Automattic/VIP-Coding-Standards), and [Slevomat Coding Standard](https://github.com/slevomat/coding-standard) tailored for the WPGraphQL ecosystem.

Our specific ruleset is defined in the [`phpcs.xml.dist`](phpcs.xml.dist) file.

You can run the PHP_CodeSniffer checks using the following command:

```bash
npm run lint:php
```

PHP_CodeSniffer can automatically fix some issues. To fix issues automatically, run:

```bash
npm run lint:php:fix
```

#### PHPStan

This project uses [PHPStan](https://phpstan.org/) to perform static analysis on the PHP code. PHPStan is a PHP Static Analysis Tool that focuses on finding errors in your code without actually running it.

You can run PHPStan using the following command:

```bash
npm run lint:phpstan
```

#### ESLint

@todo - Add ESLint configuration

### Testing

#### Codeception

This project uses the [wp-browser](https://wpbrowser.wptestkit.dev/) library for [Codeception](https://codeception.com/) to run PHP unit, integration, and acceptance tests.

Tests are located in the [`tests/`](tests) directory, with a separate directory for each test suite corresponding to its `*.suite.dist.yml` configuration file.

> [!NOTE]
> Codeception `Acceptance` tests require a live database to make changes to, as well as a seed database `dump.sql` file located in `tests/_data`
>
> To generate a clean `dump.sql` file, use WP-CLI to create an export a clean database dump. **This will delete all existing data in the database**.
>
> ```bash
> wp db drop --yes && wp db create && wp db export tests/_data/dump.sql
> ```
>
> To keep your existing data when running Acceptance tests instead of having it be overwritten by the `dump.sql` file, it is recommended to create a separate database for testing and update the `DB_NAME` value in the `.env` file.

##### Using a local WordPress Environment

To run the tests in your [local WordPress environment](#using-a-local-wordpress-environment), you can use the following commands from the root of the _plugin_ directory:

   ```bash
   # Run all tests in a test suite
   vendor/bin/codecept run <suite-name> # e.g. vendor/bin/codecept run Integration

   # Run a specific test file
   vendor/bin/codecept run tests/<suite-name>/<test-file> # e.g. vendor/bin/codecept run tests/Integration/SampleTest.php

   # Run a specific test method
   vendor/bin/codecept run tests/<suite-name>/<test-file>:<test-method> # e.g. vendor/bin/codecept run tests/Integration/SampleTest.php:testMethod

   ```
> [!IMPORTANT]
> If you are using [LocalWP](https://localwp.com/), make sure to run the testsfrom **inside the Local by Flywheel shell** at the root of the _plugin_ directory:
>
>   ```bash
>   # Switch to the plugin directory
>   cd wp-content/plugins/snapwp-helper
>
>   # Run all tests in a test suite
>   vendor/bin/codecept run <suite-name> # e.g. vendor/bin/codecept run Integration
>   ```

##### Using Docker

When using a dockerized test environment, Codeception tests must be run _inside_ the docker container.

While you can always connect to the container and run the tests manually, you can also use the following commands to run the tests from your host machine (outside the container), by using the `bin/run-codeception.sh` script with the necessary environment variables.

```bash
# Run the Integration suite.
SUITES=Integration bin/run-codeception.sh

# Run the Acceptance suite.
SUITES=Aceptance bin/run-codeception.sh

# Run a specific test method in a specific file.
SUITES="tests/Integration/SampleTest.php:testSampleFunction" bin/run-codeception.sh
```

##### Generating Code Coverage Reports

To generate code coverage reports, you can use the previous commands with the `--coverage` flag. In some cases you may need to set the `XDEBUG_MODE` environment variable to `coverage` to enable code coverage.

```bash
# Run code coverage and generate a HTML report.
XDEBUG_MODE=coverage vendor/bin/codecept run <suite-name> --coverage --<report-type> # e.g. XDEBUG_MODE=coverage vendor/bin/codecept run Integration --coverage --coverage-html
```

#### Playwright

This project uses [@wordpress/e2e-test-utils-playwright](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-e2e-test-utils-playwright/) to run end-to-end tests using [Playwright](https://playwright.dev/).

Tests are located in the [`tests/e2e-playwright`](tests/e2e-playwright) directory.

To run the Playwright tests, you can use the following commands:

```bash
# Run the test suite
npm run test:e2e

# Run the test suite in UI mode
npm run test:e2e:ui
```

### Versioning & Releasing

This project uses [Semantic Versioning](https://semver.org/). When making a release, update the version number according to the following rules:

- Increment the **major** (`X.y.z`) version when making _breaking_ API changes.
- Increment the **minor** (`x.Y.z`) version when adding new features or functionality in a backwards-compatible manner.
- Increment the **patch** (`x.y.Z`) version when making backwards-compatible _bug fixes_ only.

> [!NOTE]
> Versioning is a machine tool for developers. It is not a marketing tool for users - that's what [changelogs](CHANGELOG.md) and release announcements are for. So, don't worry too much about version numbers. Just follow the rules above.

As will all other Code Contributions, the release process is managed through Pull Requests. When you are ready to make a release, create a new branch from `develop` and make the necessary changes to update the version number (in readme, plugin header, constants, etc) and the changelog.

One the changes are made and the PR is merged, you should push a copy of the `develop` branch to the `main` branch, and then create a new GitHub release with the version number and a summary of the changes.
