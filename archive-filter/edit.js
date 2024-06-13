import React from 'react';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useState, useEffect } from '@wordpress/element';
import { withSelect } from '@wordpress/data';
import { useBlockProps } from '@wordpress/block-editor';
import Inspector from './inspector';

/**
 * Edit.js Editor Component.
 * @param { object } props block props
 * @returns { React.ReactElement } edit.js component.
 */
const Edit = props => {
	const { attributes, postTerms } = props;
	const { filterType, taxonomy, inputType, alignment, placeholder, metaKey, metaValues, customName, customValues } =
		attributes;

	const [terms, setTerms] = useState(postTerms);

	const blockProps = useBlockProps({
		className: classnames(`cty-archive-filter`, `cty-archive-filter--${inputType}`, `cty-archive-filter--${alignment}`),
	});

	let inputTypeAttr = 'radio';
	inputTypeAttr = inputType === 'button' || inputType === 'checkbox' ? 'checkbox' : inputTypeAttr;

	const tempTerms = [
		{
			id: 0,
			slug: '',
			name: placeholder || __('Show all', 'cty'),
		},
	];

	useEffect(() => {
		if (postTerms) {
			const fullTerms = [...tempTerms, ...postTerms];
			setTerms(fullTerms);
		}
	}, [postTerms, placeholder]);

	/**
	 * Render function for filter inputs/labels
	 * @param {Array} array filter values to interate through.
	 * @param {string} key filter key.
	 * @param {string} idVar which var to use as array property key for id.
	 * @param {string} valueVar which var to use as array property key for the value.
	 * @param {string} labelVar which var to use as array property key for the label.
	 * @returns {string} HTML.
	 */
	const renderArchiveInputs = (array, key, idVar = 'value', valueVar = 'value', labelVar = 'label') => {
		let values = array;
		if (valueVar === 'value' && labelVar === 'label') {
			const showAll = [
				{
					value: '',
					label: __('Show all', 'cty'),
				},
			];

			values = [...showAll, ...array];
		}
		return values.map(item => (
			<div key={item[idVar]} className={`cty-archive-filter__inner cty-archive-filter__inner--${inputType}`}>
				<input
					className="cty-archive-filter__inner__input"
					type={inputTypeAttr}
					id={`filter-${key}-${item[valueVar]}`}
					name={`filter-${key}}`}
					value={item[valueVar]}
				/>
				<label className="cty-archive-filter__inner__label" htmlFor={`filter-${key}-${item[valueVar]}`}>
					{item[labelVar]}
				</label>
			</div>
		));
	};

	/**
	 * Render function for filter dropdowns
	 * @param {Array} array filter values to interate through.
	 * @param {string} key filter key.
	 * @param {string} idVar which var to use as array property key for id.
	 * @param {string} valueVar which var to use as array property key for the value.
	 * @param {string} labelVar which var to use as array property key for the label.
	 * @returns {string} HTML.
	 */
	const renderArchiveSelect = (array, key, idVar = 'value', valueVar = 'value', labelVar = 'label') => {
		let values = array;
		if (valueVar === 'value' && labelVar === 'label') {
			const showAll = [
				{
					value: '',
					label: __('Show all', 'cty'),
				},
			];

			values = [...showAll, ...array];
		}
		return (
			<select name={`filter-${key}`} className={`cty-archive-filter__inner cty-archive-filter__inner--${inputType}`}>
				{values.map(item => (
					<option key={item[idVar]} value={item[valueVar]}>
						{item[labelVar]}
					</option>
				))}
			</select>
		);
	};

	return (
		<div {...blockProps}>
			{filterType === 'search' && (
				<div className="cty-archive-filter__inner cty-archive-filter__inner--search">
					<input
						type="search"
						className="cty-archive-filter__inner__input"
						name="filter-search"
						value=""
						placeholder={placeholder}
					/>
				</div>
			)}
			{filterType === 'sort' && (
				<select
					name="filter-sort"
					className="cty-archive-filter__inner cty-archive-filter__inner--select cty-archive-filter__inner--sort"
				>
					<option value="date-DESC">{__('Newest first', 'cty')}</option>
					<option value="date-ASC">{__('Oldest first', 'cty')}</option>
					<option value="title-ASC">{__('A - Z', 'cty')}</option>
					<option value="title-DESC">{__('Z - A', 'cty')}</option>
				</select>
			)}
			{filterType === 'taxonomy' &&
				taxonomy &&
				terms &&
				(inputType === 'button' || inputType === 'buttons' || inputType === 'radio' || inputType === 'checkbox') &&
				renderArchiveInputs(terms, taxonomy, 'id', 'slug', 'name')}
			{filterType === 'taxonomy' &&
				taxonomy &&
				terms &&
				inputType === 'select' &&
				renderArchiveSelect(terms, taxonomy, 'id', 'slug', 'name')}
			{filterType === 'meta' &&
				metaKey &&
				metaValues &&
				(inputType === 'button' || inputType === 'buttons' || inputType === 'radio' || inputType === 'checkbox') &&
				renderArchiveInputs(metaValues, metaKey)}
			{filterType === 'meta' &&
				metaKey &&
				metaValues &&
				inputType === 'select' &&
				renderArchiveSelect(metaValues, metaKey)}
			{filterType === 'custom' &&
				customName &&
				customValues &&
				(inputType === 'button' || inputType === 'buttons' || inputType === 'radio' || inputType === 'checkbox') &&
				renderArchiveInputs(customValues, customName)}
			{filterType === 'custom' &&
				customName &&
				customValues &&
				inputType === 'select' &&
				renderArchiveSelect(customValues, customName)}
			<Inspector {...props} key="inspector" />
		</div>
	);
};

export default withSelect((select, props) => ({
	postTerms: select('core').getEntityRecords('taxonomy', props.attributes.taxonomy),
}))(Edit);
