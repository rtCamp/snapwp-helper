<?php

namespace SnapWP\Tests\Integration;

class EnqueuedScriptsTest extends  \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		// Call the parent setup to ensure WP is initialized.
		parent::setUp();

		// Ensure all enqueued scripts and styles are cleared before each test.
		// global $wp_scripts, $wp_styles;
		// $wp_scripts->queue = [];
		// $wp_styles->queue = [];

        // Ensure WordPress has initialized the script and style queues.
        if ( ! isset( $GLOBALS['wp_scripts'] ) ) {
            $GLOBALS['wp_scripts'] = new \WP_Scripts();
        }

        if ( ! isset( $GLOBALS['wp_styles'] ) ) {
            $GLOBALS['wp_styles'] = new \WP_Styles();
        }

        // Reset the queues.
        $GLOBALS['wp_scripts']->queue = [];
        $GLOBALS['wp_styles']->queue = [];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {


        if ( isset( $GLOBALS['wp_scripts'] ) ) {
            $GLOBALS['wp_scripts']->queue = [];
        }

        if ( isset( $GLOBALS['wp_styles'] ) ) {
            $GLOBALS['wp_styles']->queue = [];
        }



		// Clear the global script and style queues after each test.
		// global $wp_scripts, $wp_styles;
		// $wp_scripts->queue = [];
		// $wp_styles->queue = [];

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
		// $query = '
		// 	query GetPostScripts($id: ID!) {
		// 		post(id: $id) {
		// 			enqueuedScriptsQueue
		// 		}
		// 	}
		// ';

		// Query the post using GraphQL.
		$query = '
			query GetCurrentScreen($uri: String!) {
                templateByUri(uri: $uri) {
                    enqueuedScripts(first: 80) {
                        nodes {
                            handle
                        }
                    }
                }
            }
		';


        // Get the permalink/URL for the created post
        $post_url = get_permalink( $post_id );


		  // Execute the query with the post ID as a variable
		//   $response = $this->graphql([
        //     'query' => $query,
        //     'variables' => [
        //         'id' => $post_id,
        //     ],
        // ]);

		 // Execute the GraphQL query with the post URL
		 $response = $this->graphql([
            'query' => $query,
            'variables' => [
                'uri' => $post_url,
            ],
        ]);

		// $actual = $this->graphql( compact( 'query' ) );

		// $response = $this->graphql($query, ['id' => $post_id]); // @here

        // Assert no errors
        $this->assertArrayNotHasKey('errors', $response);

		// Extract the 'handle' values from the response
		$handles = array_column($response['data']['templateByUri']['enqueuedScripts']['nodes'], 'handle');

		// Assert that the script is returned in the enqueuedScriptsQueue.
		$this->assertContains('test-head-script', $handles);
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
		// $query = '
		// 	query GetPostScripts($id: ID!) {
		// 		post(id: $id) {
		// 			enqueuedScriptsQueue
		// 		}
		// 	}
		// ';


		// Query the post using GraphQL.
		$query = '
			query GetCurrentScreen($uri: String!) {
                templateByUri(uri: $uri) {
                    enqueuedScripts(first: 80) {
                        nodes {
                            handle
                        }
                    }
                }
            }
		';


        // Get the permalink/URL for the created post
        $post_url = get_permalink( $post_id );


		 // Execute the GraphQL query with the post URL
		 $response = $this->graphql([
            'query' => $query,
            'variables' => [
                'uri' => $post_url,
            ],
        ]);


		// $response = $this->graphql($query, ['id' => $post_id]); // @here

        // Assert no errors
        $this->assertArrayNotHasKey('errors', $response);

		// Extract the 'handle' values from the response
		$handles = array_column($response['data']['templateByUri']['enqueuedScripts']['nodes'], 'handle');

		// Assert that the script is returned in the enqueuedScriptsQueue.
		$this->assertContains('test-content-script', $handles);
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
		add_action( 'wp_enqueue_scripts', function() {
			wp_enqueue_script( 'test-footer-script', 'https://decoupled.local/test-footer.js', [], '1.0', true );
		});

		// Query the post using GraphQL.
		// $query = '
		// 	query GetPostScripts($id: ID!) {
		// 		post(id: $id) {
		// 			enqueuedScriptsQueue
		// 		}
		// 	}
		// ';

		// Query the post using GraphQL.
		$query = '
			query GetCurrentScreen($uri: String!) {
                templateByUri(uri: $uri) {
                    enqueuedScripts(first: 80) {
                        nodes {
                            handle
                        }
                    }
                }
            }
		';


        // Get the permalink/URL for the created post
        $post_url = get_permalink( $post_id );


		 // Execute the GraphQL query with the post URL
		 $response = $this->graphql([
            'query' => $query,
            'variables' => [
                'uri' => $post_url,
            ],
        ]);

		// $actual = $this->graphql( compact( 'query' ) );

		error_log(print_r($response, true));

        // Assert no errors
        $this->assertArrayNotHasKey('errors', $response);

		// Extract the 'handle' values from the response
		$handles = array_column($response['data']['templateByUri']['enqueuedScripts']['nodes'], 'handle');

		// $response = $this->graphql($query, ['id' => $post_id]); // @here

		// Assert that the script is returned in the enqueuedScriptsQueue.
		$this->assertContains('test-footer-script', $handles);
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
		// $query = '
		// 	query GetPostScripts($id: ID!) {
		// 		post(id: $id) {
		// 			enqueuedScriptsQueue
		// 		}
		// 	}
		// ';

		// $response = $this->graphql($query, ['id' => $post_id]); // @here
		// Query the post using GraphQL.
		$query = '
			query GetCurrentScreen($uri: String!) {
                templateByUri(uri: $uri) {
                    enqueuedScripts(first: 80) {
                        nodes {
                            handle
                        }
                    }
                }
            }
		';


        // Get the permalink/URL for the created post
        $post_url = get_permalink( $post_id );

 // Execute the GraphQL query with the post URL
 $response = $this->graphql([
	'query' => $query,
	'variables' => [
		'uri' => $post_url,
	],
]);

		error_log(print_r($response, true));

		 // Assert no errors
		 $this->assertArrayNotHasKey('errors', $response);

		 // Extract the 'handle' values from the response
		 $handles = array_column($response['data']['templateByUri']['enqueuedScripts']['nodes'], 'handle');
 
		 // Assert that the script is returned in the enqueuedScriptsQueue.


		// Assert that the dependent script and its dependency are returned in the enqueuedScriptsQueue.
		$this->assertContains('dependency-script', $handles);
		$this->assertContains('test-dependent-script', $handles);
	}
}
