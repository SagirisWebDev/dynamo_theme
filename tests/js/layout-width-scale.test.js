/**
 * Issue #35 — PRD v1.3.0 Slice 2: Complete Width Scale + alias helper + Customizer subsection
 *
 * Jest unit tests covering (AC3 / frontend):
 *
 *   - WIDTH_PRESET_OPTIONS must expose all five presets: narrow, default, wide,
 *     container, full — each with the correct value and label.
 *   - The blocks.getSaveContent.extraProps filter must apply max-width vars for
 *     each of the five width presets.
 *   - The blocks.registerBlockType filter still injects dynamoWidth for layout-
 *     supporting blocks (regression guard).
 *
 * The module under test is `src/editor/token-presets.js`.
 * These tests will fail until the production code is updated to include all
 * five presets (RED phase for Issue #35).
 */

// ---------------------------------------------------------------------------
// WordPress block editor globals stub (mirrors tests/js/token-presets.test.js)
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

beforeAll(() => {
    global.wp = {
        hooks: { addFilter, applyFilters },
        blocks: {},
    };
});

beforeEach(() => {
    Object.keys(registeredFilters).forEach((key) => {
        delete registeredFilters[key];
    });
    jest.resetModules();
});

afterAll(() => {
    delete global.wp;
});

function loadModule() {
    return require('../../src/editor/token-presets');
}

// ---------------------------------------------------------------------------
// AC3 / AC4 — WIDTH_PRESET_OPTIONS must contain all five presets
// ---------------------------------------------------------------------------

describe('WIDTH_PRESET_OPTIONS — full five-step width scale (Issue #35 AC3/AC4)', () => {
    test('module exports WIDTH_PRESET_OPTIONS as an array', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        expect(Array.isArray(options)).toBe(true);
    });

    test('WIDTH_PRESET_OPTIONS has exactly five entries', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        expect(options).toHaveLength(5);
    });

    test('WIDTH_PRESET_OPTIONS contains an entry with value "narrow"', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        const found = options.find((o) => o.value === 'narrow');
        expect(found).toBeDefined();
    });

    test('narrow option has label "Narrow"', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        const found = options.find((o) => o.value === 'narrow');
        expect(found.label).toBe('Narrow');
    });

    test('WIDTH_PRESET_OPTIONS contains an entry with value "default"', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        const found = options.find((o) => o.value === 'default');
        expect(found).toBeDefined();
    });

    test('default option has label "Default"', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        const found = options.find((o) => o.value === 'default');
        expect(found.label).toBe('Default');
    });

    test('WIDTH_PRESET_OPTIONS contains an entry with value "wide"', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        const found = options.find((o) => o.value === 'wide');
        expect(found).toBeDefined();
    });

    test('wide option has label "Wide"', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        const found = options.find((o) => o.value === 'wide');
        expect(found.label).toBe('Wide');
    });

    test('WIDTH_PRESET_OPTIONS contains an entry with value "container"', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        const found = options.find((o) => o.value === 'container');
        expect(found).toBeDefined();
    });

    test('container option has label "Container"', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        const found = options.find((o) => o.value === 'container');
        expect(found.label).toBe('Container');
    });

    test('WIDTH_PRESET_OPTIONS contains an entry with value "full"', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        const found = options.find((o) => o.value === 'full');
        expect(found).toBeDefined();
    });

    test('full option has label "Full"', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        const found = options.find((o) => o.value === 'full');
        expect(found.label).toBe('Full');
    });

    test('every option has a non-empty string value', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        options.forEach((opt) => {
            expect(typeof opt.value).toBe('string');
            expect(opt.value.length).toBeGreaterThan(0);
        });
    });

    test('every option has a non-empty string label', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        options.forEach((opt) => {
            expect(typeof opt.label).toBe('string');
            expect(opt.label.length).toBeGreaterThan(0);
        });
    });

    test('preset order is narrow, default, wide, container, full', () => {
        const mod = loadModule();
        const options = mod.WIDTH_PRESET_OPTIONS ?? mod.widthPresetOptions ?? mod.width_preset_options;
        const values = options.map((o) => o.value);
        expect(values).toEqual(['narrow', 'default', 'wide', 'container', 'full']);
    });
});

// ---------------------------------------------------------------------------
// blocks.getSaveContent.extraProps filter — all five width vars applied correctly
// ---------------------------------------------------------------------------

describe('blocks.getSaveContent.extraProps — all five presets write correct CSS vars (Issue #35)', () => {
    const presetCssVarPairs = [
        ['narrow',    '--dynamo-layout-width-narrow'],
        ['default',   '--dynamo-layout-width-default'],
        ['wide',      '--dynamo-layout-width-wide'],
        ['container', '--dynamo-layout-width-container'],
        ['full',      '--dynamo-layout-width-full'],
    ];

    presetCssVarPairs.forEach(([preset, cssVar]) => {
        test(`filter writes max-width var "${cssVar}" when dynamoWidth === "${preset}"`, () => {
            loadModule();

            const extraProps = applyFilters(
                'blocks.getSaveContent.extraProps',
                {},
                { name: 'core/group' },
                { dynamoWidth: preset }
            );

            expect(extraProps).toHaveProperty('style');

            const styleStr =
                typeof extraProps.style === 'string'
                    ? extraProps.style
                    : Object.entries(extraProps.style || {})
                          .map(([k, v]) => `${k}:${v}`)
                          .join(';');

            expect(styleStr).toContain(`var(${cssVar})`);
        });
    });
});

// ---------------------------------------------------------------------------
// Regression: blocks.registerBlockType filter still injects dynamoWidth
// ---------------------------------------------------------------------------

describe('blocks.registerBlockType filter — regression guard for Issue #34 (Issue #35)', () => {
    test('dynamoWidth attribute is still added to layout-supporting blocks', () => {
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
        expect(filtered.attributes.dynamoWidth.type).toBe('string');
    });

    test('dynamoWidth attribute is NOT added to blocks without layout support', () => {
        loadModule();

        const settingsNoLayout = {
            name: 'core/paragraph',
            supports: {},
            attributes: {},
        };

        const filtered = applyFilters(
            'blocks.registerBlockType',
            settingsNoLayout,
            'core/paragraph'
        );

        expect(filtered.attributes).not.toHaveProperty('dynamoWidth');
    });
});
