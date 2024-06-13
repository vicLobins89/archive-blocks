<?php
/**
 * Block: Clarity Archive Summary Child Block
 *
 * @example
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#render
 *
 * @package clarity-theme
 */

global $cty;

$block_id     = $attributes['_blockProps']['id'] ?? null;
$block_class  = $attributes['_blockProps']['class'] ?? null;
$block_class .= ' cty-archive-summary';
?>

<div
	class="<?php echo esc_attr( $block_class ); ?>"
	<?php echo $block_id ? ' id="' . esc_attr( $block_id ) . '"' : ''; ?>
>
	<?php
	$cty->archive->display_search_summary();
	?>
</div>
