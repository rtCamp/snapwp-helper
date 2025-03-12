<?php
/**
 * Tests querying for `GeneralSettings > SiteIcon` field.
 *
 * @package SnapWP\Helper\Tests\Integration
 */

namespace SnapWP\Helper\Tests\Integration;

/**
 * Class - SiteIconTest
 */
class SiteIconTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {
	/**
	 * User ID.
	 *
	 * @var int
	 */
	protected $user_id;

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	protected $attachment_id;

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		flush_rewrite_rules( true ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- Required for the test.

		$this->clearSchema();

		$this->user_id = $this->factory->user->create(
			[
				'role' => 'administrator',
			]
		);

		$tmp_file            = SNAPWP_HELPER_PLUGIN_DIR . '/tests/_data/image/wordpress.png';
		$this->attachment_id = $this->factory->attachment->create_upload_object( $tmp_file );

		// Set it as the site icon.
		update_option( 'site_icon', $this->attachment_id );
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		wp_delete_user( $this->user_id );
		$this->clearSchema();

		// Reset the site icon.
		delete_option( 'site_icon' );

		parent::tearDown();
	}

	/**
	 * The GraphQL query string to use.
	 */
	protected function query(): string {
		return 'query MyQuery {
			generalSettings {
				siteIcon {
					databaseId
					mediaDetails {
						file
						sizes {
							sourceUrl
							file
							height
							width
						}
					}
					mediaItemUrl
				}
			}
		}';
	}

	/**
	 * Test Post URIs
	 */
	public function testSiteIcon(): void {
		$query  = $this->query();
		$actual = $this->graphql( compact( 'query' ) );

		// Added temporary debug.
		error_log( print_r( $actual, true ) );

		$this->assertArrayNotHasKey( 'errors', $actual, 'Query should not return errors.' );
		$this->assertNotNull( $actual['data']['generalSettings']['siteIcon'], 'Site Icon should not be null.' );
		$this->assertEquals( $this->attachment_id, $actual['data']['generalSettings']['siteIcon']['databaseId'], 'Database ID should match site_icon option.' );
		$this->assertNotEmpty( $actual['data']['generalSettings']['siteIcon']['mediaDetails']['file'], 'File should not be empty.' );
		$this->assertNotEmpty( $actual['data']['generalSettings']['siteIcon']['mediaItemUrl'], 'MediaItemUrl should not be empty.' );
		$this->assertNotEmpty( $actual['data']['generalSettings']['siteIcon']['mediaDetails']['sizes'], 'Sizes should not be empty.' );
		foreach ( $actual['data']['generalSettings']['siteIcon']['mediaDetails']['sizes'] as $size ) {
			$this->assertNotEmpty( $size['sourceUrl'], 'Size source URL should not be empty.' );
			$this->assertNotEmpty( $size['file'], 'Size file should not be empty.' );
			$this->assertNotEmpty( $size['height'], 'Size height should not be empty.' );
			$this->assertNotEmpty( $size['width'], 'Size width should not be empty.' );
		}
	}
}
