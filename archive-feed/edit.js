import React from 'react';
import classnames from 'classnames';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import Inspector from './inspector';

/**
 * Edit.js Editor Component.
 * @param { object } props block props
 * @returns { React.ReactElement } edit.js component.
 */
export default props => {
	const { attributes } = props;
	const { postType } = attributes;

	const blockProps = useBlockProps({
		className: classnames('cty-archive', 'wp-clarity-block', 'alignfull', `cty-archive--${postType}`),
	});

	const ALLOWED_BLOCKS = [
		'core/paragraph',
		'core/buttons',
		'core/heading',
		'core/columns',
		'clarity/cta',
		'clarity/icon-links',
		'clarity/archive-posts',
		'clarity/archive-filter',
		'clarity/archive-featured',
		'clarity/archive-pagination',
		'clarity/archive-summary',
	];
	const TEMPLATE = [
		[
			'core/heading',
			{
				placeholder: 'Resources...',
				level: 1,
				textAlign: 'center',
				className: 'has-text-align-center',
			},
		],
		[
			'core/paragraph',
			{
				placeholder:
					'Lorem ipsum dolor sit amet consectetur. Fermentum felis fringilla consectetur eget. Viverra justo volutpat eget arcu vel mi orci eget vitae.',
				align: 'center',
			},
		],
		[
			'clarity/archive-filter',
			{
				taxonomy: 'category',
				inputType: 'button',
				align: 'center',
			},
		],
		['clarity/archive-featured', {}],
		['clarity/archive-posts', {}],
		['clarity/cta', {}],
		['clarity/archive-posts', {}],
		[
			'clarity/archive-pagination',
			{
				align: 'center',
			},
		],
	];

	return (
		<article {...blockProps}>
			<div className="cty-archive__inner">
				<div className="cty-archive__content">
					<InnerBlocks allowedBlocks={ALLOWED_BLOCKS} template={TEMPLATE} />
				</div>
			</div>
			<Inspector {...props} key="inspector" />
		</article>
	);
};
