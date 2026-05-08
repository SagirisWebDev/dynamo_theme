require('./setup/jquery-stub');

describe('DynamoUI', () => {
    let hooks;
    let container;

    beforeEach(() => {
        delete global.DynamoUI;
        jest.resetModules();

        hooks = {};
        global.DynamoHooks = {
            addHook: jest.fn((name, cb) => {
                hooks[name] = hooks[name] || [];
                hooks[name].push(cb);
            }),
            triggerHook: jest.fn(),
        };

        function fireHook(name, data) {
            (hooks[name] || []).forEach((cb) => cb(data));
        }
        global._fireHook = fireHook;
        global.DynamoState = {
            updateOption: jest.fn(),
            saveOptions: jest.fn(),
        };

        require('../../assets/js/admin-ui');
        container = document.createElement('div');
        document.body.appendChild(container);
        global.DynamoUI.init(container);
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('window.DynamoUI is defined with an init method', () => {
        expect(global.DynamoUI).toBeDefined();
        expect(typeof global.DynamoUI.init).toBe('function');
    });

    test('options-loaded renders a layout select in the container', () => {
        global._fireHook('options-loaded', { layout_mode: 'boxed' });
        expect(container.querySelector('select[name="layout_mode"]')).not.toBeNull();
    });

    test('save button is rendered in the container', () => {
        global._fireHook('options-loaded', { layout_mode: 'boxed' });
        expect(container.querySelector('button[data-action="save"]')).not.toBeNull();
    });

    test('clicking save button calls DynamoState.saveOptions', () => {
        global._fireHook('options-loaded', { layout_mode: 'boxed' });
        container.querySelector('button[data-action="save"]').click();
        expect(global.DynamoState.saveOptions).toHaveBeenCalled();
    });

    test('option-changed for layout_mode updates the dropdown value', () => {
        global._fireHook('options-loaded', { layout_mode: 'boxed' });
        global._fireHook('option-changed', { key: 'layout_mode', value: 'full-width' });
        const select = container.querySelector('select[name="layout_mode"]');
        expect(select.value).toBe('full-width');
    });

    test('option-changed for a different key does not affect the dropdown', () => {
        global._fireHook('options-loaded', { layout_mode: 'boxed' });
        global._fireHook('option-changed', { key: 'font_size', value: '16px' });
        const select = container.querySelector('select[name="layout_mode"]');
        expect(select.value).toBe('boxed');
    });

    test('changing the dropdown calls DynamoState.updateOption with layout_mode', () => {
        global._fireHook('options-loaded', { layout_mode: 'boxed' });
        const select = container.querySelector('select[name="layout_mode"]');
        select.value = 'sidebar-right';
        select.dispatchEvent(new Event('change'));
        expect(global.DynamoState.updateOption).toHaveBeenCalledWith('layout_mode', 'sidebar-right');
    });

    test('dropdown initial value reflects layout_mode from loaded options', () => {
        global._fireHook('options-loaded', { layout_mode: 'sidebar-left' });
        const select = container.querySelector('select[name="layout_mode"]');
        expect(select.value).toBe('sidebar-left');
    });

    test('option-changed for features updates all checkbox checked states', () => {
        global._fireHook('options-loaded', {
            layout_mode: 'boxed',
            features: { sticky_header: true, breadcrumbs: false, scroll_to_top: false },
        });
        global._fireHook('option-changed', {
            key: 'features',
            value: { sticky_header: false, breadcrumbs: true, scroll_to_top: true },
        });
        const cb = (key) => container.querySelector(`input[data-feature="${key}"]`);
        expect(cb('sticky_header').checked).toBe(false);
        expect(cb('breadcrumbs').checked).toBe(true);
        expect(cb('scroll_to_top').checked).toBe(true);
    });

    test('option-changed for a non-features key leaves checkboxes unchanged', () => {
        global._fireHook('options-loaded', {
            layout_mode: 'boxed',
            features: { sticky_header: true, breadcrumbs: false, scroll_to_top: false },
        });
        global._fireHook('option-changed', { key: 'layout_mode', value: 'full-width' });
        const cb = container.querySelector('input[data-feature="sticky_header"]');
        expect(cb.checked).toBe(true);
    });

    test('toggling a feature checkbox calls updateOption with merged features', () => {
        global._fireHook('options-loaded', {
            layout_mode: 'boxed',
            features: { sticky_header: true, breadcrumbs: false, scroll_to_top: true },
        });
        const cb = container.querySelector('input[data-feature="breadcrumbs"]');
        cb.checked = true;
        cb.dispatchEvent(new Event('change'));
        expect(global.DynamoState.updateOption).toHaveBeenCalledWith('features', {
            sticky_header: true,
            breadcrumbs: true,
            scroll_to_top: true,
        });
    });

    test('feature checkboxes reflect current state from options.features', () => {
        global._fireHook('options-loaded', {
            layout_mode: 'boxed',
            features: { sticky_header: true, breadcrumbs: false, scroll_to_top: true },
        });
        const cb = (key) => container.querySelector(`input[data-feature="${key}"]`);
        expect(cb('sticky_header').checked).toBe(true);
        expect(cb('breadcrumbs').checked).toBe(false);
        expect(cb('scroll_to_top').checked).toBe(true);
    });

    test('options-loaded renders three feature checkboxes', () => {
        global._fireHook('options-loaded', {
            layout_mode: 'boxed',
            features: { sticky_header: true, breadcrumbs: false, scroll_to_top: true },
        });
        const checkboxes = container.querySelectorAll('input[type="checkbox"][data-feature]');
        expect(checkboxes).toHaveLength(3);
    });

    test('layout select contains the four layout options', () => {
        global._fireHook('options-loaded', { layout_mode: 'full-width' });
        const options = Array.from(container.querySelectorAll('select[name="layout_mode"] option'));
        const values = options.map((o) => o.value);
        expect(values).toEqual(expect.arrayContaining(['full-width', 'boxed', 'sidebar-left', 'sidebar-right']));
        expect(options).toHaveLength(4);
    });

    test('option-changed for performance updates all performance checkbox states', () => {
        global._fireHook('options-loaded', {
            layout_mode: 'boxed',
            performance: { disable_google_fonts: false, disable_emoji: false, remove_jquery_migrate: false },
        });
        global._fireHook('option-changed', {
            key: 'performance',
            value: { disable_google_fonts: true, disable_emoji: true, remove_jquery_migrate: false },
        });
        const cb = (key) => container.querySelector(`input[data-performance="${key}"]`);
        expect(cb('disable_google_fonts').checked).toBe(true);
        expect(cb('disable_emoji').checked).toBe(true);
        expect(cb('remove_jquery_migrate').checked).toBe(false);
    });

    test('option-changed for non-performance key leaves performance checkboxes unchanged', () => {
        global._fireHook('options-loaded', {
            layout_mode: 'boxed',
            performance: { disable_google_fonts: true, disable_emoji: false, remove_jquery_migrate: false },
        });
        global._fireHook('option-changed', { key: 'layout_mode', value: 'full-width' });
        const cb = container.querySelector('input[data-performance="disable_google_fonts"]');
        expect(cb.checked).toBe(true);
    });

    test('toggling a performance checkbox calls updateOption with merged performance options', () => {
        global._fireHook('options-loaded', {
            layout_mode: 'boxed',
            performance: { disable_google_fonts: false, disable_emoji: false, remove_jquery_migrate: false },
        });
        const cb = container.querySelector('input[data-performance="disable_google_fonts"]');
        cb.checked = true;
        cb.dispatchEvent(new Event('change'));
        expect(global.DynamoState.updateOption).toHaveBeenCalledWith('performance', {
            disable_google_fonts: true,
            disable_emoji: false,
            remove_jquery_migrate: false,
        });
    });

    test('performance checkboxes reflect current state from options.performance', () => {
        global._fireHook('options-loaded', {
            layout_mode: 'boxed',
            performance: { disable_google_fonts: true, disable_emoji: false, remove_jquery_migrate: true },
        });
        const cb = (key) => container.querySelector(`input[data-performance="${key}"]`);
        expect(cb('disable_google_fonts').checked).toBe(true);
        expect(cb('disable_emoji').checked).toBe(false);
        expect(cb('remove_jquery_migrate').checked).toBe(true);
    });

    test('options-loaded renders three performance checkboxes', () => {
        global._fireHook('options-loaded', {
            layout_mode: 'boxed',
            performance: { disable_google_fonts: false, disable_emoji: false, remove_jquery_migrate: false },
        });
        const checkboxes = container.querySelectorAll('input[type="checkbox"][data-performance]');
        expect(checkboxes).toHaveLength(3);
    });

    test('notice auto-dismisses after 5 seconds', () => {
        jest.useFakeTimers();
        global._fireHook('options-loaded', { layout_mode: 'boxed' });
        global._fireHook('save-complete', { success: true, message: 'Saved.' });
        expect(container.querySelector('.notice')).not.toBeNull();
        jest.advanceTimersByTime(5000);
        expect(container.querySelector('.notice')).toBeNull();
        jest.useRealTimers();
    });

    test('notice has a dismiss button that removes the notice when clicked', () => {
        global._fireHook('options-loaded', { layout_mode: 'boxed' });
        global._fireHook('save-complete', { success: true, message: 'Saved.' });
        const notice = container.querySelector('.notice');
        const dismiss = notice.querySelector('button[data-action="dismiss"]');
        expect(dismiss).not.toBeNull();
        dismiss.click();
        expect(container.querySelector('.notice')).toBeNull();
    });

    test('save-complete with failure renders an error notice containing the error', () => {
        global._fireHook('options-loaded', { layout_mode: 'boxed' });
        global._fireHook('save-complete', { success: false, error: 'Something went wrong.' });
        const notice = container.querySelector('.notice.notice-error');
        expect(notice).not.toBeNull();
        expect(notice.textContent).toContain('Something went wrong.');
    });

    test('save-complete with success renders a success notice containing the message', () => {
        global._fireHook('options-loaded', { layout_mode: 'boxed' });
        global._fireHook('save-complete', { success: true, message: 'Settings saved.' });
        const notice = container.querySelector('.notice.notice-success');
        expect(notice).not.toBeNull();
        expect(notice.textContent).toContain('Settings saved.');
    });

    test('save-complete re-enables save button and restores text to Save Settings', () => {
        global._fireHook('options-loaded', { layout_mode: 'boxed' });
        global._fireHook('save-start');
        global._fireHook('save-complete', { success: true, message: 'Saved.' });
        const btn = container.querySelector('button[data-action="save"]');
        expect(btn.disabled).toBe(false);
        expect(btn.textContent).toBe('Save Settings');
    });

    test('save-start disables save button and changes text to Saving…', () => {
        global._fireHook('options-loaded', { layout_mode: 'boxed' });
        global._fireHook('save-start');
        const btn = container.querySelector('button[data-action="save"]');
        expect(btn.disabled).toBe(true);
        expect(btn.textContent).toBe('Saving…');
    });
});
