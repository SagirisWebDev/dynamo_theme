// @ts-check
/**
 * Issue #34 — PRD v1.3.0 Slice 1: Width Preset tracer — "Narrow" option end-to-end
 *
 * Playwright browser tests covering:
 *
 *   AC5 — Opening the block editor on a page with a Group block shows a
 *         "Dynamo" InspectorControls panel with a "Width" dropdown containing
 *         a "Narrow" option.
 *
 *   AC6 — Selecting "Narrow" persists `dynamoWidth: "narrow"` in the block
 *         attributes (visible in the block markup / Code Editor view).
 *
 *   AC7 — The published page renders the block at max-width: 640px.
 *
 * Prerequisites (must be true for tests to pass — not yet implemented):
 *   - WordPress site running at http://aaachinese.local
 *   - `assets/js/editor/token-presets.js` is built and enqueued in the editor
 *   - `dynamo_layout_width_presets` filter is registered and returns the narrow preset
 *
 * Site status at time of RED-phase authoring: NOT REACHABLE (connection refused).
 * Tests are written per spec and will fail with a connection/navigation error
 * until the site is up AND the production code is implemented.
 */

const { test, expect } = require('@playwright/test');

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const BASE_URL      = process.env.WP_BASE_URL || 'http://localhost:10017';
const WP_ADMIN_URL  = `${BASE_URL}/wp-admin`;

/** Credentials — override via env vars in CI */
const WP_USER     = process.env.WP_ADMIN_USER     || 'admin';
const WP_PASSWORD = process.env.WP_ADMIN_PASSWORD || 'admin';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Log in to WordPress and land on the dashboard.
 * Skips the login form if the session cookie is already valid.
 */
async function wpLogin(page) {
    // storageState provides the session cookie from globalSetup.
    // Navigate directly to wp-admin; if the cookie is valid we land there immediately.
    await page.goto(`${WP_ADMIN_URL}/`);
    if (page.url().includes('wp-admin')) {
        return;
    }
    // Session expired — re-authenticate
    await page.goto(`${BASE_URL}/wp-login.php`);
    await page.fill('#user_login', WP_USER);
    await page.fill('#user_pass', WP_PASSWORD);
    await Promise.all([
        page.waitForURL(/\/wp-admin/),
        page.click('#wp-submit'),
    ]);
}

/**
 * Ensure the "Dynamo" InspectorControls panel is expanded.
 * The panel starts with initialOpen: true, so clicking when already open would close it.
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
 * Create a new page in the block editor and return its edit URL.
 * Navigates to Posts > Add New, then returns the current URL.
 */
async function openNewEditorPage(page) {
    await page.goto(`${WP_ADMIN_URL}/post-new.php?post_type=page`);
    // Wait for the block editor canvas to be present
    await page.waitForSelector('.editor-canvas, .block-editor-writing-flow, iframe[name="editor-canvas"]', {
        timeout: 15000,
    });
}

/**
 * Return a reference to the editor iframe's inner frame (FSE / iframed editor),
 * or the page itself when the editor is not iframed (classic block editor).
 */
async function getEditorFrame(page) {
    const editorFrame = page.frameLocator('iframe[name="editor-canvas"]');
    try {
        // Check if the iframe exists
        await editorFrame.locator('body').waitFor({ timeout: 3000 });
        return editorFrame;
    } catch {
        return page;
    }
}

/**
 * Insert a Group block programmatically, bypassing the variation-picker UI.
 * Passing layout: { type: 'constrained' } pre-selects the default Group variant.
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
    // Allow the editor to render the new block
    await page.waitForTimeout(800);
}

/**
 * Select the first Group block in the editor canvas.
 */
async function selectGroupBlock(editorFrame) {
    const groupBlock = editorFrame.locator('[data-type="core/group"]').first();
    await groupBlock.waitFor({ timeout: 8000 });
    await groupBlock.click();
    return groupBlock;
}

/**
 * Serialize the first core/group block in the editor to its HTML comment markup.
 * Uses wp.data + wp.blocks — no UI required, works in headless mode.
 */
async function serializeFirstGroupBlock(page) {
    return page.evaluate(() => {
        const blocks = window.wp.data.select('core/block-editor').getBlocks();
        const group  = blocks.find((b) => b.name === 'core/group');
        if (!group) return null;
        return window.wp.blocks.serialize([group]);
    });
}

