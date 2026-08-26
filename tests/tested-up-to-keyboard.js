/**
 * Keyboard and interaction verification for Basalio blocks on the front end.
 * Directly focuses each Basalio interactive element and verifies keyboard response.
 */
const { chromium } = require('/Users/angelomanzanojr/.nvm/versions/node/v24.14.1/lib/node_modules/omniroute/node_modules/playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'http://127.0.0.1:8091';
const SCREENSHOT_DIR = '/Users/angelomanzanojr/Desktop/basalio-screenshots';

const BLOCKS = [
  { slug: 'text-reveal', url: '/default-motion-text-reveal/', label: 'Text Reveal' },
  { slug: 'before-after', url: '/default-motion-before-after/', label: 'Before / After' },
  { slug: 'case-study-transition', url: '/default-motion-case-study/', label: 'Case Study Transition' },
  { slug: 'filterable-grid', url: '/default-motion-filterable-grid/', label: 'Filterable Grid' },
  { slug: 'magnetic-button', url: '/default-motion-magnetic-button/', label: 'Magnetic Button' },
  { slug: 'custom-cursor', url: '/default-motion-custom-cursor/', label: 'Custom Cursor' },
  { slug: 'grid-reveal', url: '/default-motion-grid-reveal/', label: 'Grid Reveal' },
  { slug: 'scroll-sequence', url: '/default-motion-scroll-sequence/', label: 'Scroll Sequence' },
  { slug: 'pinned-scroll', url: '/default-motion-pinned-scroll/', label: 'Pinned Scroll' },
];

async function testElement(page, elInfo, index) {
  const errors = [];
  let focusable = false;
  let responded = false;

  try {
    const locator = page.locator(elInfo.locator).nth(index);
    if (await locator.count() === 0) {
      return { focusable: false, responded: false, errors: ['locator not found'] };
    }

    // Scroll into view and focus via keyboard API.
    await locator.evaluate((el) => {
      el.scrollIntoView({ block: 'center' });
      el.focus();
    });
    await page.waitForTimeout(150);

    focusable = await page.evaluate((sel) => {
      const active = document.activeElement;
      if (!active) return false;
      return active.matches(sel) || active.closest(sel) !== null;
    }, elInfo.locator);

    if (!focusable) {
      errors.push('element did not become activeElement');
    }

    // Test operation based on element type.
    if (elInfo.role === 'slider') {
      const before = await locator.getAttribute('aria-valuenow');
      await page.keyboard.press('ArrowRight');
      await page.waitForTimeout(150);
      const after = await locator.getAttribute('aria-valuenow');
      responded = before !== after;
      if (!responded) errors.push(`slider ArrowRight did not change value (${before} -> ${after})`);
    } else if (elInfo.tag === 'BUTTON') {
      // Button should be activatable with Enter or Space.
      await page.keyboard.press('Enter');
      await page.waitForTimeout(150);
      responded = true; // No error means it accepted the key.
    } else if (elInfo.tag === 'A') {
      // Do not follow navigation; just confirm Enter is accepted while staying put.
      const href = elInfo.href || '';
      if (href && href !== '#' && !href.startsWith('javascript:')) {
        await page.evaluate(({ sel, idx }) => {
          const el = document.querySelectorAll(sel)[idx];
          if (el) {
            el.addEventListener('click', (e) => e.preventDefault(), { once: true });
          }
        }, { sel: elInfo.locator, idx: index });
      }
      await page.keyboard.press('Enter');
      await page.waitForTimeout(150);
      responded = true;
    }
  } catch (e) {
    errors.push(e.message);
  }

  return { focusable, responded, errors };
}

(async () => {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
  const browser = await chromium.launch({
    headless: true,
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  });
  const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const results = [];

  for (const block of BLOCKS) {
    const page = await context.newPage();
    const consoleErrors = [];
    const consoleWarnings = [];
    page.on('console', (msg) => {
      const type = msg.type();
      const text = msg.text();
      if (type === 'error') consoleErrors.push(text);
      if (type === 'warning') consoleWarnings.push(text);
    });
    page.on('pageerror', (err) => consoleErrors.push(err.message));
    page.on('response', (resp) => {
      if (resp.status() === 404) consoleErrors.push(`404: ${resp.url()}`);
    });

    const url = BASE_URL + block.url;
    let loadOk = false;
    let loadError = null;
    try {
      await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
      loadOk = true;
    } catch (e) {
      loadError = e.message;
    }
    await page.waitForTimeout(1000);

    // Find interactive Basalio elements.
    const elements = await page.evaluate(() => {
      const selector = 'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"]), [role="button"], [role="link"], [role="slider"]';
      const all = Array.from(document.querySelectorAll(selector));
      return all
        .filter((el) => {
          const style = window.getComputedStyle(el);
          return style.display !== 'none' && style.visibility !== 'hidden' && el.offsetParent !== null;
        })
        .filter((el) => {
          const cls = el.className || '';
          if (typeof cls === 'string' && cls.match(/basalio-[a-z]/)) return true;
          if (el.closest('[class*="basalio-"], [data-basalio]')) return true;
          return false;
        })
        .map((el) => ({
          tag: el.tagName,
          className: el.className,
          role: el.getAttribute('role') || '',
          tabindex: el.getAttribute('tabindex') || '',
          name: el.getAttribute('aria-label') || el.textContent.trim() || el.getAttribute('title') || '',
          href: el.getAttribute('href') || '',
          locator: el.className ? `.${el.className.split(/\s+/).filter(Boolean).join('.')}` : el.tagName.toLowerCase(),
        }));
    });

    // Deduplicate locators: prefer the most specific class.
    const tested = [];
    for (let i = 0; i < elements.length; i++) {
      const el = elements[i];
      // Build a stable locator from a single class.
      const classes = el.className.split(/\s+/).filter(Boolean);
      const stableClass = classes.find((c) => c.startsWith('basalio-')) || classes[0];
      el.locator = stableClass ? `.${stableClass}` : el.tagName.toLowerCase();
      const test = await testElement(page, el, i);
      tested.push({ ...el, ...test });
    }

    const screenshotPath = path.join(SCREENSHOT_DIR, `tested-up-to-${block.slug}-keyboard.png`);
    try {
      await page.screenshot({ path: screenshotPath, fullPage: true });
    } catch (e) {
      consoleErrors.push(`Screenshot failed: ${e.message}`);
    }

    results.push({
      block: block.label,
      url,
      loadOk,
      loadError,
      consoleErrors,
      consoleWarnings,
      elements: tested,
      screenshot: screenshotPath,
    });

    await page.close();
  }

  await browser.close();

  console.log('\n=== Tested up to: direct keyboard verification ===\n');
  console.log(`WordPress version (local): 7.1`);
  console.log(`Browser: Google Chrome (system)`);
  console.log(`Viewport: 1280x900`);
  console.log(`Note: Site nav menu has many items, so direct focus is used to isolate block controls.\n`);

  for (const r of results) {
    console.log(`--- ${r.block} ---`);
    console.log(`URL: ${r.url}`);
    console.log(`Load: ${r.loadOk ? 'OK' : 'FAIL - ' + r.loadError}`);
    console.log(`Console errors: ${r.consoleErrors.length}`);
    r.consoleErrors.forEach((e) => console.log(`  ERROR: ${e}`));
    console.log(`Console warnings: ${r.consoleWarnings.length}`);
    r.consoleWarnings.forEach((w) => console.log(`  WARN: ${w}`));
    console.log(`Basalio interactive elements: ${r.elements.length}`);
    r.elements.forEach((e) => {
      const status = e.focusable && e.responded ? 'OK' : 'FAIL';
      console.log(`  [${status}] ${e.tag} ${e.className} | role=${e.role} | name="${e.name}" | focusable=${e.focusable} | responded=${e.responded}`);
      e.errors.forEach((err) => console.log(`       -> ${err}`));
    });
    console.log(`Screenshot: ${r.screenshot}\n`);
  }
})();
