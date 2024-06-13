<?php
/**
 * Clarity Feed Actions class.
 *
 * This class provides functionality to render an archive feed within a block/template.
 *
 * @package clarity-theme
 *
 * Disable phpcs rules
 * @phpcs:disable Generic.Metrics.NestingLevel.MaxExceeded
 * @phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded
 */

namespace cty;

/**
 * Class definition.
 */
abstract class Clarity_Archive_Base {
	/**
	 * The unique id to identify the current query.
	 *
	 * @var string
	 */
	public $unique_id;

	/**
	 * The current form name.
	 *
	 * @var string
	 */
	public $current_name;


	/**
	 * The main query
	 *
	 * @var WP_Query
	 */
	public $current_query;


	/**
	 * Array to be saved in the temp file.
	 *
	 * This array stores the WP query data needed by the feed to work properly
	 *
	 * @var array
	 */
	public $query_args = [];

	/**
	 * Array to be saved in the temp file.
	 *
	 * This array stores the custom feed parameters.
	 *
	 * @var array
	 */
	public $feed_args = [];

	/**
	 * INIT
	 */
	public function __construct() {
		// Register JS.
		add_action( 'wp_enqueue_scripts', [ $this, 'register_script' ] );

		// Adding main ajax actions.
		add_action( 'wp_ajax_clarity-archive', [ $this, 'filter_on_xhr' ] );
		add_action( 'wp_ajax_nopriv_clarity-archive', [ $this, 'filter_on_xhr' ] );

		// Filter on page load.
		add_action( 'pre_get_posts', [ $this, 'filter_on_load' ] );

		// Add theme svg icons to REST API.
		add_action( 'rest_api_init', [ $this, 'register_icon_route' ] );
	}

	/**
	 * 'wp_enqueue_scripts' action hook callback
	 * Register the script.
	 */
	public function register_script() {
		$script_src        = get_template_directory_uri() . '/dist/js/clarity-archive.js';
		$script_asset_path = get_template_directory() . '/dist/js/clarity-archive.asset.php';

		// WP Scripts is required for the main theme script file.
		if ( ! file_exists( $script_asset_path ) ) {
			trigger_error(
				'You need to run `npm run watch` or `npm run build` first.',
				E_USER_ERROR
			);
		}
		$script_asset = require $script_asset_path;

		// Register script so it can be enqueued later.
		// We're adding clarity-script as a dependency so that any localized vars can be accessed in clarity-archive.
		wp_register_script(
			'clarity-archive',
			$script_src,
			[ 'clarity-script' ],
			$script_asset['version'],
			[
				'strategy'  => 'defer',
				'in_footer' => true,
			]
		);
	}

	/**
	 * 'wp_ajax_clarity-archive' action hook callback
	 * 'wp_ajax_nopriv_clarity-archive' action hook callback
	 * Parse sent data via XHR, do new WP Query and load new posts
	 */
	public function filter_on_xhr() {
		$nonce = check_ajax_referer( 'wp_rest', 'nonce' );
		if ( ! $nonce || ! isset( $_REQUEST['feedFilter'] ) ) {
			die( -1 );
		}

		// The form name (used for the filters).
		$name = '';
		if ( isset( $_REQUEST['feedName'] ) ) {
			$name               = sanitize_text_field( wp_unslash( $_REQUEST['feedName'] ) );
			$this->current_name = $name;
		}

		// The form UID.
		$unique_id = '';
		if ( isset( $_REQUEST['unique_id'] ) ) {
			$unique_id       = sanitize_text_field( wp_unslash( $_REQUEST['unique_id'] ) );
			$this->unique_id = $unique_id;
		}

		// Return if no name or uid found.
		if ( ! $name || ! $unique_id ) {
			die( -1 );
		}

		// Load temp data.
		$query_args                     = [];
		$feed_args                      = [];
		list( $query_args, $feed_args ) = $this->load_temp_data( $unique_id );
		$this->query_args               = $query_args;
		$this->feed_args                = $feed_args;

		// The WP_Query arguments.
		$params = [];
		if ( isset( $_REQUEST['params'] ) ) {
			$post_data = wp_unslash( sanitize_text_field( $_REQUEST['params'] ) ); //phpcs:ignore
			parse_str( $post_data, $params );
		}

		// Sort given params.
		foreach ( $params as $key => $param ) {
			if ( is_array( $param ) ) {
				continue;
			} else {
				$params[ $key ] = maybe_unserialize( $param );
			}
		}

		// Filter Query.
		$query_args = $this->filter_wp_query( $query_args, $params );

		// Save prop.
		$this->query_args = $query_args;

		\cty\dump( 'FEED ARGS:' );
		\cty\dump( $query_args );
		\cty\dump( $params );
		\cty\dump( $feed_args );

		// Setup response.
		$response = [];
		$posts    = [];

		// Start WP_Query.
		$posts               = new \WP_Query( $query_args );
		$this->current_query = $posts;

		// Setup returned HTML for post items.
		for ( $i = 0; $i < $query_args['cty_feed_count']; $i++ ) {
			$this->feed_args['cty_feed_no'] = $i;
			$response['post_items'][ $i ]   = $this->display_post_items( $feed_args[ 'cty_feed_' . $i ], false );
		}

		// Setup returned HTML for pagination.
		if ( isset( $feed_args['pagination'] ) ) {
			$response['paginaton'] = $this->display_pagination( $feed_args['pagination'], false );
		}

		// Setup returned HTML for search summary.
		if ( isset( $feed_args['summary'] ) ) {
			$response['summary'] = $this->display_search_summary( false );
		}

		// Adding found/offset vars to send back to clarity-archive.js.
		$found      = intval( $posts->found_posts );
		$post_count = intval( $posts->post_count );
		if ( $found ) {
			$offset                  = intval( $query_args['offset'] ?? 0 );
			$cty_offset              = $offset + $post_count;
			$response['posts_count'] = (int) $post_count;
			$response['offset']      = (int) $cty_offset;
			$response['found']       = (int) $found;
		}

		wp_reset_postdata();

		// Return to JS.
		echo wp_json_encode( $response );
		wp_die();
	}

