import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
    useBlockProps,
    InspectorControls
} from '@wordpress/block-editor';
import {
    PanelBody,
    SelectControl
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import React from 'react';

registerBlockType('we-custom-fields-block/custom-field', {
    edit: function Edit({ attributes, setAttributes, clientId }) {
        const {
            fieldKey,
            displayType,
            headingLevel
        } = attributes;

        const [customFields, setCustomFields] = useState([]);
        const [fieldValue, setFieldValue] = useState('');

        // Fetch custom fields on component mount
        useEffect(() => {
            if (window.cfbData && window.cfbData.customFields) {
                setCustomFields(window.cfbData.customFields);

                // Set field value if fieldKey is selected
                if (fieldKey) {
                    const selectedField = window.cfbData.customFields.find(field => field.key === fieldKey);
                    if (selectedField) {
                        setFieldValue(selectedField.value);
                    }
                }
            }
        }, [fieldKey]);

        const blockProps = useBlockProps({
            className: 'cfb-block'
        });

        const updateFieldValue = (newFieldKey) => {
            setAttributes({ fieldKey: newFieldKey });
            if (newFieldKey) {
                const selectedField = customFields.find(field => field.key === newFieldKey);
                if (selectedField) {
                    setFieldValue(selectedField.value);
                }
            } else {
                setFieldValue('');
            }
        };


        const renderPreview = () => {
            if (!fieldValue) {
                return (
                    <div style={{
                        padding: '20px',
                        border: '2px dashed #ccc',
                        textAlign: 'center',
                        color: '#666',
                        backgroundColor: '#f9f9f9'
                    }}>
                        <div style={{ marginBottom: '15px' }}>
                            <strong>{__('Custom Field Block', 'we-custom-fields-block')}</strong>
                        </div>
                        <SelectControl
                            label={__('Select Custom Field:', 'we-custom-fields-block')}
                            value={fieldKey}
                            options={[
                                { label: __('-- Select Field --', 'we-custom-fields-block'), value: '' },
                                ...customFields.map(field => ({
                                    label: field.label,
                                    value: field.key
                                }))
                            ]}
                            onChange={updateFieldValue}
                        />
                        <div style={{
                            marginTop: '10px',
                            fontSize: '12px',
                            color: '#888',
                            fontStyle: 'italic'
                        }}>
                            {__('Please select a custom field from the list above', 'we-custom-fields-block')}
                        </div>
                    </div>
                );
            }

            let content;
            if (displayType === 'heading') {
                content = React.createElement(`h${headingLevel || 2}`, {}, fieldValue);
            } else if (displayType === 'div') {
                content = <div>{fieldValue}</div>;
            } else {
                content = <p>{fieldValue}</p>;
            }

            return (
                <div style={{ position: 'relative' }}>
                    {content}
                    {/* Quick field selector overlay */}
                    <div style={{
                        position: 'absolute',
                        top: '-10px',
                        right: '-10px',
                        background: '#fff',
                        border: '1px solid #ddd',
                        borderRadius: '4px',
                        padding: '5px',
                        boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
                        zIndex: 10,
                        minWidth: '200px'
                    }}>
                        <SelectControl
                            label={__('Change Field:', 'we-custom-fields-block')}
                            value={fieldKey}
                            options={[
                                { label: __('-- Select Field --', 'we-custom-fields-block'), value: '' },
                                ...customFields.map(field => ({
                                    label: field.label,
                                    value: field.key
                                }))
                            ]}
                            onChange={updateFieldValue}
                        />
                    </div>
                </div>
            );
        };

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Custom Field Settings', 'we-custom-fields-block')} initialOpen={true}>
                        <SelectControl
                            label={__('Select Custom Field', 'we-custom-fields-block')}
                            value={fieldKey}
                            options={[
                                { label: __('-- Select Field --', 'we-custom-fields-block'), value: '' },
                                ...customFields.map(field => ({
                                    label: field.label,
                                    value: field.key
                                }))
                            ]}
                            onChange={updateFieldValue}
                        />

                        <SelectControl
                            label={__('Display Type', 'we-custom-fields-block')}
                            value={displayType}
                            options={[
                                { label: __('Paragraph (p)', 'we-custom-fields-block'), value: 'paragraph' },
                                { label: __('Heading (h1-h6)', 'we-custom-fields-block'), value: 'heading' },
                                { label: __('Container (div)', 'we-custom-fields-block'), value: 'div' }
                            ]}
                            onChange={(value) => setAttributes({ displayType: value })}
                        />

                        {displayType === 'heading' && (
                            <SelectControl
                                label={__('Heading Level', 'we-custom-fields-block')}
                                value={headingLevel || 2}
                                options={[
                                    { label: __('H1 - Main Heading', 'we-custom-fields-block'), value: 1 },
                                    { label: __('H2 - Subheading', 'we-custom-fields-block'), value: 2 },
                                    { label: __('H3 - Sub-subheading', 'we-custom-fields-block'), value: 3 },
                                    { label: __('H4', 'we-custom-fields-block'), value: 4 },
                                    { label: __('H5', 'we-custom-fields-block'), value: 5 },
                                    { label: __('H6', 'we-custom-fields-block'), value: 6 }
                                ]}
                                onChange={(value) => setAttributes({ headingLevel: parseInt(value) })}
                            />
                        )}
                    </PanelBody>

                </InspectorControls>

                <div {...blockProps}>
                    {renderPreview()}
                </div>
            </>
        );
    },

    save: function Save() {
        // This block uses a PHP render callback, so we don't need to save anything
        return null;
    }
}); 