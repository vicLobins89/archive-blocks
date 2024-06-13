import React from 'react';
import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';

/**
 * Inspector Controls.
 * @param { object } props - block props.
 * @returns { React.ReactElement } Returned inspector controls.
 */
export default props => {
	const { attributes, setAttributes } = props;
	const { paginationType, template, buttonText } = attributes;

	return (
		<InspectorControls key="inspector">
			<PanelBody title={__('Block settings', 'cty')} initialOpen>
				<SelectControl
					label={__('Pagination Type', 'cty')}
					value={paginationType}
					options={[
						{ label: __('Pagination', 'cty'), value: 'pagination' },
						{ label: __('Load More Button', 'cty'), value: 'loadMore' },
					]}
					onChange={value => setAttributes({ paginationType: value })}
				/>
				{paginationType === 'loadMore' && (
					<TextControl
						label={__('Button Text', 'cty')}
						value={buttonText}
						onChange={value => setAttributes({ buttonText: value })}
					/>
				)}
			</PanelBody>
			{paginationType === 'pagination' && (
				<PanelBody title={__('Developer settings', 'cty')} className="advanced-only">
					<TextControl
						label={__('Template', 'cty')}
						value={template}
						onChange={value => setAttributes({ template: value })}
					/>
				</PanelBody>
			)}
		</InspectorControls>
	);
};
