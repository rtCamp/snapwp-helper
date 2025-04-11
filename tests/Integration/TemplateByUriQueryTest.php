<?php
/**
 * Tests querying for `templateByUri`
 *
 * @package SnapWP\Helper\Tests\Integration
 */

namespace SnapWP\Helper\Tests\Integration;

use SnapWP\Helper\Tests\TestCase\IntegrationTestCase;

/**
 * Class - TemplateByUriQueryTest
 */
class TemplateByUriQueryTest extends IntegrationTestCase {
	/**
	 * User ID.
	 *
	 * @var int
	 */
	protected $user_id;

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure permalinks are set to /%year%/%monthnum%/%day%/%postname%/ .
		$this->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );

		// Reinitialize and up custom types for the test.
		create_initial_taxonomies();

		// Register the Custom Post Type used in the tests.
		register_post_type(
			'hierarchical_cpt',
			[
				'show_in_graphql'     => true,
				'graphql_single_name' => 'HierarchicalCPT',
				'graphql_plural_name' => 'HierarchicalCPTs',
				'public'              => true,
				'has_archive'         => true,
				'hierarchical'        => true,
			]
		);

		// Register the Custom Post Type used in the tests.
		register_post_type(
			'non_hierarchical_cpt',
			[
				'show_in_graphql'     => true,
				'graphql_single_name' => 'NonHierarchicalCPT',
				'graphql_plural_name' => 'NonHierarchicalCPTs',
				'public'              => true,
				'has_archive'         => true,
			]
		);

		register_post_type(
			'no_archive_cpt',
			[
				'show_in_graphql'     => true,
				'graphql_single_name' => 'CustomType',
				'graphql_plural_name' => 'CustomTypes',
				'public'              => true,
				'has_archive'         => false,
			]
		);

		register_taxonomy(
			'by_uri_tax',
			'no_archive_cpt',
			[
				'show_in_graphql'     => true,
				'graphql_single_name' => 'CustomTax',
				'graphql_plural_name' => 'CustomTaxes',
				'default_term'        => [
					'name' => 'Default Term',
					'slug' => 'default-term',
				],
			]
		);

		flush_rewrite_rules( true ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- Required for the test.

		$this->clearSchema();

		$this->user_id = $this->factory->user->create(
			[
				'role' => 'administrator',
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		wp_delete_user( $this->user_id );

		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * The GraphQL query string to use.
	 */
	protected function query(): string {
		return 'query testTemplateByUri( $uri: String! ) {
			templateByUri(uri: $uri) {
				bodyClasses
				is404
				connectedNode {
					__typename
					isPostsPage
					... on DatabaseIdentifier {
						databaseId
					}
					... on NodeWithTitle {
						title
					}
					... on TermNode {
						name
					}
					... on User {
						name
					}
				}
			}
		}';
	}

	/**
	 * The GraphQL query string to use for editor blocks.
	 */
	protected function editor_blocks_query( $flat = '' ): string {
		return '
		query GetCurrentTemplate( $uri: String! ) {
			templateByUri( uri: $uri ) {
				id
				editorBlocks' . $flat . ' {
					parentClientId
				}
			}
		}';
	}

	/**
	 * Test Post URIs
	 */
	public function testPostByUri(): void {
		$post_id = $this->factory->post->create(
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'Test postByUri',
				'post_author' => $this->user_id,
			]
		);

		$query = $this->query();

		// 1. Test a bad URI.
		$variables = [
			'uri' => '2024/12/31/this-is-not-a-post', // This URI should not exist.
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );

		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered 404 template' );
		$this->assertContains( 'error404', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the error404 class' );
		$this->assertNull( $actual['data']['templateByUri']['connectedNode'], 'connectedNode should be null' );
		$this->assertTrue( $actual['data']['templateByUri']['is404'], 'Should be a 404.' );

		// 2. Test a good URI.
		$post_link = get_permalink( $post_id );
		$this->assertNotEmpty( $post_link, 'Post link should not be empty' );
		$variables['uri']      = wp_make_link_relative( $post_link );
		$expected_graphql_type = ucfirst( get_post_type_object( 'post' )->graphql_single_name );
		$actual                = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered post template' );
		$this->assertContains( 'single-post', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the single-post class' );
		$this->assertContains( 'postid-' . $post_id, $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the postid-# class' );
		$this->assertEquals( $expected_graphql_type, $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be a ' . $expected_graphql_type );
		$this->assertEquals( $post_id, $actual['data']['templateByUri']['connectedNode']['databaseId'], 'connectedNode should have the correct databaseId' );
		$this->assertEquals( 'Test postByUri', $actual['data']['templateByUri']['connectedNode']['title'], 'connectedNode should have the correct title' );
	}

	/**
	 * Test Page URIs for parent and child posts.
	 */
	public function testPageByUri(): void {
		// Create a parent page.
		$parent_page_id = $this->factory->post->create(
			[
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Parent Page',
				'post_author' => $this->user_id,
			]
		);

		// Create a child page.
		$child_page_id = $this->factory->post->create(
			[
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Child Page',
				'post_parent' => $parent_page_id,
				'post_author' => $this->user_id,
			]
		);

		$query = $this->query();

		// 1. Test a bad URI for the parent page.
		$variables = [
			'uri' => '/this-is-not-a-page', // This URI should not exist.
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered 404 template' );
		$this->assertContains( 'error404', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the error404 class' );
		$this->assertNull( $actual['data']['templateByUri']['connectedNode'], 'connectedNode should be null' );
		$this->assertTrue( $actual['data']['templateByUri']['is404'], 'Should be a 404.' );

		// 2. Test a valid URI for the parent page.
		$parent_page_link = get_permalink( $parent_page_id );
		$this->assertNotEmpty( $parent_page_link, 'Parent page link should not be empty' );
		$variables['uri']      = wp_make_link_relative( $parent_page_link );
		$expected_graphql_type = ucfirst( get_post_type_object( 'page' )->graphql_single_name );
		$actual                = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered parent page template' );
		$this->assertContains( 'page', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the page class' );
		$this->assertContains( 'page-id-' . $parent_page_id, $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the page-id-# class' );
		$this->assertContains( 'page-parent', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the page-parent class' );
		$this->assertEquals( $expected_graphql_type, $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be a ' . $expected_graphql_type );
		$this->assertEquals( $parent_page_id, $actual['data']['templateByUri']['connectedNode']['databaseId'], 'connectedNode should have the correct databaseId' );
		$this->assertEquals( 'Parent Page', $actual['data']['templateByUri']['connectedNode']['title'], 'connectedNode should have the correct title' );

		// 3. Test an invalid child page URI under a valid parent.
		$variables['uri'] = wp_make_link_relative( $parent_page_link ) . '/invalid-child-page'; // This URI should not exist.

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered 404 template' );
		$this->assertContains( 'error404', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the error404 class' );
		$this->assertNull( $actual['data']['templateByUri']['connectedNode'], 'connectedNode should be null' );

		// 4. Test a valid child page URI.
		$child_page_link = get_permalink( $child_page_id );
		$this->assertNotEmpty( $child_page_link, 'Child page link should not be empty' );
		$variables['uri'] = wp_make_link_relative( $child_page_link );
		$actual           = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered child page template' );
		$this->assertContains( 'page', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the page class' );
		$this->assertContains( 'page-id-' . $child_page_id, $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the page-id-# class' );
		$this->assertContains( 'page-child', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the page-child class' );
		$this->assertContains( 'parent-pageid-' . $parent_page_id, $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the parent-pageid-# class' );
		$this->assertEquals( $expected_graphql_type, $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be a ' . $expected_graphql_type );
		$this->assertEquals( $child_page_id, $actual['data']['templateByUri']['connectedNode']['databaseId'], 'connectedNode should have the correct databaseId' );
		$this->assertEquals( 'Child Page', $actual['data']['templateByUri']['connectedNode']['title'], 'connectedNode should have the correct title' );
	}

	/**
	 * Test the URI handling for a Custom Post Type (CPT) archive.
	 *
	 * This test validates that:
	 * - A non-existent CPT URI returns a 404 template with the appropriate error message.
	 * - A valid CPT URI returns the correct template with the correct connected node data.
	 */
	public function testCptByUri(): void {
		$cpt_id = $this->factory->post->create(
			[
				'post_type'   => 'no_archive_cpt',
				'post_status' => 'publish',
				'post_title'  => 'Test cptByUri',
				'post_author' => $this->user_id,
			]
		);

		$query     = $this->query();
		$variables = [];

		// Test a bad URI.
		$variables = [
			'uri' => 'no_archive_cpt/not-a-real-cpt', // This URI should not exist.
		];
		$actual    = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertContains( 'error404', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the error404 class' );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered 404 template' );
		$this->assertNull( $actual['data']['templateByUri']['connectedNode'], 'connectedNode should be null' );
		$this->assertTrue( $actual['data']['templateByUri']['is404'], 'Should be a 404.' );

		// Test a good URI.
		$cpt_link = get_permalink( $cpt_id );
		$this->assertNotEmpty( $cpt_link, 'CPT link should not be empty' );
		$variables['uri']      = wp_make_link_relative( $cpt_link );
		$expected_graphql_type = ucfirst( get_post_type_object( 'no_archive_cpt' )->graphql_single_name );
		$actual                = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered CPT template' );
		$this->assertContains( 'single-no_archive_cpt', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the single-no_archive_cpt class' );
		$this->assertContains( 'postid-' . $cpt_id, $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the postid-# class' );
		$this->assertEquals( $expected_graphql_type, $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be a ' . $expected_graphql_type );
		$this->assertEquals( $cpt_id, $actual['data']['templateByUri']['connectedNode']['databaseId'], 'connectedNode should have the correct databaseId' );
		$this->assertEquals( 'Test cptByUri', $actual['data']['templateByUri']['connectedNode']['title'], 'connectedNode should have the correct title' );
	}

	/**
	 * Test the URI handling for a Category archive.
	 *
	 * This test validates that:
	 * - A non-existent category URI returns a 404 template with the appropriate error message.
	 * - A valid category URI returns the correct category template with the correct connected node data.
	 */
	public function testCategoryByUri(): void {
		$category_id = $this->factory->category->create(
			[
				'name' => 'Test Category',
				'slug' => 'test-category',
			]
		);

		$query = $this->query();

		// Test a bad URI.
		$variables = [
			'uri' => '/category/non-existent-category', // This URI should not exist.
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );

		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered 404 template' );
		$this->assertContains( 'error404', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the error404 class' );
		$this->assertNull( $actual['data']['templateByUri']['connectedNode'], 'connectedNode should be null' );
		$this->assertTrue( $actual['data']['templateByUri']['is404'], 'Should be a 404.' );

		// Test a good URI.
		$category_link = get_term_link( $category_id );
		$this->assertNotEmpty( $category_link, 'Category link should not be empty' );
		$variables['uri'] = wp_make_link_relative( $category_link );
		$actual           = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered category template' );
		$this->assertContains( 'category', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the category class' );
		$this->assertContains( 'category-' . $category_id, $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the category-id-# class' );
		$this->assertEquals( 'Category', $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be a Category' );
		$this->assertEquals( $category_id, $actual['data']['templateByUri']['connectedNode']['databaseId'], 'connectedNode should have the correct databaseId' );
		$this->assertEquals( 'Test Category', $actual['data']['templateByUri']['connectedNode']['name'], 'connectedNode should have the correct title' );
	}

	/**
	 * Test the URI handling for a Tag archive.
	 *
	 * This test validates that:
	 * - A non-existent tag URI returns a 404 template with the appropriate error message.
	 * - A valid tag URI returns the correct tag template with the correct connected node data.
	 */
	public function testTagByUri(): void {
		$tag_id = $this->factory->tag->create(
			[
				'name' => 'Test Tag',
				'slug' => 'test-tag',
			]
		);

		$query     = $this->query();
		$variables = [];

		// Test a bad URI.
		$variables = [
			'uri' => '/tag/non-a-real-tag', // This URI should not exist.
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered 404 template' );
		$this->assertContains( 'error404', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the error404 class' );
		$this->assertNull( $actual['data']['templateByUri']['connectedNode'], 'connectedNode should be null' );
		$this->assertTrue( $actual['data']['templateByUri']['is404'], 'Should be a 404.' );

		// Test a good URI.
		$tag_link = get_tag_link( $tag_id );
		$this->assertNotEmpty( $tag_link, 'Tag link should not be empty' );
		$variables['uri'] = wp_make_link_relative( $tag_link );
		$actual           = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered tag template' );
		$this->assertContains( 'tag', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the tag class' );
		$this->assertContains( 'tag-' . $tag_id, $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the tag-id-# class' );
		$this->assertEquals( 'Tag', $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be a Tag' );
		$this->assertEquals( $tag_id, $actual['data']['templateByUri']['connectedNode']['databaseId'], 'connectedNode should have the correct databaseId' );
		$this->assertEquals( 'Test Tag', $actual['data']['templateByUri']['connectedNode']['name'], 'connectedNode should have the correct title' );
	}

	/**
	 * Test the URI handling for a custom taxonomy term archive.
	 *
	 * This test validates that:
	 * - A non-existent custom taxonomy term URI returns a 404 template with the appropriate error message.
	 * - A valid custom taxonomy term URI returns the correct template with the correct connected node data.
	 */
	public function testCustomTaxTermByUri(): void {
		$term_id = $this->factory->term->create(
			[
				'taxonomy' => 'by_uri_tax',
				'name'     => 'Test Custom Tax Term',
				'slug'     => 'test-custom-tax-term',
			]
		);

		$query     = $this->query();
		$variables = [];

		// Test a bad URI.
		$variables = [
			'uri' => '/by_uri_tax/test-non-existent-tax-term', // This URI should not exist.
		];
		$actual    = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered 404 template' );
		$this->assertContains( 'error404', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the error404 class' );
		$this->assertNull( $actual['data']['templateByUri']['connectedNode'], 'connectedNode should be null' );
		$this->assertTrue( $actual['data']['templateByUri']['is404'], 'Should be a 404.' );

		// Test a good URI.
		$term_link = get_term_link( $term_id );
		$this->assertNotEmpty( $term_link, 'Term link should not be empty' );
		$variables['uri'] = wp_make_link_relative( $term_link );
		$actual           = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered custom taxonomy term template' );
		$this->assertContains( 'tax-by_uri_tax', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the term class' );
		$this->assertContains( 'term-' . $term_id, $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the term-id-# class' );
		$this->assertEquals( 'CustomTax', $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be a CustomTax' );
		$this->assertEquals( $term_id, $actual['data']['templateByUri']['connectedNode']['databaseId'], 'connectedNode should have the correct databaseId' );
		$this->assertEquals( 'Test Custom Tax Term', $actual['data']['templateByUri']['connectedNode']['name'], 'connectedNode should have the correct title' );
	}

	/**
	 * Test the URI handling for a post archive.
	 *
	 * This test validates that:
	 * - A non-existent post archive URI returns a 404 template with the appropriate error message.
	 * - A valid post archive URI returns the correct archive template with the expected content.
	 */
	public function testPostArchiveByUri(): void {
		// Create some posts to populate the archive.
		$this->factory->post->create_many(
			3,
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_author' => $this->user_id,
			]
		);

		$query     = $this->query();
		$variables = [];

		// Test a good URI.
		$archive_link = get_post_type_archive_link( 'post' );
		$this->assertNotEmpty( $archive_link, 'Post archive link should not be empty' );
		$variables['uri'] = wp_make_link_relative( $archive_link ) ?: '/';

		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered post archive template' );
		$this->assertContains( 'blog', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the blog class' );
		$this->assertEquals( 'ContentType', $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be a ContentType' );
	}

	/**
	 * Test the URI handling for non-hierarchical custom post type (CPT) archives.
	 *
	 * This test validates that:
	 * - A non-existent URI returns a 404 template with the appropriate error message.
	 * - A valid URI for the non-hierarchical CPT archive returns the correct archive template.
	 */
	public function testNonHierarchicalCptArchiveByUri(): void {
		$this->factory->post->create_many(
			3,
			[
				'post_type'   => 'non_hierarchical_cpt',
				'post_status' => 'publish',
				'post_author' => $this->user_id,
			]
		);

		$query     = $this->query();
		$variables = [];

		// Test a bad URI.
		$variables = [
			'uri' => '/no_archive_cpt', // Archive for this should not exist.
		];
		$actual    = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNull( $actual['data']['templateByUri']['connectedNode'], 'connectedNode should be null' );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered 404 template' );
		$this->assertContains( 'error404', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the error404 class' );
		$this->assertTrue( $actual['data']['templateByUri']['is404'], 'Should be a 404.' );

		// Test a good URI.
		$archive_link = get_post_type_archive_link( 'non_hierarchical_cpt' );
		$this->assertNotEmpty( $archive_link, 'Archive link should not be empty' );
		$variables['uri'] = wp_make_link_relative( $archive_link );
		$actual           = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered CPT archive template' );
		$this->assertContains( 'post-type-archive', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the post-type-archive class' );
		$this->assertContains( 'post-type-archive-non_hierarchical_cpt', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the post-type-archive-# class' );
		$this->assertEquals( 'ContentType', $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be a CustomTypeArchive' );
	}

	/**
	 * Tests template for hierarchical CPT archives by URI.
	 *
	 * - Checks response for an invalid URI to ensure a 404 page is returned.
	 * - Validates the archive link and ensures the correct archive template is returned for a valid URI.
	 */
	public function testHierarchicalCptArchiveByUri(): void {
		$parent_post_id = $this->factory->post->create(
			[
				'post_type'   => 'hierarchical_cpt',
				'post_status' => 'publish',
				'post_author' => $this->user_id,
				'post_title'  => 'Parent Post',
			]
		);

		// The child CPT post.
		$this->factory->post->create(
			[
				'post_type'   => 'hierarchical_cpt',
				'post_status' => 'publish',
				'post_author' => $this->user_id,
				'post_parent' => $parent_post_id,
				'post_title'  => 'Child Post',
			]
		);

		$query     = $this->query();
		$variables = [];

		// Test a bad URI.
		$variables = [
			'uri' => '/hierarchical_cpt/non-existent', // This URI should not exist.
		];
		$actual    = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered 404 template' );
		$this->assertContains( 'error404', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the error404 class' );
		$this->assertNull( $actual['data']['templateByUri']['connectedNode'], 'connectedNode should be null' );
		$this->assertTrue( $actual['data']['templateByUri']['is404'], 'Should be a 404.' );

		// Test a good URI for the parent post archive.
		$archive_link = get_post_type_archive_link( 'hierarchical_cpt' );
		$this->assertNotEmpty( $archive_link, 'Archive link should not be empty' );
		$variables['uri'] = wp_make_link_relative( $archive_link );
		$actual           = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered CPT archive template' );
		$this->assertContains( 'post-type-archive', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the post-type-archive class' );
		$this->assertContains( 'post-type-archive-hierarchical_cpt', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the post-type-archive-# class' );
		$this->assertEquals( 'ContentType', $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be a CustomTypeArchive' );
	}

	/**
	 * Test the URI handling for date-based archives.
	 *
	 * This test validates that:
	 * - Non-existent date URIs return a 404 template with the appropriate error message.
	 * - Valid date URIs returns the correct archive template, with the appropriate content.
	 */
	public function testDateArchiveByUri(): void {
		$this->factory->post->create(
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_date'   => '2024-01-01 10:00:00', // First post date.
				'post_author' => $this->user_id,
			]
		);
		$this->factory->post->create(
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_date'   => '2024-01-02 10:00:00', // Next day.
				'post_author' => $this->user_id,
			]
		);

		$query = $this->query();

		// Test a bad URI.
		$variables = [
			'uri' => '/2024/12/31/non-existent-date', // Non-existent date.
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered 404 template' );
		$this->assertContains( 'error404', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the error404 class' );
		$this->assertNull( $actual['data']['templateByUri']['connectedNode'], 'connectedNode should be null' );
		$this->assertTrue( $actual['data']['templateByUri']['is404'], 'Should be a 404.' );

		// Test invalid archive URIs.
		$invalid_uris = [
			get_day_link( 2045, 3, 3 ), // Far future date.
		];

		foreach ( $invalid_uris as $uri ) {
			$this->assertNotEmpty( $uri, 'get_year_link() or get_day_link() should return a non-empty URI' );
			$variables['uri'] = wp_make_link_relative( $uri );
			$actual           = $this->graphql( compact( 'query', 'variables' ) );

			$this->assertArrayNotHasKey( 'errors', $actual );
			$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered 404 template' );
			$this->assertContains( 'error404', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the error404 class' );
			$this->assertNull( $actual['data']['templateByUri']['connectedNode'], 'connectedNode should be null' );
		}

		// Test valid archive URIs.
		$archive_uris = [
			get_day_link( 2024, 1, 1 ), // Same day as the first post.
			get_day_link( 2024, 1, 2 ), // Same day as the second post.
		];

		foreach ( $archive_uris as $uri ) {
			$this->assertNotEmpty( $uri, 'get_day_link() should return a non-empty URI' );
			$variables['uri'] = wp_make_link_relative( $uri );
			$actual           = $this->graphql( compact( 'query', 'variables' ) );

			$this->assertArrayNotHasKey( 'errors', $actual );
			$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered date archive template' );
			$this->assertContains( 'date', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the date class' );
		}
	}

	/**
	 * Test the URI handling for month-based archives.
	 *
	 * This test validates that the month URI return the correct archive template, with the appropriate content.
	 */
	public function testMonthArchiveByUri(): void {
		$this->factory->post->create(
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_date'   => '2024-02-02 10:00:00', // Same day, different month.
				'post_author' => $this->user_id,
			]
		);

		$query     = $this->query();
		$variables = [];

		// Test invalid archive URIs.
		$invalid_uris = get_month_link( 2024, 13 ); // Invalid month.
		$this->assertNotEmpty( $invalid_uris, 'get_month_link() should return a non-empty URI' );
		$variables['uri'] = wp_make_link_relative( $invalid_uris );
		$actual           = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered 404 template' );
		$this->assertContains( 'error404', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the error404 class' );
		$this->assertNull( $actual['data']['templateByUri']['connectedNode'], 'connectedNode should be null' );

		// Test valid archive URIs.
		$archive_uris = get_month_link( 2024, 2 ); // Same month as the third post.
		$this->assertNotEmpty( $archive_uris, 'get_month_link() should return a non-empty URI' );
		$variables['uri'] = wp_make_link_relative( $archive_uris );
		$actual           = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered date archive template' );
		$this->assertContains( 'date', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the date class' );
	}

	/**
	 * Test the URI handling for year-based archives.
	 *
	 * This test validates that the year URI returns the correct archive template, with the appropriate content.
	 */
	public function testYearArchiveByUri(): void {
		$this->factory->post->create(
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_date'   => '2024-01-01 10:00:00', // First post date.
				'post_author' => $this->user_id,
			]
		);

		$query     = $this->query();
		$variables = [];

		// Test invalid archive URIs.
		$invalid_uri = get_year_link( 1992 ); // Previous year.

		$this->assertNotEmpty( $invalid_uri, 'get_year_link() should return a non-empty URI' );
		$variables['uri'] = wp_make_link_relative( $invalid_uri );
		$actual           = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered 404 template' );
		$this->assertContains( 'error404', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the error404 class' );
		$this->assertNull( $actual['data']['templateByUri']['connectedNode'], 'connectedNode should be null' );

		// Test valid archive URIs.
		$archive_uris = [
			'uri' => get_year_link( 2024 ), // Same year.
		];

		$this->assertNotEmpty( $archive_uris['uri'], 'get_year_link() should return a non-empty URI' );
		$variables['uri'] = wp_make_link_relative( $archive_uris['uri'] );
		$actual           = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered date archive template' );
		$this->assertContains( 'date', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the date class' );
		$this->assertFalse( $actual['data']['templateByUri']['is404'], 'Should not be a 404.' );
	}

	/**
	 * Test the templateByUri GraphQL query for a non-existent author archive page.
	 *
	 * This test verifies that the `templateByUri` query correctly handles the case where an author archive page is requested by URI.
	 * It ensures that a 404 template is returned when the author does not exist.
	 *
	 * Since authors are getting cached for some reason in the same request, we have split the test cases to individual tests.
	 */
	public function testNonExistentAuthorByUri(): void {
		// Create some posts with the same author.
		$this->factory->post->create_many(
			3,
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_author' => $this->user_id,
			]
		);

		$query     = $this->query();
		$variables = [];

		// Test a bad URI.
		$variables = [
			'uri' => '/author/non-existent-author', // This URI should not exist.
		];
		$actual    = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered 404 template' );
		$this->assertContains( 'error404', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the error404 class' );
		$this->assertNull( $actual['data']['templateByUri']['connectedNode'], 'connectedNode should be null' );
		$this->assertTrue( $actual['data']['templateByUri']['is404'], 'Should be a 404.' );
	}

	/**
	 * Test the templateByUri GraphQL query for a valid author archive page with posts.
	 *
	 * This test verifies that the `templateByUri` query correctly handles a valid author archive URI
	 * and returns the appropriate template for an author who has published posts.
	 */
	public function testAuthorWithPostsByUri(): void {
		// Create some posts with the same author.
		$this->factory->post->create_many(
			3,
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_author' => $this->user_id,
			]
		);

		$query     = $this->query();
		$variables = [];

		// Test a good URI for an author with posts.
		$uri = get_author_posts_url( $this->user_id );
		$this->assertNotEmpty( $uri, 'get_author_posts_url() should return a non-empty URI' );
		$variables['uri'] = wp_make_link_relative( $uri );
		$actual           = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered author template' );
		$this->assertContains( 'author', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the author class' );
		$this->assertContains( 'author-' . $this->user_id, $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the author-id-# class' );
		$this->assertEquals( 'User', $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be an Author' );
		$this->assertEquals( $this->user_id, $actual['data']['templateByUri']['connectedNode']['databaseId'], 'connectedNode should have the correct databaseId' );
		$this->assertEquals( get_the_author_meta( 'display_name', $this->user_id ), $actual['data']['templateByUri']['connectedNode']['name'], 'connectedNode should have the correct title' );
	}

	/**
	 * Test the templateByUri GraphQL query for an author archive page for a user with no posts, authenticated.
	 *
	 * This test verifies that the `templateByUri` query correctly handles a valid author archive URI
	 * for a user who has no posts, while the user is authenticated.
	 */
	public function testAuthenticatedAuthorWithoutPostsByUri(): void {
		// Create a user with no posts.
		$subscriber = $this->factory->user->create(
			[
				'role' => 'subscriber',
			]
		);

		$query     = $this->query();
		$variables = [];

		// Test the author archive for the user with no posts, authenticated.
		wp_set_current_user( $subscriber );
		$uri = get_author_posts_url( $subscriber );
		$this->assertNotEmpty( $uri, 'get_author_posts_url() should return a non-empty URI' );
		$variables['uri'] = wp_make_link_relative( $uri );
		$actual           = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered author template' );
		$this->assertContains( 'author', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the author class' );
		$this->assertContains( 'author-' . $subscriber, $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the author-id-# class' );
		$this->assertEquals( 'User', $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be an Author' );
		$this->assertEquals( $subscriber, $actual['data']['templateByUri']['connectedNode']['databaseId'], 'connectedNode should have the correct databaseId' );
		$this->assertEquals( get_the_author_meta( 'display_name', $subscriber ), $actual['data']['templateByUri']['connectedNode']['name'], 'connectedNode should have the correct title' );

		// Clean up: Delete the subscriber user.
		wp_delete_user( $subscriber );
	}

	/**
	 * Test the templateByUri GraphQL query for an author archive page for a user with no posts, unauthenticated.
	 *
	 * This test verifies that the `templateByUri` query correctly handles a valid author archive URI
	 * for a user who has no posts, while the user is unauthenticated.
	 *
	 * @todo: Fix the issue where `connectedNode` is null.
	 */
	public function testUnauthenticatedAuthorByUri(): void {
		// Create a user with no posts.
		$subscriber = $this->factory->user->create(
			[
				'role' => 'subscriber',
			]
		);

		$query     = $this->query();
		$variables = [];

		// Test the author archive for the user with no posts, unauthenticated.
		wp_set_current_user( 0 ); // Unauthenticated.
		$uri = get_author_posts_url( $subscriber );
		$this->assertNotEmpty( $uri, 'get_author_posts_url() should return a non-empty URI' );

		$variables['uri'] = wp_make_link_relative( $uri );
		$actual           = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered author template' );
		$this->assertContains( 'author', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the author class' );
		$this->assertContains( 'author-' . $subscriber, $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the author-id-# class' );

		$this->assertEmpty( $actual['data']['templateByUri']['connectedNode'], 'connectedNode should be null if a user has no published posts' );

		// Clean up the created user.
		wp_delete_user( $subscriber );
	}

	/**
	 * Test the templateByUri GraphQL query for a static home page.
	 *
	 * This test verifies that the `templateByUri` query correctly handles a valid home page URI
	 * when the home page is set to a static page in the WordPress settings.
	 */
	public function testHomepage(): void {
		// Set the home page to a static page.
		$home_page_id = $this->factory->post->create(
			[
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Home Page Test',
				'post_author' => $this->user_id,
			]
		);

		// Backup the original `page_for_posts` and `show_on_front` options.
		$original_page_for_posts = get_option( 'page_for_posts' );
		$original_show_on_front  = get_option( 'show_on_front' );

		// Unset posts page.
		update_option( 'page_for_posts', 0 );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_page_id );

		$query = $this->query();

		// Test the home page URI.
		$home_page_uri = wp_make_link_relative( get_permalink( $home_page_id ) );
		$this->assertEquals( '/', $home_page_uri, 'Home page URI should not be empty' );

		$variables['uri'] = $home_page_uri;
		$actual           = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered home page template' );
		$this->assertContains( 'home', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the home class' );
		$this->assertContains( 'page', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the page class' );
		$this->assertContains( 'page-id-' . $home_page_id, $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the page-id-# class' );
		$this->assertEquals( 'Page', $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be a Page' );
		$this->assertEquals( $home_page_id, $actual['data']['templateByUri']['connectedNode']['databaseId'], 'connectedNode should have the correct databaseId' );
		$this->assertEquals( 'Home Page Test', $actual['data']['templateByUri']['connectedNode']['title'], 'connectedNode should have the correct title' );

		// Clean up: Restore the original options.
		update_option( 'page_for_posts', $original_page_for_posts );
		update_option( 'show_on_front', $original_show_on_front );
	}

	/**
	 * Test the templateByUri GraphQL query for both the front page and the posts archive page.
	 *
	 * This test verifies that the `templateByUri` query correctly handles URIs for both the front page
	 * and the posts archive page when these are set to static pages in the WordPress settings.
	 */
	public function testFrontpage(): void {
		// Set up front page and posts page.
		$front_page_id = $this->factory->post->create(
			[
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Front Page Test',
				'post_author' => $this->user_id,
			]
		);

		$posts_page_id = $this->factory->post->create(
			[
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Posts Archive Test',
				'post_author' => $this->user_id,
			]
		);

		// Backup the original options.
		$original_page_on_front  = get_option( 'page_on_front' );
		$original_page_for_posts = get_option( 'page_for_posts' );
		$original_show_on_front  = get_option( 'show_on_front' );

		update_option( 'page_on_front', $front_page_id );
		update_option( 'page_for_posts', $posts_page_id );
		update_option( 'show_on_front', 'page' );

		$query = $this->query();

		// Test front page URI.
		$front_page_uri = wp_make_link_relative( get_permalink( $front_page_id ) );
		$this->assertNotEmpty( $front_page_uri, 'Front page URI should not be empty' );

		$variables = [ 'uri' => $front_page_uri ];
		$actual    = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered front page template' );
		$this->assertContains( 'home', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the home class' );
		$this->assertContains( 'page', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the page class' );
		$this->assertContains( 'page-id-' . $front_page_id, $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the page-id-# class' );
		$this->assertEquals( 'Page', $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be a Page' );
		$this->assertEquals( $front_page_id, $actual['data']['templateByUri']['connectedNode']['databaseId'], 'connectedNode should have the correct databaseId' );
		$this->assertEquals( 'Front Page Test', $actual['data']['templateByUri']['connectedNode']['title'], 'connectedNode should have the correct title' );
		$this->assertFalse( $actual['data']['templateByUri']['is404'], 'Should not be a 404.' );

		// Test posts archive URI.
		$posts_page_uri = wp_make_link_relative( get_permalink( $posts_page_id ) );
		$this->assertNotEmpty( $posts_page_uri, 'Posts archive page URI should not be empty' );

		$variables['uri'] = $posts_page_uri;
		$actual           = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered posts archive template' );
		$this->assertContains( 'blog', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the blog class' );
		$this->assertEquals( 'Page', $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be a Page' );
		$this->assertEquals( $posts_page_id, $actual['data']['templateByUri']['connectedNode']['databaseId'], 'connectedNode should have the correct databaseId' );
		$this->assertEquals( 'Posts Archive Test', $actual['data']['templateByUri']['connectedNode']['title'], 'connectedNode should have the correct title' );

		// Clean up: Restore the original options.
		update_option( 'page_on_front', $original_page_on_front );
		update_option( 'page_for_posts', $original_page_for_posts );
		update_option( 'show_on_front', $original_show_on_front );
	}

	/**
	 * Test the templateByUri GraphQL query for a posts index homepage.
	 *
	 * This test verifies that the `templateByUri` query correctly handles the root URI (`/`)
	 * when the homepage is set to display the latest posts in the WordPress settings.
	 */
	public function testLatestPostsByUri(): void {
		// Backup the original options.
		$original_page_on_front  = get_option( 'page_on_front' );
		$original_page_for_posts = get_option( 'page_for_posts' );
		$original_show_on_front  = get_option( 'show_on_front' );

		// Set "Your homepage displays" to "Your latest posts".
		update_option( 'page_on_front', 0 );
		update_option( 'page_for_posts', 0 );
		update_option( 'show_on_front', 'posts' );

		// Create some posts to be displayed on the homepage.
		$this->factory->post->create(
			[
				'post_status' => 'publish',
				'post_title'  => 'Latest Post',
				'post_author' => $this->user_id,
			]
		);

		$query = $this->query();

		// Test the root URI `/` which should display the latest posts.
		$variables = [ 'uri' => '/' ];
		$actual    = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $actual['data']['templateByUri'], 'templateByUri should return the rendered posts index template' );
		$this->assertContains( 'home', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the home class' );
		$this->assertContains( 'blog', $actual['data']['templateByUri']['bodyClasses'], 'bodyClasses should contain the blog class' );
		$this->assertEquals( 'ContentType', $actual['data']['templateByUri']['connectedNode']['__typename'], 'connectedNode should be a PostsPage' );
		$this->assertTrue( $actual['data']['templateByUri']['connectedNode']['isPostsPage'], 'connectedNode should be a posts page' );
		$this->assertFalse( $actual['data']['templateByUri']['is404'], 'Should not be a 404.' );

		// Clean up: Restore the original options.
		update_option( 'page_on_front', $original_page_on_front );
		update_option( 'page_for_posts', $original_page_for_posts );
		update_option( 'show_on_front', $original_show_on_front );
	}

	/**
	 * Placeholder test for the templateByUri GraphQL query for search results.
	 *
	 * This test is marked as incomplete and needs to be implemented.
	 *
	 * The method is intended to test the `templateByUri` query for handling URIs related to search results.
	 */
	public function testSearchByUri(): void {
		$this->markTestIncomplete( 'This test has not been implemented yet.' );
	}

	/**
	 * Test the templateByUri GraphQL query for the flat argument.
	 *
	 * This test verifies that the `templateByUri` query correctly handles the flat argument.
	 * It ensures that the query returns the flattened list of blocks when flat argument is set to true.
	 * It also ensures that the query returns non-flattened list of blocks when flat argument is set to false or null.
	 */
	public function testTemplateByUriQueryFlatArgs(): void {
		// Create a post with a block.
		$post_id = $this->factory()->post->create();

		// Get the post URL and the query.
		$post_url = get_permalink( $post_id );
		// 1. Test the query with no flat argument set.

		$query         = $this->editor_blocks_query();
		$variables     = [
			'uri' => wp_make_link_relative( $post_url ),
		];
		$actual        = $this->graphql( compact( 'query', 'variables' ) );
		$editor_blocks = $actual['data']['templateByUri']['editorBlocks'];

		// Assert that the query was successful and the editor blocks are returned.
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $editor_blocks );
		$this->assertCount( 88, $editor_blocks );

		// 2. Test the query with the flat argument set to false.
		$query         = $this->editor_blocks_query( '(flat : false)' );
		$variables     = [
			'uri' => wp_make_link_relative( $post_url ),
		];
		$actual        = $this->graphql( compact( 'query', 'variables' ) );
		$editor_blocks = $actual['data']['templateByUri']['editorBlocks'];

		// Assert that the query was successful and the editor blocks are returned.
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $editor_blocks );
		$this->assertCount( 3, $editor_blocks );

		// Assert that the editor blocks are not flattened.
		foreach ( range( 0, 2 ) as $index ) {
			$this->assertNull( $editor_blocks[ $index ]['parentClientId'] );
		}

		// 3. Test the query with the flat argument set to true.
		$query         = $this->editor_blocks_query( '(flat : true)' );
		$variables     = [
			'uri' => wp_make_link_relative( $post_url ),
		];
		$actual        = $this->graphql( compact( 'query', 'variables' ) );
		$editor_blocks = $actual['data']['templateByUri']['editorBlocks'];

		// Assert that the query was successful and the editor blocks are returned.
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotEmpty( $editor_blocks );
		$this->assertCount( 88, $editor_blocks );
	}
}
