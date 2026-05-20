// @ts-check
/**
 * AC6 — Consent Placeholder reveal-without-reload contract.
 *
 * Issue #29 / AC6:
 *   "Once the visitor grants the required consent category, the placeholder is
 *   replaced with the real embed without a page reload."
 *
 * Contract under test (assets/js/consent-reveal.js):
 *   1. PHP template outputs a data-embed attribute on .dynamo-consent-placeholder
 *      containing the encoded original iframe HTML.
 *   2. consent-reveal.js listens for:
 *        - Complianz `cmplz_status_change` event on document
 *          (event.detail.status === granted category slug)
 *        - Borlabs `borlabs-cookie-consent-saved` event on document
 *          (verified via window.BorlabsCookie.checkCookieConsent(category))
 *   3. On DOMContentLoaded, the script checks for already-granted consent via
 *        window.cmplz_has_consent(category) or
 *        window.BorlabsCookie.checkCookieConsent(category)
 *      and replaces matching placeholders immediately.
 *   4. Replacement is per-category — placeholders with mismatched data-category
 *      remain untouched.
 *   5. The script must not modify content outside .dynamo-consent-placeholder.
 *
 * Test strategy: page.setContent() injects a self-contained HTML fixture, then
 * page.addScriptTag() loads the JS under test. A `pageload` sentinel attribute
 * confirms no full reload occurred between the initial state and final state
 * assertions. Plugin globals are stubbed via init scripts BEFORE the JS loads
 * so on-page-load consent checks can be exercised.
 */

const { test, expect } = require('@playwright/test');
const path = require('path');

const SCRIPT_PATH = path.resolve(
    __dirname,
    '../../../assets/js/consent-reveal.js'
);

/**
 * Encoded iframe HTML as it would appear inside the data-embed attribute when
 * rendered by the PHP template via esc_attr().
 */
const IFRAME_HTML =
    '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315" frameborder="0" allowfullscreen></iframe>';

/**
 * Helper to build a minimal HTML page containing one or more placeholders.
 * Uses a window.__pageLoadToken sentinel so we can confirm later that no
 * full reload occurred.
 */
function fixturePage(bodyHtml) {
    return `<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>consent-reveal fixture</title></head>
<body>
    <script>window.__pageLoadToken = 'initial-load-' + Date.now();</script>
    ${bodyHtml}
</body>
</html>`;
}

function placeholderHtml({ category = 'marketing', service = 'YouTube', embed = IFRAME_HTML } = {}) {
    // Mimic the PHP template output, including the data-embed attribute that
    // the implementation MUST add (AC6 contract).
    const encodedEmbed = embed
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
    return `<div class="dynamo-consent-placeholder"
        data-service="${service}"
        data-category="${category}"
        data-embed="${encodedEmbed}">
        <p><strong>${service}</strong></p>
        <p>This content requires ${category} cookies to display.</p>
    </div>`;
}

