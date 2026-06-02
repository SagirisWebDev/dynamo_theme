// @ts-check
/**
 * Issue #36 — PRD v1.3.0 Slice 3: Radius Preset tracer — "Large" option end-to-end
 *
 * Playwright browser tests covering:
 *
 *   AC4 (editor) — A block that declares `borders.radius` support (Cover block)
 *                  shows a "Radius" dropdown in the "Dynamo" InspectorControls
 *                  panel whose only selectable option is "Large".
 *                  A layout-supporting block (Group) still shows the "Width"
 *                  dropdown (regression guard for Slice 1).
 *
 *   AC7 (frontend) — On a page where the Cover block has been saved with
 *                    `dynamoRadius: "lg"`, the block on the frontend has
 *                    `border-radius: 0.5rem` in its computed style (via
 *                    `--dynamo-borders-radius-lg`). The CSS custom property
 *                    `--dynamo-borders-radius-lg` must also be defined on :root.
 *
 * Prerequisites (must be true for tests to pass — not yet implemented):
 *   - WordPress site running at http://aaachinese.local (or WP_BASE_URL env var)
 *   - `assets/js/editor/token-presets.js` is built and enqueued in the editor
 *   - `dynamo_border_radius_presets` filter is registered and returns the lg preset
 *   - The `render_block` filter for `borders.radius`-supporting blocks injects
 *     `border-radius: var(--dynamo-borders-radius-lg)` server-side (AC7)
 *
 * Site status at time of RED-phase authoring: implementation does not exist.
 * Tests are written per spec and will fail until the production code is in place.
 */

const { test, expect } = require('@playwright/test');

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const BASE_URL     = process.env.WP_BASE_URL || 'http://aaachinese.local';
const WP_ADMIN_URL = `${BASE_URL}/wp-admin`;

const WP_USER     = process.env.WP_ADMIN_USER     || 'admin';
const WP_PASSWORD = process.env.WP_ADMIN_PASSWORD || 'admin';

// ---------------------------------------------------------------------------
// Shared helpers (mirrors the pattern from width-preset.spec.js)
// ---------------------------------------------------------------------------

/**
 * Log in to WordPress and land on the dashboard.
 * Skips the login form if the session cookie is already valid.
 */
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

/**
 * Navigate to a new blank page in the block editor.
 */
async function openNewEditorPage(page) {
    await page.goto(`${WP_ADMIN_URL}/post-new.php?post_type=page`);
    await page.waitForSelector(
        '.editor-canvas, .block-editor-writing-flow, iframe[name="editor-canvas"]',
        { timeout: 15000 }
    );
}

/**
 * Return a reference to the editor iframe inner frame (FSE / iframed editor),
 * or the page itself when the editor is not iframed.
 */
async function getEditorFrame(page) {
    const editorFrame = page.frameLocator('iframe[name="editor-canvas"]');
    try {
        await editorFrame.locator('body').waitFor({ timeout: 3000 });
        return editorFrame;
    } catch {
        return page;
    }
}

/**
 * Ensure the "Dynamo" InspectorControls panel is expanded.
 */
async function openDynamoPanel(page) {
    const btn = page.getByRole('button', { name: /dynamo/i });
    await btn.waitFor({ timeout: 10000 });
    const expanded = await btn.getAttribute('aria-expanded');
    if (expanded !== 'true') {
        await btn.click();
    }
}

/**
 * Insert a Cover block programmatically into the editor.
 */
async function insertCoverBlock(page) {
    await page.evaluate(() => {
        const block = window.wp.blocks.createBlock('core/cover', {}, []);
        window.wp.data.dispatch('core/block-editor').insertBlock(block);
    });
    await page.waitForTimeout(800);
}

/**
 * Insert a Group block programmatically into the editor.
 */
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

/**
 * Select the first block of a given type in the editor canvas.
 */
async function selectBlock(editorFrame, blockType) {
    const block = editorFrame.locator(`[data-type="${blockType}"]`).first();
    await block.waitFor({ timeout: 8000 });
    await block.click();
    return block;
}

/**
 * Serialize the first block of a given type in the editor to its HTML comment markup.
 */
async function serializeFirstBlock(page, blockType) {
    return page.evaluate((type) => {
        const blocks = window.wp.data.select('core/block-editor').getBlocks();
        const block  = blocks.find((b) => b.name === type);
        if (!block) return null;
        return window.wp.blocks.serialize([block]);
    }, blockType);
}

