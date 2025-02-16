#!/bin/bash

# Exit if any command fails.
set -e

## Add the `wp plugin install` and `wp plugin activate` commands here for any external plugins that this one depends on for testing.
#
# Example: Install and activate WPGraphQL from the .org plugin repository.
#
# if ! $( wp plugin is-installed wp-graphql --allow-root ); then
#   wp plugin install wp-graphql --allow-root
# fi
# wp plugin activate wp-graphql  --allow-root
#
# Example: Install and activate the WPGraphQL Upload plugin from GitHub.
#
# if ! $( wp plugin is-installed wp-graphql-upload --allow-root ); then
#   wp plugin install https://github.com/dre1080/wp-graphql-upload/archive/refs/heads/master.zip --allow-root
# fi
# wp plugin activate wp-graphql-upload --allow-root

# WPGraphQL for WPGraphQL plugin.
if ! $( wp plugin is-installed wp-graphql --allow-root ); then
	wp plugin install wp-graphql --version=1.32.1 --allow-root # @todo: revert to latest version once the plugin is compatible with v2.0
fi
wp plugin activate wp-graphql --allow-root

# WPGraphQL Content Blocks plugin.
if ! $( wp plugin is-installed wp-graphql-content-blocks --allow-root ); then
	wp plugin install https://github.com/wpengine/wp-graphql-content-blocks/releases/latest/download/wp-graphql-content-blocks.zip --allow-root
fi
wp plugin activate wp-graphql-content-blocks --allow-root

# WPGraphQL IDE
if ! $( wp plugin is-installed wpgraphql-ide --allow-root ); then
	wp plugin install https://github.com/wp-graphql/wpgraphql-ide/releases/latest/download/wpgraphql-ide.zip --allow-root
fi
wp plugin activate wpgraphql-ide --allow-root
