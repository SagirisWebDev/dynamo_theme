require('./setup/jquery-stub');

describe('DynamoState', () => {
    beforeEach(() => {
        delete global.DynamoState;
        delete global.DynamoHooks;
        jest.resetModules();
        require('../../assets/js/admin-hooks');
        global.DynamoHooks.triggerHook = jest.fn();
        require('../../assets/js/admin-state');
    });

    test('window.DynamoState is defined', () => {
        expect(global.DynamoState).toBeDefined();
    });

    test('getOptions returns empty object initially', () => {
        expect(global.DynamoState.getOptions()).toEqual({});
    });

    test('saveOptions fires save-start before POST', async () => {
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ message: 'Saved.' }),
        });
        await global.DynamoState.saveOptions();
        const calls = global.DynamoHooks.triggerHook.mock.calls;
        const saveStartIndex = calls.findIndex((c) => c[0] === 'save-start');
        const saveCompleteIndex = calls.findIndex((c) => c[0] === 'save-complete');
        expect(saveStartIndex).toBeGreaterThanOrEqual(0);
        expect(saveStartIndex).toBeLessThan(saveCompleteIndex);
    });

    test('saveOptions fires save-complete with success on successful POST', async () => {
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ message: 'Saved.' }),
        });
        await global.DynamoState.saveOptions();
        expect(global.DynamoHooks.triggerHook).toHaveBeenCalledWith(
            'save-complete',
            { success: true, message: 'Saved.' }
        );
    });

    test('saveOptions fires save-complete with error on failed POST', async () => {
        global.fetch = jest.fn().mockResolvedValue({
            ok: false,
            json: async () => ({ message: 'Unauthorized.' }),
        });
        await global.DynamoState.saveOptions();
        expect(global.DynamoHooks.triggerHook).toHaveBeenCalledWith(
            'save-complete',
            expect.objectContaining({ success: false, error: expect.any(String) })
        );
    });

    test('updateOption updates state retrievable via getOptions', () => {
        global.DynamoState.updateOption('color', 'red');
        expect(global.DynamoState.getOptions().color).toBe('red');
    });

    test('updateOption fires option-changed hook with key and value', () => {
        global.DynamoState.updateOption('size', 'large');
        expect(global.DynamoHooks.triggerHook).toHaveBeenCalledWith('option-changed', { key: 'size', value: 'large' });
    });

    test('init fires options-loaded hook with fetched data', async () => {
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ color: 'blue' }),
        });
        await global.DynamoState.init();
        expect(global.DynamoHooks.triggerHook).toHaveBeenCalledWith('options-loaded', { color: 'blue' });
    });

    test('init populates state from fetch response', async () => {
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ color: 'blue', size: 'large' }),
        });
        await global.DynamoState.init();
        expect(global.DynamoState.getOptions()).toEqual({ color: 'blue', size: 'large' });
    });
});
