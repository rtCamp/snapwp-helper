<?php
/**
 * Tests the RenderedTemplate class.
 *
 * @package SnapWP\Helper\Tests\Integration
 */

namespace SnapWP\Tests\Integration;

use WPGraphQL;

/**
 * Tests Enqueuing Script Modules.
 */
class EnqueuedScriptModulesTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		// Clear the schema.
		WPGraphQL::clear_schema();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		// Clear the schema.
		WPGraphQL::clear_schema();

		// Call the parent teardown to clean up.
		parent::tearDown();
	}

	/**
	 * Helper to execute a GraphQL query with the post URL.
	 */
	private function query(): string {
		return '
			query GetEnqueuedScriptModules($uri: String!) {
				templateByUri(uri: $uri) {
					enqueuedScriptModules(first:1000) {
						nodes {
							handle
							id
							src
							dependencies {
								importType
								connectedScriptModule {
									handle
									id
									src
								}
							}
						}
					}
				}
			}
		';
	}

	/**
	 * Test that the WordPress Interactivity API scripts are properly registered and enqueued.
	 */
	public function testInteractivityAPI(): void {

		// Create a regular post without interactive blocks for comparison.
		$post_id = $this->factory()->post->create(
			[
				'post_content' => '<!-- wp:paragraph -->Regular post<!-- /wp:paragraph -->',
			]
		);

		$post_url = get_permalink( $post_id );

		// Execute the GraphQL query with the post URL.
		$query     = $this->query();
		$variables = [
			'uri' => wp_make_link_relative( $post_url ),
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		// Assert no errors.
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertArrayHasKey( 'data', $actual );

		// Extract the script handles.
		$handles = array_column( $actual['data']['templateByUri']['enqueuedScriptModules']['nodes'], 'handle' );

		// Assert that the Interactivity API scripts are present with sanitized handles.
		$this->assertContains( '@wordpress/interactivity', $handles, 'Main interactivity script should be enqueued' );
	}

	/**
	 * Test script modules registration and enqueuing with Modules.
	 */
	public function testEnqueuedScriptModules(): void {
		// Register custom script modules.
		wp_register_script_module(
			'test-grandparent-dependency',
			'https://example.com/test-grandparent-dependency.js',
			[],
			'1.0.0'
		);
		wp_register_script_module(
			'test-parent-dependency',
			'https://example.com/test-parent-dependency.js',
			[
				[
					'id'     => 'test-grandparent-dependency',
					'import' => 'dynamic',
				],
			],
			'1.0.0'
		);
		wp_register_script_module(
			'test-module-main',
			'https://example.com/main.js',
			[ 'test-parent-dependency' ],
			'1.0.0'
		);

		// Create a post.
		$post_id = $this->factory()->post->create(
			[
				'post_content' => '<!-- wp:paragraph -->Test post<!-- /wp:paragraph -->',
			]
		);

		$post_url = get_permalink( $post_id );

		// First verify modules aren't present when not enqueued.
		$query     = $this->query();
		$variables = [ 'uri' => wp_make_link_relative( $post_url ) ];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertArrayHasKey( 'data', $actual );

		$handles = array_column( $actual['data']['templateByUri']['enqueuedScriptModules']['nodes'], 'handle' );

		$this->assertNotContains( 'test-module-main', $handles );
		$this->assertNotContains( 'test-parent-dependency', $handles );
		$this->assertNotContains( 'test-grandparent-dependency', $handles );

		// Test enqueuing the main module.
		wp_enqueue_script_module( 'test-module-main' );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertArrayHasKey( 'data', $actual );

		$handles = array_column( $actual['data']['templateByUri']['enqueuedScriptModules']['nodes'], 'handle' );

		$this->assertContains( 'test-module-main', $handles );
		$this->assertContains( 'test-parent-dependency', $handles );
		$this->assertContains( 'test-grandparent-dependency', $handles );

		// Test the individual script modules.
		$main_module = array_values(
			array_filter(
				$actual['data']['templateByUri']['enqueuedScriptModules']['nodes'],
				static function ( $node ) {
					return 'test-module-main' === $node['handle'];
				}
			)
		)[0];

		$this->assertNotEmpty( $main_module['id'] );
		$this->assertEquals( 'https://example.com/main.js', $main_module['src'] );
		$this->assertCount( 1, $main_module['dependencies'] );
		$this->assertEquals( 'static', $main_module['dependencies'][0]['importType'] );
		$this->assertEquals( 'test-parent-dependency', $main_module['dependencies'][0]['connectedScriptModule']['handle'] );

		$parent_module = array_values(
			array_filter(
				$actual['data']['templateByUri']['enqueuedScriptModules']['nodes'],
				static function ( $node ) {
					return 'test-parent-dependency' === $node['handle'];
				}
			)
		)[0];

		$this->assertNotEmpty( $parent_module['id'] );
		$this->assertEquals( 'https://example.com/test-parent-dependency.js', $parent_module['src'] );
		$this->assertCount( 1, $parent_module['dependencies'] );
		$this->assertEquals( 'dynamic', $parent_module['dependencies'][0]['importType'] );
		$this->assertEquals( 'test-grandparent-dependency', $parent_module['dependencies'][0]['connectedScriptModule']['handle'] );

		$grandparent_module = array_values(
			array_filter(
				$actual['data']['templateByUri']['enqueuedScriptModules']['nodes'],
				static function ( $node ) {
					return 'test-grandparent-dependency' === $node['handle'];
				}
			)
		)[0];

		$this->assertNotEmpty( $grandparent_module['id'] );
		$this->assertEquals( 'https://example.com/test-grandparent-dependency.js', $grandparent_module['src'] );
		$this->assertEmpty( $grandparent_module['dependencies'] );
	}
}