/**
 * Dismiss a Complianz (or generic) cookie consent banner if one is present.
 * Silently skips if no banner appears within the timeout.
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
 * Handles the pre-publish panel that appears after the first click.
 *
 * Uses `?page_id=N` (plain-permalink form) so the URL works even when pretty
 * permalinks are enabled but the web server isn't configured for rewrites.
 */
async function publishAndGetFrontendUrl(page) {
    // First click opens the pre-publish panel
    await page.getByRole('button', { name: /^publish$/i }).first().click();

    // The pre-publish panel should appear
    const panel = page.locator('.editor-post-publish-panel');
    await panel.waitFor({ timeout: 10000 });

    // Click the confirm Publish button inside the panel
    await panel.getByRole('button', { name: /^publish$/i }).click();

    // Wait for the post-publish "View Page" link
    const viewPageLink = page.getByRole('link', { name: /view page/i });
    await expect(viewPageLink).toBeVisible({ timeout: 15000 });

    // Read the current post ID directly from the editor store — more reliable than
    // parsing the URL (which may not reflect history.pushState timing).
    const postId = await page.evaluate(() =>
        window.wp.data.select('core/editor').getCurrentPostId()
    );

    if (postId) {
        // preview=true suppresses WordPress's redirect_canonical() so the response
        // is served at the query-string URL rather than redirecting to the
        // pretty-permalink (which uses siteurl = aaachinese.local, not reachable here).
        return `${BASE_URL}/?page_id=${postId}&preview=true`;
    }

    // Fallback: normalise the origin in the View Page href
    const rawHref = await viewPageLink.getAttribute('href');
    return rawHref.replace(/^https?:\/\/[^/]+/, BASE_URL);
}

// ---------------------------------------------------------------------------
// AC5 — Block editor shows "Dynamo" panel with "Width" dropdown
// ---------------------------------------------------------------------------

test.describe('Issue #34 AC5 — Dynamo panel in block inspector', () => {
    test('Group block inspector shows a "Dynamo" panel', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertGroupBlock(page);
        await selectGroupBlock(editorFrame);

        // The InspectorControls panel should show "Dynamo" as a section heading
        const dynamoPanel = page.getByRole('button', { name: /dynamo/i });
        await expect(dynamoPanel).toBeVisible({ timeout: 10000 });
    });

    test('Dynamo panel contains a "Width" control label', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertGroupBlock(page);
        await selectGroupBlock(editorFrame);

        // Open the Dynamo panel if it is collapsed
        await openDynamoPanel(page);

        const widthLabel = page.getByText('Width', { exact: true });
        await expect(widthLabel).toBeVisible({ timeout: 10000 });
    });

    test('Width dropdown contains a "Narrow" option', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertGroupBlock(page);
        await selectGroupBlock(editorFrame);

        // Open the Dynamo panel
        await openDynamoPanel(page);

        // Find the Width select/combobox
        const widthSelect = page.getByRole('combobox', { name: /width/i });
        await expect(widthSelect).toBeVisible({ timeout: 10000 });

        // The select must have a "Narrow" option
        const narrowOption = widthSelect.locator('option[value="narrow"]');
        await expect(narrowOption).toBeAttached();
        await expect(narrowOption).toHaveText('Narrow');
    });

    test('Width dropdown has five non-empty options (full scale)', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertGroupBlock(page);
        await selectGroupBlock(editorFrame);

        await openDynamoPanel(page);

        const widthSelect = page.getByRole('combobox', { name: /width/i });
        await expect(widthSelect).toBeVisible({ timeout: 10000 });

        // Count non-placeholder options (exclude the empty "— Default —" placeholder)
        const optionCount = await widthSelect.locator('option:not([value=""])').count();
        expect(optionCount).toBe(5);
    });
});

// ---------------------------------------------------------------------------
// AC6 — Selecting "Narrow" persists dynamoWidth: "narrow" in block attributes
// ---------------------------------------------------------------------------

