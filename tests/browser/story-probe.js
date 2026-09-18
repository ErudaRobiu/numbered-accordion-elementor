/**
 * Measures the Scroll Story in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 * Headless on purpose: a scrubbed animation is driven by requestAnimationFrame,
 * and Chrome does not run rAF in a tab that is not visible. Driving a real
 * window means every measurement silently freezes the moment the window loses
 * focus, which reads exactly like a broken build.
 *
 *   node tests/browser/story-probe.js
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: 'new', args: ['--no-sandbox'],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1280, height: 900 });
  const errs = [];
  page.on('pageerror', e => errs.push(String(e)));

  await page.goto('http://127.0.0.1:8732/tests/browser/story.html', { waitUntil: 'load' });

  await page.evaluate(() => {
    window.__lit = () => [...document.querySelectorAll('.estry__item')]
      .map(it => [...it.querySelectorAll('.estry-c')].filter(c => c.classList.contains('is-on')).length);
    window.__chars = () => [...document.querySelectorAll('.estry__item')]
      .map(it => it.querySelectorAll('.estry-c').length);
    window.__band = () => {
      const p = document.querySelector('clipPath path');
      if (!p) return null;
      const cmds = p.getAttribute('d').trim().match(/[MLAZ][^MLAZ]*/g) || [];
      const pts = [];
      for (const c of cmds) {
        const n = (c.match(/-?[\d.]+/g) || []).map(Number);
        if (n.length >= 2) pts.push([n[n.length - 2], n[n.length - 1]]);
      }
      const h = Math.round(document.querySelector('.estry__frame').getBoundingClientRect().height);
      const ys = pts.filter(q => q[1] > 2 && q[1] < h - 2).map(q => q[1]);
      return { h, lo: Math.min(...ys), hi: Math.max(...ys) };
    };
    window.__at = async (y) => {
      window.scrollTo(0, y);
      await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
      await new Promise(r => setTimeout(r, 40));
      return { y, lit: window.__lit(), band: window.__band() };
    };
  });

  const max = await page.evaluate(() => document.body.scrollHeight - innerHeight);
  const stops = [0, 0.15, 0.3, 0.45, 0.6, 0.75, 0.9, 1].map(f => Math.round(f * max));

  const fwd = [];
  for (const y of stops) fwd.push(await page.evaluate(y => window.__at(y), y));
  const rev = [];
  for (const y of [...stops].reverse()) rev.push(await page.evaluate(y => window.__at(y), y));

  const chars = await page.evaluate(() => window.__chars());

  // --- the sweep runs forwards --------------------------------------------
  const firstLit = fwd.map(s => s.lit[0]);
  check('sweep advances with scroll',
    firstLit[0] === 0 && firstLit[firstLit.length - 1] === chars[0] &&
    firstLit.every((v, i) => i === 0 || v >= firstLit[i - 1]),
    'item 0: ' + firstLit.join(' -> '));

  const lastLit = fwd.map(s => s.lit[2]);
  check('the last item lights too',
    lastLit[0] === 0 && lastLit[lastLit.length - 1] === chars[2],
    'item 2: ' + lastLit.join(' -> '));

  check('the sweep is partial in between',
    fwd.some(s => s.lit[0] > 0 && s.lit[0] < chars[0]),
    'a mid-sweep frame exists');

  // --- and backwards -------------------------------------------------------
  const back = rev.map(s => s.lit[2]);
  check('sweep retreats when you scroll back',
    back[0] === chars[2] && back[back.length - 1] === 0 &&
    back.every((v, i) => i === 0 || v <= back[i - 1]),
    'item 2 reversing: ' + back.join(' -> '));

  const pairs = stops.map((y, i) => [fwd[i].lit.join(), rev[rev.length - 1 - i].lit.join()]);
  check('the same scroll position gives the same sweep either way',
    pairs.every(p => p[0] === p[1]),
    pairs.filter(p => p[0] !== p[1]).map(p => p[0] + ' vs ' + p[1]).join(' | ') || 'all match');

  // --- the notch travels, and stops short of both ends ---------------------
  const bands = fwd.filter(s => s.band).map(s => s.band);
  const h = bands[0].h;
  const los = bands.map(b => b.lo);
  const his = bands.map(b => b.hi);
  check('the notch travels with the scroll',
    Math.max(...los) - Math.min(...los) > 1,
    'top edge ' + Math.min(...los).toFixed(1) + ' -> ' + Math.max(...los).toFixed(1) + ' of ' + h);

  const clearTop = Math.min(...los);
  const clearBottom = h - Math.max(...his);
  check('the notch never reaches either corner',
    clearTop > 8 && clearBottom > 8,
    'clearance ' + clearTop.toFixed(1) + 'px top, ' + clearBottom.toFixed(1) + 'px bottom');

  check('that clearance is even at both ends',
    Math.abs(clearTop - clearBottom) < 2,
    clearTop.toFixed(1) + ' vs ' + clearBottom.toFixed(1));

  // --- the panel is centred on the screen ---------------------------------
  const centring = await page.evaluate(async (max) => {
    const out = [];
    for (const f of [0.3, 0.5, 0.7]) {
      window.scrollTo(0, Math.round(f * max));
      await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
      const r = document.querySelector('.estry__media').getBoundingClientRect();
      out.push({ top: Math.round(r.top), bottom: Math.round(innerHeight - r.bottom) });
    }
    return out;
  }, max);
  check('the panel sits centred while it is pinned',
    centring.every(c => Math.abs(c.top - c.bottom) <= 1),
    centring.map(c => c.top + '/' + c.bottom).join(' '));

  // --- media fills the panel, whatever shape it is -------------------------
  /*
   * Measured off the layout box, not the painted one. The active picture is
   * always mid-drift, so its painted box is a few per cent larger than the
   * panel -- which is the intent. What must never happen is a picture smaller
   * than the panel in either direction, because that is the gap that used to
   * show the panel's background through as a black band.
   */
  const fill = await page.evaluate(() => {
    const frame = document.querySelector('.estry__frame');
    const w = frame.clientWidth;
    const h = frame.clientHeight;
    return [...document.querySelectorAll('.estry__img')].map(img => ({
      nat: img.naturalWidth + 'x' + img.naturalHeight,
      fit: getComputedStyle(img).objectFit,
      box: img.offsetWidth + 'x' + img.offsetHeight,
      covers: img.offsetWidth >= w - 1 && img.offsetHeight >= h - 1,
    }));
  });
  check('every picture fills the panel, whatever shape it is',
    fill.every(f => f.covers && f.fit === 'cover'),
    fill.map(f => f.nat + ' -> ' + f.box + (f.covers ? ' fills' : ' LEAVES A GAP')).join(', '));

  // --- the entrance actually animates --------------------------------------
  const entrance = await page.evaluate(async (max) => {
    window.scrollTo(0, Math.round(0.1 * max));
    await new Promise(r => setTimeout(r, 1200));
    const slide = document.querySelectorAll('.estry__slide')[1];
    window.scrollTo(0, Math.round(0.45 * max));
    const seen = [];
    for (let i = 0; i < 14; i++) {
      await new Promise(r => requestAnimationFrame(r));
      const cs = getComputedStyle(slide);
      seen.push({
        clip: cs.clipPath,
        inner: getComputedStyle(slide.querySelector('.estry__inner')).transform,
        z: slide.style.zIndex,
      });
      await new Promise(r => setTimeout(r, 45));
    }
    return seen;
  }, max);

  const clips = [...new Set(entrance.map(e => e.clip))];
  check('the wipe animates rather than snapping',
    clips.length > 2 && entrance.some(e => /inset\(\s*[1-9]/.test(e.clip)),
    clips.length + ' distinct clip values, e.g. ' + clips.slice(0, 3).join(' / '));

  const inners = [...new Set(entrance.map(e => e.inner))];
  check('the picture parallax survives the drift animation',
    inners.length > 2,
    inners.length + ' distinct inner transforms');

  const zs = await page.evaluate(() =>
    [...document.querySelectorAll('.estry__slide')].map(s => Number(s.style.zIndex || 0)));
  const onZ = await page.evaluate(() => {
    const on = document.querySelector('.estry__slide[data-estry-on]');
    return on ? Number(on.style.zIndex || 0) : -1;
  });
  check('the item being read is on top of the stack',
    onZ === Math.max(...zs),
    'active z=' + onZ + ' of [' + zs.join(', ') + ']');

  // --- report --------------------------------------------------------------
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
