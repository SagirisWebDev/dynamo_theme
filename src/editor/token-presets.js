const WIDTH_PRESET_OPTIONS = [
    { value: 'narrow',    label: 'Narrow' },
    { value: 'default',   label: 'Default' },
    { value: 'wide',      label: 'Wide' },
    { value: 'container', label: 'Container' },
    { value: 'full',      label: 'Full' },
];

const { addFilter } = wp.hooks;

addFilter(
    'blocks.registerBlockType',
    'dynamo/token-presets/add-width-attribute',
    function addWidthAttribute(settings) {
        if (!settings.supports || !settings.supports.layout) {
            return settings;
        }
        return Object.assign({}, settings, {
            attributes: Object.assign({}, settings.attributes, {
                dynamoWidth: {
                    type: 'string',
                    default: '',
                },
            }),
        });
    }
);

addFilter(
    'blocks.getSaveContent.extraProps',
    'dynamo/token-presets/apply-width-style',
    function applyWidthStyle(props, blockType, attributes) {
        if (!attributes.dynamoWidth) {
            return props;
        }
        return Object.assign({}, props, {
            style: Object.assign(
                {},
                props.style && typeof props.style === 'object' ? props.style : {},
                { maxWidth: 'var(--dynamo-layout-width-' + attributes.dynamoWidth + ')' }
            ),
        });
    }
);

// The editor HOC requires editor-only globals not available in unit-test environments.
// Guard so Jest can load this module with only wp.hooks stubbed.
if (wp.compose && wp.blockEditor && wp.components && wp.element && wp.blocks) {
    var withDynamoWidthControl = wp.compose.createHigherOrderComponent(
        function (BlockEdit) {
            return function (props) {
                var name          = props.name;
                var attributes    = props.attributes;
                var setAttributes = props.setAttributes;

                if (!wp.blocks.hasBlockSupport(name, 'layout')) {
                    return wp.element.createElement(BlockEdit, props);
                }

                var dynamoWidth = attributes.dynamoWidth || '';
                var options     = [{ value: '', label: '— Default —' }].concat(WIDTH_PRESET_OPTIONS);

                return wp.element.createElement(
                    wp.element.Fragment,
                    null,
                    wp.element.createElement(BlockEdit, props),
                    wp.element.createElement(
                        wp.blockEditor.InspectorControls,
                        null,
                        wp.element.createElement(
                            wp.components.PanelBody,
                            { title: 'Dynamo', initialOpen: true },
                            wp.element.createElement(wp.components.SelectControl, {
                                label: 'Width',
                                value: dynamoWidth,
                                options: options,
                                onChange: function (value) {
                                    setAttributes({ dynamoWidth: value });
                                },
                            })
                        )
                    )
                );
            };
        },
        'withDynamoWidthControl'
    );

    addFilter(
        'editor.BlockEdit',
        'dynamo/token-presets/width-control',
        withDynamoWidthControl
    );
}

module.exports = { WIDTH_PRESET_OPTIONS };
