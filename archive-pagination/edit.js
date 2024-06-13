import React from 'react';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { SvgIcon } from '@cty/block-components';
import Inspector from './inspector';

/**
 * Edit.js Editor Component.
 * @param { object } props block props
 * @returns { React.ReactElement } edit.js component.
 */
export default props => {
	const { attributes } = props;
	const { paginationType, buttonText } = attributes;

	const blockProps = useBlockProps({
		className: classnames('cty-archive-pagination'),
	});

	return (
		<div {...blockProps}>
			{paginationType === 'pagination' && (
				<nav className="cty-archive-pagination__pagination pagination">
					<span className="prev page-numbers">
						<SvgIcon name="icon-arrow" />
						{__('Back', 'cty')}
					</span>
					<span aria-current="page" className="page-numbers current">
						1
					</span>
					<span className="page-numbers">2</span>
					<span className="page-numbers">3</span>
					<span className="next page-numbers">
						{__('Next', 'cty')}
						<SvgIcon name="icon-arrow" />
					</span>
				</nav>
			)}
			{paginationType === 'loadMore' && (
				<div className="wp-block-button cty-archive-pagination__button">
					<button type="submit" className="wp-block-button__link cty-archive-pagination__submit">
						<span>{buttonText}</span>
					</button>
				</div>
			)}
			<Inspector {...props} key="inspector" />
		</div>
	);
};
