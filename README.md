# SnapWP Helper - WPGraphQL Extension Updater

> A helper plugin for WordPress used to power [SnapWP](https://snapwp.io)'s Headless WordPress framework.

## Table of Contents

- [Overview](#overview)
- [System Requirements](#system-requirements)
- [Getting Started](#getting-started)
- [Usage](#usage)
- [Features](#features)
- [Reference](#reference)
- [Local Development & Contributing Guidelines](#local-development-testing-and-contribution)

## Overview

SnapWP Helper is a WordPress plugin that allows you to quickly install WPGraphQL extensions and manage extension updates from the WordPress dashboard.

## System Requirements

- **PHP** 7.4 or higher
- **WordPress** 6.7 or higher
- **WPGraphQL** 1.28.0 or higher
- **WPGraphQL Content Blocks** 4.6.0 or higher

## Getting Started

1. Clone this repository to the `wp-content/plugins` directory.
2. Initialize the plugin by running `npm run install-local-deps`
3. Activate the plugin from WordPress dashboard.

> [!NOTE]
> To build the plugin for production, run `npm run build:dist && npm run plugin-zip` and then upload the generated `snapwp-helper.zip` file to your WordPress site.
>
> For more information on building the plugin, see [DEVELOPMENT.md](DEVELOPMENT.md#building-for-production).

## Usage

@todo - Add usage instructions

## Features

@todo - Add features

## Documentation

- [Actions & Filters](docs/hooks.md)
- [GraphQL Queries](docs/graphql-queries.md)
- [REST API](docs/rest-api.md)

## Development & Contribution

SnapWP Helper is a free and open-source project developed and maintained by [rtCamp](https://rtcamp.com/). We *welcome* and **encourage** contributions from the community to help improve this project.

To contribute to this project, please follow the guidelines outlined in the [CONTRIBUTING.md](.github/CONTRIBUTING.md) file. To get started with local development and learn more about the project's development processes, code standards, and testing, refer to the [DEVELOPMENT.md](DEVELOPMENT.md) document.

## License

This library is released under ["GPL 3.0 or later" License](LICENSE).

## BTW, We're Hiring!

<a href="https://rtcamp.com/"><img src="https://rtcamp.com/wp-content/uploads/sites/2/2019/04/github-banner@2x.png" alt="Join us at rtCamp, we specialize in providing high performance enterprise WordPress solutions"></a>
