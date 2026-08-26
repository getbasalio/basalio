/**
 * Keyboard verification for Text Reveal and Pinned Scroll after fixture updates.
 */
const { chromium } = require('/Users/angelomanzanojr/.nvm/versions/node/v24.14.1/lib/node_modules/omniroute/node_modules/playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'http://127.0.0.1:8091';
const SCREENSHOT_DIR = '/Users/angelomanzanojr/Desktop/basalio-screenshots';

const BLOCKS = [
  { slug: 'text-reveal', url: '/default-motion-text-reveal/', label: 'Text Reveal' },
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

    if (elInfo.tag === 'A') {
      const href = elInfo.href || '';
      if (href && href !== '#' && !href.startsWith('javascript:')) {
        await page.evaluate(({ sel, idx }) => {
          const el = document.querySelectorAll(sel)[idx];
          if (el) el.addEventListener('click', (e) => e.preventDefault(), { once: true });
        }, { sel: elInfo.locator, idx: index });
      }
      await page.keyboard.press('Enter');
      await page.waitForTimeout(150);
      responded = true;
    } else if (elInfo.tag === 'BUTTON') {
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
    page.on('console', (msg) => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
    page.on('pageerror', (err) => consoleErrors.push(err.message));
    page.on('response', (resp) => { if (resp.status() === 404) consoleErrors.push(`404: ${resp.url()}`); });

    await page.goto(BASE_URL + block.url, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);

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
        .map((el, i) => {
          const classes = el.className.split(/\s+/).filter(Boolean);
          const stableClass = classes.find((c) => c.startsWith('basalio-')) || classes[0];
          return {
            tag: el.tagName,
            className: el.className,
            role: el.getAttribute('role') || '',
            name: el.getAttribute('aria-label') || el.textContent.trim() || el.getAttribute('title') || '',
            href: el.getAttribute('href') || '',
            locator: stableClass ? `.${stableClass}` : el.tagName.toLowerCase(),
          };
        });
    });

    const tested = [];
    for (let i = 0; i < elements.length; i++) {
      const test = await testElement(page, elements[i], i);
      tested.push({ ...elements[i], ...test });
    }

    const screenshotPath = path.join(SCREENSHOT_DIR, `tested-up-to-${block.slug}-gap.png`);
    await page.screenshot({ path: screenshotPath, fullPage: true });

    results.push({ block: block.label, url: BASE_URL + block.url, elements: tested, consoleErrors, screenshot: screenshotPath });
    await page.close();
  }

  await browser.close();

  console.log('\n=== Gap block keyboard verification ===\n');
  for (const r of results) {
    console.log(`--- ${r.block} ---`);
    console.log(`URL: ${r.url}`);
    console.log(`Console errors: ${r.consoleErrors.length}`);
    r.consoleErrors.forEach((e) => console.log(`  ERROR: ${e}`));
    console.log(`Interactive elements: ${r.elements.length}`);
    r.elements.forEach((e) => {
      const status = e.focusable && e.responded ? 'OK' : 'FAIL';
      console.log(`  [${status}] ${e.tag} ${e.className} | role=${e.role} | name="${e.name}" | focusable=${e.focusable} | responded=${e.responded}`);
      e.errors.forEach((err) => console.log(`       -> ${err}`));
    });
    console.log(`Screenshot: ${r.screenshot}\n`);
  }
})();
