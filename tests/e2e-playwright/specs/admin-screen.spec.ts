/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Plugin Admin Screen', () => {
	test( 'Admin screen is present', async ( { page, admin } ) => {
		await admin.visitAdminPage( 'admin.php?page=snapwp-helper' );

		const heading = await page.locator( '#snapwp-admin > h2' ).innerText();
		const envHeading = await page
			.locator( '#snapwp-admin > h3:nth-of-type(1)' )
			.innerText();
		const setupHeading = await page
			.locator( '#snapwp-admin > h3:nth-of-type(2)' )
			.innerText();

		expect( heading ).toBe( 'SnapWP' );
		expect( envHeading ).toBe( 'Environment Variables' );
		expect( setupHeading ).toBe( 'SnapWP Frontend Setup Guide' );
	} );
} );
