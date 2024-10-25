<?php

namespace SnapWP\Tests\Integration;

use WPGraphQL;

class EnqueuedScriptsTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		// Ensure WordPress has initialized the script and style queues.
		if ( ! isset( $GLOBALS['wp_scripts'] ) ) {
			$GLOBALS['wp_scripts'] = new \WP_Scripts();
		}

		if ( ! isset( $GLOBALS['wp_styles'] ) ) {
			$GLOBALS['wp_styles'] = new \WP_Styles();
		}

			// Register scripts used across tests.
			wp_register_script( 'test-head-script', 'https://decoupled.local/test-head.js', [], '1.0', false );
			wp_register_script( 'test-content-script', 'https://decoupled.local/test-content.js', [], '1.0', false );
			wp_register_script( 'test-footer-script', 'https://decoupled.local/test-footer.js', [], '1.0', true );
			wp_register_script( 'dependency-script', 'https://decoupled.local/dependency.js', [], '1.0', false );
			wp_register_script( 'test-dependent-script', 'https://decoupled.local/test-dependent.js', ['dependency-script'], '1.0', false );
	

		// Reset the queues.
		$GLOBALS['wp_scripts']->queue = [];
		$GLOBALS['wp_styles']->queue  = [];

		// Clear the schema.
		WPGraphQL::clear_schema();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {

		remove_all_actions( 'wp_enqueue_scripts' );

		// Reset the queues.
		if ( isset( $GLOBALS['wp_scripts'] ) ) {
			$GLOBALS['wp_scripts']->queue = [];
		}

		if ( isset( $GLOBALS['wp_styles'] ) ) {
			$GLOBALS['wp_styles']->queue = [];
		}

		// Clear the schema.
		WPGraphQL::clear_schema();

		// Call the parent teardown to clean up.
		parent::tearDown();
	}

	/**
	 * Helper to execute a GraphQL query with the post URL.
	 */
	private function query( $post_url ): array {
		$query = '
			query GetCurrentScreen($uri: String!) {
				templateByUri(uri: $uri) {
					enqueuedScripts(first: 100) {
						nodes {
							handle
						}
					}
				}
			}
		';

		return $this->graphql([
			'query'     => $query,
			'variables' => [
				'uri' => $post_url,
			],
		]);
	}

	/**
	 * Test that the scripts correctly getting enqueued in the head are returned in the GraphQL response.
	 */
	public function test_enqueued_scripts_in_head(): void {
		// Create a post.
		$post_id = $this->factory()->post->create( [
			'post_title' => 'Test Post with Head Script'
		] );

		// Add a hook to enqueue a script to the head of the post.
		add_action( 'wp_enqueue_scripts', function() use ( $post_id ) {
			if ( get_the_ID() !== $post_id ) {
				return;
			}
			wp_enqueue_script( 'test-head-script', 'https://decoupled.local/test-head.js', [], '1.0', false );
		} );

		// Get the permalink/URL for the created post.
		$post_url = get_permalink( $post_id );

		// Execute the GraphQL query with the post URL.
		$actual = $this->query( $post_url );

		error_log( print_r( $actual, true ) );

		// Assert no errors.
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertArrayHasKey( 'data', $actual );

		// Extract the 'handle' values from the response.
		$handles = array_column( $actual['data']['templateByUri']['enqueuedScripts']['nodes'], 'handle' );

		// Assert that the script is returned in the enqueuedScriptsQueue.
		$this->assertContains( 'test-head-script', $handles );
	}

	/**
	 * Test that the scripts correctly getting enqueued in the content are returned in the GraphQL response.
	 */
	public function test_enqueued_scripts_in_content(): void {
		// Create a post with a block that enqueues a script.
		$post_id = $this->factory()->post->create( [
			'post_content' => '<!-- wp:script {"id":"test-content-script"} /-->'
		] );

		// Simulate block render with script enqueue.
		add_action( 'wp_enqueue_scripts', function() use ( $post_id ) {
			if ( get_the_ID() !== $post_id ) {
				return;
			}
			wp_enqueue_script( 'test-content-script', 'https://decoupled.local/test-content.js', [], '1.0', false );
		} );

		// Get the URL for the created post.
		$post_url = get_permalink( $post_id );

		$actual = $this->query( $post_url );
		error_log( print_r( $actual, true ) );

		// Assert no errors
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertArrayHasKey( 'data', $actual );

		// Extract the 'handle' values from the response.
		$handles = array_column( $actual['data']['templateByUri']['enqueuedScripts']['nodes'], 'handle' );

		// Assert that the script is returned in the enqueuedScriptsQueue.
		$this->assertContains('test-content-script', $handles );
	}

	/**
	 * Test that the scripts correctly getting enqueued in the footer are returned in the GraphQL response.
	 */
	public function test_enqueued_scripts_in_footer(): void {
		// Create a post.
		$post_id = $this->factory()->post->create([
			'post_title' => 'Test Post with Footer Script'
		]);

		// Add a hook to enqueue a script to the footer.
		add_action( 'wp_enqueue_scripts', function() use ( $post_id ) {
			if ( get_the_ID() !== $post_id ) {
				return;
			}
			wp_enqueue_script( 'test-footer-script', 'https://decoupled.local/test-footer.js', [], '1.0', true );
		});

		// Get the URL for the created post.
		$post_url = get_permalink( $post_id );

		$actual = $this->query( $post_url );
		error_log( print_r( $actual, true ) );

		// Assert no errors.
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertArrayHasKey( 'data', $actual );

		// Extract the 'handle' values from the response.
		$handles = array_column( $actual['data']['templateByUri']['enqueuedScripts']['nodes'], 'handle' );

		// Assert that the script is returned in the enqueuedScriptsQueue.
		$this->assertContains( 'test-footer-script', $handles );
	}

	/**
	 * Test that scripts with dependencies are enqueued properly.
	 */
	public function test_enqueued_scripts_with_dependencies(): void {
		// Create a post.
		$post_id = $this->factory()->post->create([
			'post_title' => 'Test Post with Dependencies'
		]);

		// Add a hook to enqueue a script with dependencies.
		add_action( 'wp_enqueue_scripts', function() use ( $post_id ) {
			if ( get_the_ID() !== $post_id ) {
				return;
			}
			wp_enqueue_script( 'dependency-script', 'https://decoupled.local/dependency.js', [], '1.0', false );
			wp_enqueue_script( 'test-dependent-script', 'https://decoupled.local/test-dependent.js', ['dependency-script'], '1.0', false );
		});

		// Get the URL for the created post.
		$post_url = get_permalink( $post_id );

		$actual = $this->query( $post_url );

		error_log( print_r( $actual, true ) );

		// Assert no errors.
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertArrayHasKey( 'data', $actual );

		// Extract the 'handle' values from the response.
		$handles = array_column( $actual['data']['templateByUri']['enqueuedScripts']['nodes'], 'handle' );

		// Assert that the dependent script and its dependency are returned in the enqueuedScriptsQueue.
		$this->assertContains( 'dependency-script', $handles );
		$this->assertContains( 'test-dependent-script', $handles );
		
		// Assert that the dependency script is enqueued before the dependent script.
		$this->assertGreaterThan( array_search( 'dependency-script', $handles ), array_search( 'test-dependent-script', $handles ) );
	}
}
