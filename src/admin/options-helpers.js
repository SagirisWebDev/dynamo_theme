const LAYOUT_OPTIONS = [
    { label: 'Full Width',    value: 'full-width' },
    { label: 'Boxed',         value: 'boxed' },
    { label: 'Sidebar Left',  value: 'sidebar-left' },
    { label: 'Sidebar Right', value: 'sidebar-right' },
];

const LAYOUT_VALUES = LAYOUT_OPTIONS.map((o) => o.value);

const FEATURE_LABELS = {
    sticky_header: 'Sticky Header',
    breadcrumbs:   'Breadcrumbs',
    scroll_to_top: 'Scroll to Top Button',
};

const PERFORMANCE_LABELS = {
    disable_google_fonts:  'Disable Google Fonts',
    disable_emoji:         'Disable Emoji Scripts',
    remove_jquery_migrate: 'Remove jQuery Migrate',
};

const FEATURE_DEFAULTS = {
    sticky_header: true,
    breadcrumbs:   true,
    scroll_to_top: true,
};

const PERFORMANCE_DEFAULTS = {
    disable_google_fonts:  false,
    disable_emoji:         false,
    remove_jquery_migrate: false,
};

const DEFAULT_LAYOUT_MODE = 'full-width';

function buildOptions(saved) {
    const data = saved && typeof saved === 'object' ? saved : {};
    const savedFeatures = data.features && typeof data.features === 'object' ? data.features : {};
    const savedPerf     = data.performance && typeof data.performance === 'object' ? data.performance : {};

    const features = {};
    for (const key of Object.keys(FEATURE_DEFAULTS)) {
        features[key] = savedFeatures[key] ?? FEATURE_DEFAULTS[key];
    }

    const performance = {};
    for (const key of Object.keys(PERFORMANCE_DEFAULTS)) {
        performance[key] = savedPerf[key] ?? PERFORMANCE_DEFAULTS[key];
    }

    return {
        layout_mode: LAYOUT_VALUES.includes(data.layout_mode) ? data.layout_mode : DEFAULT_LAYOUT_MODE,
        features,
        performance,
    };
}

function setLayoutMode(state, value) {
    return { ...state, layout_mode: value };
}

function setFeature(state, key, value) {
    return { ...state, features: { ...state.features, [key]: value } };
}

function setPerformance(state, key, value) {
    return { ...state, performance: { ...state.performance, [key]: value } };
}

module.exports = {
    LAYOUT_OPTIONS,
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
};