/**
 * Dismiss a cookie consent banner if present (silently skips if absent).
 */
async function dismissConsentBanner(page) {
    try {
        const acceptBtn = page.locator([
            '.cc-nb-okagree',
            '.cmplz-accept',
            '.cmplz-btn.cmplz-accept',
            '[data-cc="accept-all"]',
            'button.accept-all',
            'button[aria-label*="ccept"]',
        ].join(', ')).first();
        await acceptBtn.click({ timeout: 4000 });
    } catch {
        // No banner present — continue
    }
}

/**
 * Publish the current page in the block editor and return the live frontend URL.
 */
async function publishAndGetFrontendUrl(page) {
    await page.getByRole('button', { name: /^publish$/i }).first().click();

    const panel = page.locator('.editor-post-publish-panel');
    await panel.waitFor({ timeout: 10000 });

    await panel.getByRole('button', { name: /^publish$/i }).click();

    const viewPageLink = page.getByRole('link', { name: /view page/i });
    await expect(viewPageLink).toBeVisible({ timeout: 15000 });

    const postId = await page.evaluate(() =>
        window.wp.data.select('core/editor').getCurrentPostId()
    );

    if (postId) {
        return `${BASE_URL}/?page_id=${postId}&preview=true`;
    }

    const rawHref = await viewPageLink.getAttribute('href');
    return rawHref.replace(/^https?:\/\/[^/]+/, BASE_URL);
}

// ---------------------------------------------------------------------------
// AC4 — Block editor shows "Dynamo" panel with "Radius" dropdown on Cover block
// ---------------------------------------------------------------------------

test.describe('Issue #36 AC4 — Radius dropdown in Dynamo panel (Cover block)', () => {
    test('Cover block inspector shows a "Dynamo" panel', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertCoverBlock(page);
        await selectBlock(editorFrame, 'core/cover');

        const dynamoPanel = page.getByRole('button', { name: /dynamo/i });
        await expect(dynamoPanel).toBeVisible({ timeout: 10000 });
    });

    test('Dynamo panel contains a "Radius" control label for Cover block', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertCoverBlock(page);
        await selectBlock(editorFrame, 'core/cover');

        await openDynamoPanel(page);

        const radiusLabel = page.getByText('Radius', { exact: true });
        await expect(radiusLabel).toBeVisible({ timeout: 10000 });
    });

    test('Radius dropdown contains a "Large" option', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertCoverBlock(page);
        await selectBlock(editorFrame, 'core/cover');

        await openDynamoPanel(page);

        const radiusSelect = page.getByRole('combobox', { name: /radius/i });
        await expect(radiusSelect).toBeVisible({ timeout: 10000 });

        const largeOption = radiusSelect.locator('option[value="lg"]');
        await expect(largeOption).toBeAttached();
        await expect(largeOption).toHaveText('Large');
    });

    test('Radius dropdown has exactly one non-empty option out of the box (Large only)', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertCoverBlock(page);
        await selectBlock(editorFrame, 'core/cover');

        await openDynamoPanel(page);

        const radiusSelect = page.getByRole('combobox', { name: /radius/i });
        await expect(radiusSelect).toBeVisible({ timeout: 10000 });

        // Exclude the empty placeholder option (value="")
        const optionCount = await radiusSelect.locator('option:not([value=""])').count();
        expect(optionCount).toBe(1);
    });

    // Regression guard: Group block (layout support) still shows Width dropdown
    test('Group block still shows Width dropdown in Dynamo panel (Slice 1 regression guard)', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertGroupBlock(page);
        await selectBlock(editorFrame, 'core/group');

        await openDynamoPanel(page);

        const widthSelect = page.getByRole('combobox', { name: /width/i });
        await expect(widthSelect).toBeVisible({ timeout: 10000 });
    });
});

// ---------------------------------------------------------------------------
// AC4 (attribute persistence) — Selecting "Large" persists dynamoRadius: "lg"
// ---------------------------------------------------------------------------

