/**
 * Issue #36 — PRD v1.3.0 Slice 3: Radius Preset tracer — "Large" option end-to-end
 *
 * Jest unit tests covering (frontend AC4, AC5, AC6):
 *
 *   AC4 — The `blocks.registerBlockType` filter adds a `dynamoRadius` attribute
 *          (type: 'string', default: '') to blocks that declare
 *          `borders: { radius: true }` core support, and does NOT add it to
 *          blocks without that support.
 *          The Width dropdown from Slice 1 continues to appear on layout-supporting
 *          blocks — verified here by checking no cross-contamination between
 *          dynamoWidth and dynamoRadius attributes.
 *
 *   AC5 — The `blocks.getSaveContent.extraProps` filter, when `dynamoRadius`
 *          is `'lg'`, adds `border-radius: var(--dynamo-borders-radius-lg)` to
 *          the `style` prop (as `borderRadius` in the React-style object or
 *          as the CSS-property string form).
 *
 *   AC6 — When both `dynamoWidth` is `'narrow'` and `dynamoRadius` is `'lg'`,
 *          the resulting `style` prop contains BOTH declarations in the same
 *          object: `maxWidth: 'var(--dynamo-layout-width-narrow)'` AND
 *          `borderRadius: 'var(--dynamo-borders-radius-lg)'`.
 *
 * The module under test is `src/editor/token-presets.js`.
 * These tests will fail until the radius implementation is added to that module
 * (RED phase).
 */

// ---------------------------------------------------------------------------
// WordPress block editor globals stub
// Mirrors the same minimal subset used in token-presets.test.js
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

// Minimal wp.blocks stub
const blockTypeRegistry = {};

function registerBlockType(name, settings) {
    blockTypeRegistry[name] = settings;
    return settings;
}

function getBlockType(name) {
    return blockTypeRegistry[name] || null;
}

// Set up globals that token-presets.js will consume
beforeAll(() => {
    global.wp = {
        hooks: { addFilter, applyFilters },
        blocks: { getBlockType, registerBlockType },
    };
});

