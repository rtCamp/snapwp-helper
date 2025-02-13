<?php

namespace Tests\Acceptance;

use AcceptanceTester;

class AdminMenuCest {
	/**
	 * Tests that the SnapWP Helper is added to the admin menu.
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

		$active_plugins   = unserialize( $active_plugins );
		$active_plugins[] = 'wp-graphql/wp-graphql.php';

		$I->updateInDatabase(
			'wp_options',
			[ 'option_value' => serialize( $active_plugins ) ],
			[ 'option_name' => 'active_plugins' ]
		);

		$I->amOnPluginsPage();
		$I->seePluginActivated( 'wp-graphql' );

		// Submenu should be under WPGraphQL.
		$I->seeElement( 'a', [ 'href' => 'admin.php?page=snapwp-helper' ] );
		$I->amOnAdminPage( 'admin.php?page=snapwp-helper' );
		$I->dontSee( 'Sorry, you are not allowed to access this page.' );
	}

	/**
	 * Tests that the menu is not added when the user does not have the required capability.
	 */
	public function test_no_menu_for_non_admins( AcceptanceTester $I ): void {
		$user_id = $I->haveUserInDatabase( 'no_manage_options', 'editor', [ 'user_pass' => 'pass' ] );
		$I->haveUserCapabilitiesInDatabase(
			$user_id,
			[
				'manage_options'   => false,
				'activate_plugins' => true,
			]
		);
		$I->loginAs( 'no_manage_options', 'pass' );

		// Deactivate all plugins in the db.
		$I->updateInDatabase(
			'wp_options',
			[ 'option_value' => 'a:0:{}' ],
			[ 'option_name' => 'active_plugins' ]
		);

		$I->amOnPluginsPage();
		$I->activatePlugin( 'snapwp-helper' );
		$I->seePluginActivated( 'snapwp-helper' );

		$I->dontSeeElement( 'a', [ 'href' => 'tools.php?page=snapwp-helper' ] );
		$I->dontSeeElement( 'a', [ 'href' => 'admin.php?page=snapwp-helper' ] );

		// Manually activate WPGraphQL
		// This is necessary because the plugin for some reason fails with $I->activatePlugin().
		$active_plugins = $I->grabFromDatabase( 'wp_options', 'option_value', [ 'option_name' => 'active_plugins' ] );

		$active_plugins   = unserialize( $active_plugins );
		$active_plugins[] = 'wp-graphql/wp-graphql.php';

		$I->updateInDatabase(
			'wp_options',
			[ 'option_value' => serialize( $active_plugins ) ],
			[ 'option_name' => 'active_plugins' ]
		);

		$I->amOnPluginsPage();
		$I->seePluginActivated( 'wp-graphql' );

		$I->dontSeeElement( 'a', [ 'href' => 'tools.php?page=snapwp-helper' ] );
		$I->dontSeeElement( 'a', [ 'href' => 'admin.php?page=snapwp-helper' ] );

		// Confirm that the user cannot access the page directly.
		$I->amOnAdminPage( 'tools.php?page=snapwp-helper' );
		$I->see( 'Sorry, you are not allowed to access this page.' );

		$I->amOnAdminPage( 'admin.php?page=snapwp-helper' );
		$I->see( 'Sorry, you are not allowed to access this page.' );

		// Confirm that an admin can access the page.
		$I->haveUserCapabilitiesInDatabase(
			$user_id,
			[
				'manage_options'   => true,
				'activate_plugins' => true,
			]
		);
		$I->amOnPluginsPage();
		$I->seeElement( 'a', [ 'href' => 'admin.php?page=snapwp-helper' ] );
		$I->amOnAdminPage( 'admin.php?page=snapwp-helper' );

		$I->dontSee( 'Sorry, you are not allowed to access this page.' );
	}
}