test.describe('Issue #34 AC6 — Selecting Narrow persists the attribute', () => {
    test('selecting Narrow updates block attributes to include dynamoWidth="narrow"', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertGroupBlock(page);
        await selectGroupBlock(editorFrame);

        // Open the Dynamo panel
        await openDynamoPanel(page);

        // Select "Narrow" in the Width dropdown
        const widthSelect = page.getByRole('combobox', { name: /width/i });
        await widthSelect.selectOption('narrow');

        // Read back the serialized block markup directly from the editor store
        const markup = await serializeFirstGroupBlock(page);
        expect(markup).not.toBeNull();
        expect(markup).toContain('"dynamoWidth":"narrow"');
        expect(markup).toContain('narrow');
    });

    test('block save output includes style with max-width var for narrow', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertGroupBlock(page);
        await selectGroupBlock(editorFrame);

        await openDynamoPanel(page);

        const widthSelect = page.getByRole('combobox', { name: /width/i });
        await widthSelect.selectOption('narrow');

        // Verify the serialized save output contains the CSS variable reference
        const markup = await serializeFirstGroupBlock(page);
        expect(markup).not.toBeNull();
        expect(markup).toContain('var(--dynamo-layout-width-narrow)');
    });
});

// ---------------------------------------------------------------------------
// AC7 — Published page renders the block at max-width: 640px
// ---------------------------------------------------------------------------

test.describe('Issue #34 AC7 — Published page renders at max-width 640px', () => {
    /**
     * Full end-to-end: create page with a narrow Group block, publish it,
     * then visit the frontend and assert the computed max-width is 640px.
     */
    test('group block renders with computed max-width 640px on the frontend', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertGroupBlock(page);
        await selectGroupBlock(editorFrame);

        // Set width to narrow
        await openDynamoPanel(page);
        const widthSelect = page.getByRole('combobox', { name: /width/i });
        await widthSelect.selectOption('narrow');

        // Add a distinctive class so we can find it on the frontend
        // (via the block's Advanced > Additional CSS class)
        const advancedPanel = page.getByRole('button', { name: /advanced/i });
        await advancedPanel.click().catch(() => {});
        const cssClassInput = page.getByLabel(/additional css class/i);
        if (await cssClassInput.isVisible()) {
            await cssClassInput.fill('test-narrow-group');
        }

        // Publish and navigate to the live page
        const frontendUrl = await publishAndGetFrontendUrl(page);
        await page.goto(frontendUrl);

        // Dismiss any consent banner before asserting
        await dismissConsentBanner(page);

        // Find the group block by its class or by inline max-width style.
        // Use toBeAttached (not toBeVisible) because the empty block has zero height.
        const groupBlock = page.locator('.test-narrow-group, .wp-block-group[style*="max-width"]').first();
        await expect(groupBlock).toBeAttached({ timeout: 10000 });

        // Assert computed max-width is 640px
        const computedMaxWidth = await groupBlock.evaluate((el) => {
            return window.getComputedStyle(el).maxWidth;
        });

        expect(computedMaxWidth).toBe('640px');
    });

    test('group block without dynamoWidth set does not have max-width 640px applied', async ({ page }) => {
        await wpLogin(page);
        await openNewEditorPage(page);

        const editorFrame = await getEditorFrame(page);
        await insertGroupBlock(page);
        // Do NOT set dynamoWidth

        // Add a distinctive class
        await selectGroupBlock(editorFrame);
        const advancedPanel = page.getByRole('button', { name: /advanced/i });
        await advancedPanel.click().catch(() => {});
        const cssClassInput = page.getByLabel(/additional css class/i);
        if (await cssClassInput.isVisible()) {
            await cssClassInput.fill('test-unstyled-group');
        }

        // Publish and navigate to the live page
        const frontendUrl = await publishAndGetFrontendUrl(page);
        await page.goto(frontendUrl);

        await dismissConsentBanner(page);

        // toBeAttached because the empty block has zero height (not visually visible).
        const groupBlock = page.locator('.test-unstyled-group').first();
        await expect(groupBlock).toBeAttached({ timeout: 10000 });

        const inlineStyle = await groupBlock.getAttribute('style');
        // Must NOT have inline max-width set to the narrow var
        const hasNarrowStyle = (inlineStyle || '').includes('--dynamo-layout-width-narrow');
        expect(hasNarrowStyle).toBe(false);
    });

    test('frontend CSS includes --dynamo-layout-width-narrow: 640px in :root', async ({ page }) => {
        await page.goto(BASE_URL);

        await dismissConsentBanner(page);

        // Read the computed value of the custom property from :root
        const cssVarValue = await page.evaluate(() => {
            return window
                .getComputedStyle(document.documentElement)
                .getPropertyValue('--dynamo-layout-width-narrow')
                .trim();
        });

        expect(cssVarValue).toBe('640px');
    });
});