	/**
	 * 'pre_get_posts' action hook callback
	 * Filters posts on page load if URL params are present
	 *
	 * @param WP_Query $query the query object.
	 */
	public function filter_on_load( $query ) {
		$is_feed = $query->get( 'cty_feed' );

		if ( $is_feed && ! wp_doing_ajax() ) {
			$query_vars = isset( $query->query ) ? $query->query : [];
			$query_args = $this->filter_wp_query( $query_vars, $_GET );

			if ( is_array( $query_args ) ) {
				// Apply only the query args we need.
				foreach ( $query_args as $key => $value ) {
					$query->set( $key, $value );
				}

				// Unset the ones we don't.
				foreach ( $query->query_vars as $key => $value ) {
					if ( ! isset( $query_args[ $key ] ) ) {
						$query->set( $key, null );
						unset( $query->query_vars[ $key ] );
					}
				}
			}

			$this->current_query = $query;
			\cty\dump( 'QUERY ARGS:' );
			\cty\dump( $query->query_vars );
		}
	}

	/**
	 * Filter the WP_Query arguments by applying the data received from the URL.
	 *
	 * @param array $query_args array of arguments to pass to WP_Query.
	 * @param array $params the $_REQUEST data.
	 */
	public function filter_wp_query( $query_args, $params = [] ) {
		/**
		 * To avoid conflict with WP the taxonomy names are prefixed with "filter-"
		 * While meta queries are prefixed with "meta-"
		 */
		$data   = [];
		$meta   = [];
		$others = [];

		foreach ( $params as $key => $value ) {
			$key   = sanitize_text_field( $key );
			$value = is_array( $value ) ? $value : sanitize_text_field( $value );

			if ( strpos( $key, 'meta-' ) === 0 ) {
				$key = str_replace( 'meta-', '', $key );

				/**
				 * If the meta value passed is empty, we need to check if it exists in the original query
				 * If not we have to remove it!
				 */
				if ( empty( $value ) ) {
					$meta_query = $query_args['meta_query'] ?? [];

					foreach ( $meta_query as $id => $arg ) {
						if ( $query_args['key'] === $key ) {
							unset( $query_args['meta_query'][ $id ] );
						}
					}

					continue;
				}

				/**
				 * Check if the filter is present as a previous $params, if so we need to:
				 *  - Convert the "value" as array (if is not an array yet)
				 *  - append it to the list of values
				 */
				if ( is_array( $value ) ) {
					$value = array_map(
						function( $val ) {
							return sanitize_title( $val );
						},
						$value
					);
				} else {
					$value = sanitize_title( $value );
				}

				$meta[ $key ] = [
					'key'   => str_replace( 'meta-', '', $key ),
					'value' => $value,
				];
			} elseif ( stripos( $key, 'filter-' ) === 0 ) {
				$key          = str_replace( 'filter-', '', $key );
				$data[ $key ] = $value;
			} elseif ( stripos( $key, 's' ) === 0 ) {
				$data['search'] = $value;
			}
		}

		/**
		 * Sanitize the taxonomies data array.
		 * Any filter that is not a taxonomy will be added to others array.
		 */
		$taxonomies = [];
		foreach ( $data as $key => $value ) {
			if ( taxonomy_exists( $key ) ) {
				if ( ! is_array( $value ) ) {
					$value = [ $value ];
				}

				$taxonomies[ $key ] = $value;
			} else {
				$others[ $key ] = $value;
			}
		}

		/**
		 * Only "special" keys are kept, like:
		 * - sort
		 * - sortby
		 * - search
		 *
		 * This will prevent the user from passing any custom argument to the WP_Query, like:
		 *  https://example.com?posts_per_page=-1&post_type=post
		 */
		$sort   = $others['sort'] ?? '';
		$sortby = $others['sortby'] ?? '';

		// Sort?
		if ( ! empty( $sort ) ) {
			$query_args['order'] = $sort;

			// If sort field contains 2 values, explode by '-', then orderby = sort[0] and order = $sort[1].
			if ( strpos( $sort, '-' ) !== false ) {
				$sort                  = explode( '-', $sort );
				$query_args['orderby'] = $sort[0];
				$query_args['order']   = $sort[1];
			}
		}

		if ( ! empty( $sortby ) ) {
			$query_args['orderby'] = $sortby;
		}

		// Search?
		$search = $others['search'] ?? '';
		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}

