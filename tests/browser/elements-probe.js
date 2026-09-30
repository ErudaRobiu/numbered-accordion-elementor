/**
 * Measures Element List and Annotated Mark in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 *   php tests/browser/elements-fixture.php > tests/browser/elements.html
 *   node tests/browser/elements-probe.js [screenshot-dir]
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];
const URL = 'http://127.0.0.1:8732/tests/browser/elements.html';
const SHOTS = process.argv[2] || '';
const sleep = ms => new Promise(r => setTimeout(r, ms));

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

const lit = () => {
  const on = sel => [...document.querySelectorAll(sel + ' .is-on')].map(e => e.className.baseVal !== undefined ? 'line:' + e.dataset.key : e.className.split(' ')[0].replace(/^e(el|am)__/, '') + ':' + e.dataset.key).sort().join(',');
  return { a: on('#a'), b: on('#b'), c: on('#c'), d: on('#d'), e: on('#e'),
    bHas: document.querySelector('#b .eam').classList.contains('has-on') };
};

// Where each line starts and ends against its tag and dot, and whether the
// tags stay inside the stage and clear of each other.
const geometry = sel => {
  const root = document.querySelector(sel + ' .eam');
  const s = root.getBoundingClientRect();
  const out = { width: Math.round(s.width), lines: [], inside: true, overlap: false, scroll: document.documentElement.scrollWidth };
  const tags = [...root.querySelectorAll('.eam__tag')];
  tags.forEach(t => {
    const r = t.getBoundingClientRect();
    if (r.left < s.left - 1 || r.right > s.right + 1 || r.top < s.top - 1 || r.bottom > s.bottom + 1) out.inside = false;
    tags.forEach(u => {
      if (u === t) return;
      const q = u.getBoundingClientRect();
      if (r.left < q.right && q.left < r.right && r.top < q.bottom && q.top < r.bottom) out.overlap = true;
    });
  });
  root.querySelectorAll('.eam__line').forEach(p => {
    const key = p.dataset.key;
    const t = root.querySelector('.eam__tag[data-key="' + key + '"]').getBoundingClientRect();
    const d = root.querySelector('.eam__dot[data-key="' + key + '"]').getBoundingClientRect();
    const n = p.getTotalLength();
    const a = p.getPointAtLength(0), b = p.getPointAtLength(n);
    const left = p.classList.contains('eam__line--l');
    out.lines.push({
      key,
      startOnTag: Math.abs(s.left + a.x - (left ? t.right : t.left)) < 1.5 && Math.abs(s.top + a.y - (t.top + t.height / 2)) < 1.5,
      endOnDot: Math.abs(s.left + b.x - (d.left + d.width / 2)) < 1.5 && Math.abs(s.top + b.y - (d.top + d.height / 2)) < 1.5,
    });
  });
  return out;
};

// Pixels along the middle of the fire line: is it painted?
async function linePainted(page, sel) {
  const pt = await page.evaluate(sel => {
    const root = document.querySelector(sel + ' .eam');
    const p = root.querySelector('.eam__line[data-key="fire"]');
    const s = root.getBoundingClientRect();
    const m = p.getPointAtLength(p.getTotalLength() * 0.25);
    // Clip is in document pixels.
    return { x: Math.round(s.left + m.x + scrollX), y: Math.round(s.top + m.y + scrollY) };
  }, sel);
  const png = await page.screenshot({ clip: { x: pt.x - 3, y: pt.y - 3, width: 7, height: 7 }, encoding: 'base64' });
  return page.evaluate(async src => {
    const img = new Image();
    img.src = 'data:image/png;base64,' + src;
    await img.decode();
    const c = document.createElement('canvas');
    c.width = img.width; c.height = img.height;
    const x = c.getContext('2d');
    x.drawImage(img, 0, 0);
    const d = x.getImageData(0, 0, c.width, c.height).data;
    let dark = 0;
    for (let i = 0; i < d.length; i += 4) if (d[i] + d[i + 1] + d[i + 2] < 640) dark++;
    return dark;
  }, png);
}

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: 'new', args: ['--no-sandbox'],
  });
  const errs = [];
  const page = await browser.newPage();
  page.on('pageerror', e => errs.push(String(e)));
  await page.setViewport({ width: 1440, height: 900 });
  await page.goto(URL, { waitUntil: 'networkidle0' });
  await page.evaluate(() => document.fonts.ready);
  await sleep(200);

  /* -------------------------------------------------------- entrance --- */

  let armed = await page.evaluate(() => ({ b: document.querySelector('#b .eam').className, e: document.querySelector('#e .eam').className }));
  check('entrance: armed off screen, a mark without it never arms', /eam--armed/.test(armed.b) && !/is-in/.test(armed.b) && !/armed/.test(armed.e), JSON.stringify(armed));
  const hidden = await page.evaluate(() => getComputedStyle(document.querySelector('#b .eam__tag')).opacity);
  check('entrance: tags hidden until it arrives', hidden === '0', hidden);

  await page.evaluate(() => document.querySelector('#b').scrollIntoView({ block: 'center' }));
  await sleep(300);
  const mid = await page.evaluate(() => document.querySelector('#b .eam').className);
  check('entrance: plays on arrival', /is-in/.test(mid) && /armed/.test(mid), mid);
  await sleep(1800);
  const done = await page.evaluate(() => ({ cls: document.querySelector('#b .eam').className, op: [...document.querySelectorAll('#b .eam__tag, #b .eam__dot, #b .eam__logo')].every(e => getComputedStyle(e).opacity === '1') }));
  check('entrance: disarmed after, everything shown', !/armed/.test(done.cls) && done.op, JSON.stringify(done));
  const ink = await linePainted(page, '#b');
  check('entrance: the line is painted once drawn', ink > 0, ink + ' dark px');

  /* ------------------------------------------------------- geometry --- */

  for (const [w, h] of [[1440, 900], [1100, 900], [767, 1000], [360, 800], [320, 700]]) {
    await page.setViewport({ width: w, height: h });
    await sleep(350);
    for (const sel of ['#b', '#d', '#e']) {
      const g = await page.evaluate(geometry, sel);
      const bad = g.lines.filter(l => !l.startOnTag || !l.endOnDot).map(l => l.key);
      check(`${w} ${sel}: lines run tag edge to dot`, bad.length === 0, bad.join(',') + ' w' + g.width);
      check(`${w} ${sel}: tags inside the stage, apart`, g.inside && !g.overlap, JSON.stringify({ inside: g.inside, overlap: g.overlap, w: g.width }));
    }
    const pg = await page.evaluate(() => ({
      scroll: document.documentElement.scrollWidth,
      spill: [...document.querySelectorAll('.eel__row')].some(r => r.scrollWidth > r.clientWidth + 1),
      tile: Math.round(document.querySelector('#a .eel__tile').getBoundingClientRect().width),
      subBelow: (() => { const n = document.querySelector('#a .eel__name').getBoundingClientRect(), s = document.querySelector('#a .eel__sub').getBoundingClientRect(); return s.top >= n.bottom - 1; })(),
      tag: parseFloat(getComputedStyle(document.querySelector('#e .eam__tag')).fontSize),
    }));
    check(`${w}: no sideways scroll, nothing spills`, pg.scroll === w && !pg.spill, JSON.stringify(pg));
    if (w <= 767) check(`${w}: 44px tiles`, pg.tile === 44, pg.tile);
    if (w <= 400) check(`${w}: subtitle under the name`, pg.subBelow, '');
    check(`${w}: the 300px mark uses small tags`, pg.tag <= 20, pg.tag);
    if (SHOTS) {
      for (const id of ['a', 'b', 'e']) {
        await page.evaluate(id => document.querySelector('#' + id).scrollIntoView({ block: 'center' }), id);
        await sleep(150);
        await page.screenshot({ path: `${SHOTS}/elements-${w}-${id}.png` });
      }
    }
  }

  /* --------------------------------------------------------- linking --- */

  await page.setViewport({ width: 1440, height: 900 });
  await page.evaluate(() => document.querySelector('#b').scrollIntoView({ block: 'center' }));
  await sleep(300);

  await page.hover('#b .eam__tag[data-key="water"]');
  await sleep(100);
  let s = await page.evaluate(lit);
  check('hover a tag: its tag, line and dot light', s.b === 'dot:water,line:water,tag:water' && s.bHas, s.b);
  check('hover a tag: the list lights the same key', s.a === 'row:water', s.a);
  check('hover a tag: other groups stay dark', !s.c && !s.d && !s.e, JSON.stringify(s));
  await sleep(300);
  const fade = await page.evaluate(() => [getComputedStyle(document.querySelector('#b .eam__tag[data-key="air"]')).opacity, getComputedStyle(document.querySelector('#b .eam__tag[data-key="water"]')).opacity]);
  check('hover a tag: the rest fade back', fade[0] === '0.35' && fade[1] === '1', fade.join(' '));

  await page.hover('#b .eam__tag[data-key="earth"]');
  await sleep(100);
  s = await page.evaluate(lit);
  check('tag to tag: moves straight over', s.a === 'row:earth' && /tag:earth/.test(s.b), s.a + ' ' + s.b);

  await page.mouse.move(5, 5);
  await sleep(100);
  s = await page.evaluate(lit);
  check('leaving clears everywhere', !s.a && !s.b && !s.bHas, JSON.stringify(s));

  await page.evaluate(() => document.querySelector('#a').scrollIntoView({ block: 'center' }));
  await page.hover('#a .eel__row[data-key="air"] .eel__text');
  await sleep(300);
  s = await page.evaluate(lit);
  check('hover a row: the mark lights the same key', s.b === 'dot:air,line:air,tag:air' && s.a === 'row:air', s.a + ' | ' + s.b);
  const row = await page.evaluate(() => { const r = document.querySelector('#a .eel__row[data-key="air"]'); return { pad: getComputedStyle(r).paddingLeft, name: getComputedStyle(r.querySelector('.eel__name')).color }; });
  check('active row: steps in, name goes green', row.pad === '10px' && row.name === 'rgb(7, 134, 79)', JSON.stringify(row));
  await page.mouse.move(5, 5);
  await sleep(100);

  // Keyboard.
  await page.focus('#a .eel__row[data-key="fire"]');
  await sleep(50);
  s = await page.evaluate(lit);
  check('focus a row: lights both', s.a === 'row:fire' && /tag:fire/.test(s.b), s.a + ' ' + s.b);
  await page.keyboard.press('Tab');
  await sleep(50);
  s = await page.evaluate(lit);
  check('tab to the next row: follows', s.a === 'row:water' && /tag:water/.test(s.b), s.a);
  await page.keyboard.press('Escape');
  await sleep(50);
  s = await page.evaluate(lit);
  check('Esc clears', !s.a && !s.b, JSON.stringify(s));
  await page.evaluate(() => document.activeElement.blur());

  // Anyone may announce.
  await page.evaluate(() => document.dispatchEvent(new CustomEvent('eruda:link', { detail: { group: 'other', key: 'fire' } })));
  s = await page.evaluate(lit);
  check('an outside eruda:link event drives its group only', /tag:fire/.test(s.e) && !s.a && !s.b, s.e);
  await page.evaluate(() => document.dispatchEvent(new CustomEvent('eruda:link', { detail: { group: 'other', key: '' } })));

  const theme = await page.evaluate(() => {
    const r = document.querySelector('#a .eel__row');
    return { marker: getComputedStyle(r).listStyleType, pad: getComputedStyle(document.querySelector('#a .eel')).paddingLeft, em: getComputedStyle(r.querySelector('.eel__sub')).fontStyle, fig: getComputedStyle(document.querySelector('#b .eam')).marginLeft };
  });
  check('hostile theme: no bullets, no indent, no italics', theme.marker === 'none' && theme.pad === '0px' && theme.em === 'normal', JSON.stringify(theme));

  await page.close();

  /* ------------------------------------------------------------ touch --- */

  const t = await browser.newPage();
  t.on('pageerror', e => errs.push(String(e)));
  await t.setViewport({ width: 390, height: 844, hasTouch: true, isMobile: true });
  await t.goto(URL, { waitUntil: 'networkidle0' });
  await t.evaluate(() => document.querySelector('#b').scrollIntoView({ block: 'center' }));
  await sleep(2200);
  await t.tap('#b .eam__tag[data-key="fire"]');
  await sleep(150);
  s = await t.evaluate(lit);
  check('touch: a tap lights both', s.a === 'row:fire' && /tag:fire/.test(s.b), s.a + ' ' + s.b);
  await t.tap('#b .eam__tag[data-key="fire"]');
  await sleep(150);
  s = await t.evaluate(lit);
  check('touch: tapping it again clears', !s.a && !s.b, JSON.stringify(s));
  await t.tap('#b .eam__tag[data-key="air"]');
  await sleep(150);
  await t.tap('#b .eam__tag[data-key="earth"]');
  await sleep(150);
  s = await t.evaluate(lit);
  check('touch: tapping another moves over', s.a === 'row:earth', s.a);
  await t.touchscreen.tap(10, 10);
  await sleep(150);
  s = await t.evaluate(lit);
  check('touch: tapping outside clears', !s.a && !s.b, JSON.stringify(s));
  await t.close();

  /* ------------------------------------------------------------ cycle --- */

  const c = await browser.newPage();
  c.on('pageerror', e => errs.push(String(e)));
  await c.setViewport({ width: 1440, height: 900 });
  await c.goto(URL, { waitUntil: 'networkidle0' });
  await c.evaluate(() => document.querySelector('#c').scrollIntoView({ block: 'start' }));
  await sleep(3300);
  s = await c.evaluate(lit);
  check('cycle: first step after 3s, in both widgets', s.c === 'row:air' && /tag:air/.test(s.d), s.c + ' ' + s.d);
  await sleep(3000);
  s = await c.evaluate(lit);
  check('cycle: steps on', s.c === 'row:fire', s.c);
  check('cycle: page 28 group untouched', !s.a && !s.b, JSON.stringify(s));
  await c.hover('#c .eel__row[data-key="earth"]');
  await sleep(100);
  await c.mouse.move(5, 5);
  await sleep(3400);
  s = await c.evaluate(lit);
  check('cycle: pointing stops it for good', !s.c && !s.d, JSON.stringify(s));
  await c.close();

  /* --------------------------------------------------- less motion --- */

  const r = await browser.newPage();
  r.on('pageerror', e => errs.push(String(e)));
  await r.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await r.setViewport({ width: 1440, height: 900 });
  await r.goto(URL, { waitUntil: 'networkidle0' });
  const rm = await r.evaluate(() => ({ armed: document.querySelectorAll('.eam--armed').length, op: getComputedStyle(document.querySelector('#b .eam__tag')).opacity }));
  check('less motion: never armed, shown at once', rm.armed === 0 && rm.op === '1', JSON.stringify(rm));
  await r.evaluate(() => document.querySelector('#c').scrollIntoView({ block: 'start' }));
  await sleep(3400);
  s = await r.evaluate(lit);
  check('less motion: no cycling', !s.c, s.c);
  await r.hover('#b .eam__tag[data-key="fire"]').catch(() => {});
  await r.evaluate(() => document.querySelector('#b').scrollIntoView({ block: 'center' }));
  await r.hover('#b .eam__tag[data-key="fire"]');
  await sleep(50);
  s = await r.evaluate(lit);
  check('less motion: linking still works', s.a === 'row:fire', s.a);
  await r.close();

  check('no script errors', errs.length === 0, errs.join(' | '));

  await browser.close();
  console.log(PASS.map(p => 'PASS  ' + p).join('\n'));
  console.log(FAIL.map(f => 'FAIL  ' + f).join('\n'));
  console.log(`\n${PASS.length} passed, ${FAIL.length} failed`);
  process.exit(FAIL.length ? 1 : 0);
})();
