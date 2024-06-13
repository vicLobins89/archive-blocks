<?php
/**
 * Block: cty-archive-pagination - Feed Pagination Child Block
 *
 * @example
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#render
 *
 * @package clarity-theme
 */

global $cty;

$block_id        = $attributes['_blockProps']['id'] ?? null;
$pagination_type = $attributes['paginationType'] ?? 'pagination';
$button_text     = $attributes['buttonText'] ?? __( 'Load More', 'cty' );
$template        = $attributes['template'] ?? 'partials/archive-pagination';
$block_class     = $attributes['_blockProps']['class'] ?? null;
$block_class    .= ' cty-archive-pagination';
?>

<div
	class="<?php echo esc_attr( $block_class ); ?>"
	<?php echo $block_id ? ' id="' . esc_attr( $block_id ) . '"' : ''; ?>
>
	<?php
	if ( $pagination_type === 'pagination' ) :
		$cty->archive->display_pagination( $template );
	else :
		$cty->archive->display_load_more( $button_text );
	endif;
	?>
</div>
