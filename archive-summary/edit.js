import React from 'react';
import classnames from 'classnames';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Edit.js Editor Component.
 * @returns { React.ReactElement } edit.js component.
 */
export default () => {
	const blockProps = useBlockProps({
		className: classnames('cty-archive-summary'),
	});

	return (
		<div {...blockProps}>
			<div className="cty-archive-summary__inner">Showing 1 - 15 of 30</div>
		</div>
	);
};
