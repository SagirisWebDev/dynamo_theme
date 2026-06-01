// @ts-check
const { chromium } = require('@playwright/test');
const path = require('path');

module.exports = async function globalSetup() {
    const baseUrl  = process.env.WP_BASE_URL  || 'http://localhost:10017';
    const user     = process.env.WP_ADMIN_USER     || 'admin';
    const password = process.env.WP_ADMIN_PASSWORD || 'admin';

    const browser = await chromium.launch();
    const page    = await browser.newPage();

    await page.goto(`${baseUrl}/wp-login.php`);
    await page.fill('#user_login', user);
    await page.fill('#user_pass', password);
    await Promise.all([
        page.waitForURL(/\/wp-admin/),
        page.click('#wp-submit'),
    ]);

    await page.context().storageState({
        path: path.resolve(__dirname, '.auth-state.json'),
    });
    await browser.close();
};
