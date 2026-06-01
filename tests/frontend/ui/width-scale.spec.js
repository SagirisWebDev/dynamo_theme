// @ts-check
/**
 * Issue #35 — PRD v1.3.0 Slice 2: Complete Width Scale + alias helper + Customizer subsection
 *
 * Playwright browser tests covering:
 *
 *   AC4 — The block-editor Width dropdown lists all five labels:
 *         Narrow, Default, Wide, Container, Full.
 *
 * Prerequisites:
 *   - WordPress site running at WP_BASE_URL (default: http://localhost:10017)
 *   - assets/js/editor/token-presets.js is built and enqueued in the editor
 *   - dynamo_layout_width_presets filter returns all five presets
 *   - storageState populated by global-setup.js (auth cookie)
 */

const { test, expect } = require('@playwright/test');

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const BASE_URL     = process.env.WP_BASE_URL || 'http://localhost:10017';
const WP_ADMIN_URL = `${BASE_URL}/wp-admin`;
const WP_USER      = process.env.WP_ADMIN_USER     || 'admin';
const WP_PASSWORD  = process.env.WP_ADMIN_PASSWORD || 'admin';

// All five expected presets: { value, label }
const EXPECTED_PRESETS = [
    { value: 'narrow',    label: 'Narrow'    },
    { value: 'default',   label: 'Default'   },
    { value: 'wide',      label: 'Wide'      },
    { value: 'container', label: 'Container' },
    { value: 'full',      label: 'Full'      },
];

// ---------------------------------------------------------------------------
// Helpers (mirror width-preset.spec.js)
// ---------------------------------------------------------------------------

async function wpLogin(page) {
    await page.goto(`${WP_ADMIN_URL}/`);
    if (page.url().includes('wp-admin')) {
        return;
    }
    await page.goto(`${BASE_URL}/wp-login.php`);
    await page.fill('#user_login', WP_USER);
    await page.fill('#user_pass', WP_PASSWORD);
    await Promise.all([
        page.waitForURL(/\/wp-admin/),
        page.click('#wp-submit'),
    ]);
}

async function openNewEditorPage(page) {
    await page.goto(`${WP_ADMIN_URL}/post-new.php?post_type=page`);
    await page.waitForSelector(
        '.editor-canvas, .block-editor-writing-flow, iframe[name="editor-canvas"]',
        { timeout: 15000 }
    );
}

async function getEditorFrame(page) {
    const editorFrame = page.frameLocator('iframe[name="editor-canvas"]');
    try {
        await editorFrame.locator('body').waitFor({ timeout: 3000 });
        return editorFrame;
    } catch {
        return page;
    }
}

async function insertGroupBlock(page) {
    await page.evaluate(() => {
        const block = window.wp.blocks.createBlock(
            'core/group',
            { layout: { type: 'constrained' } },
            []
        );
        window.wp.data.dispatch('core/block-editor').insertBlock(block);
    });
    await page.waitForTimeout(800);
}

async function selectGroupBlock(editorFrame) {
    const groupBlock = editorFrame.locator('[data-type="core/group"]').first();
    await groupBlock.waitFor({ timeout: 8000 });
    await groupBlock.click();
    return groupBlock;
}

async function openDynamoPanel(page) {
    const btn = page.getByRole('button', { name: /dynamo/i });
    await btn.waitFor({ timeout: 10000 });
    const expanded = await btn.getAttribute('aria-expanded');
    if (expanded !== 'true') {
        await btn.click();
    }
}

// ---------------------------------------------------------------------------
// AC4 — Block editor Width dropdown lists all five labels
// ---------------------------------------------------------------------------

test.describe('Issue #35 AC4 — Width dropdown has all five preset options', () => {
    test('Width dropdown contains exactly five non-placeholder options', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertGroupBlock(page);
        await selectGroupBlock(editorFrame);

        await openDynamoPanel(page);

        const widthSelect = page.getByRole('combobox', { name: /width/i });
        await expect(widthSelect).toBeVisible({ timeout: 10000 });

        // Exclude the blank/default placeholder option (value="")
        const optionCount = await widthSelect.locator('option:not([value=""])').count();
        expect(optionCount).toBe(5);
    });

    for (const { value, label } of EXPECTED_PRESETS) {
        test(`Width dropdown has a "${label}" option (value="${value}")`, async ({ page }) => {
            await wpLogin(page);
            await openNewEditorPage(page);

            const editorFrame = await getEditorFrame(page);
            await insertGroupBlock(page);
            await selectGroupBlock(editorFrame);

            await openDynamoPanel(page);

            const widthSelect = page.getByRole('combobox', { name: /width/i });
            await expect(widthSelect).toBeVisible({ timeout: 10000 });

            const option = widthSelect.locator(`option[value="${value}"]`);
            await expect(option).toBeAttached();
            await expect(option).toHaveText(label);
        });
    }

    test('selecting "Default" preset updates block attribute to dynamoWidth="default"', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertGroupBlock(page);
        await selectGroupBlock(editorFrame);

        await openDynamoPanel(page);

        const widthSelect = page.getByRole('combobox', { name: /width/i });
        await widthSelect.selectOption('default');

        const markup = await page.evaluate(() => {
            const blocks = window.wp.data.select('core/block-editor').getBlocks();
            const group  = blocks.find((b) => b.name === 'core/group');
            if (!group) return null;
            return window.wp.blocks.serialize([group]);
        });

        expect(markup).not.toBeNull();
        expect(markup).toContain('"dynamoWidth":"default"');
    });

    test('selecting "Wide" preset updates block attribute to dynamoWidth="wide"', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertGroupBlock(page);
        await selectGroupBlock(editorFrame);

        await openDynamoPanel(page);

        const widthSelect = page.getByRole('combobox', { name: /width/i });
        await widthSelect.selectOption('wide');

        const markup = await page.evaluate(() => {
            const blocks = window.wp.data.select('core/block-editor').getBlocks();
            const group  = blocks.find((b) => b.name === 'core/group');
            if (!group) return null;
            return window.wp.blocks.serialize([group]);
        });

        expect(markup).not.toBeNull();
        expect(markup).toContain('"dynamoWidth":"wide"');
    });

    test('selecting "Container" preset applies --dynamo-layout-width-container CSS var in save output', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertGroupBlock(page);
        await selectGroupBlock(editorFrame);

        await openDynamoPanel(page);

        const widthSelect = page.getByRole('combobox', { name: /width/i });
        await widthSelect.selectOption('container');

        const markup = await page.evaluate(() => {
            const blocks = window.wp.data.select('core/block-editor').getBlocks();
            const group  = blocks.find((b) => b.name === 'core/group');
            if (!group) return null;
            return window.wp.blocks.serialize([group]);
        });

        expect(markup).not.toBeNull();
        expect(markup).toContain('var(--dynamo-layout-width-container)');
    });

    test('selecting "Full" preset applies --dynamo-layout-width-full CSS var in save output', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertGroupBlock(page);
        await selectGroupBlock(editorFrame);

        await openDynamoPanel(page);

        const widthSelect = page.getByRole('combobox', { name: /width/i });
        await widthSelect.selectOption('full');

        const markup = await page.evaluate(() => {
            const blocks = window.wp.data.select('core/block-editor').getBlocks();
            const group  = blocks.find((b) => b.name === 'core/group');
            if (!group) return null;
            return window.wp.blocks.serialize([group]);
        });

        expect(markup).not.toBeNull();
        expect(markup).toContain('var(--dynamo-layout-width-full)');
    });
});
