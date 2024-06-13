<?php
/**
 * Block: Clarity Archive Filter Child Block
 *
 * @example
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#render
 *
 * @package clarity-theme
 */

global $cty;

$block_id      = $attributes['_blockProps']['id'] ?? null;
$filter_type   = $attributes['filterType'] ?? 'taxonomy';
$meta_key      = $attributes['metaKey'] ?? '';
$meta_values   = $attributes['metaValues'] ?? [];
$custom_name   = $attributes['customName'] ?? '';
$custom_values = $attributes['customValues'] ?? [];
$filter_tax    = $attributes['taxonomy'] ?? '';
$filter_style  = $attributes['inputType'] ?? 'select';
$alignment     = $attributes['alignment'] ?? 'horizontal';
$placeholder   = $attributes['placeholder'] ?? '';
$block_class   = $attributes['_blockProps']['class'] ?? null;
$block_class  .= ' cty-archive-filter cty-archive-filter--' . $alignment;
$block_class  .= ' cty-archive-filter--' . $filter_style;

switch ( $filter_type ) {
	case 'meta':
		$filter_args = [
			'name'    => $meta_key,
			'values'  => $meta_values ? wp_list_pluck( $meta_values, 'label', 'value' ) : [],
			'is-meta' => true,
		];
		break;
	case 'custom':
		$filter_args = [
			'name'   => $custom_name,
			'values' => $custom_values ? wp_list_pluck( $custom_values, 'label', 'value' ) : [],
		];
		break;
	case 'sort':
		$filter_args  = [
			'name'   => 'sort',
			'values' => [
				'date-DESC'  => __( 'Newest first', 'cty' ),
				'date-ASC'   => __( 'Oldest first', 'cty' ),
				'title-ASC'  => __( 'A - Z', 'cty' ),
				'title-DESC' => __( 'Z - A', 'cty' ),
			],
			'class'  => 'cty-archive-filter__inner--sort',
		];
		$filter_style = 'select';
		break;
	default:
		$filter_args = [];
}

$defaults    = [
	'placeholder' => $placeholder,
];
$filter_args = array_merge( $defaults, $filter_args );

?>

<div
	class="<?php echo esc_attr( $block_class ); ?>"
	<?php echo $block_id ? ' id="' . esc_attr( $block_id ) . '"' : ''; ?>
>
	<?php
	if ( $filter_type === 'taxonomy' && $filter_tax ) {
		$cty->archive->add_taxonomy_filter( $filter_tax, $filter_args, $filter_style );
	} elseif ( $filter_type === 'search' ) {
		$cty->archive->add_search_filter( $filter_args );
	} else {
		if ( $filter_style === 'select' ) {
			$cty->archive->add_dropdown_filter( $filter_args );
		} else {
			$cty->archive->add_radio_or_checkbox_filter( $filter_args, $filter_style );
		}
	}
	?>
</div>
