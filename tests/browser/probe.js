/**
 * Measures whether the animations actually animate, in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 */
const puppeteer = require('puppeteer-core');

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: 'new', args: ['--no-sandbox'],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1200, height: 800 });
  const errs = [];
  page.on('pageerror', e => errs.push(String(e)));

  // Start sampling before the page settles.
  await page.goto('http://127.0.0.1:8732/tests/browser/page.html', { waitUntil: 'domcontentloaded' });

  const samples = await page.evaluate(async () => {
    const ids = ['t-fade','t-blur','t-words','t-fade2'];
    const out = {};
    ids.forEach(i => out[i] = []);
    const t0 = performance.now();
    for (let n = 0; n < 24; n++) {
      ids.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        const cs = getComputedStyle(el);
        const inner = el.querySelector('.eanm-i');
        out[id].push({
          t: Math.round(performance.now() - t0),
          o: (inner ? getComputedStyle(inner).opacity : cs.opacity),
          f: cs.filter === 'none' ? '-' : cs.filter,
          x: (inner ? getComputedStyle(inner).transform : cs.transform),
          in: el.hasAttribute('data-eanm-in') ? 1 : 0,
        });
      });
      await new Promise(r => setTimeout(r, 60));
    }
    return out;
  });

  const fired = await page.evaluate(() => window.__fired.map(f => f.type + ':' + f.target + ':' + f.prop));

  const fmt = (id) => {
    const s = samples[id];
    const line = s.map(p => `${p.t}ms o=${p.o}${p.f !== '-' ? ' ' + p.f.replace(/blur\(([\d.]+)px\)/,'blur$1') : ''} in=${p.in}`);
    return line.filter((_, i) => i % 3 === 0 || i < 6).slice(0, 9).join('\n  ');
  };

  for (const id of ['t-fade','t-blur','t-words']) {
    console.log(`\n=== ${id} (above the fold)\n  ` + fmt(id));
  }
  console.log('\n=== t-fade2 (below the fold, must stay hidden)\n  ' + fmt('t-fade2'));
  console.log('\n=== transition events fired:', fired.length);
  console.log('  ' + fired.slice(0,8).join('\n  '));
  if (errs.length) console.log('\nPAGE ERRORS:', errs);
  await browser.close();
})();
