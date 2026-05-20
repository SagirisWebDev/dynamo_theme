(function () {
    'use strict';

    var el             = wp.element.createElement;
    var __             = wp.i18n.__;
    var useState       = wp.element.useState;
    var useEffect      = wp.element.useEffect;
    var registerBlockType = wp.blocks.registerBlockType;
    var InnerBlocks    = wp.blockEditor.InnerBlocks;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var SelectControl  = wp.components.SelectControl;
    var PanelBody      = wp.components.PanelBody;
    var Notice         = wp.components.Notice;

    registerBlockType('dynamo/consent-gate', {
        apiVersion: 3,
        title: __('Consent Gate', 'dynamo'),
        category: 'embed',
        description: __('Hides inner content until the visitor grants the required consent category.', 'dynamo'),
        attributes: {
            consentCategory: { type: 'string', default: '' },
        },
        supports: { html: false },
        edit: function (props) {
            var attributes      = props.attributes;
            var setAttributes   = props.setAttributes;
            var consentCategory = attributes.consentCategory || '';

            var categoriesState = useState([]);
            var categories      = categoriesState[0];
            var setCategories   = categoriesState[1];

            useEffect(function () {
                wp.apiFetch({ path: '/dynamo/v1/cookie-categories' })
                    .then(function (data) { setCategories(data); })
                    .catch(function ()    { setCategories([]); });
            }, []);

            var options = [{ label: __('— Select a category —', 'dynamo'), value: '' }].concat(
                categories.map(function (cat) {
                    return { label: cat.label, value: cat.slug };
                })
            );

            return el(
                'div',
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __('Consent Settings', 'dynamo') },
                        el(Notice, {
                            status: 'warning',
                            isDismissible: false,
                        }, __('This block hides content visually. Do not use it to protect sensitive data — hidden content is still present in the page source.', 'dynamo')),
                        el(SelectControl, {
                            label: __('Required Consent Category', 'dynamo'),
                            value: consentCategory,
                            options: options,
                            onChange: function (value) {
                                setAttributes({ consentCategory: value });
                            },
                        })
                    )
                ),
                el(InnerBlocks, null)
            );
        },
        save: function () {
            return el(InnerBlocks.Content, null);
        },
    });
}());
