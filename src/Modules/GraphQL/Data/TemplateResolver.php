<?php
/**
 * Used to resolve the template for a given URI.
 *
 * @package SnapWP\Helper\Modules\GraphQL\Data
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace SnapWP\Helper\Modules\GraphQL\Data;

use WPGraphQL\AppContext;
use WPGraphQL\Router;

/**
 * Class - TemplateResolver
 */
class TemplateResolver {
	/**
	 * A Local copy of the WP object.
	 *
	 * @var \WP
	 */
	protected $wp;

	/**
	 * The AppContext object.
	 *
	 * @var \WPGraphQL\AppContext
	 */
	protected $context;

	/**
	 * The constructor.
	 *
	 * @param \WPGraphQL\AppContext $context The AppContext object.
	 */
	public function __construct( AppContext $context ) {
		// Stash the $wp global.
		global $wp;
		$this->wp = $wp;

		// Set the matched rule to the GraphQL endpoint.
		$this->wp->matched_rule = Router::$route . '/?$';

		$this->context = $context;
	}

	/**
	 * Given the URI of a resource, this method attempts to resolve it and return the appropriate hydrated template.
	 *
	 * @param string                     $uri              The path to be used as an identifier for the resource.
	 * @param array<string,mixed>|string $extra_query_vars Any extra query vars to consider
	 *
	 * @return ?array{renderedHtml:string,uri:string} The resolved template.
	 * @throws \GraphQL\Error\UserError If the query class does not exist.
	 */
	public function resolve_uri( string $uri, $extra_query_vars = '' ): ?array {
		/**
		 * When this filter return anything other than null, it will be used as a resolved node
		 * and the execution will be skipped.
		 *
		 * This is to be used in extensions to resolve their own templates which might not use
		 * WordPress permalink structure.
		 *
		 * @param mixed|null $node The node, defaults to nothing.
		 * @param string $uri The uri being searched.
		 * @param \WPGraphQL\AppContext $content The app context.
		 * @param \WP $wp WP object.
		 * @param array<string,mixed>|string $extra_query_vars Any extra query vars to consider.
		 */
		$template = apply_filters( 'snapwp_helper/graphql/resolve_template_uri', null, $uri, $this->context, $this->wp, $extra_query_vars );

		if ( ! empty( $template ) ) {
			return $template;
		}

		/**
		 * Try to resolve the URI with WP_Query.
		 *
		 * This is the way WordPress native permalinks are resolved.
		 *
		 * @see \WP::main()
		 */

		// Parse the URI and sets the $wp->query_vars property.
		$uri = $this->parse_request( $uri, $extra_query_vars );

		if ( null === $uri ) {
			return null;
		}

		/**
		 * If the URI is '/' we should try and get the home page id.
		 *
		 * We don't rely on $this->parse_request(), since the home page doesn't get a rewrite rule.
		 */
		if ( '/' === $uri ) {
			$home_page_id = $this->get_home_page_id();

			if ( null !== $home_page_id ) {
				$this->wp->query_vars['page_id'] = $home_page_id;
			}
		}

		$this->wp->build_query_string();

		/**
		 * @var \WP_Query $wp_the_query
		 */
		global $wp_the_query;

		$wp_the_query->query( $this->wp->query_vars );

		$this->handle_404();
		$this->wp->register_globals();

		return [
			'content'      => $this->get_rendered_template(),
			'renderedHtml' => get_the_block_template_html(),
			'uri'          => $uri,
		];
	}

