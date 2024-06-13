<?php
/**
 * Block: Clarity Archive Feed Block
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#render
 *
 * @package clarity-theme
 */

global $cty;

$block_id           = $attributes['_blockProps']['id'] ?? null;
$selected_post_type = $attributes['postTypeValues'] ?? [ 'post' ];
$numberposts        = $attributes['postsPerPage'] ?? 10;
$feed_name          = $attributes['name'] ?? 'cty-feed-resources';
$block_class        = 'cty-archive wp-clarity-block alignfull cty-archive--' . $feed_name;
$inner_block_names  = $cty->fn->get_inner_block_names( $block->parsed_block['innerBlocks'] );
$feed_count         = count( array_keys( $inner_block_names, 'clarity/archive-posts' ) );

$query_args = [
	'post_type'      => $selected_post_type,
	'posts_per_page' => $numberposts,
	'paged'          => max( 1, get_query_var( 'paged' ) ),
	'cty_feed'       => $feed_name,
	'cty_feed_count' => $feed_count,
];

?>

<article
	class="<?php echo esc_attr( $block_class ); ?>"
	<?php echo $block_id ? ' id="' . esc_attr( $block_id ) . '"' : ''; ?>
>
	<div class="cty-archive__inner">
		<div class="cty-archive__content">
			<?php $cty->archive->start_feed( $query_args ); ?>
			<?php $cty->fn->render_inner_blocks( $block ); ?>
			<?php $cty->archive->end_feed(); ?>
		</div>
	</div>
</article>
