/**
 * Issue #34 — PRD v1.3.0 Slice 1: Width Preset tracer — "Narrow" option
 *
 * Jest unit tests covering (AC8 / frontend):
 *
 *   - The `blocks.registerBlockType` filter adds a `dynamoWidth` attribute to
 *     blocks that declare `layout` support, and does NOT add it to blocks
 *     without layout support.
 *
 *   - The `blocks.getSaveContent.extraProps` filter writes
 *     `style="max-width: var(--dynamo-layout-width-narrow)"` when
 *     `dynamoWidth === 'narrow'`, and omits the style when `dynamoWidth` is
 *     unset or empty.
 *
 *   - The Width dropdown options array exposed by the module contains exactly
 *     one option with value "narrow" and label "Narrow".
 *
 * The module under test is `src/editor/token-presets.js` which does not exist
 * yet. These tests will fail with a module-not-found error until it is created
 * (RED phase).
 */

// ---------------------------------------------------------------------------
// WordPress block editor globals stub
// These mirror the minimal subset of @wordpress/blocks and @wordpress/hooks
// that token-presets.js is expected to consume.
// ---------------------------------------------------------------------------

const registeredFilters = {};

function addFilter(hookName, namespace, callback, priority = 10) {
    if (!registeredFilters[hookName]) {
        registeredFilters[hookName] = [];
    }
    registeredFilters[hookName].push({ namespace, callback, priority });
}

function applyFilters(hookName, value, ...args) {
    const filters = (registeredFilters[hookName] || [])
        .slice()
        .sort((a, b) => a.priority - b.priority);
    return filters.reduce((acc, { callback }) => callback(acc, ...args), value);
}

// Minimal wp.blocks.getBlockType stub
const blockTypeRegistry = {};

function registerBlockType(name, settings) {
    blockTypeRegistry[name] = settings;
    return settings;
}

function getBlockType(name) {
    return blockTypeRegistry[name] || null;
}

// Set up globals that token-presets.js will likely use
beforeAll(() => {
    global.wp = {
        hooks: { addFilter, applyFilters },
        blocks: { getBlockType, registerBlockType },
    };
});

beforeEach(() => {
    // Reset filter registrations between tests
    Object.keys(registeredFilters).forEach((key) => {
        delete registeredFilters[key];
    });
    Object.keys(blockTypeRegistry).forEach((key) => {
        delete blockTypeRegistry[key];
    });
    jest.resetModules();
});

afterAll(() => {
    delete global.wp;
});

// ---------------------------------------------------------------------------
// Load the module under test
// This will throw if src/editor/token-presets.js does not exist yet — which
// is the expected RED-phase failure for this test suite.
// ---------------------------------------------------------------------------

function loadModule() {
    return require('../../src/editor/token-presets');
}

// ---------------------------------------------------------------------------
// Filter shape: WIDTH_PRESET_OPTIONS
// ---------------------------------------------------------------------------

describe('WIDTH_PRESET_OPTIONS — filter shape (AC3 / frontend)', () => {
    test('module exports WIDTH_PRESET_OPTIONS (or equivalent) as an array', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        expect(Array.isArray(options)).toBe(true);
    });

    test('WIDTH_PRESET_OPTIONS has at least one entry', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        expect(options.length).toBeGreaterThanOrEqual(1);
    });

    test('the first option has value "narrow"', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        expect(options[0].value).toBe('narrow');
    });

    test('the first option has label "Narrow"', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        expect(options[0].label).toBe('Narrow');
    });
});

// ---------------------------------------------------------------------------
// blocks.registerBlockType filter — dynamoWidth attribute injection
// ---------------------------------------------------------------------------