test.describe('Issue #36 AC4/AC5 — Selecting Large persists the attribute and style', () => {
    test('selecting Large updates block attributes to include dynamoRadius="lg"', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertCoverBlock(page);
        await selectBlock(editorFrame, 'core/cover');

        await openDynamoPanel(page);

        const radiusSelect = page.getByRole('combobox', { name: /radius/i });
        await radiusSelect.selectOption('lg');

        const markup = await serializeFirstBlock(page, 'core/cover');
        expect(markup).not.toBeNull();
        expect(markup).toContain('"dynamoRadius":"lg"');
    });

    test('block save output includes border-radius var after selecting Large', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertCoverBlock(page);
        await selectBlock(editorFrame, 'core/cover');

        await openDynamoPanel(page);

        const radiusSelect = page.getByRole('combobox', { name: /radius/i });
        await radiusSelect.selectOption('lg');

        const markup = await serializeFirstBlock(page, 'core/cover');
        expect(markup).not.toBeNull();
        expect(markup).toContain('var(--dynamo-borders-radius-lg)');
    });
});

// ---------------------------------------------------------------------------
// AC7 — Published page renders the block with border-radius: 0.5rem
// ---------------------------------------------------------------------------

test.describe('Issue #36 AC7 — Published page renders border-radius 0.5rem', () => {
    /**
     * Full end-to-end: create a page with a Cover block set to dynamoRadius: "lg",
     * publish it, then visit the frontend and assert the computed border-radius is 0.5rem.
     */
    test('cover block renders with computed border-radius 0.5rem on the frontend', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertCoverBlock(page);
        await selectBlock(editorFrame, 'core/cover');

        // Set radius to large
        await openDynamoPanel(page);
        const radiusSelect = page.getByRole('combobox', { name: /radius/i });
        await radiusSelect.selectOption('lg');

        // Add a distinctive class via Advanced > Additional CSS class
        const advancedPanel = page.getByRole('button', { name: /advanced/i });
        await advancedPanel.click().catch(() => {});
        const cssClassInput = page.getByLabel(/additional css class/i);
        if (await cssClassInput.isVisible()) {
            await cssClassInput.fill('test-radius-lg-cover');
        }

        // Publish and navigate to the live page
        const frontendUrl = await publishAndGetFrontendUrl(page);
        await page.goto(frontendUrl);

        await dismissConsentBanner(page);

        // Find the cover block by its class or by inline border-radius style.
        const coverBlock = page
            .locator('.test-radius-lg-cover, .wp-block-cover[style*="border-radius"]')
            .first();
        await expect(coverBlock).toBeAttached({ timeout: 10000 });

        // Assert computed border-radius is 0.5rem (8px)
        const computedBorderRadius = await coverBlock.evaluate((el) => {
            return window.getComputedStyle(el).borderRadius;
        });

        // 0.5rem resolves to 8px at default font-size; accept both forms.
        expect(['0.5rem', '8px']).toContain(computedBorderRadius);
    });

    test('cover block without dynamoRadius set does not have border-radius from dynamo var', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertCoverBlock(page);
        // Do NOT set dynamoRadius

        // Add a distinctive class
        await selectBlock(editorFrame, 'core/cover');
        const advancedPanel = page.getByRole('button', { name: /advanced/i });
        await advancedPanel.click().catch(() => {});
        const cssClassInput = page.getByLabel(/additional css class/i);
        if (await cssClassInput.isVisible()) {
            await cssClassInput.fill('test-unstyled-cover');
        }

        const frontendUrl = await publishAndGetFrontendUrl(page);
        await page.goto(frontendUrl);

        await dismissConsentBanner(page);

        const coverBlock = page.locator('.test-unstyled-cover').first();
        await expect(coverBlock).toBeAttached({ timeout: 10000 });

        const inlineStyle = await coverBlock.getAttribute('style');
        const hasDynamoRadiusVar = (inlineStyle || '').includes('--dynamo-borders-radius-lg');
        expect(hasDynamoRadiusVar).toBe(false);
    });

    test('frontend CSS includes --dynamo-borders-radius-lg: 0.5rem in :root', async ({ page }) => {
        await page.goto(BASE_URL);

        await dismissConsentBanner(page);

        const cssVarValue = await page.evaluate(() => {
            return window
                .getComputedStyle(document.documentElement)
                .getPropertyValue('--dynamo-borders-radius-lg')
                .trim();
        });

        expect(cssVarValue).toBe('0.5rem');
    });
});