test.describe('AC6 — Consent placeholder is replaced with embed without reload', () => {

    test('Complianz: cmplz_status_change event replaces placeholder with embed (no reload)', async ({ page }) => {
        await page.setContent(fixturePage(placeholderHtml({ category: 'marketing' })));
        await page.addScriptTag({ path: SCRIPT_PATH });

        // Initial state: placeholder present, no iframe yet.
        await expect(page.locator('.dynamo-consent-placeholder')).toHaveCount(1);
        await expect(page.locator('iframe[src*="youtube.com"]')).toHaveCount(0);
        const tokenBefore = await page.evaluate(() => window.__pageLoadToken);

        // Act: dispatch Complianz consent-granted event for "marketing".
        await page.evaluate(() => {
            document.dispatchEvent(
                new CustomEvent('cmplz_status_change', { detail: { status: 'marketing' } })
            );
        });

        // Final state: placeholder gone, embed rendered, page never reloaded.
        await expect(page.locator('.dynamo-consent-placeholder')).toHaveCount(0);
        await expect(page.locator('iframe[src*="youtube.com"]')).toHaveCount(1);
        const tokenAfter = await page.evaluate(() => window.__pageLoadToken);
        expect(tokenAfter).toBe(tokenBefore);
    });

    test('Borlabs: borlabs-cookie-consent-saved event + BorlabsCookie.checkCookieConsent replaces placeholder', async ({ page }) => {
        // Stub BorlabsCookie global BEFORE the script is loaded so it can wire
        // up listeners against it on init. addInitScript only fires on
        // navigation, so we explicitly goto('about:blank') before setContent
        // (which otherwise reuses the current document and skips init scripts).
        await page.addInitScript(() => {
            window.BorlabsCookie = {
                _grants: {},
                grant(category) { this._grants[category] = true; },
                checkCookieConsent(category) { return !!this._grants[category]; },
            };
        });

        await page.goto('about:blank');
        await page.setContent(fixturePage(placeholderHtml({ category: 'marketing' })));
        await page.addScriptTag({ path: SCRIPT_PATH });

        await expect(page.locator('.dynamo-consent-placeholder')).toHaveCount(1);
        await expect(page.locator('iframe[src*="youtube.com"]')).toHaveCount(0);
        const tokenBefore = await page.evaluate(() => window.__pageLoadToken);

        // Act: grant marketing consent, then fire Borlabs save event.
        await page.evaluate(() => {
            window.BorlabsCookie.grant('marketing');
            document.dispatchEvent(new Event('borlabs-cookie-consent-saved'));
        });

        await expect(page.locator('.dynamo-consent-placeholder')).toHaveCount(0);
        await expect(page.locator('iframe[src*="youtube.com"]')).toHaveCount(1);
        const tokenAfter = await page.evaluate(() => window.__pageLoadToken);
        expect(tokenAfter).toBe(tokenBefore);
    });

    test('On page load: cmplz_has_consent already true => placeholder immediately replaced', async ({ page }) => {
        // Pre-existing return visitor — Complianz reports consent before our
        // script runs. addInitScript only fires on navigation; goto about:blank
        // forces it to run before setContent populates the page.
        await page.addInitScript(() => {
            window.cmplz_has_consent = (category) => category === 'marketing';
        });

        await page.goto('about:blank');
        await page.setContent(fixturePage(placeholderHtml({ category: 'marketing' })));
        await page.addScriptTag({ path: SCRIPT_PATH });

        // No user interaction needed — initial-load handler should reveal it.
        await expect(page.locator('iframe[src*="youtube.com"]')).toHaveCount(1);
        await expect(page.locator('.dynamo-consent-placeholder')).toHaveCount(0);
    });

    test('On page load: BorlabsCookie.checkCookieConsent already true => placeholder immediately replaced', async ({ page }) => {
        // addInitScript only fires on navigation; goto about:blank forces it
        // to run before setContent populates the page.
        await page.addInitScript(() => {
            window.BorlabsCookie = {
                checkCookieConsent: (category) => category === 'marketing',
            };
        });

        await page.goto('about:blank');
        await page.setContent(fixturePage(placeholderHtml({ category: 'marketing' })));
        await page.addScriptTag({ path: SCRIPT_PATH });

        await expect(page.locator('iframe[src*="youtube.com"]')).toHaveCount(1);
        await expect(page.locator('.dynamo-consent-placeholder')).toHaveCount(0);
    });

    test('Consent for a different category does NOT reveal a placeholder needing another category', async ({ page }) => {
        // Page contains one "marketing" placeholder. We grant "statistics" only.
        await page.setContent(fixturePage(placeholderHtml({ category: 'marketing' })));
        await page.addScriptTag({ path: SCRIPT_PATH });

        await expect(page.locator('.dynamo-consent-placeholder[data-category="marketing"]')).toHaveCount(1);

        await page.evaluate(() => {
            document.dispatchEvent(
                new CustomEvent('cmplz_status_change', { detail: { status: 'statistics' } })
            );
        });

        // Marketing placeholder must remain — no iframe should appear.
        await expect(page.locator('.dynamo-consent-placeholder[data-category="marketing"]')).toHaveCount(1);
        await expect(page.locator('iframe[src*="youtube.com"]')).toHaveCount(0);
    });

    test('Non-placeholder content is untouched when consent is granted', async ({ page }) => {
        const body = `
            <p id="paragraph-before">Lorem ipsum dolor sit amet.</p>
            <article id="article-sibling">
                <h2>Existing article heading</h2>
                <iframe id="existing-iframe" src="https://example.com/existing" width="200" height="100"></iframe>
            </article>
            ${placeholderHtml({ category: 'marketing' })}
            <p id="paragraph-after">Sed ut perspiciatis unde omnis.</p>
        `;
        await page.setContent(fixturePage(body));
        await page.addScriptTag({ path: SCRIPT_PATH });

        // Capture pre-event snapshots of unrelated nodes.
        const beforeSnapshot = await page.evaluate(() => ({
            paragraphBefore: document.getElementById('paragraph-before')?.outerHTML,
            articleSibling: document.getElementById('article-sibling')?.outerHTML,
            paragraphAfter: document.getElementById('paragraph-after')?.outerHTML,
            existingIframeSrc: document.getElementById('existing-iframe')?.getAttribute('src'),
        }));

        await page.evaluate(() => {
            document.dispatchEvent(
                new CustomEvent('cmplz_status_change', { detail: { status: 'marketing' } })
            );
        });

        // Placeholder should be replaced.
        await expect(page.locator('.dynamo-consent-placeholder')).toHaveCount(0);
        await expect(page.locator('iframe[src*="youtube.com"]')).toHaveCount(1);

        // Unrelated nodes must be byte-for-byte identical.
        const afterSnapshot = await page.evaluate(() => ({
            paragraphBefore: document.getElementById('paragraph-before')?.outerHTML,
            articleSibling: document.getElementById('article-sibling')?.outerHTML,
            paragraphAfter: document.getElementById('paragraph-after')?.outerHTML,
            existingIframeSrc: document.getElementById('existing-iframe')?.getAttribute('src'),
        }));
        expect(afterSnapshot).toEqual(beforeSnapshot);
    });
});