describe('blocks.registerBlockType filter — dynamoWidth attribute (AC8)', () => {
    test('loading the module registers the blocks.registerBlockType filter', () => {
        loadModule();
        expect(registeredFilters['blocks.registerBlockType']).toBeDefined();
        expect(registeredFilters['blocks.registerBlockType'].length).toBeGreaterThan(0);
    });

    test('filter adds dynamoWidth attribute to a block with layout support', () => {
        loadModule();

        const settingsWithLayout = {
            name: 'core/group',
            supports: { layout: true },
            attributes: {},
        };

        const filtered = applyFilters(
            'blocks.registerBlockType',
            settingsWithLayout,
            'core/group'
        );

        expect(filtered.attributes).toHaveProperty('dynamoWidth');
    });

    test('dynamoWidth attribute has type "string"', () => {
        loadModule();

        const settingsWithLayout = {
            name: 'core/group',
            supports: { layout: true },
            attributes: {},
        };

        const filtered = applyFilters(
            'blocks.registerBlockType',
            settingsWithLayout,
            'core/group'
        );

        expect(filtered.attributes.dynamoWidth.type).toBe('string');
    });

    test('dynamoWidth attribute defaults to empty string or undefined (not "narrow")', () => {
        loadModule();

        const settingsWithLayout = {
            name: 'core/group',
            supports: { layout: true },
            attributes: {},
        };

        const filtered = applyFilters(
            'blocks.registerBlockType',
            settingsWithLayout,
            'core/group'
        );

        const defaultValue = filtered.attributes.dynamoWidth.default;
        // Default must be empty/falsy — not pre-set to 'narrow'
        expect(defaultValue == null || defaultValue === '').toBe(true);
    });

    test('filter does NOT add dynamoWidth to a block without layout support', () => {
        loadModule();

        const settingsWithoutLayout = {
            name: 'core/paragraph',
            supports: {},
            attributes: {},
        };

        const filtered = applyFilters(
            'blocks.registerBlockType',
            settingsWithoutLayout,
            'core/paragraph'
        );

        expect(filtered.attributes).not.toHaveProperty('dynamoWidth');
    });

    test('filter does NOT add dynamoWidth to a block with layout: false', () => {
        loadModule();

        const settingsLayoutFalse = {
            name: 'core/columns',
            supports: { layout: false },
            attributes: {},
        };

        const filtered = applyFilters(
            'blocks.registerBlockType',
            settingsLayoutFalse,
            'core/columns'
        );

        expect(filtered.attributes).not.toHaveProperty('dynamoWidth');
    });

    test('filter preserves existing attributes on blocks with layout support', () => {
        loadModule();

        const settingsWithLayout = {
            name: 'core/group',
            supports: { layout: true },
            attributes: {
                align: { type: 'string', default: 'wide' },
            },
        };

        const filtered = applyFilters(
            'blocks.registerBlockType',
            settingsWithLayout,
            'core/group'
        );

        expect(filtered.attributes.align).toEqual({ type: 'string', default: 'wide' });
        expect(filtered.attributes).toHaveProperty('dynamoWidth');
    });
});

// ---------------------------------------------------------------------------
// blocks.getSaveContent.extraProps filter — inline style output (AC8)
// ---------------------------------------------------------------------------

describe('blocks.getSaveContent.extraProps filter — inline-style output (AC8)', () => {
    test('loading the module registers the blocks.getSaveContent.extraProps filter', () => {
        loadModule();
        expect(registeredFilters['blocks.getSaveContent.extraProps']).toBeDefined();
        expect(registeredFilters['blocks.getSaveContent.extraProps'].length).toBeGreaterThan(0);
    });

    test('filter writes max-width var when dynamoWidth === "narrow"', () => {
        loadModule();

        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            {},                               // existing props
            { name: 'core/group' },           // blockType
            { dynamoWidth: 'narrow' }         // attributes
        );

        expect(extraProps).toHaveProperty('style');
        // style may be a string or an object — test both forms
        const styleStr =
            typeof extraProps.style === 'string'
                ? extraProps.style
                : Object.entries(extraProps.style || {})
                      .map(([k, v]) => `${k}:${v}`)
                      .join(';');
        expect(styleStr).toContain('var(--dynamo-layout-width-narrow)');
    });

    test('filter writes max-width with the narrow CSS variable specifically', () => {
        loadModule();

        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            {},
            { name: 'core/group' },
            { dynamoWidth: 'narrow' }
        );

        const style = extraProps.style;
        if (typeof style === 'string') {
            expect(style).toMatch(/max-width\s*:\s*var\(--dynamo-layout-width-narrow\)/);
        } else {
            // React-style object: { maxWidth: 'var(--dynamo-layout-width-narrow)' }
            const maxWidth = style.maxWidth ?? style['max-width'];
            expect(maxWidth).toBe('var(--dynamo-layout-width-narrow)');
        }
    });

    test('filter omits the style prop when dynamoWidth is unset', () => {
        loadModule();

        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            {},
            { name: 'core/group' },
            {}  // no dynamoWidth attribute
        );

        // style should be absent, empty, or not contain the narrow var
        const hasNarrow =
            extraProps.style &&
            JSON.stringify(extraProps.style).includes('--dynamo-layout-width-narrow');
        expect(hasNarrow).toBeFalsy();
    });

    test('filter omits the style prop when dynamoWidth is empty string', () => {
        loadModule();

        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            {},
            { name: 'core/group' },
            { dynamoWidth: '' }
        );

        const hasNarrow =
            extraProps.style &&
            JSON.stringify(extraProps.style).includes('--dynamo-layout-width-narrow');
        expect(hasNarrow).toBeFalsy();
    });

    test('filter preserves existing extraProps alongside the new style', () => {
        loadModule();

        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            { className: 'my-custom-class' },
            { name: 'core/group' },
            { dynamoWidth: 'narrow' }
        );

        expect(extraProps.className).toBe('my-custom-class');
    });

    test('filter does not mutate the incoming props object', () => {
        loadModule();

        const incoming = { className: 'original' };
        const frozen   = Object.freeze(incoming);

        // Should not throw when input is frozen — must return a new object
        expect(() => {
            applyFilters(
                'blocks.getSaveContent.extraProps',
                frozen,
                { name: 'core/group' },
                { dynamoWidth: 'narrow' }
            );
        }).not.toThrow();
    });
});
