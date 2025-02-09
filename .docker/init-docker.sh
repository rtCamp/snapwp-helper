#!/bin/bash

# Exit if any command fails.
set -e

# Wait for the database
dockerize -wait tcp://"${WORDPRESS_DB_HOST}":3306 -timeout 1m

cd "$WORDPRESS_ROOT_DIR/wp-content/plugins/$PLUGIN_SLUG"

# Setup the test environment
chmod +x ./bin/install-test-env.sh

bash -c "./bin/install-test-env.sh"

# Enable XDebug
if [[ "$COVERAGE" == '1' ]]; then
	echo "Enabling XDebug 3"
	cp /usr/local/etc/php/conf.d/disabled/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/
elif [[ -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ]]; then
	echo "Disabling XDebug"
	rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
fi

# Go back to the root directory
cd "$WORDPRESS_ROOT_DIR"

# Get current user and update permissions
CURRENT_USER=$(id -u):$(id -g)
chown $CURRENT_USER .
