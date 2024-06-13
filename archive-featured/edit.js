import React from 'react';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useState, useEffect } from '@wordpress/element';
import { getPosts } from '@cty/block-helpers';
import { CardPostItem } from '@cty/block-partials';
import Inspector from './inspector';

/**
 * Edit.js Editor Component.
 * @param { object } props block props
 * @returns { React.ReactElement } edit.js component.
 */
export default props => {
	const { attributes } = props;
	const { postType, featuredPosts, postRest } = attributes;

	const [manualPosts, setManualPosts] = useState([]);

	const blockProps = useBlockProps({
		className: classnames('cty-archive-featured'),
	});

	useEffect(() => {
		getPosts(featuredPosts, postRest, setManualPosts);
	}, [featuredPosts, postType]);

	return (
		<div {...blockProps}>
			{manualPosts &&
				manualPosts.length > 0 &&
				manualPosts.map(post => <CardPostItem post={post} isFeatured key={post.id} />)}
			{manualPosts && manualPosts.length === 0 && <span>{__('Please add Featured Post', 'cty')}</span>}
			<Inspector {...props} key="inspector" />
		</div>
	);
};
