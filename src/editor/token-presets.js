const WIDTH_PRESET_OPTIONS = [
    { value: 'narrow',    label: 'Narrow' },
    { value: 'default',   label: 'Default' },
    { value: 'wide',      label: 'Wide' },
    { value: 'container', label: 'Container' },
    { value: 'full',      label: 'Full' },
];

const RADIUS_PRESET_OPTIONS = [
    { value: 'lg', label: 'Large' },
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
    'blocks.registerBlockType',
    'dynamo/token-presets/add-radius-attribute',
    function addRadiusAttribute(settings) {
        var s = settings.supports;
        if (!s) return settings;
        // Support declared as __experimentalBorder (WP 6.x), border (WP 6.5+), or borders
        var hasRadius =
            (s.__experimentalBorder && s.__experimentalBorder.radius) ||
            (s.border  && s.border.radius)  ||
            (s.borders && s.borders.radius);
        if (!hasRadius) {
            return settings;
        }
        return Object.assign({}, settings, {
            attributes: Object.assign({}, settings.attributes, {
                dynamoRadius: {
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

addFilter(
    'blocks.getSaveContent.extraProps',
    'dynamo/token-presets/apply-radius-style',
    function applyRadiusStyle(props, blockType, attributes) {
        if (!attributes.dynamoRadius) {
            return props;
        }
        return Object.assign({}, props, {
            style: Object.assign(
                {},
                props.style && typeof props.style === 'object' ? props.style : {},
                { borderRadius: 'var(--dynamo-borders-radius-' + attributes.dynamoRadius + ')' }
            ),
        });
    }
);

// The editor HOC requires editor-only globals not available in unit-test environments.
// Guard so Jest can load this module with only wp.hooks stubbed.
if (wp.compose && wp.blockEditor && wp.components && wp.element && wp.blocks) {
    var withDynamoControls = wp.compose.createHigherOrderComponent(
        function (BlockEdit) {
            return function (props) {
                var name          = props.name;
                var attributes    = props.attributes;
                var setAttributes = props.setAttributes;

                var hasLayout = wp.blocks.hasBlockSupport(name, 'layout');
                var blockType = wp.blocks.getBlockType(name);
                var bs = blockType && blockType.supports;
                var hasRadius = !!(bs && (
                    (bs.__experimentalBorder && bs.__experimentalBorder.radius) ||
                    (bs.border  && bs.border.radius)  ||
                    (bs.borders && bs.borders.radius)
                ));

                if (!hasLayout && !hasRadius) {
                    return wp.element.createElement(BlockEdit, props);
                }

                var dynamoWidth  = attributes.dynamoWidth  || '';
                var dynamoRadius = attributes.dynamoRadius || '';

                var widthOptions  = [{ value: '', label: '— Default —' }].concat(WIDTH_PRESET_OPTIONS);
                var radiusOptions = [{ value: '', label: '— None —' }].concat(RADIUS_PRESET_OPTIONS);

                var controls = [];

                if (hasLayout) {
                    controls.push(
                        wp.element.createElement(wp.components.SelectControl, {
                            label: 'Width',
                            value: dynamoWidth,
                            options: widthOptions,
                            onChange: function (value) {
                                setAttributes({ dynamoWidth: value });
                            },
                        })
                    );
                }

                if (hasRadius) {
                    controls.push(
                        wp.element.createElement(wp.components.SelectControl, {
                            label: 'Radius',
                            value: dynamoRadius,
                            options: radiusOptions,
                            onChange: function (value) {
                                setAttributes({ dynamoRadius: value });
                            },
                        })
                    );
                }

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
                            controls
                        )
                    )
                );
            };
        },
        'withDynamoControls'
    );

    addFilter(
        'editor.BlockEdit',
        'dynamo/token-presets/dynamo-controls',
        withDynamoControls
    );
}

module.exports = { WIDTH_PRESET_OPTIONS, RADIUS_PRESET_OPTIONS };
