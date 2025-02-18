# SnapWP Helper
A helper plugin for WordPress used to power [SnapWP](https://snapwp.io)'s solution for headless WordPress.

* [Join the WPGraphQL community on Discord.](https://discord.gg/ZQzAqk4heU)
* [Documentation](#usage)

## Overview

SnapWP Helper is a WordPress plugin that provides the necessary functionality to run [SnapWP](https://github.com/rtCamp/snapwp), extending the capabilities of [WPGraphQL](https://github.com/wp-graphql/wp-graphql) and [WPGraphQL Content Blocks](https://github.com/wpengine/wp-graphql-content-blocks) to power "turn-key" headless WordPress applications.

## System Requirements

- **PHP** 7.4+
- **WordPress** 6.7+
- **[WPGraphQL](https://github.com/wp-graphql/wp-graphql/releases)** 1.28.0+
- **[WPGraphQL Content Blocks](https://github.com/wpengine/wp-graphql-content-blocks/releases)** 4.6.0+

## Quick Install

1. Install and activate [WPGraphQL](https://github.com/wp-graphql/wp-graphql/releases) and [WPGraphQL Content Blocks](https://github.com/wpengine/wp-graphql-content-blocks/releases).
2. Download the [latest SnapWP Helper release](https://github.com/rtCamp/snapwp-helper/releases) `.zip` file, upload it to your WordPress install, and activate the plugin.

### With WP-CLI

```bash
wp plugin install https://github.com/rtCamp/snapwp-helper/releases/latest/download/snapwp-helper.zip --activate
```

### With Composer

```bash
composer require rtcamp/snapwp-helper
```

## Features

- **Block Theme support for WPGraphQL**: SnapWP Helper provides the necessary functionality to power Block Themes in headless WordPress applications, allowing you to use WordPress's Block Editor as the full - or fallback - source of truth for your frontend.
- **Easy Onboarding**: The SnapWP Helper admin screen makes setting up your local development environment a breeze, putting your entire .env configuration in a single, copyable location.
- **WPGraphQL Extension Updates**: SnapWP Helper adds wp-admin update support for GitHub hosted WPGraphQL extensions recommended for the SnapWP stack, and makes it easy to add update checking for any additional extensions you use.
- **Enterprise-grade Codebase**: SnapWP Helper is maintained by [rtCamp](https://rtcamp.com/), a leading WordPress agency with a focus on high-performance, enterprise-grade solutions. Even at version `0.0.1`, this plugin is more stable than many plugins at `1.0.0`, and is performant, extensible, thoroughly tested, and strictly follows the best practices of the headless WordPress ecosystem.

## Usage

> [!TIP]
> While this plugin can be used independently to provide Block Theme support for WPGraphQL, we recommend using it in conjunction with the SnapWP framework. For more information on setting up this plugin as part of SnapWP, please refer to the [SnapWP documentation](https://github.com/rtCamp/snapwp/blob/main/docs/getting-started.md).

- [Actions & Filters](docs/hooks.md)
- [GraphQL Queries](docs/graphql-queries.md)
- [REST API](docs/rest-api.md)

## Development & Contribution

SnapWP Helper is a free and open-source project developed and maintained by [rtCamp](https://rtcamp.com/) and can be used standalone in the headless WordPress ecosystem or as part of  [SnapWP](https://github.com/rtCamp/snapwp)'s framework.

Contributions are _welcome_ and **encouraged!**

To learn more about contributing to this package or SnapWP as a whole, please read the [Contributing Guide](.github/CONTRIBUTING.md).

For development guidelines, please refer to our [Development Guide](DEVELOPMENT.md).

## License

This library is released under ["GPL 3.0 or later" License](LICENSE).

## BTW, We're Hiring!

<a href="https://rtcamp.com/"><img src="https://rtcamp.com/wp-content/uploads/sites/2/2019/04/github-banner@2x.png" alt="Join us at rtCamp, we specialize in providing high performance enterprise WordPress solutions"></a>
