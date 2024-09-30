/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

const timeout = 10000;

test.describe( 'Plugin Admin Screen', () => {
	test( 'Admin screen is present', async ( { page, admin } ) => {
		await admin.visitAdminPage( 'admin.php?page=snapwp-helper' );

		const heading = await page.locator( '#snapwp-admin > h1' ).innerText();
		const pText = await page.locator( '#snapwp-admin > p' ).innerText();

		expect( heading ).toBe( 'SnapWP' );
		expect( pText ).toBe( 'Welcome to SnapWP.' );
	} );
} );
