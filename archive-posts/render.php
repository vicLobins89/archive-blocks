<?php
/**
 * Block: Clarity Archive Post Items Child Block
 *
 * @example
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#render
 *
 * @package clarity-theme
 */

global $cty;

$block_id    = $attributes['_blockProps']['id'] ?? null;
$columns     = $attributes['columnsCount'] ?? 3;
$block_class = 'cty-archive-posts';
$feed_args   = [
	'template' => [
		'single' => $attributes['templateSingle'] ?? 'partials/card-post-item',
		'none'   => $attributes['templateNone'] ?? 'partials/archive-no-results',
	],
	'columns'  => $columns,
];

?>

<div
	style="--cty-archive-posts-cols: <?php echo esc_attr( $columns ); ?>;"
	class="<?php echo esc_attr( $block_class ); ?>"
	<?php echo $block_id ? ' id="' . esc_attr( $block_id ) . '"' : ''; ?>
>
	<?php
	$cty->archive->display_post_items( $feed_args );
	?>
</div>
