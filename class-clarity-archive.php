<?php
/**
 * Clarity Feed class.
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
final class Clarity_Archive extends Clarity_Archive_Base {
	/**
	 * Start the feed markup.
	 *
	 * @param array $query_args WP_Query arguments.
	 */
	public function start_feed( $query_args = [] ) {
		// Enqueue script.
		wp_enqueue_script( 'clarity-archive' );

		// Set up default wp_query if no args given.
		if ( ! $query_args ) {
			global $wp_query;
			$query_args = $wp_query ? $wp_query->query_vars : [];
		}

		// Ensure 'feed' arg is added to query.
		if ( ! isset( $query_args['cty_feed'] ) ) {
			$query_args['cty_feed'] = 1;
		}

		// Set up object params.
		$this->query_args    = $query_args;
		$this->current_query = new \WP_Query( $this->query_args );
		$this->unique_id     = spl_object_hash( $this->current_query );
		$this->current_name  = 'cty-feed';

		// Set name.
		if ( isset( $query_args['cty_feed'] ) ) {
			$this->current_name = $query_args['cty_feed'];
		} elseif ( isset( $query_args['post_type'] ) ) {
			$post_type          = $query_args['post_type'];
			$post_type          = is_array( $post_type ) ? $post_type[0] : $post_type;
			$this->current_name = 'cty-feed-' . $post_type;
		} else {
			$post_type          = $this->current_query->get( 'post_type' );
			$post_type          = is_array( $post_type ) ? $post_type[0] : $post_type;
			$this->current_name = 'cty-feed-' . $post_type;
		}

		// Are there more items to be loaded?
		$count       = isset( $this->current_query->posts ) ?
			count( $this->current_query->posts ) : 0;
		$found_posts = $this->current_query->found_posts ?? 0;

		// The form tag classes.
		$classes = [ 'cty-archive__form' ];

		// Add class if there are no more posts to be loaded.
		if ( $count >= $found_posts ) {
			$classes[] = 'cty-archive__form--none';
		}

		$class = trim( join( ' ', $classes ) );

		// Build form element.
		printf(
			'<form name="%s" method="get" class="%s" data-unique_id="%s">',
			esc_attr( $this->current_name ),
			esc_attr( $class ),
			esc_attr( $this->unique_id )
		);

		// Add loader.
		?>
		<div class="cty-archive__loader"><?php esc_html_e( 'Loading', 'cty' ); ?></div>
		<?php
	}

	/**
	 * End the feed markup.
	 */
	public function end_feed() {
		// Write vars to temp file.
		$saved = $this->write_to_file();
		if ( ! $saved ) {
			// Handle save error.
			\cty\dump( 'ERROR!' );
		}

		// End form element.
		echo '</form>';
	}

	/**
	 * Display taxonomy filters
	 *
	 * @param string $filter_tax the taxonomy to filter.
	 * @param array  $filter_args custom filter options.
	 * @param string $filter_style the style of filter.
	 */
	public function add_taxonomy_filter( $filter_tax, $filter_args = [], $filter_style = 'select' ) {
		// Get terms.
		$terms = get_terms(
			[
				'taxonomy'   => $filter_tax,
				'hide_empty' => $filter_tax['hide_empty'] ?? true,
			]
		);

		// Set up values array.
		$values = [];
		foreach ( $terms as $term ) {
			$values[ $term->slug ] = $term->name;
		}

		// Set up extra filter args.
		$filter_args['name']   = $filter_tax;
		$filter_args['values'] = $values;

		if ( $filter_style === 'select' ) {
			$this->add_dropdown_filter( $filter_args );
		} else {
			$this->add_radio_or_checkbox_filter( $filter_args, $filter_style );
		}
	}

	/**
	 * Displays a select HTML dropdown
	 *
	 * @param array $filter_args dropdown options.
	 */
	public function add_dropdown_filter( $filter_args ) {
		// Defaults.
		$defaults    = [
			'name'     => '',
			'values'   => [],
			'class'    => '',
			'is-meta'  => false,
			'selected' => '',
		];
		$filter_args = wp_parse_args( $filter_args, $defaults );

		// Setup filter name.
		$class       = trim( 'cty-archive-filter__inner cty-archive-filter__inner--select ' . $filter_args['class'] );
		$name        = $filter_args['name'];
		$filter      = $filter_args['is-meta'] ? 'meta' : 'filter';
		$filter_name = sprintf( '%s-%s', $filter, $name );

		// Sets the selected term on page load.
		$selected = '';
		if ( ! empty( $filter_args['selected'] ) ) {
			$selected = $filter_args['selected'];
		} elseif ( isset( $_GET[ $filter_name ] ) ) {
			$selected = sanitize_text_field( wp_unslash( $_GET[ $filter_name ] ) );
		}

		// Render select element.
		printf(
			'<select name="%s" data-filter="%s" class="%s">',
			esc_attr( $filter_name ),
			esc_attr( $filter_name ),
			esc_attr( $class )
		);

		// Add 'Show All' option.
		if ( $name !== 'sort' ) {
			$filter_args['values'] = [ '' => __( 'Show All', 'cty' ) ] + $filter_args['values'];
		}

		// The placeholder, if set, is added as disabled option.
		if ( $filter_args['placeholder'] ) {
			printf(
				'<option %s disabled="disabled" style="display: none;">%s</option>',
				selected( null, $selected, false ),
				esc_html( $filter_args['placeholder'] )
			);
		}

		// Print values.
		foreach ( $filter_args['values'] as $value => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				$value !== '' ? selected( $value, $selected, false ) : '',
				esc_html( $label )
			);
		}

		// End select.
		echo '</select>';
	}

	/**
	 * Displays radio/checkbox inputs
	 *
	 * @param array  $filter_args dropdown options.
	 * @param string $filter_style checkbox, radio or button.
	 */
	public function add_radio_or_checkbox_filter( $filter_args, $filter_style = 'checkbox' ) {
		// Defaults.
		$defaults    = [
			'name'     => '',
			'values'   => [],
			'class'    => '',
			'is-meta'  => false,
			'selected' => '',
		];
		$filter_args = wp_parse_args( $filter_args, $defaults );

		// Setup filter name.
		$classes     = [
			'cty-archive-filter__inner',
			'cty-archive-filter__inner--' . $filter_style,
			$filter_args['class'],
		];
		$class       = trim( implode( ' ', $classes ) );
		$name        = $filter_args['name'];
		$filter      = $filter_args['is-meta'] ? 'meta' : 'filter';
		$filter_name = sprintf( '%s-%s', $filter, $name );
		$max         = 10;

		// Reset 'button' type to radio.
		$filter_style = $filter_style === 'button' ? 'radio' : $filter_style;
		$filter_style = $filter_style === 'buttons' ? 'checkbox' : $filter_style;

		// Sets the selected term on page load.
		$selected = '';
		if ( ! empty( $filter_args['selected'] ) ) {
			$selected = $filter_args['selected'];
		} elseif ( isset( $_GET[ $filter_name ] ) ) {
			if ( is_array( $_GET[ $filter_name ] ) ) {
				$selected = array_map(
					function( $val ) {
						return sanitize_text_field( wp_unslash( $val ) );
					},
					$_GET[ $filter_name ] //phpcs:ignore
				);
			} else {
				$selected = sanitize_text_field( wp_unslash( $_GET[ $filter_name ] ) );
			}
		}

		// Checkbox value is an array.
		if ( $filter_style === 'checkbox' ) {
			$filter_name = $filter_name . '[]';
		}

		// Add a default option for radio buttons.
		$values = $filter_args['values'];
		if ( $filter_style === 'radio' ) {
			$values = [ '' => __( 'Show All', 'cty' ) ] + $values;
		}

		// Add inputs.
		$row = 0;
		foreach ( $values as $key => $value ) {
			$checked = '';
			if (
				( is_array( $selected ) && in_array( $key, $selected ) ) ||
				( is_string( $selected ) && $selected === $key )
			) {
				$checked = ' checked';
			}

			printf(
				'<div class="%s%s">',
				esc_attr( $class ),
				$row >= $max ? ' is-hidden' : ''
			);

			printf(
				'<input
					class="%1$s__input"
					type="%2$s"
					id="filter-%3$s-%4$s"
					name="%5$s"
					value="%4$s"
					data-filter="filter-%3$s"
					%6$s
				/>
				<label class="%1$s__label" for="filter-%3$s-%4$s">%7$s</label>',
				'cty-archive-filter__inner',
				esc_attr( $filter_style ),
				esc_attr( $name ),
				esc_attr( $key ),
				esc_attr( $filter_name ),
				esc_attr( $checked ),
				esc_html( $value )
			);

			echo '</div>';
			$row++;
		}

		// Add See More button.
		if ( count( $values ) >= $max ) {
			printf(
				'<span class="cty-archive-filter__see-more">%s</span>',
				esc_html__( 'See More', 'cty' )
			);
		}
	}

	/**
	 * Add a search input field
	 *
	 * @param array $filter_args arguments to customise the search field.
	 */
	public function add_search_filter( $filter_args = [] ) {
		$defaults    = [
			'class'       => '',
			'placeholder' => __( 'Search', 'luna' ),
			'name'        => 'filter-search',
		];
		$filter_args = wp_parse_args( $filter_args, $defaults );

		// Setup filter name.
		$class       = trim( 'cty-archive-filter__inner cty-archive-filter__inner--search ' . $filter_args['class'] );
		$name        = $filter_args['name'];
		$filter_name = sprintf( '%s', $name );

		// Setup search param.
		$search = '';
		if ( ! empty( $_GET['s'] ) ) {
			$search = sanitize_title( wp_unslash( $_GET['s'] ) );
		} elseif ( ! empty( $_GET['filter-search'] ) ) {
			$search = sanitize_title( wp_unslash( $_GET['filter-search'] ) );
		}

		printf(
			'<div class="%s">',
			esc_attr( $class )
		);

		printf(
			'<input
				type="search"
				class="%1$s__input"
				name="%2$s"
				value="%3$s"
				placeholder="%4$s"
				data-debounce="%5$d"
			/>',
			'cty-archive-filter__inner',
			esc_attr( $name ),
			esc_attr( $search ),
			esc_attr( $filter_args['placeholder'] ),
			200
		);

		echo '</div>';
	}

	/**
	 * Displays featured items
	 *
	 * @param array  $featured_items the array of featured posts (IDs).
	 * @param string $template string for template part to render.
	 * @param bool   $echo if true render.
	 */
	public function display_featured( $featured_items, $template, $echo = true ) {
		if ( ! $featured_items ) {
			return;
		}

		// Save to temp feed args.
		$this->feed_args['featured'] = $featured_items;

		// Do not display is paged or filtered.
		if ( $this->is_filtered() || $this->is_paged() ) {
			return;
		}

		// Render.
		ob_start();
		foreach ( $featured_items as $post_id ) {
			get_template_part(
				$template,
				'',
				[
					'id'       => $post_id,
					'featured' => true,
				]
			);
		}
		$rendered_featured_items = ob_get_clean();

		if ( $echo ) {
			echo wp_kses_post( $rendered_featured_items );
		}
		return $rendered_featured_items;
	}

	/**
	 * Displays load more button
	 *
	 * @param string $label the button label.
	 */
	public function display_load_more( $label = '' ) {
		$count                    = isset( $this->current_query->chunk_total ) ?
			$this->current_query->chunk_total : 0;
		$this->feed_args['count'] = $count;
		?>
		<div class="wp-block-button cty-archive-pagination__button">
			<button type="submit" class="wp-block-button__link cty-archive-pagination__submit">
				<span aria-hidden="true"><?php echo esc_html( $label ); ?></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Click to load more posts', 'cty' ); ?></span>
			</button>
		</div>
		<?php
	}
}

/**
 * Re-enable phpcs rule
 * @phpcs:enable Generic.Metrics.NestingLevel.MaxExceeded
 * @phpcs:enable Generic.Metrics.CyclomaticComplexity.MaxExceeded
 */
