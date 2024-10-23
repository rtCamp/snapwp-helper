<?php

namespace SnapWP\Tests\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;

class EnqueuedScriptsTest extends WPTestCase {

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		// Call the parent setup to ensure WP is initialized.
		parent::setUp();

		// Ensure all enqueued scripts and styles are cleared before each test.
		global $wp_scripts, $wp_styles;
		$wp_scripts->queue = [];
		$wp_styles->queue = [];
	}

	/**
	 * Tear down the test environment.
	 */
	protected function tearDown(): void {
		// Clear the global script and style queues after each test.
		global $wp_scripts, $wp_styles;
		$wp_scripts->queue = [];
		$wp_styles->queue = [];

		// Call the parent teardown to clean up.
		parent::tearDown();
	}

	/**
	 * Test that scripts enqueued in the head are returned in the GraphQL response.
	 */
	public function test_enqueued_scripts_in_head() {
		// Create a post.
		$post_id = $this->factory()->post->create([
			'post_title' => 'Test Post with Head Script'
		]);

		// Add a hook to enqueue a script to the head of the post.
		add_action( 'wp_enqueue_scripts', function() {
			wp_enqueue_script( 'test-head-script', 'https://decoupled.local/test-head.js', [], '1.0', false );
		});

		// Query the post using GraphQL.
		$query = '
			query GetPostScripts($id: ID!) {
				post(id: $id) {
					enqueuedScriptsQueue
				}
			}
		';

		$response = $this->graphql($query, ['id' => $post_id]);

		// Assert that the script is returned in the enqueuedScriptsQueue.
		$this->assertStringContains('test-head-script', $response['data']['post']['enqueuedScriptsQueue']);
	}

	/**
	 * Test that scripts enqueued in the content are returned in the GraphQL response.
	 */
	public function test_enqueued_scripts_in_content() {
		// Create a post with a block that enqueues a script.
		$post_id = $this->factory()->post->create([
			'post_content' => '<!-- wp:script {"id":"test-content-script"} /-->'
		]);

		// Simulate block render with script enqueue.
		add_action('wp_enqueue_scripts', function() {
			wp_enqueue_script( 'test-content-script', 'https://decoupled.local/test-content.js', [], '1.0', false );
		});

		// Query the post using GraphQL.
		$query = '
			query GetPostScripts($id: ID!) {
				post(id: $id) {
					enqueuedScriptsQueue
				}
			}
		';

		$response = $this->graphql($query, ['id' => $post_id]);

		// Assert that the script is returned in the enqueuedScriptsQueue.
		$this->assertContains('test-content-script', $response['data']['post']['enqueuedScriptsQueue']);
	}

	/**
	 * Test that scripts enqueued in the footer are returned in the GraphQL response.
	 */
	public function test_enqueued_scripts_in_footer() {
		// Create a post.
		$post_id = $this->factory()->post->create([
			'post_title' => 'Test Post with Footer Script'
		]);

		// Add a hook to enqueue a script to the footer.
		add_action( 'wp_footer', function() {
			wp_enqueue_script( 'test-footer-script', 'https://decoupled.local/test-footer.js', [], '1.0', true );
		});

		// Query the post using GraphQL.
		$query = '
			query GetPostScripts($id: ID!) {
				post(id: $id) {
					enqueuedScriptsQueue
				}
			}
		';

		$response = $this->graphql($query, ['id' => $post_id]);

		// Assert that the script is returned in the enqueuedScriptsQueue.
		$this->assertContains('test-footer-script', $response['data']['post']['enqueuedScriptsQueue']);
	}

	/**
	 * Test that scripts with dependencies are enqueued properly.
	 */
	public function test_enqueued_scripts_with_dependencies() {
		// Create a post.
		$post_id = $this->factory()->post->create([
			'post_title' => 'Test Post with Dependencies'
		]);

		// Add a hook to enqueue a script with dependencies.
		add_action( 'wp_enqueue_scripts', function() {
			wp_enqueue_script( 'dependency-script', 'https://decoupled.local/dependency.js', [], '1.0', false );
			wp_enqueue_script( 'test-dependent-script', 'https://decoupled.local/test-dependent.js', ['dependency-script'], '1.0', false );
		});

		// Query the post using GraphQL.
		$query = '
			query GetPostScripts($id: ID!) {
				post(id: $id) {
					enqueuedScriptsQueue
				}
			}
		';

		$response = $this->graphql($query, ['id' => $post_id]);

		// Assert that the dependent script and its dependency are returned in the enqueuedScriptsQueue.
		$this->assertContains('dependency-script', $response['data']['post']['enqueuedScriptsQueue']);
		$this->assertContains('test-dependent-script', $response['data']['post']['enqueuedScriptsQueue']);
	}
}
