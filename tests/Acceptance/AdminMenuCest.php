<?php

namespace Tests\Acceptance;

use AcceptanceTester;

class AdminMenuCest {
	/**
	 * Tests that the plugin can be activated and deactivated correctly.
	 */
	public function test_submenu_location( AcceptanceTester $I ): void {

		// Deactivate all plugins in the db:
		$I->updateInDatabase(
				'wp_options',
				[ 'option_value' => 'a:0:{}' ],
				[ 'option_name' => 'active_plugins' ]
		);

		$I->loginAsAdmin();
		$I->amOnPluginsPage();

		$I->activatePlugin( 'snapwp-helper' );
		$I->seePluginActivated( 'snapwp-helper' );

		$I->seeElement( 'a', [ 'href' => 'tools.php?page=snapwp-helper' ] );
		$I->amOnAdminPage( 'tools.php?page=snapwp-helper' );

		// Manually activate WPGraphQL
		// This is necessary because the plugin for some reason fails with $I->activatePlugin().
		$active_plugins = $I->grabFromDatabase( 'wp_options', 'option_value', [ 'option_name' => 'active_plugins' ] );

		$active_plugins = unserialize( $active_plugins );
		$active_plugins[] = 'wp-graphql/wp-graphql.php';

		$I->updateInDatabase(
			'wp_options',
			[ 'option_value' => serialize( $active_plugins ) ],
			[ 'option_name' => 'active_plugins' ]
		);
		
		$I->amOnPluginsPage();
		$I->seePluginActivated( 'wp-graphql' );

		// Submenu should be under WPGraphQL
		$I->seeElement( 'a', [ 'href' => 'admin.php?page=snapwp-helper' ] );
		$I->amOnAdminPage( 'admin.php?page=snapwp-helper' );
	}
}
