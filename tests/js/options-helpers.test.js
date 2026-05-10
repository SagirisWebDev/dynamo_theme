const {
    LAYOUT_VALUES,
    FEATURE_LABELS,
    PERFORMANCE_LABELS,
    FEATURE_DEFAULTS,
    PERFORMANCE_DEFAULTS,
    DEFAULT_LAYOUT_MODE,
    buildOptions,
    setLayoutMode,
    setFeature,
    setPerformance,
} = require('../../src/admin/options-helpers');

const FEATURE_KEYS     = Object.keys(FEATURE_LABELS);
const PERFORMANCE_KEYS = Object.keys(PERFORMANCE_LABELS);

function freshState() {
    return buildOptions({});
}

describe('buildOptions defaults', () => {
    test('layout_mode defaults to full-width when missing', () => {
        expect(buildOptions({}).layout_mode).toBe('full-width');
    });

    test('feature toggles default to enabled', () => {
        const state = buildOptions({});
        for (const key of FEATURE_KEYS) {
            expect(state.features[key]).toBe(true);
        }
    });

    test('performance toggles default to disabled', () => {
        const state = buildOptions({});
        for (const key of PERFORMANCE_KEYS) {
            expect(state.performance[key]).toBe(false);
        }
    });

    test('rejects an unrecognised layout_mode', () => {
        expect(buildOptions({ layout_mode: 'evil-mode' }).layout_mode).toBe(DEFAULT_LAYOUT_MODE);
    });

    test('preserves saved feature flags over defaults', () => {
        const state = buildOptions({ features: { sticky_header: false } });
        expect(state.features.sticky_header).toBe(false);
        expect(state.features.breadcrumbs).toBe(true);
        expect(state.features.scroll_to_top).toBe(true);
    });

    test('preserves saved performance flags over defaults', () => {
        const state = buildOptions({ performance: { disable_emoji: true } });
        expect(state.performance.disable_emoji).toBe(true);
        expect(state.performance.disable_google_fonts).toBe(false);
        expect(state.performance.remove_jquery_migrate).toBe(false);
    });
});

describe('Layout Mode selection', () => {
    test.each(LAYOUT_VALUES)('selecting "%s" updates layout_mode to that value', (value) => {
        const next = setLayoutMode(freshState(), value);
        expect(next.layout_mode).toBe(value);
    });

    test('changing layout_mode does not mutate features or performance', () => {
        const state = freshState();
        const next  = setLayoutMode(state, 'boxed');
        expect(next.features).toEqual(FEATURE_DEFAULTS);
        expect(next.performance).toEqual(PERFORMANCE_DEFAULTS);
    });

    test('changing layout_mode returns a new object', () => {
        const state = freshState();
        const next  = setLayoutMode(state, 'boxed');
        expect(next).not.toBe(state);
    });
});

describe('Feature toggles', () => {
    test.each(FEATURE_KEYS)('toggling "%s" off sets only that key to false', (key) => {
        const next = setFeature(freshState(), key, false);
        expect(next.features[key]).toBe(false);
        for (const other of FEATURE_KEYS.filter((k) => k !== key)) {
            expect(next.features[other]).toBe(true);
        }
    });

    test.each(FEATURE_KEYS)('toggling "%s" back on sets it to true', (key) => {
        const off = setFeature(freshState(), key, false);
        const on  = setFeature(off, key, true);
        expect(on.features[key]).toBe(true);
    });

    test('toggling a feature does not affect layout_mode or performance', () => {
        const state = setLayoutMode(freshState(), 'sidebar-right');
        const next  = setFeature(state, 'sticky_header', false);
        expect(next.layout_mode).toBe('sidebar-right');
        expect(next.performance).toEqual(state.performance);
    });

    test('every advertised feature key has a label', () => {
        for (const key of Object.keys(FEATURE_DEFAULTS)) {
            expect(FEATURE_LABELS[key]).toBeTruthy();
        }
    });
});

describe('Performance toggles', () => {
    test.each(PERFORMANCE_KEYS)('toggling "%s" on sets only that key to true', (key) => {
        const next = setPerformance(freshState(), key, true);
        expect(next.performance[key]).toBe(true);
        for (const other of PERFORMANCE_KEYS.filter((k) => k !== key)) {
            expect(next.performance[other]).toBe(false);
        }
    });

    test.each(PERFORMANCE_KEYS)('toggling "%s" back off sets it to false', (key) => {
        const on  = setPerformance(freshState(), key, true);
        const off = setPerformance(on, key, false);
        expect(off.performance[key]).toBe(false);
    });

    test('toggling a performance setting does not affect layout_mode or features', () => {
        const state = setLayoutMode(freshState(), 'sidebar-left');
        const next  = setPerformance(state, 'disable_google_fonts', true);
        expect(next.layout_mode).toBe('sidebar-left');
        expect(next.features).toEqual(state.features);
    });

    test('every advertised performance key has a label', () => {
        for (const key of Object.keys(PERFORMANCE_DEFAULTS)) {
            expect(PERFORMANCE_LABELS[key]).toBeTruthy();
        }
    });
});

describe('Round-trip: helpers compose without cross-contamination', () => {
    test('multiple sequential edits accumulate independently', () => {
        let state = freshState();
        state = setLayoutMode(state, 'boxed');
        state = setFeature(state, 'breadcrumbs', false);
        state = setPerformance(state, 'remove_jquery_migrate', true);

        expect(state.layout_mode).toBe('boxed');
        expect(state.features.breadcrumbs).toBe(false);
        expect(state.features.sticky_header).toBe(true);
        expect(state.features.scroll_to_top).toBe(true);
        expect(state.performance.remove_jquery_migrate).toBe(true);
        expect(state.performance.disable_google_fonts).toBe(false);
        expect(state.performance.disable_emoji).toBe(false);
    });
});
