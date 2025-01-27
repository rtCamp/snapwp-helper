#!/usr/bin/env bash

function setup_wordpress_files() {
	# Since it is a plugin deployment, we skip this step
	cd "$GITHUB_WORKSPACE"
	build_root="$(pwd)"
	export build_root
}

function cleanup_node_modules() {
	# Since it is a plugin deployment, we skip this step
	cd "$GITHUB_WORKSPACE"
	# required to remove development dependencies used for building but not required for runtime
	npm ci --omit dev
}

function main() {
	setup_hosts_file
	check_branch_in_hosts_file
	setup_ssh_access
	maybe_install_submodules
	maybe_install_node_dep
	maybe_run_node_build
	setup_wordpress_files
	block_emails
	cleanup_node_modules
	deploy
}

main
