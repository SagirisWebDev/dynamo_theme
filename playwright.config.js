// @ts-check
const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/frontend/ui',
    use: {
        baseURL: process.env.WP_BASE_URL || 'http://localhost:10017',
        headless: true,
    },
});
