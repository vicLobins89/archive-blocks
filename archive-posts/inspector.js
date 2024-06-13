import { __ } from '@wordpress/i18n';
import { PanelBody, TextControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';

/**
 * Inspector Controls.
 * @param { object } props - block props.
 * @returns { InspectorControls } Returned inspector controls.
 */
export default props => {
	const { attributes, setAttributes } = props;
	const { templateSingle, templateNone, columnsCount } = attributes;

	return (
		<InspectorControls key="inspector">
			<PanelBody title={__('Block settings', 'cty')} initialOpen>
				<TextControl
					type="number"
					label={__('Number of Columns', 'cty')}
					value={columnsCount}
					onChange={value => setAttributes({ columnsCount: parseInt(value, 10) })}
					min={0}
				/>
			</PanelBody>
			<PanelBody title={__('Developer settings', 'cty')} className="advanced-only">
				<TextControl
					label={__('Template Single', 'cty')}
					value={templateSingle}
					onChange={value => setAttributes({ templateSingle: value })}
				/>
				<TextControl
					label={__('Template None', 'cty')}
					value={templateNone}
					onChange={value => setAttributes({ templateNone: value })}
				/>
			</PanelBody>
		</InspectorControls>
	);
};
