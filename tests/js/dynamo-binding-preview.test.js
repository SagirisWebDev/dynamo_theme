const BINDING_PREVIEW_PATH = '../../assets/js/dynamo-binding-preview.js';

function mountWpCustomize(bindings) {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
    delete document.documentElement.style.cssText;

    const subscribers = {};
    const values = {};

    window.dynamoBindings = bindings;
    window.wp = {
        customize: function (settingId, ready) {
            const setting = {
                get: () => values[settingId],
                set: (newValue) => {
                    values[settingId] = newValue;
                    (subscribers[settingId] || []).forEach((cb) => cb(newValue));
                },
                bind: (cb) => {
                    subscribers[settingId] = (subscribers[settingId] || []).concat(cb);
                },
            };
            if (typeof ready === 'function') {
                ready(setting);
            }
            return setting;
        },
    };

    return { subscribers, values, fire: (settingId, newValue) => window.wp.customize(settingId).set(newValue) };
}

function loadPreview() {
    jest.resetModules();
    require(BINDING_PREVIEW_PATH);
}

describe('dynamo-binding-preview', () => {
    afterEach(() => {
        delete window.dynamoBindings;
        delete window.wp;
        document.head.innerHTML = '';
        document.body.innerHTML = '';
    });

    test('simple variable update: color binding pushes saved value into custom property', () => {
        const ctx = mountWpCustomize({
            dynamo_header_bg: {
                selector: '.site-header',
                property: 'background-color',
                type: 'color',
            },
        });
        loadPreview();
        ctx.fire('dynamo_header_bg', '#abcdef');
        expect(document.documentElement.style.getPropertyValue('--dynamo-header_bg')).toBe('#abcdef');
    });

    test('code binding extracts bound-property value from a full CSS block', () => {
        const ctx = mountWpCustomize({
            dynamo_card_shadow: {
                selector: '.card',
                property: 'box-shadow',
                type: 'code',
            },
        });
        loadPreview();
        ctx.fire(
            'dynamo_card_shadow',
            '.card {\n    box-shadow: 0 8px 24px rgba(0,0,0,0.3);\n}'
        );
        expect(document.documentElement.style.getPropertyValue('--dynamo-card_shadow'))
            .toBe('0 8px 24px rgba(0,0,0,0.3)');
    });

    test('code binding emits a per-binding <style> tag with sibling declarations', () => {
        const ctx = mountWpCustomize({
            dynamo_card_shadow: {
                selector: '.card',
                property: 'box-shadow',
                type: 'code',
            },
        });
        loadPreview();
        ctx.fire(
            'dynamo_card_shadow',
            '.card {\n    box-shadow: 0 4px 12px rgba(0,0,0,0.15);\n    border: 2px solid red;\n}'
        );
        const tag = document.getElementById('dynamo-binding-extras-card_shadow');
        expect(tag).not.toBeNull();
        expect(tag.tagName).toBe('STYLE');
        expect(tag.textContent).toBe('.card { border: 2px solid red; }');
    });

    test('code binding clears the extras tag when only bound prop remains', () => {
        const ctx = mountWpCustomize({
            dynamo_card_shadow: {
                selector: '.card',
                property: 'box-shadow',
                type: 'code',
            },
        });
        loadPreview();
        ctx.fire(
            'dynamo_card_shadow',
            '.card {\n    box-shadow: 0 4px 12px rgba(0,0,0,0.15);\n    border: 2px solid red;\n}'
        );
        ctx.fire(
            'dynamo_card_shadow',
            '.card {\n    box-shadow: 0 4px 12px rgba(0,0,0,0.15);\n}'
        );
        const tag = document.getElementById('dynamo-binding-extras-card_shadow');
        expect(tag.textContent).toBe('');
    });

    test('code binding reuses the same extras <style> tag across multiple changes', () => {
        const ctx = mountWpCustomize({
            dynamo_card_shadow: {
                selector: '.card',
                property: 'box-shadow',
                type: 'code',
            },
        });
        loadPreview();
        ctx.fire(
            'dynamo_card_shadow',
            '.card {\n    box-shadow: 0 4px 12px rgba(0,0,0,0.15);\n    transition: 200ms ease;\n}'
        );
        ctx.fire(
            'dynamo_card_shadow',
            '.card {\n    box-shadow: 0 4px 12px rgba(0,0,0,0.15);\n    border: 1px solid #000;\n}'
        );
        const tags = document.querySelectorAll('style[id^="dynamo-binding-extras-"]');
        expect(tags.length).toBe(1);
        expect(tags[0].textContent).toBe('.card { border: 1px solid #000; }');
    });

    test('code binding with bound prop missing pushes empty string into the variable', () => {
        const ctx = mountWpCustomize({
            dynamo_card_shadow: {
                selector: '.card',
                property: 'box-shadow',
                type: 'code',
            },
        });
        loadPreview();
        ctx.fire(
            'dynamo_card_shadow',
            '.card {\n    border: 2px solid red;\n}'
        );
        expect(document.documentElement.style.getPropertyValue('--dynamo-card_shadow')).toBe('');
        const tag = document.getElementById('dynamo-binding-extras-card_shadow');
        expect(tag.textContent).toBe('.card { border: 2px solid red; }');
    });

    test('range binding with unit appends the unit to the variable value', () => {
        const ctx = mountWpCustomize({
            dynamo_header_pad: {
                selector: '.site-header',
                property: 'padding-block',
                type: 'range',
                unit: 'rem',
            },
        });
        loadPreview();
        ctx.fire('dynamo_header_pad', '2.5');
        expect(document.documentElement.style.getPropertyValue('--dynamo-header_pad')).toBe('2.5rem');
    });

    test('content-bound binding wraps a bare string value in double quotes', () => {
        const ctx = mountWpCustomize({
            dynamo_sale_ends: {
                selector: '.banner::before',
                property: 'content',
                type: 'date',
            },
        });
        loadPreview();
        ctx.fire('dynamo_sale_ends', '2026-05-14');
        expect(document.documentElement.style.getPropertyValue('--dynamo-sale_ends'))
            .toBe('"2026-05-14"');
    });

    test('content-bound binding does not double-wrap an already-quoted value', () => {
        const ctx = mountWpCustomize({
            dynamo_before_text: {
                selector: '.banner::before',
                property: 'content',
                type: 'text',
            },
        });
        loadPreview();
        ctx.fire('dynamo_before_text', '"Hello"');
        expect(document.documentElement.style.getPropertyValue('--dynamo-before_text'))
            .toBe('"Hello"');
    });

    test('content-bound binding leaves CSS keywords unquoted', () => {
        const ctx = mountWpCustomize({
            dynamo_before_text: {
                selector: '.banner::before',
                property: 'content',
                type: 'text',
            },
        });
        loadPreview();
        ctx.fire('dynamo_before_text', 'none');
        expect(document.documentElement.style.getPropertyValue('--dynamo-before_text'))
            .toBe('none');
    });

    test('content-bound binding leaves CSS functions unwrapped', () => {
        const ctx = mountWpCustomize({
            dynamo_before_text: {
                selector: '.banner::before',
                property: 'content',
                type: 'text',
            },
        });
        loadPreview();
        ctx.fire('dynamo_before_text', 'counter(section)');
        expect(document.documentElement.style.getPropertyValue('--dynamo-before_text'))
            .toBe('counter(section)');
    });

    test('content-bound binding does not push a stray wrap when the value is empty', () => {
        const ctx = mountWpCustomize({
            dynamo_sale_ends: {
                selector: '.banner::before',
                property: 'content',
                type: 'date',
            },
        });
        loadPreview();
        ctx.fire('dynamo_sale_ends', '');
        expect(document.documentElement.style.getPropertyValue('--dynamo-sale_ends'))
            .toBe('');
    });

    test('url-type binding wraps the value with url(...) and single-quotes the URL', () => {
        const ctx = mountWpCustomize({
            dynamo_hero_bg: {
                selector: '.hero',
                property: 'background-image',
                type: 'url',
            },
        });
        loadPreview();
        ctx.fire('dynamo_hero_bg', 'http://localhost/hero.jpg');
        expect(document.documentElement.style.getPropertyValue('--dynamo-hero_bg'))
            .toBe("url('http://localhost/hero.jpg')");
    });

    test('image-type binding wraps the value with url(...)', () => {
        const ctx = mountWpCustomize({
            dynamo_logo_image: {
                selector: '.site-logo',
                property: 'background-image',
                type: 'image',
            },
        });
        loadPreview();
        ctx.fire('dynamo_logo_image', 'http://localhost/logo.png');
        expect(document.documentElement.style.getPropertyValue('--dynamo-logo_image'))
            .toBe("url('http://localhost/logo.png')");
    });

    test('url-type binding does not double-wrap an already-wrapped value', () => {
        const ctx = mountWpCustomize({
            dynamo_hero_bg: {
                selector: '.hero',
                property: 'background-image',
                type: 'url',
            },
        });
        loadPreview();
        ctx.fire('dynamo_hero_bg', "url('http://localhost/hero.jpg')");
        expect(document.documentElement.style.getPropertyValue('--dynamo-hero_bg'))
            .toBe("url('http://localhost/hero.jpg')");
    });

    test('url-type binding with empty value pushes empty without an empty url() wrap', () => {
        const ctx = mountWpCustomize({
            dynamo_hero_bg: {
                selector: '.hero',
                property: 'background-image',
                type: 'url',
            },
        });
        loadPreview();
        ctx.fire('dynamo_hero_bg', '');
        expect(document.documentElement.style.getPropertyValue('--dynamo-hero_bg'))
            .toBe('');
    });

    test('radio binding resolves a slug through choicesMap before setProperty', () => {
        const ctx = mountWpCustomize({
            dynamo_sidebar: {
                selector: '.site-content',
                property: 'grid-template-columns',
                type: 'radio',
                choicesMap: { left: '300px 1fr', full: '1fr' },
            },
        });
        loadPreview();
        ctx.fire('dynamo_sidebar', 'left');
        expect(document.documentElement.style.getPropertyValue('--dynamo-sidebar')).toBe('300px 1fr');
    });
});
