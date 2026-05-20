// @ts-check
/**
 * Issue #30 — Consent Gate Block: frontend reveal contract.
 *
 * Acceptance criteria covered here (real-browser):
 *
 *   AC5 — Frontend JS detects the active plugin (window.cmplz_has_consent for
 *         Complianz, window.BorlabsCookie for Borlabs) and binds to the
 *         correct consent event.
 *
 *   AC6 — Content reveals immediately on page load if consent was already
 *         granted on a previous visit.
 *
 *   AC7 — Content reveals without a page reload when the visitor grants
 *         consent via the cookie banner.
 *
 * Contract under test (blocks/consent-gate/frontend.js):
 *
 *   1. Server-side render outputs:
 *        <div class="dynamo-consent-gate" style="display:none"
 *             data-consent-category="SLUG">
 *          <!-- inner blocks -->
 *        </div>
 *
 *   2. The frontend script listens for:
 *        - Complianz `cmplz_status_change` event on document
 *          (event.detail.status === granted category slug)
 *        - Borlabs `borlabs-cookie-consent-saved` event on document
 *          (verified via window.BorlabsCookie.checkCookieConsent(category))
 *
 *   3. On DOMContentLoaded, the script checks for already-granted consent via
 *        window.cmplz_has_consent(category) or
 *        window.BorlabsCookie.checkCookieConsent(category)
 *      and reveals matching gates immediately.
 *
 *   4. Reveal removes the inline `display:none` (the wrapper becomes visible).
 *
 *   5. Gates with a different data-consent-category remain hidden when consent
 *      is granted for an unrelated category.
 *
 * Test strategy: page.setContent() injects a self-contained HTML fixture, then
 * page.addScriptTag() loads the frontend JS under test. A `pageload` sentinel
 * confirms no full reload occurred between the initial state and final state
 * assertions. Plugin globals are stubbed via addInitScript BEFORE the JS loads
 * so on-page-load consent checks can be exercised.
 */

const { test, expect } = require('@playwright/test');
const path = require('path');

const SCRIPT_PATH = path.resolve(
    __dirname,
    '../../../blocks/consent-gate/frontend.js'
);

/**
 * Helper to build a minimal HTML page containing one or more consent gates.
 * Uses a window.__pageLoadToken sentinel so we can confirm later that no
 * full reload occurred.
 */
function fixturePage(bodyHtml) {
    return `<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>consent-gate fixture</title></head>
<body>
    <script>window.__pageLoadToken = 'initial-load-' + Date.now();</script>
    ${bodyHtml}
</body>
</html>`;
}

/**
 * Mimic the server-side render output of the Consent Gate block:
 *   <div class="dynamo-consent-gate" style="display:none"
 *        data-consent-category="SLUG">
 *      <!-- inner blocks HTML -->
 *   </div>
 */
function consentGateHtml({ category = 'marketing', innerHtml = '<p class="gate-inner">Hidden inner content</p>' } = {}) {
    return `<div class="dynamo-consent-gate"
        style="display:none"
        data-consent-category="${category}">
        ${innerHtml}
    </div>`;
}

/**
 * Returns whether the first .dynamo-consent-gate is visually shown (not hidden).
 * Visibility is judged by computed display style — the reveal contract is to
 * remove the inline `display:none`.
 */
async function isGateRevealed(page, category) {
    return await page.evaluate((cat) => {
        const el = document.querySelector(
            '.dynamo-consent-gate[data-consent-category="' + cat + '"]'
        );
        if (!el) { return false; }
        const computed = window.getComputedStyle(el);
        return computed.display !== 'none';
    }, category);
}