		// Unset search if empty.
		if ( empty( $params['filter-search'] ) && empty( $params['s'] ) ) {
			unset( $query_args['s'] );
		}

		// Offset have to be used only when clicking the LOAD MORE button.
		$append = $_REQUEST['feedAppend'] ?? 'false'; // phpcs:ignore
		if ( $append !== 'false' ) {
			$query_args['offset'] = is_numeric( $append ) ? intval( $append ) : intval( $this->feed_args['count'] );
		} else {
			unset( $query_args['offset'] );
		}

		// Remove featured items from main query on first page on load.
		if (
			isset( $this->feed_args['featured'] ) &&
			! wp_doing_ajax() &&
			! $this->is_filtered() &&
			! $this->is_paged()
		) {
			$query_args['post__not_in'] = $this->feed_args['featured'];
		}

		// Create tax/meta queries for WP_Query.
		$query_args = $this->modify_wp_query_args( $query_args, $taxonomies, $meta );

		return $query_args;
	}

	/**
	 * Use the $query_args to properly set up the argument array needed for the WP_Query.
	 *
	 * For example:
	 * Information like 'taxonomy' are passed as simple array/string by the form, so we need
	 * to convert it in a "tax_query" or "meta_query" array used by WP_Query.
	 *
	 * @param array $query_args WP_Query args to modify.
	 * @param array $taxonomies list of taxonomies to filter.
	 * @param array $meta the meta_query data.
	 */
	private function modify_wp_query_args( $query_args, $taxonomies = [], $meta = [] ) {
		// Append the taxonomy term.
		if ( isset( $query_args['taxonomy'] ) && isset( $query_args['term_taxonomy_id'] ) ) {
			$query_args['tax_query'][] = [
				'taxonomy' => $query_args['taxonomy'],
				'field'    => 'term_id',
				'terms'    => [ (int) $query_args['term_taxonomy_id'] ],
			];

			unset( $query_args['taxonomy'] );
			unset( $query_args['term_taxonomy_id'] );
		}

		// Set up tax_query.
		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy => $values ) {
				if ( is_array( $values ) ) {
					$values = array_filter( $values );
				}

				if ( empty( $values ) ) {
					continue;
				}

				$tq = [
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $values,
				];

				if ( is_array( $values ) ) {
					$tq['compare'] = 'IN';
				}

				$query_args['tax_query'][] = $tq;
			}
		}

		/**
		 * Category?
		 */
		// Filter by category id(s).
		if ( isset( $query_args['category'] ) ) {
			$values = $query_args['category'];

			if ( ! is_array( $values ) ) {
				$values = [ $values ];
			}

			$query_args['category__in'] = $values;
			unset( $query_args['category'] );
		}

		// Filter by category name(s).
		if ( isset( $query_args['category-name'] ) ) {
			$values = $query_args['category-name'];

			if ( is_array( $values ) ) {
				$values = implode( ',', [ $values ] );
			}

			$query_args['category_name'] = $values;

			unset( $query_args['category__in'] );
			unset( $query_args['category-name'] );
			unset( $query_args['categoryName'] );
		}

		/**
		 * Tag?
		 */
		if ( isset( $query_args['tag'] ) ) {
			$values = $query_args['tag'];

			if ( ! is_array( $values ) ) {
				$values = [ $values ];
			}
			$query_args['tag__in'] = $values;
			unset( $query_args['tag'] );
		}

		/**
		 * Meta filter?
		 */
		if ( ! empty( $meta ) ) {
			if ( ! isset( $query_args['meta_key'] ) ) {
				$query_args['meta_query'] = [];
			}

			$query_args['meta_query'] += $meta;
		}

		return $query_args;
	}

	/**
	 * Display or return queried post items.
	 *
	 * @param array $item_args custom post item block arguments.
	 * @param bool  $echo if true render.
	 */
	public function display_post_items( $item_args = [], $echo = true ) {
		// Get the loop property and return if none.
		$posts_query = $this->current_query;
		if ( $posts_query === null ) {
			return;
		}

		// Setup internal vars.
		$template_single = $item_args['template']['single'] ?? 'partials/card-post-link';
		$template_none   = $item_args['template']['none'] ?? 'partials/archive-no-results';
		$columns         = $item_args['columns'] ?? 3;

		// On page load, iterate each feed number so we know which block we're rendering later.
		if ( ! wp_doing_ajax() ) {
			$this->feed_args['cty_feed_no']        = isset( $this->feed_args['cty_feed_no'] )
				? $this->feed_args['cty_feed_no'] += 1
				: 0;
		}

		// Set up class props for XHR filtering.
		$cty_feed_count = $posts_query->query['cty_feed_count'] ?? 1;
		for ( $i = 0; $i < $cty_feed_count; $i++ ) {
			if ( $this->feed_args['cty_feed_no'] === $i ) {
				$this->feed_args[ 'cty_feed_' . $i ] = [
					'template' => [
						'single' => $template_single,
						'none'   => $template_none,
					],
					'columns'  => $columns,
				];
			}
		}

		ob_start();
		if ( $posts_query->have_posts() ) {
			// If there's more than one post item block, split the feed based on colum size.
			if ( $cty_feed_count > 1 ) {
				// Max post count for each block should be double the column count.
				$chunk_size = intval( $columns * 2 );

				// Loop through how many feeds there are and render templates for corresponding block #no.
				for ( $i = 0; $i < $cty_feed_count; $i++ ) {
					if ( $this->feed_args['cty_feed_no'] === $i ) {
						$chunks = array_splice( $posts_query->posts, 0, $chunk_size );

						// If this is the last block and there are still items left.
						if ( $cty_feed_count === $i + 1 && count( $posts_query->posts ) > 0 ) {
							$chunks = array_merge( $chunks, $posts_query->posts );
						}

						// Add new query prop to count total for load more offsets.
						if ( isset( $posts_query->chunk_total ) ) {
							$posts_query->chunk_total += count( $chunks );
						} else {
							$posts_query->chunk_total = count( $chunks );
						}

						// Loop and render through chunks array of posts.
						foreach ( $chunks as $the_post ) {
							get_template_part( $template_single, '', [ 'id' => $the_post->ID ] );
						}
					}
				}
			} else {
				while ( $posts_query->have_posts() ) {
					$posts_query->the_post();
					get_template_part( $template_single, '', [ 'id' => get_the_ID() ] );
				}
			}
		} else {
			// If there are multiple feed this only needs to be rendered once.
			if ( $cty_feed_count > 1 ) {
				if ( $this->feed_args['cty_feed_no'] === 0 ) {
					get_template_part( $template_none );
				}
			} else {
				get_template_part( $template_none );
			}
		}
		$rendered_post_items = ob_get_clean();

		if ( $echo ) {
			echo wp_kses_post( $rendered_post_items );
		}
		return $rendered_post_items;
	}

	/**
	 * Pagination function for archive pages.
	 *
	 * @param string $template partial for rendering template.
	 * @param bool   $echo if true render.
	 */
	public function display_pagination( $template = '', $echo = true ) {
		// Save template as class feed_args prop if set.
		if ( $template ) {
			$this->feed_args['pagination'] = $template;
		} else {
			$template = $this->feed_args['pagination'] ?? 'partials/archive-pagination';
		}

		// Get the current page number.
		$current_page = max( 1, get_query_var( 'paged' ) );

		// Amend found_posts var if first page and featured posts are set.
		if ( isset( $this->current_query->query_vars['post__not_in'] ) && $current_page === 1 ) {
			$this->current_query->found_posts += count( $this->current_query->query_vars['post__not_in'] );

			// Calculate new max_num_pages.
			$ppp   = intval( $this->current_query->query_vars['posts_per_page'] );
			$total = intval( $this->current_query->found_posts );
			$pages = ceil( $total / $ppp );

			$this->current_query->max_num_pages = $pages;
		}

		// Get query object.
		$posts_query = $this->current_query;
		if ( $posts_query === null ) {
			return;
		}

		// How many pages? If just one, no pagination is required.
		$total_pages = $posts_query->max_num_pages;
		\cty\dump( $posts_query->max_num_pages );
		if ( $total_pages < 2 ) {
			return;
		}

		// Get any custom $_GET params from the url, these will be appended to page links.
		$custom_params = count( $_GET ) > 0 ? '?' . http_build_query( $_GET ) : '';

		// Get the current filter args from $params, needs to be appended to the base_url.
		if ( isset( $_REQUEST['params'] ) ) {
			parse_str( sanitize_text_field( wp_unslash( $_REQUEST['params'] ) ), $params_array );
			foreach ( $params_array as $key => $value ) {
				if (
					empty( $value ) ||
					stripos( $key, 'query-' ) !== false
				) {
					unset( $params_array[ $key ] );
				}
			}
			$params_string = http_build_query( $params_array );
			$custom_params = $params_string ? '?' . $params_string : '';
			$_REQUEST      = [];
		}

		// Get the base url of the current archive/taxonomy/whatever page without any pagination queries.
		if ( isset( $this->feed_args['base_url'] ) ) {
			$base_url = $this->feed_args['base_url'];
		} else {
			$this->feed_args['base_url'] = explode( '?', get_pagenum_link( 1 ) )[0];
			$base_url                    = $this->feed_args['base_url'];
		}

		// Set first/last page slugs.
		$page_slug  = 'page';
		$first_page = $base_url . $custom_params;
		$last_page  = $base_url . $page_slug . '/' . $total_pages . $custom_params;

		// The custom paginate links args.
		$pagination_args = [
			'base'       => $base_url . '%_%' . $custom_params,
			'format'     => $page_slug . '/%#%/',
			'current'    => $current_page,
			'total'      => $total_pages,
			'type'       => 'plain',
			'prev_text'  => '<span>&lsaquo;</span>',
			'next_text'  => '<span>&rsaquo;</span>',
			'first_page' => $first_page,
			'last_page'  => $last_page,
			'class'      => 'cty-archive-pagination__pagination pagination',
		];

		// Start buffer.
		ob_start();

		/**
		 * The paginate_links function appends GET params from the so this is a fix
		 * to make sure the server request URI is not set as admin-ajax.php while doing ajax
		 */
		$original_uri           = $_SERVER['REQUEST_URI']; //phpcs:ignore
		$_SERVER['REQUEST_URI'] = $base_url;

		// Get template part.
		get_template_part( $template, '', $pagination_args );

		// Reset URI.
		$_SERVER['REQUEST_URI'] = $original_uri;

		// Rendering.
		$rendered_pagination = ob_get_clean();
		if ( $echo ) {
			echo wp_kses_post( $rendered_pagination );
		}
		return $rendered_pagination;
	}

	/**
	 * Displays search summary
	 *
	 * @param bool $echo if true render.
	 */
	public function display_search_summary( $echo = true ) {
		$query = $this->current_query;
		if ( ! $query ) {
			return;
		}

		// Let XHR know we're using this method.
		$this->feed_args['summary'] = true;

		// Create a comma separated string from the search terms.
		$terms = isset( $query->query_vars['search_terms'] ) ? implode( ', ', $query->query_vars['search_terms'] ) : '';
		$terms = sanitize_text_field( $terms );

		// If there are no search terms, get tax query items.
		if ( ! $terms ) {
			if ( isset( $query->query_vars['tax_query'] ) ) {
				$terms = wp_list_pluck( $query->query_vars['tax_query'], 'terms' );
				$terms = '"' . implode( ', ', array_merge( ...$terms ) ) . '"';
			} else {
				$terms = __( 'your query', 'cty' );
			}
		} else {
			$terms = '"' . $terms . '"';
		}

		// Get the current search results page number.
		$page_num = max( 1, get_query_var( 'paged' ) );

		// Get the number of posts on the current page.
		$page_total_posts =
			isset( $query->query_vars['posts_per_page'] ) && $query->query_vars['posts_per_page'] < $query->found_posts
			? $query->query_vars['posts_per_page']
			: $query->found_posts;

		// Get index number of first post on current page.
		$from = ( $page_num - 1 ) * $page_total_posts + 1;

		// Index number for last post on the current page.
		$to = $page_total_posts * $page_num;

		// If this is the last page, then the $to value will be the total number of found posts.
		if ( $to > $query->found_posts ) {
			$to = $query->found_posts;
		}

		// Calculate 'load more' loaded posts.
		if ( $page_num === 1 && isset( $query->query_vars['offset'] ) ) {
			$to += $query->query_vars['offset'];

			if ( $to >= $query->found_posts ) {
				$to = $query->found_posts;
			}
		}

		if ( $query->found_posts === 0 ) {
			// No posts found template string.
			// translators: %4$s = $terms.
			$template = __( 'No results found for %4$s', 'cty' );
		} else {
			// Default template string, if one hasn't been passed.
			// translators: %1$s = $from, %2$s = $to, %3$s = total found.
			$template = __( 'Showing %1$s - %2$s of %3$s', 'cty' );
		}

		// Start buffer.
		ob_start();

		printf(
			'<div class="cty-archive-summary__inner">' . wp_kses_post( $template ) . '</div>',
			esc_html( $from ),
			esc_html( $to ),
			esc_html( $query->found_posts ),
			esc_html( $terms )
		);

		$rendered_summary = ob_get_clean();

		// Rendering.
		if ( $echo ) {
			echo wp_kses_post( $rendered_summary );
		}
		return $rendered_summary;
	}

	/**
	 * Write to temp file.
	 */
	protected function write_to_file() {
		// Save the temp data.
		$temp_file  = trailingslashit( sys_get_temp_dir() ) . 'cty--' . $this->unique_id;
		$query_args = var_export( $this->query_args, true );
		$feed_args  = var_export( $this->feed_args, true );
		$temp_data  = '<?php $query_args = ' . $query_args . '; $feed_args = ' . $feed_args . ';';
		$saved      = file_put_contents( $temp_file, $temp_data, LOCK_EX );
		return $saved;
	}

	/**
	 * Load temp data from file.
	 *
	 * @param string $unique_id ID of the tmp file to fetch.
	 */
	public function load_temp_data( $unique_id = '' ) {
		$unique_id = $unique_id ? $unique_id : $this->unique_id;

		// Nothing to do here.
		if ( empty( $unique_id ) ) {
			return false;
		}

		// Load the "temp" data.
		$temp_file = trailingslashit( sys_get_temp_dir() ) . 'cty--' . $unique_id;
		if ( ! file_exists( $temp_file ) ) {
			\cty\dump( 'ERROR LOADING TEMP FILE' );
			return false;
		}

		include $temp_file;
		return [ $query_args, $feed_args ];
	}

	/**
	 * Check if a filter is applied on load
	 */
	protected function is_filtered() {
		foreach ( $_GET as $param => $value ) {
			if ( strpos( $param, 'filter-' ) !== 0 ) {
				continue;
			}
			if ( $value ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if the paged var is set and is more than 1
	 */
	protected function is_paged() {
		$paged = max( get_query_var( 'paged' ), 1 );
		return $paged > 1 ? true : false;
	}

	/**
	 * 'rest_api_init' action hook callback
	 * Registers route/endpoint for REST API so we can fetch icons
	 */
	public function register_icon_route() {
		register_rest_route(
			'cty/v1',
			'/svgs',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'add_svgs_to_rest' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Callback function for register_icon_route
	 * Returns all svg icons as keyed array
	 */
	public function add_svgs_to_rest() {
		$svgs = [];

		// Get icons from folder.
		$theme_path = get_template_directory();

		// Get partials files.
		$template_files = glob( $theme_path . '/assets/images/svg/*' );

		// Loop through the array that glob returned.
		foreach ( $template_files as $filename ) {
			$filename = str_replace( $theme_path . '/', '', $filename );
			$filename = str_replace( '.svg', '', $filename );
			$filename = str_replace( 'assets/images/svg/', '', $filename );

			$svgs[] = [
				'label' => $filename,
				'value' => $filename,
			];
		}

		return $svgs;
	}
}

/**
 * Re-enable phpcs rule
 * @phpcs:enable Generic.Metrics.NestingLevel.MaxExceeded
 * @phpcs:enable Generic.Metrics.CyclomaticComplexity.MaxExceeded
 */
