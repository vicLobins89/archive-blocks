import React from 'react';
import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { Relationship } from '@cty/block-components';
import { withSelect } from '@wordpress/data';

/**
 * Inspector Controls.
 * @param { object } props - block props.
 * @returns { React.ReactElement } Returned inspector controls.
 */
const Inspector = props => {
	const { attributes, setAttributes, postTypes } = props;
	const { postType, featuredPosts, template } = attributes;

	const selectedPostType = postTypes.find(type => type.slug === postType);
	const selectedRestBase = selectedPostType ? selectedPostType.rest_base : 'posts';

	const options = postTypes.map(type => ({
		label: type.labels.singular_name,
		value: type.slug,
	}));

	/**
	 * setRestBase
	 * @param { string } value Selected post type string.
	 */
	const setRestBase = value => {
		const findPostType = postTypes.find(type => type.slug === value);
		const findRestBase = findPostType ? findPostType.rest_base : 'posts';
		setAttributes({ postRest: findRestBase });
	};

	return (
		<InspectorControls key="inspector">
			<PanelBody title={__('Block settings', 'cty')} initialOpen>
				<SelectControl
					label={__('Post Type', 'cty')}
					value={postType}
					options={options}
					onChange={value => {
						setAttributes({ postType: value });
						setRestBase(value);
						setAttributes({ featuredPosts: [] });
					}}
				/>

				<Relationship
					key={postType}
					label={__('Featured Posts', 'cty')}
					posts={featuredPosts}
					postEndpoint={selectedRestBase}
					onPostsUpdate={value => {
						setAttributes({ featuredPosts: value });
					}}
				/>
			</PanelBody>
			<PanelBody title={__('Developer settings', 'cty')} className="advanced-only">
				<TextControl
					label={__('Template', 'cty')}
					value={template}
					onChange={value => setAttributes({ template: value })}
				/>
			</PanelBody>
		</InspectorControls>
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
})(Inspector);