	/**
	 * Parses a URL to produce an array of query variables.
	 *
	 * Mimics WP::parse_request()
	 *
	 * @param string                     $uri              The URI to parse.
	 * @param array<string,mixed>|string $extra_query_vars Any extra query vars to consider.
	 *
	 * @return string|null The parsed uri.
	 */
	protected function parse_request( string $uri, $extra_query_vars = '' ) {
		// Attempt to parse the provided URI.
		$parsed_url = wp_parse_url( $uri );

		if ( false === $parsed_url ) {
			graphql_debug(
				__( 'Cannot parse provided URI', 'snapwp-helper' ),
				[
					'uri' => $uri,
				]
			);
			return null;
		}

		// Bail if external URI.
		if ( isset( $parsed_url['host'] ) ) {
			$site_url = wp_parse_url( site_url() );
			$home_url = wp_parse_url( home_url() );

			/**
			 * @var array<string,mixed> $home_url
			 * @var array<string,mixed> $site_url
			 */
			if ( ! in_array(
				$parsed_url['host'],
				[
					$site_url['host'],
					$home_url['host'],
				],
				true
			) ) {
				graphql_debug(
					__( 'Cannot return a resource for an external URI', 'snapwp-helper' ),
					[
						'uri' => $uri,
					]
				);
				return null;
			}
		}

		if ( isset( $parsed_url['query'] ) && ( empty( $parsed_url['path'] ) || '/' === $parsed_url['path'] ) ) {
			$uri = $parsed_url['query'];
		} elseif ( isset( $parsed_url['path'] ) ) {
			$uri = $parsed_url['path'];
		}

		/**
		 * Follows pattern from WP::parse_request()
		 *
		 * @see https://github.com/WordPress/wordpress-develop/blob/6.0.2/src/wp-includes/class-wp.php#L135
		 */
		global $wp_rewrite;

		$this->wp->query_vars = [];
		$post_type_query_vars = [];

		if ( is_array( $extra_query_vars ) ) {
			$this->wp->query_vars = &$extra_query_vars;
		} elseif ( ! empty( $extra_query_vars ) ) {
			parse_str( $extra_query_vars, $this->wp->extra_query_vars );
		}

		// Set uri to Query vars.
		$this->wp->query_vars['uri'] = $uri;

		// Process PATH_INFO, REQUEST_URI, and 404 for permalinks.

		// Fetch the rewrite rules.
		$rewrite = $wp_rewrite->wp_rewrite_rules();
		if ( ! empty( $rewrite ) ) {
			// If we match a rewrite rule, this will be cleared.
			$error                   = '404';
			$this->wp->did_permalink = true;

			$pathinfo         = ! empty( $uri ) ? $uri : '';
			list( $pathinfo ) = explode( '?', $pathinfo );
			$pathinfo         = str_replace( '%', '%25', $pathinfo );

			list( $req_uri ) = explode( '?', $pathinfo );
			$home_path       = parse_url( home_url(), PHP_URL_PATH ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
			$home_path_regex = '';
			if ( is_string( $home_path ) && '' !== $home_path ) {
				$home_path       = trim( $home_path, '/' );
				$home_path_regex = sprintf( '|^%s|i', preg_quote( $home_path, '|' ) );
			}

			/*
			 * Trim path info from the end and the leading home path from the front.
			 * For path info requests, this leaves us with the requesting filename, if any.
			 * For 404 requests, this leaves us with the requested permalink.
			 */
			$query    = '';
			$matches  = null;
			$req_uri  = str_replace( $pathinfo, '', $req_uri );
			$req_uri  = trim( $req_uri, '/' );
			$pathinfo = trim( $pathinfo, '/' );

			if ( ! empty( $home_path_regex ) ) {
				$req_uri  = preg_replace( $home_path_regex, '', $req_uri );
				$req_uri  = trim( $req_uri, '/' ); // @phpstan-ignore-line
				$pathinfo = preg_replace( $home_path_regex, '', $pathinfo );
				$pathinfo = trim( $pathinfo, '/' ); // @phpstan-ignore-line
			}

			// The requested permalink is in $pathinfo for path info requests and
			// $req_uri for other requests.
			if ( ! empty( $pathinfo ) && ! preg_match( '|^.*' . $wp_rewrite->index . '$|', $pathinfo ) ) {
				$requested_path = $pathinfo;
			} else {
				// If the request uri is the index, blank it out so that we don't try to match it against a rule.
				if ( $req_uri === $wp_rewrite->index ) {
					$req_uri = '';
				}
				$requested_path = $req_uri;
			}
			$requested_file = $req_uri;

			$this->wp->request = $requested_path;

			// Look for matches.
			$request_match = $requested_path;
			if ( empty( $request_match ) ) {
				// An empty request could only match against ^$ regex.
				if ( isset( $rewrite['$'] ) ) {
					$this->wp->matched_rule = '$';
					$query                  = $rewrite['$'];
					$matches                = [ '' ];
				}
			} else {
				foreach ( (array) $rewrite as $match => $query ) {
					// If the requested file is the anchor of the match, prepend it to the path info.
					if ( ! empty( $requested_file ) && strpos( $match, $requested_file ) === 0 && $requested_file !== $requested_path ) {
						$request_match = $requested_file . '/' . $requested_path;
					}

					if (
						preg_match( "#^$match#", $request_match, $matches ) ||
						preg_match( "#^$match#", urldecode( $request_match ), $matches )
					) {
						if ( $wp_rewrite->use_verbose_page_rules && preg_match( '/pagename=\$matches\[([0-9]+)\]/', $query, $varmatch ) ) {
							// This is a verbose page match, let's check to be sure about it.
							$page = get_page_by_path( $matches[ $varmatch[1] ] ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_page_by_path_get_page_by_path
							if ( ! $page ) {
								continue;
							}

							$post_status_obj = get_post_status_object( $page->post_status );
							if (
								( ! isset( $post_status_obj->public ) || ! $post_status_obj->public ) &&
								( ! isset( $post_status_obj->protected ) || ! $post_status_obj->protected ) &&
								( ! isset( $post_status_obj->private ) || ! $post_status_obj->private ) &&
								( ! isset( $post_status_obj->exclude_from_search ) || $post_status_obj->exclude_from_search )
							) {
								continue;
							}
						}

						// Got a match.
						$this->wp->matched_rule = $match;
						break;
					}
				}
			}

			if ( ! empty( $this->wp->matched_rule ) ) {
				// Trim the query of everything up to the '?'.
				$query = preg_replace( '!^.+\?!', '', $query );

				// Substitute the substring matches into the query.
				$query = addslashes( \WP_MatchesMapRegex::apply( $query, $matches ) ); // @phpstan-ignore-line

				$this->wp->matched_query = $query;

				// Parse the query.
				parse_str( $query, $perma_query_vars );

				// If we're processing a 404 request, clear the error var since we found something.
				// @phpstan-ignore-next-line .
				if ( '404' == $error ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
					unset( $error );
				}
			}
		}

		/**
		 * Filters the query variables allowed before processing.
		 *
		 * Allows (publicly allowed) query vars to be added, removed, or changed prior
		 * to executing the query. Needed to allow custom rewrite rules using your own arguments
		 * to work, or any other custom query variables you want to be publicly available.
		 *
		 * @since 1.5.0
		 *
		 * @param string[] $public_query_vars The array of allowed query variable names.
		 */
		$this->wp->public_query_vars = apply_filters( 'query_vars', $this->wp->public_query_vars ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP core hook.

		foreach ( get_post_types( [ 'show_in_graphql' => true ], 'objects' )  as $post_type => $t ) {
			/** @var \WP_Post_Type $t */
			if ( $t->query_var ) {
				$post_type_query_vars[ $t->query_var ] = $post_type;
			}
		}

		foreach ( $this->wp->public_query_vars as $wpvar ) {
			$parsed_query = [];
			if ( isset( $parsed_url['query'] ) ) {
				parse_str( $parsed_url['query'], $parsed_query );
			}

			if ( isset( $this->wp->extra_query_vars[ $wpvar ] ) ) {
				$this->wp->query_vars[ $wpvar ] = $this->wp->extra_query_vars[ $wpvar ];
			} elseif ( isset( $_GET[ $wpvar ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$this->wp->query_vars[ $wpvar ] = $_GET[ $wpvar ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $perma_query_vars[ $wpvar ] ) ) {
				$this->wp->query_vars[ $wpvar ] = $perma_query_vars[ $wpvar ];
			} elseif ( isset( $parsed_query[ $wpvar ] ) ) {
				$this->wp->query_vars[ $wpvar ] = $parsed_query[ $wpvar ];
			}

			if ( ! empty( $this->wp->query_vars[ $wpvar ] ) ) {
				if ( ! is_array( $this->wp->query_vars[ $wpvar ] ) ) {
					$this->wp->query_vars[ $wpvar ] = (string) $this->wp->query_vars[ $wpvar ];
				} else {
					foreach ( $this->wp->query_vars[ $wpvar ] as $vkey => $v ) {
						if ( is_scalar( $v ) ) {
							$this->wp->query_vars[ $wpvar ][ $vkey ] = (string) $v;
						}
					}
				}

				if ( isset( $post_type_query_vars[ $wpvar ] ) ) {
					$this->wp->query_vars['post_type'] = $post_type_query_vars[ $wpvar ];
					$this->wp->query_vars['name']      = $this->wp->query_vars[ $wpvar ];
				}
			}
		}

		// Convert urldecoded spaces back into '+'.
		foreach ( get_taxonomies( [ 'show_in_graphql' => true ], 'objects' ) as $t ) {
			if ( $t->query_var && isset( $this->wp->query_vars[ $t->query_var ] ) ) {
				$this->wp->query_vars[ $t->query_var ] = str_replace( ' ', '+', $this->wp->query_vars[ $t->query_var ] );
			}
		}

		// Limit publicly queried post_types to those that are publicly_queryable.
		if ( isset( $this->wp->query_vars['post_type'] ) ) {
			$queryable_post_types = get_post_types( [ 'show_in_graphql' => true ] );
			if ( ! is_array( $this->wp->query_vars['post_type'] ) ) {
				if ( ! in_array( $this->wp->query_vars['post_type'], $queryable_post_types, true ) ) {
					unset( $this->wp->query_vars['post_type'] );
				}
			} else {
				$this->wp->query_vars['post_type'] = array_intersect( $this->wp->query_vars['post_type'], $queryable_post_types );
			}
		}

		// Resolve conflicts between posts with numeric slugs and date archive queries.
		$this->wp->query_vars = wp_resolve_numeric_slug_conflicts( $this->wp->query_vars );

		foreach ( (array) $this->wp->private_query_vars as $var ) {
			if ( isset( $this->wp->extra_query_vars[ $var ] ) ) {
				$this->wp->query_vars[ $var ] = $this->wp->extra_query_vars[ $var ];
			}
		}

		if ( isset( $error ) ) {
			$this->wp->query_vars['error'] = $error;
		}

		// if the parsed url is ONLY a query, unset the pagename query var.
		if ( isset( $this->wp->query_vars['pagename'], $parsed_url['query'] ) && ( $parsed_url['query'] === $this->wp->query_vars['pagename'] ) ) {
			unset( $this->wp->query_vars['pagename'] );
		}

		/**
		 * Filters the array of parsed query variables.
		 *
		 * @param array<string,mixed> $query_vars The array of requested query variables.
		 *
		 * @since 2.1.0
		 */
		$this->wp->query_vars = apply_filters( 'request', $this->wp->query_vars ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP core hook.

		// We don't need the GraphQL args anymore.
		unset( $this->wp->query_vars['graphql'] );

		do_action_ref_array( 'parse_request', [ &$this->wp ] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP core hook.

		return $uri;
	}

	/**
	 * Gets the home page id, if the home page is set.
	 *
	 * If the home page is a static page, this method will return the query vars for that page. Otherwise, it will return null.
	 */
	protected function get_home_page_id(): ?int {
		$page_id       = (int) get_option( 'page_on_front', 0 );
		$show_on_front = get_option( 'show_on_front', 'posts' );

		if ( 'page' !== $show_on_front || empty( $page_id ) ) {
			return null;
		}

		return $page_id;
	}

	/**
	 * Checks whether the current request is a 404.
	 *
	 * Mimics WP::handle_404()
	 */
	protected function handle_404(): void {
		/**
		 * @global \WP_Query $wp_query WordPress Query object.
		 */
		global $wp_query;

		/**
		 * Filters whether to short-circuit default header status handling.
		 *
		 * Returning a non-false value from the filter will short-circuit the handling
		 * and return early.
		 *
		 * @since 4.5.0
		 *
		 * @param bool     $preempt  Whether to short-circuit default header status handling. Default false.
		 * @param \WP_Query $wp_query WordPress Query object.
		 */
		if ( false !== apply_filters( 'pre_handle_404', false, $wp_query ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP core hook.
			return;
		}

		// If we've already issued a 404, bail.
		if ( is_404() ) {
			return;
		}

		$set_404 = true;

		// Never 404 for the admin, robots, or favicon.
		if ( is_admin() || is_robots() || is_favicon() ) {
			$set_404 = false;

			// If posts were found, check for paged content.
		} elseif ( $wp_query->posts ) {
			$content_found = true;

			if ( is_singular() ) {
				$post = isset( $wp_query->post ) ? $wp_query->post : null;
				$next = '<!--nextpage-->';

				// Check for paged content that exceeds the max number of pages.
				if ( $post && ! empty( $this->wp->query_vars['page'] ) ) {
					// Check if content is actually intended to be paged.
					if ( str_contains( $post->post_content, $next ) ) {
						$page          = trim( $this->wp->query_vars['page'], '/' );
						$content_found = (int) $page <= ( substr_count( $post->post_content, $next ) + 1 );
					} else {
						$content_found = false;
					}
				}
			}

			// The posts page does not support the <!--nextpage--> pagination.
			if ( $wp_query->is_posts_page && ! empty( $this->wp->query_vars['page'] ) ) {
				$content_found = false;
			}

			if ( $content_found ) {
				$set_404 = false;
			}

			// We will 404 for paged queries, as no posts were found.
		} elseif ( ! is_paged() ) {
			$author = get_query_var( 'author' );

			$author = is_numeric( $author ) ? (int) $author : 0;

			// Don't 404 for authors without posts as long as they matched an author on this site.
			if ( ( is_author() && $author > 0 && is_user_member_of_blog( (int) $author ) )
				// Don't 404 for these queries if they matched an object.
				|| ( ( is_tag() || is_category() || is_tax() || is_post_type_archive() ) && get_queried_object() )
				// Don't 404 for these queries either.
				|| is_home() || is_search() || is_feed()
			) {
				$set_404 = false;
			}
		}

		if ( $set_404 ) {
			// Guess it's time to 404.
			$wp_query->set_404();
		}
	}

	/**
	 * Gets the rendered template.
	 *
	 * Mimics logic in template-loader.php
	 */
	protected function get_rendered_template(): ?string {
		$tag_templates = [
			'is_embed'             => 'get_embed_template',
			'is_404'               => 'get_404_template',
			'is_search'            => 'get_search_template',
			'is_front_page'        => 'get_front_page_template',
			'is_home'              => 'get_home_template',
			'is_privacy_policy'    => 'get_privacy_policy_template',
			'is_post_type_archive' => 'get_post_type_archive_template',
			'is_tax'               => 'get_taxonomy_template',
			'is_attachment'        => 'get_attachment_template',
			'is_single'            => 'get_single_template',
			'is_page'              => 'get_page_template',
			'is_singular'          => 'get_singular_template',
			'is_category'          => 'get_category_template',
			'is_tag'               => 'get_tag_template',
			'is_author'            => 'get_author_template',
			'is_date'              => 'get_date_template',
			'is_archive'           => 'get_archive_template',
		];

		$template = false;

		// Loop through each of the template conditionals, and find the appropriate template file.
		foreach ( $tag_templates as $tag => $template_getter ) {
			if ( call_user_func( $tag ) ) {
				$template = call_user_func( $template_getter );
			}

			if ( $template ) {
				if ( 'is_attachment' === $tag ) {
					remove_filter( 'the_content', 'prepend_attachment' );
				}

				break;
			}
		}

		if ( ! $template ) {
			$template = get_index_template(); // phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable -- we only need to call the function for the globals.
		}

		return $this->get_the_block_template_content();
	}

	/**
	 * Gets the block template content.
	 *
	 * Mimics get_the_block_template_html() without the do_blocks() call, which strips the block attributes.
	 */
	protected function get_the_block_template_content(): ?string {
		global $_wp_current_template_content, $wp_embed;

		if ( ! $_wp_current_template_content ) {
			return null;
		}

		$content = $wp_embed->run_shortcode( $_wp_current_template_content );

		$content = $wp_embed->autoembed( $content );
		$content = shortcode_unautop( $content );
		$content = do_shortcode( $content );

		return $content;
	}
}
