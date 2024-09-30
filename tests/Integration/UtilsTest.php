<?php
/**
 * Tests methods in the Utils class.
 *
 * @package SnapWP\Helper\Tests\Integration
 */

namespace SnapWP\Helper\Tests\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;
use SnapWP\Helper\Utils\Utils;

/**
 * Tests the Utils class.
 */
class UtilsTest extends WPTestCase {
	
	/**
	 * Tests Utils::kebab_to_camel_case().
	 *
	 * @covers \SnapWP\Helper\Utils\Utils::kebab_to_camel_case
	 */
	public function test_kebab_to_camel_case() {
		$strings_to_test = [
			'hello-world' => 'helloWorld',
				'kebab-case-string' => 'kebabCaseString',
				'single' => 'single',
				'double--dash' => 'doubleDash',
				'trailing-dash-' => 'trailingDash',
				'UPPER-CASE' => 'upperCase',
				'mixed-UPPER-and-lower' => 'mixedUpperAndLower',
				'123-numbers' => '123Numbers',
				'with-special-characters-!@#' => 'withSpecialCharacters!@#',
				'--leading-dashes' => 'leadingDashes',
		];

		foreach( $strings_to_test as $kebab_case => $camel_case ) {
			$this->assertEquals( $camel_case, Utils::kebab_to_camel_case( $kebab_case ) );
		}
	}
}
