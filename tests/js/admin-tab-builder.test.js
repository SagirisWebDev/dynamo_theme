require('./setup/jquery-stub');

describe('TabBuilder', () => {
    beforeEach(() => {
        delete global.TabBuilder;
        jest.resetModules();
        require('../../assets/js/admin-tab-builder');
    });

    test('window.TabBuilder is defined with a render method', () => {
        expect(global.TabBuilder).toBeDefined();
        expect(typeof global.TabBuilder.render).toBe('function');
    });

    function makeContainer(tabs) {
        const container = document.createElement('div');
        tabs.forEach((t) => {
            const div = document.createElement('div');
            div.id = t.contentId;
            document.body.appendChild(div);
        });
        return container;
    }

    const defaultTabs = [
        { id: 'tab-a', label: 'Alpha', contentId: 'content-a' },
        { id: 'tab-b', label: 'Beta', contentId: 'content-b' },
    ];

    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('render inserts a nav button for each tab', () => {
        const container = document.createElement('div');
        global.TabBuilder.render(container, { tabs: defaultTabs });
        const buttons = container.querySelectorAll('button');
        expect(buttons).toHaveLength(2);
        expect(buttons[0].textContent).toBe('Alpha');
        expect(buttons[1].textContent).toBe('Beta');
    });

    test('active tab content is visible, others are hidden on render', () => {
        const container = makeContainer(defaultTabs);
        global.TabBuilder.render(container, { tabs: defaultTabs });
        expect(document.getElementById('content-a').style.display).not.toBe('none');
        expect(document.getElementById('content-b').style.display).toBe('none');
    });

    test('clicking a tab shows its content and hides others', () => {
        const container = makeContainer(defaultTabs);
        global.TabBuilder.render(container, { tabs: defaultTabs });
        container.querySelectorAll('button')[1].click();
        expect(document.getElementById('content-b').style.display).not.toBe('none');
        expect(document.getElementById('content-a').style.display).toBe('none');
    });

    test('activeTab option sets a non-first tab as initially active', () => {
        const container = makeContainer(defaultTabs);
        global.TabBuilder.render(container, { tabs: defaultTabs, activeTab: 'tab-b' });
        const buttons = container.querySelectorAll('button');
        expect(buttons[1].classList.contains('active')).toBe(true);
        expect(buttons[0].classList.contains('active')).toBe(false);
        expect(document.getElementById('content-b').style.display).not.toBe('none');
        expect(document.getElementById('content-a').style.display).toBe('none');
    });

    test('clicking a tab button makes it active and deactivates others', () => {
        const container = makeContainer(defaultTabs);
        global.TabBuilder.render(container, { tabs: defaultTabs });
        const buttons = container.querySelectorAll('button');
        buttons[1].click();
        expect(buttons[1].classList.contains('active')).toBe(true);
        expect(buttons[0].classList.contains('active')).toBe(false);
    });

    test('first tab button is active by default', () => {
        const container = makeContainer(defaultTabs);
        global.TabBuilder.render(container, { tabs: defaultTabs });
        const buttons = container.querySelectorAll('button');
        expect(buttons[0].classList.contains('active')).toBe(true);
        expect(buttons[1].classList.contains('active')).toBe(false);
    });
});