test.describe('Issue #30 — Consent Gate frontend reveal (AC5/AC6/AC7)', () => {

    test('AC7 — Complianz: cmplz_status_change event reveals matching gate without reload', async ({ page }) => {
        await page.setContent(fixturePage(consentGateHtml({ category: 'marketing' })));
        await page.addScriptTag({ path: SCRIPT_PATH });

        // Initial state: gate is hidden.
        await expect(page.locator('.dynamo-consent-gate[data-consent-category="marketing"]')).toHaveCount(1);
        expect(await isGateRevealed(page, 'marketing')).toBe(false);

        const tokenBefore = await page.evaluate(() => window.__pageLoadToken);

        // Act: dispatch Complianz consent-granted event for "marketing".
        await page.evaluate(() => {
            document.dispatchEvent(
                new CustomEvent('cmplz_status_change', { detail: { status: 'marketing' } })
            );
        });

        // Final state: gate is revealed; inner content is visible; page never reloaded.
        await expect.poll(() => isGateRevealed(page, 'marketing')).toBe(true);
        await expect(page.locator('.dynamo-consent-gate .gate-inner')).toBeVisible();

        const tokenAfter = await page.evaluate(() => window.__pageLoadToken);
        expect(tokenAfter).toBe(tokenBefore);
    });

    test('AC5/AC7 — Borlabs: consent-saved event + BorlabsCookie.checkCookieConsent reveals matching gate', async ({ page }) => {
        // Stub BorlabsCookie BEFORE the script loads so it can wire up listeners.
        await page.addInitScript(() => {
            window.BorlabsCookie = {
                _grants: {},
                grant(category) { this._grants[category] = true; },
                checkCookieConsent(category) { return !!this._grants[category]; },
            };
        });

        await page.goto('about:blank');
        await page.setContent(fixturePage(consentGateHtml({ category: 'marketing' })));
        await page.addScriptTag({ path: SCRIPT_PATH });

        expect(await isGateRevealed(page, 'marketing')).toBe(false);
        const tokenBefore = await page.evaluate(() => window.__pageLoadToken);

        // Act: grant marketing consent, then fire Borlabs save event.
        await page.evaluate(() => {
            window.BorlabsCookie.grant('marketing');
            document.dispatchEvent(new Event('borlabs-cookie-consent-saved'));
        });

        await expect.poll(() => isGateRevealed(page, 'marketing')).toBe(true);
        await expect(page.locator('.dynamo-consent-gate .gate-inner')).toBeVisible();

        const tokenAfter = await page.evaluate(() => window.__pageLoadToken);
        expect(tokenAfter).toBe(tokenBefore);
    });

    test('AC6 — On page load: cmplz_has_consent already true => gate immediately revealed', async ({ page }) => {
        // Pre-existing return visitor — Complianz reports consent before our
        // script runs. addInitScript only fires on navigation; goto about:blank
        // forces it to run before setContent populates the page.
        await page.addInitScript(() => {
            window.cmplz_has_consent = (category) => category === 'marketing';
        });

        await page.goto('about:blank');
        await page.setContent(fixturePage(consentGateHtml({ category: 'marketing' })));
        await page.addScriptTag({ path: SCRIPT_PATH });

        // No user interaction — initial-load handler must reveal the gate.
        await expect.poll(() => isGateRevealed(page, 'marketing')).toBe(true);
        await expect(page.locator('.dynamo-consent-gate .gate-inner')).toBeVisible();
    });

    test('AC6 — On page load: BorlabsCookie.checkCookieConsent already true => gate immediately revealed', async ({ page }) => {
        await page.addInitScript(() => {
            window.BorlabsCookie = {
                checkCookieConsent: (category) => category === 'marketing',
            };
        });

        await page.goto('about:blank');
        await page.setContent(fixturePage(consentGateHtml({ category: 'marketing' })));
        await page.addScriptTag({ path: SCRIPT_PATH });

        await expect.poll(() => isGateRevealed(page, 'marketing')).toBe(true);
        await expect(page.locator('.dynamo-consent-gate .gate-inner')).toBeVisible();
    });

    test('AC5 — Unrelated category event does NOT reveal a gate needing another category', async ({ page }) => {
        // Page contains one "marketing" gate. We grant "statistics" only.
        await page.setContent(fixturePage(consentGateHtml({ category: 'marketing' })));
        await page.addScriptTag({ path: SCRIPT_PATH });

        expect(await isGateRevealed(page, 'marketing')).toBe(false);

        await page.evaluate(() => {
            document.dispatchEvent(
                new CustomEvent('cmplz_status_change', { detail: { status: 'statistics' } })
            );
        });

        // Marketing gate must remain hidden.
        // Wait briefly to give any (incorrect) listener time to fire.
        await page.waitForTimeout(150);
        expect(await isGateRevealed(page, 'marketing')).toBe(false);
        await expect(page.locator('.dynamo-consent-gate[data-consent-category="marketing"]')).toHaveCount(1);
    });

    test('AC5 — Unrelated category page-load consent does NOT reveal a gate needing another category', async ({ page }) => {
        await page.addInitScript(() => {
            // Complianz reports consent ONLY for "statistics".
            window.cmplz_has_consent = (category) => category === 'statistics';
        });

        await page.goto('about:blank');
        await page.setContent(fixturePage(consentGateHtml({ category: 'marketing' })));
        await page.addScriptTag({ path: SCRIPT_PATH });

        // Allow initial-load handler to run.
        await page.waitForTimeout(150);
        expect(await isGateRevealed(page, 'marketing')).toBe(false);
    });

    test('AC7 — Multiple gates: only the matching category is revealed on consent grant', async ({ page }) => {
        const body =
            consentGateHtml({ category: 'marketing', innerHtml: '<p class="marketing-inner">marketing inner</p>' }) +
            consentGateHtml({ category: 'statistics', innerHtml: '<p class="statistics-inner">statistics inner</p>' });

        await page.setContent(fixturePage(body));
        await page.addScriptTag({ path: SCRIPT_PATH });

        expect(await isGateRevealed(page, 'marketing')).toBe(false);
        expect(await isGateRevealed(page, 'statistics')).toBe(false);

        // Grant marketing only.
        await page.evaluate(() => {
            document.dispatchEvent(
                new CustomEvent('cmplz_status_change', { detail: { status: 'marketing' } })
            );
        });

        await expect.poll(() => isGateRevealed(page, 'marketing')).toBe(true);
        // statistics must remain hidden.
        expect(await isGateRevealed(page, 'statistics')).toBe(false);
    });
});
