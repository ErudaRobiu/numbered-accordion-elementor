/**
 * Measures the Eruda Spin extension in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 * Headless on purpose: the spin-down is a requestAnimationFrame ramp, and
 * Chrome does not run rAF in a tab that is not visible.
 *
 *   node tests/browser/spin-probe.js
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

const URL = 'http://127.0.0.1:8732/tests/browser/spin.html';

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: 'new', args: ['--no-sandbox'],
  });

  const page = await browser.newPage();
  await page.setViewport({ width: 1100, height: 900 });
  const errs = [];
  page.on('pageerror', e => errs.push(String(e)));

  await page.evaluateOnNewDocument(() => {
    window.__t = sel => getComputedStyle(document.querySelector(sel)).transform;
    window.__deg = sel => {
      const m = /matrix\(([^)]+)\)/.exec(window.__t(sel));
      if (!m) return 0;
      const n = m[1].split(',').map(Number);
      return (Math.atan2(n[1], n[0]) * 180) / Math.PI;
    };
  });
  await page.goto(URL, { waitUntil: 'load' });
  await page.evaluate(() => new Promise(r => setTimeout(r, 300)));

  // --- it turns the thing inside, not the widget ---------------------------
  const which = await page.evaluate(() => ({
    ready: document.getElementById('w1').hasAttribute('data-espin-ready'),
    img: window.__t('#w1 img'),
    widget: window.__t('#w1'),
    cssAnim: getComputedStyle(document.querySelector('#w1 img')).animationName,
  }));
  check('the picture turns, not the widget around it',
    which.img !== 'none' && (which.widget === 'none' || /matrix\(1, 0, 0, 1/.test(which.widget)),
    'picture ' + which.img.slice(0, 28) + '..., widget ' + which.widget);

  check('and the Web Animations one is doing it, not the stylesheet',
    which.ready && which.cssAnim === 'none',
    'ready, CSS animation ' + which.cssAnim);

  const turning = await page.evaluate(async () => {
    const seen = [];
    for (let i = 0; i < 8; i++) {
      seen.push(window.__t('#w1 img'));
      await new Promise(r => setTimeout(r, 80));
    }
    return new Set(seen).size;
  });
  check('it keeps turning on its own', turning >= 6, turning + ' distinct angles over 8 frames');

  // --- it slows to a stop --------------------------------------------------
  const stopping = await page.evaluate(async () => {
    document.getElementById('w1').dispatchEvent(new MouseEvent('mouseenter'));
    const seen = [];
    for (let i = 0; i < 20; i++) {
      seen.push(window.__deg('#w1 img'));
      await new Promise(r => setTimeout(r, 60));
    }
    return seen;
  });
  const steps = stopping.slice(1).map((a, i) => {
    let d = a - stopping[i];
    while (d > 180) d -= 360;
    while (d < -180) d += 360;
    return Math.abs(d);
  });
  const early = steps.slice(0, 3).reduce((a, b) => a + b, 0) / 3;
  const late = steps.slice(-4).reduce((a, b) => a + b, 0) / 4;

  check('hovering slows it down', early > late * 3 && early > 0.2,
    early.toFixed(2) + ' deg per frame at first, ' + late.toFixed(3) + ' at the end');
  check('and it comes to a full stop', late < 0.05, 'final frames moved ' + late.toFixed(4) + ' deg');
  check('it took time to stop rather than stopping on the spot',
    steps.findIndex(s => s < 0.05) > 3,
    'still moving ' + steps.findIndex(s => s < 0.05) + ' frames in');

  const restart = await page.evaluate(async () => {
    document.getElementById('w1').dispatchEvent(new MouseEvent('mouseleave'));
    await new Promise(r => setTimeout(r, 900));
    const seen = [];
    for (let i = 0; i < 5; i++) {
      seen.push(window.__t('#w1 img'));
      await new Promise(r => setTimeout(r, 80));
    }
    return new Set(seen).size;
  });
  check('taking the pointer away starts it again', restart >= 4,
    restart + ' distinct angles over 5 frames');

  // --- the lift ------------------------------------------------------------
  await page.hover('#w1 img');
  await page.evaluate(() => new Promise(r => setTimeout(r, 800)));
  const lifted = await page.evaluate(() => window.__t('#w1'));
  const y = t => { const m = /matrix\(([^)]+)\)/.exec(t || ''); return m ? Number(m[1].split(',')[5]) : 0; };
  check('hovering lifts the widget', y(lifted) <= -8, 'moved ' + y(lifted) + 'px');

  /*
   * The lift is on the widget and the rotation on the picture inside it. If
   * they ever share an element, the lift overwrites the rotation at exactly
   * the moment a pointer arrives -- which is when it matters.
   */
  const separate = await page.evaluate(() => ({
    widget: window.__t('#w1'),
    img: window.__t('#w1 img'),
  }));
  check('the lift and the turn are on different elements',
    /matrix\(1, 0, 0, 1, 0, -?\d/.test(separate.widget) && separate.img !== separate.widget,
    'widget ' + separate.widget + ', picture rotated separately');

  // --- the second widget: whole thing, other way, never stops --------------
  const other = await page.evaluate(async () => {
    const before = window.__t('#w2 .elementor-widget-container');
    document.getElementById('w2').dispatchEvent(new MouseEvent('mouseenter'));
    await new Promise(r => setTimeout(r, 900));
    const seen = [];
    for (let i = 0; i < 5; i++) {
      seen.push(window.__t('#w2 .elementor-widget-container'));
      await new Promise(r => setTimeout(r, 80));
    }
    return { before, moving: new Set(seen).size, img: window.__t('#w2 img') };
  });
  check('set to the whole widget, the container turns and the picture does not',
    other.before !== 'none' && (other.img === 'none' || other.img === 'matrix(1, 0, 0, 1, 0, 0)'),
    'container turning, picture ' + other.img);

  check('set to keep turning, a pointer does not stop it',
    other.moving >= 4,
    other.moving + ' distinct angles while hovered');

  // --- reduced motion ------------------------------------------------------
  await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await page.goto(URL, { waitUntil: 'load' });
  await page.evaluate(() => new Promise(r => setTimeout(r, 400)));
  const calm = await page.evaluate(async () => {
    const seen = [];
    for (let i = 0; i < 5; i++) {
      seen.push(window.__t('#w1 img'));
      await new Promise(r => setTimeout(r, 80));
    }
    return {
      still: new Set(seen).size === 1,
      ready: document.getElementById('w1').hasAttribute('data-espin-ready'),
      css: getComputedStyle(document.querySelector('#w1 img')).animationName,
    };
  });
  check('reduced motion turns nothing at all',
    calm.still && !calm.ready && calm.css === 'none',
    'still=' + calm.still + ', CSS animation ' + calm.css);

  console.log('\nPASS');
  PASS.forEach(p => console.log('  + ' + p));
  if (FAIL.length) {
    console.log('\nFAIL');
    FAIL.forEach(f => console.log('  - ' + f));
  }
  if (errs.length) console.log('\nPAGE ERRORS:\n  ' + errs.join('\n  '));
  console.log('\n' + PASS.length + ' passed, ' + FAIL.length + ' failed\n');

  await browser.close();
  process.exit(FAIL.length || errs.length ? 1 : 0);
})();
