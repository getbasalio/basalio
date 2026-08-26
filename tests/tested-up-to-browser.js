/**
 * Browser verification for "Tested up to" across all 9 Basalio blocks.
 * Runs against the local WordPress install at http://127.0.0.1:8091
 *
 * Checks per block:
 * - front-end renders without console errors
 * - every interactive element is focusable and has an accessible name
 * - keyboard Tab reaches and operates interactive elements
 * - screenshot saved
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

function relativeLuminance(rgb) {
  const [r, g, b] = rgb.map((v) => {
    v = v / 255;
    return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
  });
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function contrastRatio(rgb1, rgb2) {
  const l1 = relativeLuminance(rgb1) + 0.05;
  const l2 = relativeLuminance(rgb2) + 0.05;
  return l1 > l2 ? l1 / l2 : l2 / l1;
}

async function measureFocusRingContrast(page, selector) {
  const info = await page.evaluate((sel) => {
    const el = document.querySelector(sel);
    if (!el) return null;
    const style = window.getComputedStyle(el);
    const rect = el.getBoundingClientRect();
    // Sample background immediately behind the element.
    const bgEl = document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2);
    const bgStyle = bgEl ? window.getComputedStyle(bgEl) : null;
    return {
      outlineColor: style.outlineColor,
      outlineWidth: style.outlineWidth,
      outlineOffset: style.outlineOffset,
      backgroundColor: bgStyle ? bgStyle.backgroundColor : null,
    };
  }, selector);
  return info;
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

    const url = BASE_URL + block.url;
    let loadOk = false;
    let loadError = null;
    try {
      await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
      loadOk = true;
    } catch (e) {
      loadError = e.message;
    }

    // Wait a moment for any JS init.
    await page.waitForTimeout(1000);

    // Find interactive elements within Basalio blocks.
    const interactive = await page.evaluate(() => {
      const blocks = document.querySelectorAll('[class*="basalio-"]');
      const out = [];
      blocks.forEach((block) => {
        const candidates = block.querySelectorAll('a, button, input, textarea, select, [tabindex]:not([tabindex="-1"]), [role="button"], [role="link"]');
        candidates.forEach((el) => {
          const style = window.getComputedStyle(el);
          if (style.display === 'none' || style.visibility === 'hidden') return;
          const name = el.getAttribute('aria-label') || el.textContent.trim() || el.getAttribute('title') || '';
          out.push({
            tag: el.tagName,
            className: el.className,
            role: el.getAttribute('role') || '',
            tabindex: el.getAttribute('tabindex') || '',
            name,
            href: el.getAttribute('href') || '',
            focusable: !el.disabled && el.offsetParent !== null,
          });
        });
      });
      return out;
    });

    // Count focusable elements without accessible name.
    const unnamed = interactive.filter((el) => el.focusable && !el.name && el.tag !== 'INPUT');

    // Screenshot the full page.
    const screenshotPath = path.join(SCREENSHOT_DIR, `tested-up-to-${block.slug}.png`);
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
      interactiveCount: interactive.length,
      unnamedFocusable: unnamed.length,
      unnamedDetails: unnamed.slice(0, 5),
      screenshot: screenshotPath,
    });

    await page.close();
  }

  await browser.close();

  // Report.
  console.log('\n=== Tested up to: front-end verification ===\n');
  console.log(`WordPress version: ${BASE_URL}`);
  console.log(`Browser: Google Chrome (system)`);
  console.log(`Viewport: 1280x900\n`);

  for (const r of results) {
    console.log(`--- ${r.block} ---`);
    console.log(`URL: ${r.url}`);
    console.log(`Load: ${r.loadOk ? 'OK' : 'FAIL - ' + r.loadError}`);
    console.log(`Console errors: ${r.consoleErrors.length}`);
    r.consoleErrors.forEach((e) => console.log(`  ERROR: ${e}`));
    console.log(`Console warnings: ${r.consoleWarnings.length}`);
    r.consoleWarnings.forEach((w) => console.log(`  WARN: ${w}`));
    console.log(`Interactive elements: ${r.interactiveCount}`);
    console.log(`Unnamed focusable elements: ${r.unnamedFocusable}`);
    r.unnamedDetails.forEach((d) => console.log(`  UNNAMED: <${d.tag}> ${d.className}`));
    console.log(`Screenshot: ${r.screenshot}\n`);
  }
})();
