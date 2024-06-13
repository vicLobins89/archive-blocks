import { registerBlockType } from '@wordpress/blocks';
import { Save } from '@cty/block-helpers';
import blockAttrs from './block.json';
import Edit from './edit';

registerBlockType(blockAttrs, {
	edit: Edit,
	save: Save,
});
