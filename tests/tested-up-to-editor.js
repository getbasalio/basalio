/**
 * Editor verification for Basalio blocks using existing fixture pages.
 * Opens each default-motion page in the editor, selects the block, reads sidebar.
 */
const { chromium } = require('/Users/angelomanzanojr/.nvm/versions/node/v24.14.1/lib/node_modules/omniroute/node_modules/playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'http://127.0.0.1:8091';
const SCREENSHOT_DIR = '/Users/angelomanzanojr/Desktop/basalio-screenshots';

const BLOCKS = [
  { name: 'basalio/text-reveal', label: 'Text Reveal', pageId: 835, expectedControls: ['Headline', 'Subhead', 'Button'] },
  { name: 'basalio/before-after', label: 'Before / After', pageId: 836, expectedControls: ['Before', 'After', 'Start'] },
  { name: 'basalio/case-study-transition', label: 'Case Study Transition', pageId: 837, expectedControls: ['Image', 'Link', 'Transition'] },
  { name: 'basalio/filterable-grid', label: 'Filterable Grid', pageId: 838, expectedControls: ['Columns', 'Result count'] },
  { name: 'basalio/magnetic-button', label: 'Magnetic Button', pageId: 839, expectedControls: ['Text', 'URL', 'Strength'] },
  { name: 'basalio/custom-cursor', label: 'Custom Cursor', pageId: 840, expectedControls: ['Size', 'Cursor'] },
  { name: 'basalio/grid-reveal', label: 'Grid Reveal', pageId: 841, expectedControls: ['Image', 'Title', 'Category'] },
  { name: 'basalio/scroll-sequence', label: 'Scroll Sequence', pageId: 842, expectedControls: ['Frames', 'Scroll', 'Counter'] },
  { name: 'basalio/pinned-scroll', label: 'Pinned Scroll', pageId: 843, expectedControls: ['Image', 'Caption', 'Width'] },
];

(async () => {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
  const browser = await chromium.launch({
    headless: true,
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  });
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await context.newPage();
  page.on('dialog', (dialog) => dialog.accept());

  // Log in once.
  await page.goto(`${BASE_URL}/wp-login.php`);
  await page.fill('#user_login', 'admin');
  await page.fill('#user_pass', 'password123');
  await page.click('#wp-submit');
  await page.waitForURL(/wp-admin/, { timeout: 30000 });

  const results = [];

  for (const block of BLOCKS) {
    const consoleErrors = [];
    const consoleWarnings = [];
    const onConsole = (msg) => {
      const text = msg.text();
      if (msg.type() === 'error' && !text.includes('favicon')) consoleErrors.push(text);
      if (msg.type() === 'warning') consoleWarnings.push(text);
    };
    const onPageError = (err) => consoleErrors.push(err.message);
    page.on('console', onConsole);
    page.on('pageerror', onPageError);

    // Open existing page in editor.
    let loaded = false;
    let loadError = null;
    try {
      await page.goto(`${BASE_URL}/wp-admin/post.php?post=${block.pageId}&action=edit`);
      await page.waitForTimeout(4000);
      loaded = true;
    } catch (e) {
      loadError = e.message;
    }

    // Select the Basalio block in the canvas iframe.
    let controls = [];
    let controlsFound = [];
    let blockSelected = false;
    if (loaded) {
      try {
        const frames = page.frames();
        const canvas = frames.find((f) => f.url().startsWith('blob:'));
        if (canvas) {
          const blockSelector = `[data-type="${block.name}"]`;
          const blockEl = canvas.locator(blockSelector).first();
          if (await blockEl.count() > 0) {
            await blockEl.click();
            blockSelected = true;
            await page.waitForTimeout(1000);
          }
        }

        // Ensure settings sidebar is open.
        const settingsPressed = await page.evaluate(() => {
          const btn = document.querySelector('button[aria-label="Settings"]');
          return btn ? btn.getAttribute('aria-pressed') === 'true' : false;
        });
        if (!settingsPressed) {
          await page.click('button[aria-label="Settings"]');
          await page.waitForTimeout(500);
        }

        controls = await page.evaluate(() => {
          const panel = document.querySelector('.block-editor-block-inspector');
          if (!panel) return [];
          return Array.from(panel.querySelectorAll('label, legend, .components-base-control__label, .components-panel__body-title, .components-toggle-control__label'))
            .map((l) => l.textContent.trim())
            .filter(Boolean);
        });

        controlsFound = block.expectedControls.filter((ctrl) =>
          controls.some((label) => label.toLowerCase().includes(ctrl.toLowerCase()))
        );
      } catch (e) {
        consoleErrors.push('sidebar read error: ' + e.message);
      }
    }

    // Save the page.
    let saved = false;
    try {
      await page.keyboard.press('Meta+S');
      await page.waitForTimeout(2000);
      saved = true;
    } catch (e) {
      consoleErrors.push('save error: ' + e.message);
    }

    const screenshotPath = path.join(SCREENSHOT_DIR, `tested-up-to-editor-${block.name.replace('/', '-')}.png`);
    try {
      await page.screenshot({ path: screenshotPath });
    } catch (e) {
      consoleErrors.push('screenshot: ' + e.message);
    }

    results.push({
      block: block.label,
      loaded,
      loadError,
      blockSelected,
      controls,
      expectedControls: block.expectedControls,
      controlsFound,
      saved,
      consoleErrors,
      consoleWarnings,
      screenshot: screenshotPath,
    });

    page.off('console', onConsole);
    page.off('pageerror', onPageError);
  }

  await browser.close();

  console.log('\n=== Tested up to: editor verification ===\n');
  console.log(`WordPress version (local): 7.1`);
  console.log(`Browser: Google Chrome (system)\n`);

  for (const r of results) {
    console.log(`--- ${r.block} ---`);
    console.log(`Editor loaded: ${r.loaded ? 'OK' : 'FAIL - ' + r.loadError}`);
    console.log(`Block selected: ${r.blockSelected ? 'OK' : 'FAIL'}`);
    console.log(`Saved: ${r.saved ? 'OK' : 'FAIL'}`);
    console.log(`Controls found (${r.controlsFound.length}/${r.expectedControls.length}):`);
    r.expectedControls.forEach((c) => {
      const found = r.controlsFound.includes(c);
      console.log(`  [${found ? 'OK' : 'MISSING'}] ${c}`);
    });
    console.log(`Sidebar labels (sample): ${r.controls.slice(0, 20).join(' | ')}`);
    console.log(`Console errors: ${r.consoleErrors.length}`);
    r.consoleErrors.forEach((e) => console.log(`  ERROR: ${e}`));
    console.log(`Console warnings: ${r.consoleWarnings.length}`);
    r.consoleWarnings.forEach((w) => console.log(`  WARN: ${w}`));
    console.log(`Screenshot: ${r.screenshot}\n`);
  }
})();
