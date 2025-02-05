#!/usr/bin/env bash

BASEDIR=$(dirname "$0")

source "${BASEDIR}/_lib.sh"
source "${BASEDIR}/docker-functions.sh"

echo -e "$(status_message "WordPress: ${WP_VERSION} PHP: ${PHP_VERSION}")"

# Processes parameters and runs Codeception.
run_tests() {

	if [[ -n "$DEBUG" ]]; then
		local debug="--debug"
	fi

	local suites=$1
	if [[ -z "$suites" ]]; then
		echo echo -e "$(error_message "No test suites specified. Must specify variable SUITES.")"
		exit 1
	fi

	if [[ -n "$COVERAGE" ]]; then
		local coverage="--coverage --coverage-xml $suites-coverage.xml"
	fi

	# If maintenance mode is active, de-activate it
	if $(container wp maintenance-mode is-active --allow-root); then
		echo -e "$(status_message "Deactivating maintenance mode")"
		container wp maintenance-mode deactivate --allow-root
	fi

	# Suites is the comma separated list of suites/tests to run.
	echo -e "$(status_message "Running Test Suite $suites")"
	container bash -c "cd wp-content/plugins/$PLUGIN_SLUG && vendor/bin/codecept run -c codeception.dist.yml ${suites} ${coverage:-} ${debug:-} --no-exit"
}

# Set output permission
echo -e "$(status_message "Setting Codeception output directory permissions")"
container bash -c "chmod 777 wp-content/plugins/$PLUGIN_SLUG/tests/_output"

# Run the tests
run_tests $SUITES

# Set public test result files permissions.
if [ -n "$(ls tests/_output)" ]; then
	echo -e "$(status_message 'Setting result files permissions'.)"
	container bash -c "chmod 777 -R wp-content/plugins/$PLUGIN_SLUG/tests/_output/*"
fi

# Check results and exit accordingly.
if [ -f "tests/_output/failed" ]; then
	echo -e "$(error_message "Uh oh, Codeception tests failed.")"
	exit 1
else
	echo -e "$(status_message "Woohoo! Codeception tests completed succesfully!")"
fi
