require('./setup/jquery-stub');

describe('DynamoAdmin', () => {
    let mount;

    beforeEach(() => {
        jest.resetModules();

        global.TabBuilder = { render: jest.fn() };
        global.DynamoUI = { init: jest.fn() };
        global.DynamoState = { init: jest.fn() };
        global.DynamoHooks = {
            addHook: jest.fn(),
            triggerHook: jest.fn(),
        };

        mount = document.createElement('div');
        mount.id = 'dynamo-admin';
        document.body.appendChild(mount);

        require('../../assets/js/admin');
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('DynamoAdmin is defined with an init method', () => {
        expect(global.DynamoAdmin).toBeDefined();
        expect(typeof global.DynamoAdmin.init).toBe('function');
    });

    test('init calls TabBuilder.render with Layout, Features, and Performance tabs', () => {
        global.DynamoAdmin.init();
        expect(global.TabBuilder.render).toHaveBeenCalledTimes(1);
        const [, config] = global.TabBuilder.render.mock.calls[0];
        const labels = config.tabs.map((t) => t.label);
        expect(labels).toEqual(['Layout', 'Features', 'Performance']);
    });

    test('init calls DynamoUI.init with the mount element', () => {
        global.DynamoAdmin.init();
        expect(global.DynamoUI.init).toHaveBeenCalledWith(mount);
    });

    test('init calls DynamoUI.init before DynamoState.init', () => {
        const order = [];
        global.DynamoUI.init.mockImplementation(() => order.push('ui'));
        global.DynamoState.init.mockImplementation(() => order.push('state'));
        global.DynamoAdmin.init();
        expect(order).toEqual(['ui', 'state']);
    });

    test('init calls DynamoState.init', () => {
        global.DynamoAdmin.init();
        expect(global.DynamoState.init).toHaveBeenCalledTimes(1);
    });
});
