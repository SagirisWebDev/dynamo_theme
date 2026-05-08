require('./setup/jquery-stub');

describe('DynamoHooks', () => {
    beforeEach(() => {
        // Reset global state and re-require the module fresh each test
        delete global.DynamoHooks;
        jest.resetModules();
        require('../../assets/js/admin-hooks');
    });

    test('window.DynamoHooks is defined', () => {
        expect(global.DynamoHooks).toBeDefined();
    });

    test('triggerHook fires registered callback with data', () => {
        const cb = jest.fn();
        global.DynamoHooks.addHook('test-event', cb);
        global.DynamoHooks.triggerHook('test-event', { foo: 'bar' });
        expect(cb).toHaveBeenCalledWith(expect.anything(), { foo: 'bar' });
    });

    test('removeHook stops callback from firing', () => {
        const cb = jest.fn();
        global.DynamoHooks.addHook('test-event', cb);
        global.DynamoHooks.removeHook('test-event', cb);
        global.DynamoHooks.triggerHook('test-event', {});
        expect(cb).not.toHaveBeenCalled();
    });
});
