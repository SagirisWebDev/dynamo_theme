// Minimal jQuery stub for IIFE-based modules under jsdom
const listeners = {};

const $ = function (selector) {
    const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
    return {
        on(event, cb) {
            listeners[event] = listeners[event] || [];
            listeners[event].push(cb);
            return this;
        },
        off(event, cb) {
            if (listeners[event]) {
                listeners[event] = listeners[event].filter((fn) => fn !== cb);
            }
            return this;
        },
        trigger(event, data) {
            (listeners[event] || []).forEach((fn) => fn({}, data));
            return this;
        },
    };
};

$._listeners = listeners;

global.jQuery = $;
global.$ = $;