beforeEach(() => {
    // Reset filter registrations and block registry between tests
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
// ---------------------------------------------------------------------------

function loadModule() {
    return require('../../src/editor/token-presets');
}

// ---------------------------------------------------------------------------
// Helper: normalise a style prop (object or string) to a plain object so
// assertions can check for both camelCase and CSS-property forms uniformly.
// ---------------------------------------------------------------------------

function styleToObject(style) {
    if (!style) return {};
    if (typeof style === 'object') return style;
    // Parse "max-width:var(...);border-radius:var(...)" into { 'max-width': 'var(...)' }
    return Object.fromEntries(
        style.split(';').filter(Boolean).map((decl) => {
            const idx = decl.indexOf(':');
            return [decl.slice(0, idx).trim(), decl.slice(idx + 1).trim()];
        })
    );
}

// ---------------------------------------------------------------------------
// AC4 — blocks.registerBlockType filter: dynamoRadius attribute injection
// ---------------------------------------------------------------------------

describe('blocks.registerBlockType filter — dynamoRadius attribute (AC4)', () => {
    test('loading the module registers the blocks.registerBlockType filter', () => {
        loadModule();
        expect(registeredFilters['blocks.registerBlockType']).toBeDefined();
        expect(registeredFilters['blocks.registerBlockType'].length).toBeGreaterThan(0);
    });

    test('filter adds dynamoRadius attribute to a block with borders.radius support', () => {
        loadModule();

        const settings = {
            name: 'core/cover',
            supports: { borders: { radius: true } },
            attributes: {},
        };

        const filtered = applyFilters('blocks.registerBlockType', settings, 'core/cover');

        expect(filtered.attributes).toHaveProperty('dynamoRadius');
    });

    test('dynamoRadius attribute has type "string"', () => {
        loadModule();

        const settings = {
            name: 'core/cover',
            supports: { borders: { radius: true } },
            attributes: {},
        };

        const filtered = applyFilters('blocks.registerBlockType', settings, 'core/cover');

        expect(filtered.attributes.dynamoRadius.type).toBe('string');
    });

    test('dynamoRadius attribute defaults to empty string', () => {
        loadModule();

        const settings = {
            name: 'core/cover',
            supports: { borders: { radius: true } },
            attributes: {},
        };

        const filtered = applyFilters('blocks.registerBlockType', settings, 'core/cover');

        const defaultValue = filtered.attributes.dynamoRadius.default;
        expect(defaultValue == null || defaultValue === '').toBe(true);
    });

    test('filter does NOT add dynamoRadius to a block without borders.radius support', () => {
        loadModule();

        const settings = {
            name: 'core/paragraph',
            supports: {},
            attributes: {},
        };

        const filtered = applyFilters('blocks.registerBlockType', settings, 'core/paragraph');

        expect(filtered.attributes).not.toHaveProperty('dynamoRadius');
    });

    test('filter does NOT add dynamoRadius to a block with borders: true but radius not true', () => {
        loadModule();

        const settings = {
            name: 'core/group',
            supports: { borders: { color: true } },
            attributes: {},
        };

        const filtered = applyFilters('blocks.registerBlockType', settings, 'core/group');

        expect(filtered.attributes).not.toHaveProperty('dynamoRadius');
    });

    test('filter preserves existing attributes alongside dynamoRadius', () => {
        loadModule();

        const settings = {
            name: 'core/cover',
            supports: { borders: { radius: true } },
            attributes: {
                url: { type: 'string' },
            },
        };

        const filtered = applyFilters('blocks.registerBlockType', settings, 'core/cover');

        expect(filtered.attributes.url).toEqual({ type: 'string' });
        expect(filtered.attributes).toHaveProperty('dynamoRadius');
    });

    // Width-only blocks: layout support but no borders.radius support — no dynamoRadius
    test('width-only block (layout support, no borders.radius): no dynamoRadius added', () => {
        loadModule();

        const settings = {
            name: 'core/group',
            supports: { layout: true },
            attributes: {},
        };

        const filtered = applyFilters('blocks.registerBlockType', settings, 'core/group');

        expect(filtered.attributes).not.toHaveProperty('dynamoRadius');
        // dynamoWidth SHOULD be present (Width preset from Slice 1)
        expect(filtered.attributes).toHaveProperty('dynamoWidth');
    });

    // Radius-only blocks: borders.radius support but no layout support — no dynamoWidth
    test('radius-only block (borders.radius support, no layout): no dynamoWidth added', () => {
        loadModule();

        const settings = {
            name: 'core/image',
            supports: { borders: { radius: true } },
            attributes: {},
        };

        const filtered = applyFilters('blocks.registerBlockType', settings, 'core/image');

        expect(filtered.attributes).toHaveProperty('dynamoRadius');
        expect(filtered.attributes).not.toHaveProperty('dynamoWidth');
    });
});

// ---------------------------------------------------------------------------
// AC5 — blocks.getSaveContent.extraProps filter: border-radius inline style
// ---------------------------------------------------------------------------

describe('blocks.getSaveContent.extraProps filter — border-radius style (AC5)', () => {
    test('loading the module registers the blocks.getSaveContent.extraProps filter', () => {
        loadModule();
        expect(registeredFilters['blocks.getSaveContent.extraProps']).toBeDefined();
        expect(registeredFilters['blocks.getSaveContent.extraProps'].length).toBeGreaterThan(0);
    });

    test('filter writes border-radius var when dynamoRadius === "lg"', () => {
        loadModule();

        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            {},                               // existing props
            { name: 'core/cover' },           // blockType
            { dynamoRadius: 'lg' }            // attributes
        );

        expect(extraProps).toHaveProperty('style');
        const styleStr =
            typeof extraProps.style === 'string'
                ? extraProps.style
                : JSON.stringify(extraProps.style);
        expect(styleStr).toContain('var(--dynamo-borders-radius-lg)');
    });

    test('filter writes border-radius with the correct CSS variable specifically', () => {
        loadModule();

        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            {},
            { name: 'core/cover' },
            { dynamoRadius: 'lg' }
        );

        const style = extraProps.style;
        if (typeof style === 'string') {
            expect(style).toMatch(/border-radius\s*:\s*var\(--dynamo-borders-radius-lg\)/);
        } else {
            // React-style object: { borderRadius: 'var(--dynamo-borders-radius-lg)' }
            const borderRadius = style.borderRadius ?? style['border-radius'];
            expect(borderRadius).toBe('var(--dynamo-borders-radius-lg)');
        }
    });

    test('filter omits border-radius when dynamoRadius is unset', () => {
        loadModule();

        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            {},
            { name: 'core/cover' },
            {}  // no dynamoRadius
        );

        const hasRadiusVar =
            extraProps.style &&
            JSON.stringify(extraProps.style).includes('--dynamo-borders-radius-lg');
        expect(hasRadiusVar).toBeFalsy();
    });

    test('filter omits border-radius when dynamoRadius is empty string', () => {
        loadModule();

        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            {},
            { name: 'core/cover' },
            { dynamoRadius: '' }
        );

        const hasRadiusVar =
            extraProps.style &&
            JSON.stringify(extraProps.style).includes('--dynamo-borders-radius-lg');
        expect(hasRadiusVar).toBeFalsy();
    });

    test('filter preserves existing extraProps alongside the new border-radius style', () => {
        loadModule();

        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            { className: 'my-cover-block' },
            { name: 'core/cover' },
            { dynamoRadius: 'lg' }
        );

        expect(extraProps.className).toBe('my-cover-block');
    });

    test('filter does not mutate the incoming props object', () => {
        loadModule();

        const incoming = { className: 'original' };
        const frozen   = Object.freeze(incoming);

        expect(() => {
            applyFilters(
                'blocks.getSaveContent.extraProps',
                frozen,
                { name: 'core/cover' },
                { dynamoRadius: 'lg' }
            );
        }).not.toThrow();
    });
});

