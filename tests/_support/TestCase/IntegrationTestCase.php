<?php
/**
 * Plugin Test Case.
 *
 * @package SnapWP\Helper\Tests\TestCase
 */

namespace SnapWP\Helper\Tests\TestCase;

use Tests\WPGraphQL\Logger\CodeceptLogger;
use Tests\WPGraphQL\TestCase\WPGraphQLTestCommon;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * IntegrationTestCase class.
 *
 * @todo extend WPGraphQLTestCase once it supports WPBrowser > 3.5
 */
class IntegrationTestCase extends WPTestCase {
	use WPGraphQLTestCommon;

	// Possible field anonymous values.
	public const NOT_NULL  = 'codecept_field_value_not_null';
	public const IS_NULL   = 'codecept_field_value_is_null';
	public const NOT_FALSY = 'codecept_field_value_not_falsy';
	public const IS_FALSY  = 'codecept_field_value_is_falsy';

	// Search operation enumerations.
	public const MESSAGE_EQUALS      = 100;
	public const MESSAGE_CONTAINS    = 200;
	public const MESSAGE_STARTS_WITH = 300;
	public const MESSAGE_ENDS_WITH   = 400;

	/**
	 * Stores the logger instance.
	 *
	 * @var \Tests\WPGraphQL\Logger\CodeceptLogger
	 */
	protected $logger;

	/**
	 * @var \IntegrationTester
	 */
	protected $tester;

	/**
	 * {@inheritDoc}
	 */
	protected static function getLogger() {
		return new CodeceptLogger();
	}
}
