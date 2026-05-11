const NAV_PATH = '../../assets/js/primary-nav.js';

function mount() {
    document.body.innerHTML = `
        <header>
            <div class="menu-primary-container">
                <button type="button" class="dynamo-menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                    <span class="screen-reader-text">Menu</span>
                </button>
                <ul id="primary-menu">
                    <li><a href="#">Home</a></li>
                </ul>
            </div>
        </header>
        <main><a id="elsewhere" href="#">Outside</a></main>
    `;
    return {
        container: document.querySelector('.menu-primary-container'),
        btn: document.querySelector('.dynamo-menu-toggle'),
        outside: document.querySelector('#elsewhere'),
    };
}

function setViewportWidth(px) {
    Object.defineProperty(window, 'innerWidth', {
        configurable: true,
        writable: true,
        value: px,
    });
}

function loadModule() {
    jest.resetModules();
    require(NAV_PATH);
}

beforeEach(() => {
    setViewportWidth(800);
});

describe('primary-nav toggle', () => {
    test('clicking the toggle opens the container and updates aria-expanded', () => {
        const { container, btn } = mount();
        loadModule();

        btn.click();

        expect(container.classList.contains('is-open')).toBe(true);
        expect(btn.getAttribute('aria-expanded')).toBe('true');
    });

    test('clicking the toggle a second time closes the container', () => {
        const { container, btn } = mount();
        loadModule();

        btn.click();
        btn.click();

        expect(container.classList.contains('is-open')).toBe(false);
        expect(btn.getAttribute('aria-expanded')).toBe('false');
    });

    test('clicking outside the container closes an open menu', () => {
        const { container, btn, outside } = mount();
        loadModule();

        btn.click();
        outside.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        expect(container.classList.contains('is-open')).toBe(false);
    });

    test('clicking outside is a no-op when the menu is already closed', () => {
        const { container, outside } = mount();
        loadModule();

        outside.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        expect(container.classList.contains('is-open')).toBe(false);
    });

    test('clicking the toggle does not bubble to the outside-click handler', () => {
        const { container, btn } = mount();
        loadModule();

        btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        expect(container.classList.contains('is-open')).toBe(true);
    });

    test('Escape closes an open menu and refocuses the toggle', () => {
        const { container, btn } = mount();
        loadModule();

        btn.click();
        document.querySelector('#elsewhere').focus();

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));

        expect(container.classList.contains('is-open')).toBe(false);
        expect(document.activeElement).toBe(btn);
    });

    test('resize past 921px closes an open menu', () => {
        const { container, btn } = mount();
        loadModule();

        btn.click();
        setViewportWidth(1200);
        window.dispatchEvent(new Event('resize'));

        expect(container.classList.contains('is-open')).toBe(false);
    });

    test('resize within mobile range leaves an open menu open', () => {
        const { container, btn } = mount();
        loadModule();

        btn.click();
        setViewportWidth(600);
        window.dispatchEvent(new Event('resize'));

        expect(container.classList.contains('is-open')).toBe(true);
    });

    test('exactly 921px does not close (boundary)', () => {
        const { container, btn } = mount();
        loadModule();

        btn.click();
        setViewportWidth(921);
        window.dispatchEvent(new Event('resize'));

        expect(container.classList.contains('is-open')).toBe(true);
    });

    test('does not throw when .menu-primary-container is absent', () => {
        document.body.innerHTML = '<main>no menu</main>';
        expect(() => loadModule()).not.toThrow();
    });

    test('does not throw when toggle button is absent', () => {
        document.body.innerHTML = '<div class="menu-primary-container"></div>';
        expect(() => loadModule()).not.toThrow();
    });
});