// ---------------------------------------------------------------------------
// AC6 — Both dynamoWidth and dynamoRadius set: coexist in same style object
// ---------------------------------------------------------------------------

describe('blocks.getSaveContent.extraProps — both dynamoWidth and dynamoRadius coexist (AC6)', () => {
    test('style contains maxWidth when only dynamoWidth is set', () => {
        loadModule();

        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            {},
            { name: 'core/group' },
            { dynamoWidth: 'narrow' }
        );

        const style = styleToObject(extraProps.style);
        const maxWidth = style.maxWidth ?? style['max-width'];
        expect(maxWidth).toBe('var(--dynamo-layout-width-narrow)');
    });

    test('style contains borderRadius when only dynamoRadius is set', () => {
        loadModule();

        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            {},
            { name: 'core/cover' },
            { dynamoRadius: 'lg' }
        );

        const style = styleToObject(extraProps.style);
        const borderRadius = style.borderRadius ?? style['border-radius'];
        expect(borderRadius).toBe('var(--dynamo-borders-radius-lg)');
    });

    test('when both dynamoWidth and dynamoRadius are set, style contains both declarations', () => {
        loadModule();

        // A block that declares both layout support and borders.radius support.
        // The extraProps filter must merge both CSS vars into the same style object.
        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            {},
            { name: 'core/group' },
            { dynamoWidth: 'narrow', dynamoRadius: 'lg' }
        );

        expect(extraProps).toHaveProperty('style');

        const style = extraProps.style;
        const styleStr =
            typeof style === 'string'
                ? style
                : JSON.stringify(style);

        // Both CSS variables must be present in the same style attribute
        expect(styleStr).toContain('var(--dynamo-layout-width-narrow)');
        expect(styleStr).toContain('var(--dynamo-borders-radius-lg)');
    });

    test('when both are set, maxWidth and borderRadius live in the same style object', () => {
        loadModule();

        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            {},
            { name: 'core/group' },
            { dynamoWidth: 'narrow', dynamoRadius: 'lg' }
        );

        const style = extraProps.style;

        if (typeof style === 'object' && style !== null) {
            // React-style: both keys must be in the SAME object
            const maxWidth    = style.maxWidth ?? style['max-width'];
            const borderRadius = style.borderRadius ?? style['border-radius'];
            expect(maxWidth).toBe('var(--dynamo-layout-width-narrow)');
            expect(borderRadius).toBe('var(--dynamo-borders-radius-lg)');
        } else if (typeof style === 'string') {
            // CSS string: both declarations must be present
            expect(style).toMatch(/max-width\s*:\s*var\(--dynamo-layout-width-narrow\)/);
            expect(style).toMatch(/border-radius\s*:\s*var\(--dynamo-borders-radius-lg\)/);
        } else {
            fail('style prop must be a non-null string or object when both attributes are set');
        }
    });

    test('when both are set, the style is a single object — not two separate style props', () => {
        loadModule();

        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            {},
            { name: 'core/group' },
            { dynamoWidth: 'narrow', dynamoRadius: 'lg' }
        );

        // There must be exactly one `style` key — not style + style2 or similar
        const styleKeys = Object.keys(extraProps).filter((k) => k.toLowerCase().includes('style'));
        expect(styleKeys).toHaveLength(1);
        expect(styleKeys[0]).toBe('style');
    });

    test('when both are set, existing props are preserved alongside the merged style', () => {
        loadModule();

        const extraProps = applyFilters(
            'blocks.getSaveContent.extraProps',
            { className: 'my-block', 'data-foo': 'bar' },
            { name: 'core/group' },
            { dynamoWidth: 'narrow', dynamoRadius: 'lg' }
        );

        expect(extraProps.className).toBe('my-block');
        expect(extraProps['data-foo']).toBe('bar');
    });
});
