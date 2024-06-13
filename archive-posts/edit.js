import React from 'react';
import classnames from 'classnames';
import apiFetch from '@wordpress/api-fetch';
import { withSelect } from '@wordpress/data';
import { useBlockProps } from '@wordpress/block-editor';
import { useState, useEffect } from '@wordpress/element';
import { CardPostItem } from '@cty/block-partials';
import Inspector from './inspector';

/**
 * Edit.js Editor Component.
 * @param { object } props block props
 * @returns { React.ReactElement } edit.js component.
 */
const Edit = props => {
	const { attributes, postTypes } = props;
	const { inheritedCPTs, columnsCount } = attributes;
	const [selectedPosts, setSelectedPosts] = useState([]);

	const blockProps = useBlockProps({
		className: classnames('cty-archive-posts'),
	});

	// Fetch automatic posts.
	useEffect(() => {
		const params = `per_page=${columnsCount}&order=desc&orderby=date`;
		const selectedCPT = inheritedCPTs ? inheritedCPTs[0] : 'post';
		const selectedCPTObj = postTypes && postTypes.length > 0 ? postTypes.filter(type => type.slug === selectedCPT) : [];
		const postRest = selectedCPTObj && selectedCPTObj.length > 0 ? selectedCPTObj[0].rest_base : 'posts';

		apiFetch({ path: `/wp/v2/${postRest}?${params}` })
			.then(results => {
				setSelectedPosts(results);
			})
			.catch(error => {
				console.error(error);
				setSelectedPosts(null);
			});
	}, [inheritedCPTs, columnsCount]);

	return (
		<div {...blockProps} style={{ '--cty-archive-posts-cols': columnsCount }}>
			{selectedPosts.map(post => (
				<CardPostItem post={post} key={post.id} />
			))}
			<Inspector {...props} key="inspector" />
		</div>
	);
};

export default withSelect(select => {
	const allPostTypes = select('core').getPostTypes({ per_page: -1 });
	const unwantedPostTypes = [
		'wp_block',
		'wp_template',
		'wp_template_part',
		'wp_navigation',
		'wp_font_family',
		'wp_font_face',
		'nav_menu_item',
		'attachment',
		'author',
		'page',
		'nine3_editor',
	];

	// Filter out specific post types
	const filteredPostTypes = allPostTypes ? allPostTypes.filter(type => !unwantedPostTypes.includes(type.slug)) : [];

	return {
		postTypes: filteredPostTypes,
	};
})(Edit);
