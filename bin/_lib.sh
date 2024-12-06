#!/usr/bin/env bash

source ".env"

##
# Add error message formatting to a string, and echo it.
#
# @param {string} message The string to add formatting to.
##
error_message() {
	echo -en "\033[31mERROR\033[0m: $1"
}

##
# Add warning message formatting to a string, and echo it.
#
# @param {string} message The string to add formatting to.
##
warning_message() {
	echo -en "\033[33mWARNING\033[0m: $1"
}

##
# Add status message formatting to a string, and echo it.
#
# @param {string} message The string to add formatting to.
##
status_message() {
	echo -en "\033[32mSTATUS\033[0m: $1"
}

##
# Add formatting to an action string.
#
# @param {string} message The string to add formatting to.
##
action_format() {
	echo -en "\033[32m$1\033[0m"
}

##
# Check if the command exists as some sort of executable.
#
# The executable form of the command could be an alias, function, builtin, executable file or shell keyword.
#
# @param {string} command The command to check.
#
# @return {bool} Whether the command exists or not.
##
command_exists() {
	type -t "$1" >/dev/null 2>&1
}


install_db() {
	if [ "${SKIP_DB_CREATE}" = "true" ]; then
		return 0
	fi

	local EXTRA=""

	if [ -n "$WORDPRESS_DB_SOCKET" ]; then
		EXTRA=" --socket=$WORDPRESS_DB_SOCKET"
	elif [ -n "$WORDPRESS_DB_HOST" ]; then
		EXTRA=" --host=$WORDPRESS_DB_HOST --protocol=tcp"
		if [ -n "$WORDPRESS_DB_PORT" ]; then
				EXTRA="$EXTRA --port=$WORDPRESS_DB_PORT"
		fi
	fi

	# create database
	echo -e "$(status_message "Creating the database (if it does not exist)...")"

	RESULT=$(mysql -u $WORDPRESS_DB_USER --password="$WORDPRESS_DB_PASSWORD" --skip-column-names -e "SHOW DATABASES LIKE '$WORDPRESS_DB_NAME'"$EXTRA)
	if [ "$RESULT" != $WORDPRESS_DB_NAME ]; then
		mysqladmin create $WORDPRESS_DB_NAME --user="$WORDPRESS_DB_USER" --password="$WORDPRESS_DB_PASSWORD"$EXTRA
	fi
}
