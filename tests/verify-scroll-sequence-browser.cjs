/**
 * Run against the verify-scroll-sequence page created by make-verify-pages.php.
 * Requires Playwright (or NODE_PATH pointing to it), BASE_URL and optionally
 * CHROME_PATH. Fails if reduced motion shows multiple frames or moves the page.
 */
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const baseUrl = process.env.BASE_URL || 'http://127.0.0.1:8091';

(async () => {
 const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
 let failures = 0;
 try {
  for (const mode of ['no-preference', 'reduce']) {
   const context = await browser.newContext({ viewport: { width: 1280, height: 900 }, reducedMotion: mode });
   const page = await context.newPage();
   try {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const response = await page.goto(`${baseUrl}/verify-scroll-sequence/`, { waitUntil: 'networkidle' });
    assert.equal(response.status(), 200);
    const sequence = page.locator('[data-basalio-scroll-sequence][data-basalio-ready]');
    await sequence.waitFor();
    const frames = sequence.locator('.basalio-scroll-sequence__frame');
    const total = await frames.count();
    assert.ok(total > 1, 'fixture must contain multiple frames');
    const assertState = async () => {
     const state = await frames.evaluateAll(elements => elements.map((element, index) => ({
      index, active: element.classList.contains('is-active'),
      visible: Number(getComputedStyle(element).opacity) > 0.9 && getComputedStyle(element).visibility !== 'hidden',
     })));
     const visible = state.filter(frame => frame.visible);
     assert.equal(visible.length, 1, 'exactly one visible frame');
     assert.equal(visible[0].active, true, 'visible frame agrees with active frame');
     assert.equal(state.filter(frame => frame.active).length, 1);
     assert.equal(await sequence.locator('.basalio-scroll-sequence__status').innerText(), `Frame ${visible[0].index + 1} of ${total}`);
     return visible[0].index;
    };
    if (mode === 'no-preference') {
     await page.evaluate(() => window.scrollTo(0, 0));
     await page.waitForTimeout(150);
     await assertState();
     // Changing the OS preference during a visit must also synchronize state.
     await page.emulateMedia({ reducedMotion: 'reduce' });
     await page.waitForTimeout(150);
    }
    assert.equal(await assertState(), total - 1, 'reduced motion starts with the last frame');
    for (const [selector, index] of [['[data-basalio-seq-prev]', total - 2], ['[data-basalio-seq-next]', total - 1]]) {
     await sequence.locator(selector).focus();
     const before = await page.evaluate(() => window.scrollY);
     await page.keyboard.press('Enter');
     await page.waitForTimeout(150);
     assert.equal(await assertState(), index, 'keyboard control changes the visible frame');
     assert.equal(await sequence.locator('[data-basalio-seq-announcer]').innerText(), `Frame ${index + 1} of ${total}`);
     assert.equal(await page.evaluate(() => window.scrollY), before, 'manual reduced-motion selection must not move the page');
    }
    assert.deepEqual(errors, []);
    console.log(`PASS Scroll Sequence: ${mode}, frame visibility, counter, keyboard, announcement and scroll position`);
   } catch (error) {
    failures++;
    console.error(`FAIL Scroll Sequence: ${mode}: ${error.message}`);
   } finally {
    await context.close();
   }
  }
 } finally {
  await browser.close();
 }
 process.exitCode = failures ? 1 : 0;
})().catch(error => { console.error(error); process.exitCode = 1; });
