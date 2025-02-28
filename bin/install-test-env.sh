#!/bin/bash

# Exit if any command fails.
set -e

ORIGINAL_PATH=$(pwd)
BASEDIR=$(dirname "$0")

# Common variables.
WP_DEBUG=${WP_DEBUG:-true}
SCRIPT_DEBUG=${SCRIPT_DEBUG:-true}
WP_VERSION=${WP_VERSION:-"latest"}

# Include common environment variables and functions
source "${BASEDIR}/_lib.sh"

echo "Installing test environment for WordPress ${WP_VERSION}..."

# Create the database if it doesn't exist.
install_db

echo - "switching to the WordPress root directory $WORDPRESS_ROOT_DIR"
mkdir -p "$WORDPRESS_ROOT_DIR"
cd "$WORDPRESS_ROOT_DIR" || { echo "Failed to enter directory: $WORDPRESS_ROOT_DIR"; exit 1; }

# If this is the test site, we reset the database so no posts/comments/etc.
# dirty up the tests.
if [ "$1" == '--reset-site' ]; then
	echo -e "$(status_message "Resetting test database...")"
	wp db reset --yes --quiet --allow-root
fi

if [[ -f "wp-load.php" ]]; then
	CURRENT_WP_VERSION=$(wp core version --allow-root | cut -d '.' -f 1,2)
	echo "Current WordPress version: $CURRENT_WP_VERSION..."

	# Update WordPress if the version is different.
	if [[ -n "$WP_VERSION" ]] && [[ "$WP_VERSION" != "latest" ]] && [[ "$WP_VERSION" != "$CURRENT_WP_VERSION" ]]; then
		status_message "Updating WordPress version $WP_VERSION..."
		wp core download --version="$WP_VERSION" --force --allow-root
	fi
else
	# If WordPress is not present, download it
	echo "WordPress not found. Downloading version $WP_VERSION..."
	wp core download --version="$WP_VERSION" --allow-root
fi


# Create a wp-config.php file if it doesn't exist.
if [ ! -f "wp-config.php" ]; then
	echo -e "$(status_message "Creating wp-config.php file...")"
	wp config create --dbname="$WORDPRESS_DB_NAME" --dbuser="$WORDPRESS_DB_USER" --dbpass="$WORDPRESS_DB_PASSWORD" --dbhost="$WORDPRESS_DB_HOST" --dbprefix="$WORDPRESS_TABLE_PREFIX" --allow-root
fi

# Install WordPress.
echo -e "$(status_message "Installing WordPress...")"

wp core install --title="$SITE_TITLE" --admin_user="$WORDPRESS_ADMIN_USER" --admin_password="$WORDPRESS_ADMIN_PASSWORD" --admin_email=admin@test.local --skip-email --url="$WORDRESS_URL:$BUILTIN_SERVER_PORT" --allow-root

wp core update-db --allow-root

# Make sure the uploads and upgrade folders exist and we have permissions to add files.
echo -e "$(status_message "Ensuring that files can be uploaded...")"
# pwd


mkdir -p \
	wp-content/uploads \
	wp-content/upgrade
chmod 777 \
	wp-content \
	wp-content/plugins \
	wp-config.php \
	wp-settings.php \
	wp-content/uploads \
	wp-content/upgrade

# Install a dummy favicon to avoid 404 errors.
echo -e "$(status_message "Installing a dummy favicon...")"
touch favicon.ico
chmod 767 favicon.ico

# Install external plugins if there is a install-plugins.sh script next to the install-test-env.sh script
if [ -f "$ORIGINAL_PATH/$BASEDIR/install-plugins.sh" ]; then
	echo -e "$(status_message "Installing external plugins...")"
	bash "$ORIGINAL_PATH/$BASEDIR/install-plugins.sh"
fi


# Symlink the plugin to the WordPress plugins directory.
if [ ! -d "$WORDPRESS_ROOT_DIR/wp-content/plugins/$PLUGIN_SLUG" ]; then
	echo -e "$(status_message "Symlinking the plugin to the WordPress plugins directory...")"
	# symlink the current directory to the plugins directory
	cd "$ORIGINAL_PATH"
	ln -s "$(pwd)" "$WORDPRESS_ROOT_DIR/wp-content/plugins/$PLUGIN_SLUG"
fi

# Ensure we are in the WordPress root directory.
cd "$WORDPRESS_ROOT_DIR"

# Activate the plugin.
echo -e "$(status_message "Activating the plugin...")"
wp plugin activate "$PLUGIN_SLUG" --allow-root

# Set pretty permalinks.
echo -e "$(status_message "Setting permalink structure...")"
wp rewrite structure '%postname%' --hard --quiet --allow-root

# Configure site constants.
echo -e "$(status_message "Configuring site constants...")"

WP_DEBUG_CURRENT=$(wp config get --type=constant --format=json WP_DEBUG --allow-root | tr -d '\r')

if [ "$WP_DEBUG" != $WP_DEBUG_CURRENT ]; then
	wp config set WP_DEBUG $WP_DEBUG --raw --type=constant --quiet --allow-root
	WP_DEBUG_RESULT=$(wp config get --type=constant --format=json WP_DEBUG  --allow-root | tr -d '\r')
	echo -e "$(status_message "WP_DEBUG: $WP_DEBUG_RESULT...")"
fi

# Disable Update Checks
echo -e "$(status_message "Disabling update checks...")"
wp config set WP_AUTO_UPDATE_CORE false --raw --type=constant --quiet --allow-root
wp config set AUTOMATIC_UPDATER_DISABLED true --raw --type=constant --quiet --allow-root

SCRIPT_DEBUG_CURRENT=$(wp config get --type=constant --format=json SCRIPT_DEBUG --allow-root | tr -d '\r')
if [ "$SCRIPT_DEBUG" != "$SCRIPT_DEBUG_CURRENT" ]; then
	wp config set SCRIPT_DEBUG "$SCRIPT_DEBUG" --raw --type=constant --quiet --allow-root
	SCRIPT_DEBUG_RESULT=$(wp config get --type=constant --format=json SCRIPT_DEBUG --allow-root | tr -d '\r')
	echo -e "$(status_message "SCRIPT_DEBUG: $SCRIPT_DEBUG_RESULT...")"
fi

SQLDUMP="$WORDPRESS_ROOT_DIR/wp-content/plugins/$PLUGIN_SLUG/tests/_data/dump.sql" 
mkdir -p "$(dirname "$SQLDUMP")"
if [ ! -f "$SQLDUMP" ]; then
	echo -e "$(status_message "Exporting test database dump...")"

	wp db export "$SQLDUMP" --allow-root
fi

# Proof it all worked.
wp plugin list --allow-root
