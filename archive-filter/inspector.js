import React from 'react';
import { __ } from '@wordpress/i18n';
import { PanelBody, TextControl, SelectControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { Repeater } from '@cty/block-components';
import { withSelect } from '@wordpress/data';
import { updateField } from '@cty/block-helpers';

/**
 * Inspector Controls.
 * @param { object } props - block props.
 * @returns { React.ReactElement } Returned inspector controls.
 */
const Inspector = props => {
	const { attributes, setAttributes, taxonomies } = props;
	const { filterType, metaKey, metaValues, customName, customValues, taxonomy, inputType, alignment, placeholder } =
		attributes;

	const taxOptions = taxonomies.map(type => ({
		label: type.labels.singular_name,
		value: type.slug,
	}));
	taxOptions.unshift({
		label: __('Please Select', 'cty'),
		value: '',
	});
	const fieldDefaults = { value: '', label: '' };

	return (
		<InspectorControls key="inspector">
			<PanelBody title={__('Block settings', 'cty')} initialOpen>
				<SelectControl
					label={__('Filter Type', 'cty')}
					value={filterType}
					options={[
						{ label: __('Search', 'cty'), value: 'search' },
						{ label: __('Sort', 'cty'), value: 'sort' },
						{ label: __('Taxonomy', 'cty'), value: 'taxonomy' },
						{ label: __('Meta', 'cty'), value: 'meta' },
						{ label: __('Custom', 'cty'), value: 'custom' },
					]}
					onChange={value => setAttributes({ filterType: value })}
				/>
				{filterType === 'taxonomy' && (
					<SelectControl
						label={__('Taxonomy', 'cty')}
						value={taxonomy}
						options={taxOptions}
						onChange={value => setAttributes({ taxonomy: value })}
					/>
				)}
				{filterType === 'meta' && (
					<>
						<TextControl
							label={__('Meta Key', 'cty')}
							value={metaKey}
							onChange={value => setAttributes({ metaKey: value })}
						/>

						<Repeater
							items={metaValues}
							fieldDefaults={fieldDefaults}
							onUpdate={value => setAttributes({ metaValues: value })}
							renderFields={(item, index) => (
								<>
									<TextControl
										value={item.value}
										label={__('Value', 'cty')}
										onChange={value =>
											setAttributes({
												metaValues: updateField(metaValues, index, 'value', value),
											})
										}
									/>
									<TextControl
										value={item.label}
										label={__('Label', 'cty')}
										onChange={value =>
											setAttributes({
												metaValues: updateField(metaValues, index, 'label', value),
											})
										}
									/>
								</>
							)}
						/>
					</>
				)}
				{filterType === 'custom' && (
					<>
						<TextControl
							label={__('Filter Name', 'cty')}
							value={customName}
							onChange={value => setAttributes({ customName: value })}
						/>

						<Repeater
							items={customValues}
							fieldDefaults={fieldDefaults}
							onUpdate={value => setAttributes({ customValues: value })}
							renderFields={(item, index) => (
								<>
									<TextControl
										value={item.value}
										label={__('Value', 'cty')}
										onChange={value =>
											setAttributes({
												customValues: updateField(customValues, index, 'value', value),
											})
										}
									/>
									<TextControl
										value={item.label}
										label={__('Label', 'cty')}
										onChange={value =>
											setAttributes({
												customValues: updateField(customValues, index, 'label', value),
											})
										}
									/>
								</>
							)}
						/>
					</>
				)}
				{(filterType === 'taxonomy' || filterType === 'meta' || filterType === 'custom') && (
					<SelectControl
						label={__('Filter Style', 'cty')}
						value={inputType}
						options={[
							{ label: __('Please Select', 'cty'), value: '' },
							{ label: __('Dropdown', 'cty'), value: 'select' },
							{ label: __('Buttons', 'cty'), value: 'button' },
							{ label: __('Buttons (multiselect)', 'cty'), value: 'buttons' },
							{ label: __('Checkbox', 'cty'), value: 'checkbox' },
							{ label: __('Radio', 'cty'), value: 'radio' },
						]}
						onChange={value => setAttributes({ inputType: value })}
					/>
				)}
				{(inputType === 'radio' || inputType === 'checkbox' || inputType === 'button' || inputType === 'buttons') && (
					<SelectControl
						label={__('Alignment', 'cty')}
						value={alignment}
						options={[
							{ label: __('Horizontal', 'cty'), value: 'horizontal' },
							{ label: __('Vertical', 'cty'), value: 'vertical' },
						]}
						onChange={value => setAttributes({ alignment: value })}
					/>
				)}
				<TextControl
					label={__('Placeholder', 'cty')}
					value={placeholder}
					onChange={value => setAttributes({ placeholder: value })}
				/>
			</PanelBody>
		</InspectorControls>
	);
};

export default withSelect(select => {
	const allTax = select('core').getTaxonomies({ per_page: -1 });
	const unwantedTax = ['nav_menu', 'wp_pattern_category'];

	// Filter out specific taxonomies
	const filteredTax = allTax ? allTax.filter(type => !unwantedTax.includes(type.slug)) : [];

	return {
		taxonomies: filteredTax,
	};
})(Inspector);
