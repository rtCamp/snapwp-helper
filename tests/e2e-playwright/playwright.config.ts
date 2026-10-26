import path from 'node:path';
import { defineConfig, devices } from '@playwright/test';

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
// require('dotenv').config();

process.env.WP_ARTIFACTS_PATH = path.join(
	process.cwd(),
	'tests/_output/artifacts'
);

const baseConfig = require( '@wordpress/scripts/config/playwright.config' );

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig( {
	...baseConfig,
	testDir: './specs',
	/* Run tests in files in parallel */
	fullyParallel: true,
	/* Opt out of parallel tests on CI. */
	workers: process.env.CI ? 1 : undefined,
	/* Configure global setup file */
	// globalSetup: require.resolve( './config/global-setup.js' ),
	/* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
	use: {
		...baseConfig.use,
		/* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
		trace: 'on-first-retry',
	},

	/* Configure projects for major browsers */
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
		{
			name: 'firefox',
			use: { ...devices[ 'Desktop Firefox' ] },
		},

		{
			name: 'webkit',
			use: { ...devices[ 'Desktop Safari' ] },
		},
	],
	/* Configure web server */
	webServer: {
		...baseConfig.webServer,
		command: 'npm run wp-env -- start',
	},
} );
