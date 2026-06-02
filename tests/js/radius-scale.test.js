/**
 * Issue #37 — PRD v1.3.0 Slice 4: Complete Radius Scale + Customizer subsection
 *
 * Jest unit tests covering (AC5 / frontend):
 *
 *   - RADIUS_PRESET_OPTIONS must expose all six presets: none, sm, default, lg,
 *     xl, pill — each with the correct value and label.
 *   - The blocks.getSaveContent.extraProps filter must apply border-radius vars for
 *     each of the six radius presets.
 *   - The blocks.registerBlockType filter still injects dynamoRadius for border-radius-
 *     supporting blocks (regression guard from issue #36).
 *
 * The module under test is `src/editor/token-presets.js`.
 * These tests will fail until the production code is updated to include all
 * six radius presets (RED phase for Issue #37).
 */

// ---------------------------------------------------------------------------
// WordPress block editor globals stub (mirrors tests/js/layout-width-scale.test.js)
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
// AC5 — RADIUS_PRESET_OPTIONS must contain all six presets
// ---------------------------------------------------------------------------

describe('RADIUS_PRESET_OPTIONS — full six-step radius scale (Issue #37 AC5)', () => {
    test('module exports RADIUS_PRESET_OPTIONS as an array', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        expect(Array.isArray(options)).toBe(true);
    });

    test('RADIUS_PRESET_OPTIONS has exactly six entries', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        expect(options).toHaveLength(6);
    });

    test('RADIUS_PRESET_OPTIONS contains an entry with value "none"', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        const found = options.find((o) => o.value === 'none');
        expect(found).toBeDefined();
    });

    test('none option has label "None"', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        const found = options.find((o) => o.value === 'none');
        expect(found.label).toBe('None');
    });

    test('RADIUS_PRESET_OPTIONS contains an entry with value "sm"', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        const found = options.find((o) => o.value === 'sm');
        expect(found).toBeDefined();
    });

    test('sm option has label "Small"', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        const found = options.find((o) => o.value === 'sm');
        expect(found.label).toBe('Small');
    });

    test('RADIUS_PRESET_OPTIONS contains an entry with value "default"', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        const found = options.find((o) => o.value === 'default');
        expect(found).toBeDefined();
    });

    test('default option has label "Default"', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        const found = options.find((o) => o.value === 'default');
        expect(found.label).toBe('Default');
    });

    test('RADIUS_PRESET_OPTIONS contains an entry with value "lg"', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        const found = options.find((o) => o.value === 'lg');
        expect(found).toBeDefined();
    });

    test('lg option has label "Large"', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        const found = options.find((o) => o.value === 'lg');
        expect(found.label).toBe('Large');
    });

    test('RADIUS_PRESET_OPTIONS contains an entry with value "xl"', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        const found = options.find((o) => o.value === 'xl');
        expect(found).toBeDefined();
    });

    test('xl option has label "X-Large"', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        const found = options.find((o) => o.value === 'xl');
        expect(found.label).toBe('X-Large');
    });

    test('RADIUS_PRESET_OPTIONS contains an entry with value "pill"', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        const found = options.find((o) => o.value === 'pill');
        expect(found).toBeDefined();
    });

    test('pill option has label "Pill"', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        const found = options.find((o) => o.value === 'pill');
        expect(found.label).toBe('Pill');
    });

    test('every option has a non-empty string value', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        options.forEach((opt) => {
            expect(typeof opt.value).toBe('string');
            expect(opt.value.length).toBeGreaterThan(0);
        });
    });

    test('every option has a non-empty string label', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        options.forEach((opt) => {
            expect(typeof opt.label).toBe('string');
            expect(opt.label.length).toBeGreaterThan(0);
        });
    });

    test('preset order is none, sm, default, lg, xl, pill', () => {
        const mod = loadModule();
        const options = mod.RADIUS_PRESET_OPTIONS ?? mod.radiusPresetOptions ?? mod.radius_preset_options;
        const values = options.map((o) => o.value);
        expect(values).toEqual(['none', 'sm', 'default', 'lg', 'xl', 'pill']);
    });
});

// ---------------------------------------------------------------------------
// blocks.getSaveContent.extraProps filter — all six radius vars applied correctly
// ---------------------------------------------------------------------------

describe('blocks.getSaveContent.extraProps — all six radius presets write correct CSS vars (Issue #37)', () => {
    const presetCssVarPairs = [
        ['none',    'var(--dynamo-borders-radius-none)'],
        ['sm',      'var(--dynamo-borders-radius-sm)'],
        ['default', 'var(--dynamo-borders-radius-default)'],
        ['lg',      'var(--dynamo-borders-radius-lg)'],
        ['xl',      'var(--dynamo-borders-radius-xl)'],
        ['pill',    'var(--dynamo-borders-radius-pill)'],
    ];

    presetCssVarPairs.forEach(([preset, cssVar]) => {
        test(`filter writes border-radius "${cssVar}" when dynamoRadius === "${preset}"`, () => {
            loadModule();

            const extraProps = applyFilters(
                'blocks.getSaveContent.extraProps',
                {},
                { name: 'core/group' },
                { dynamoRadius: preset }
            );

            expect(extraProps).toHaveProperty('style');

            const styleStr =
                typeof extraProps.style === 'string'
                    ? extraProps.style
                    : Object.entries(extraProps.style || {})
                          .map(([k, v]) => `${k}:${v}`)
                          .join(';');

            expect(styleStr).toContain(cssVar);
        });
    });
});

// ---------------------------------------------------------------------------
// Regression: blocks.registerBlockType filter still injects dynamoRadius
// ---------------------------------------------------------------------------

describe('blocks.registerBlockType filter — regression guard for Issue #36 (Issue #37)', () => {
    test('dynamoRadius attribute is still added to border-radius-supporting blocks', () => {
        loadModule();

        const settingsWithBorder = {
            name: 'core/image',
            supports: { __experimentalBorder: { radius: true } },
            attributes: {},
        };

        const filtered = applyFilters(
            'blocks.registerBlockType',
            settingsWithBorder,
            'core/image'
        );

        expect(filtered.attributes).toHaveProperty('dynamoRadius');
        expect(filtered.attributes.dynamoRadius.type).toBe('string');
    });

    test('dynamoRadius attribute is NOT added to blocks without border radius support', () => {
        loadModule();

        const settingsNoBorder = {
            name: 'core/paragraph',
            supports: {},
            attributes: {},
        };

        const filtered = applyFilters(
            'blocks.registerBlockType',
            settingsNoBorder,
            'core/paragraph'
        );

        expect(filtered.attributes).not.toHaveProperty('dynamoRadius');
    });
});
