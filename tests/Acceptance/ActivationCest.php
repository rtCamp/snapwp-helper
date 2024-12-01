<?php

namespace Tests\Acceptance;

use AcceptanceTester;

class ActivationCest {
	/**
	 * Tests that the plugin can be activated and deactivated correctly.
	 */
	public function test_it_deactivates_activates_correctly( AcceptanceTester $I ): void {
		$I->loginAsAdmin();
		$I->amOnPluginsPage();

		// Log the page content.
		echo $I->grabPageSource();

		$I->seePluginInstalled( 'snapwp-helper' );

		$I->activatePlugin( 'snapwp-helper' );

		$I->seePluginActivated( 'snapwp-helper' );

		$I->deactivatePlugin( 'snapwp-helper' );

		$I->seePluginDeactivated( 'snapwp-helper' );

		$I->activatePlugin( 'snapwp-helper' );

		$I->seePluginActivated( 'snapwp-helper' );
	}
}
