import React from 'react';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { PanelBody, TextControl, BaseControl, CheckboxControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { withSelect, dispatch } from '@wordpress/data';

/**
 * Inspector Controls.
 * @param { object } props - block props.
 * @returns { React.ReactElement } Returned inspector controls.
 */
const Inspector = props => {
	const { attributes, setAttributes, postTypes, children } = props;
	const { name, postTypeValues, postsPerPage } = attributes;
	const [selectedPostType, setSeletedPostType] = useState(postTypeValues);

	useEffect(() => {
		setAttributes({ postTypeValues: selectedPostType });

		// Update the child block's attributes
		children.forEach(child => {
			if (child.name === 'clarity/archive-posts') {
				console.log(child);
				dispatch('core/block-editor').updateBlockAttributes(child.clientId, { inheritedCPTs: selectedPostType });
			}
		});
	}, [selectedPostType]);

	/**
	 * Update attrs.
	 * @param {boolean} checked true/false whether the box is checked.
	 * @param {string} cpt the selected post type.
	 */
	const setPostTypes = (checked, cpt) => {
		const newArray = [...selectedPostType];

		if (checked) {
			newArray.push(cpt);
		} else {
			const index = newArray.indexOf(cpt);
			newArray.splice(index, 1);
		}

		setSeletedPostType(newArray);
	};

	return (
		<InspectorControls key="inspector">
			<PanelBody title={__('Block settings', 'cty')} initialOpen>
				<BaseControl label={__('Post Types', 'cty')}>
					{postTypes.map(type => (
						<CheckboxControl
							key={`cpt-${type.slug}`}
							label={type.labels.singular_name}
							checked={selectedPostType.includes(type.slug)}
							onChange={checked => setPostTypes(checked, type.slug)}
						/>
					))}
				</BaseControl>

				<TextControl
					type="number"
					value={postsPerPage}
					label={__('Number of Posts', 'cty')}
					onChange={value => setAttributes({ postsPerPage: parseInt(value, 10) })}
					min={0}
				/>
			</PanelBody>
			<PanelBody title={__('Developer settings', 'cty')} className="advanced-only">
				<TextControl value={name} label={__('Name', 'cty')} onChange={value => setAttributes({ name: value })} />
			</PanelBody>
		</InspectorControls>
	);
};

export default withSelect((select, props) => {
	const children = select('core/block-editor').getBlocksByClientId(props.clientId)[0].innerBlocks;
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
		children,
	};
})(Inspector);
