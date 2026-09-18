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
    /*
     * The band's two ends are the only points sitting on the left edge that
     * are not a corner arc landing there. Now that the corners are rounded,
     * "any point that is not a corner of the box" is no longer good enough:
     * the corner arcs end at (0, radius) and (0, height - radius), and taking
     * those for band ends makes a band that never appears to move.
     */
    window.__band = () => {
      const p = document.querySelector('clipPath path');
      if (!p) return null;
      const frame = document.querySelector('.estry__frame');
      const cmds = p.getAttribute('d').trim().match(/[MLAZ][^MLAZ]*/g) || [];
      const pts = [];
      for (const c of cmds) {
        const n = (c.match(/-?[\d.]+/g) || []).map(Number);
        if (n.length >= 2) pts.push([n[n.length - 2], n[n.length - 1]]);
      }
      const h = Math.round(frame.getBoundingClientRect().height);
      const c = parseFloat(getComputedStyle(frame).getPropertyValue('--estry-radius')) || 0;
      const depth = parseFloat(getComputedStyle(frame).getPropertyValue('--estry-notch')) || 0;
      // The four shoulder arcs are the notch; the point before the first and
      // the point after the last are its two ends. Found structurally rather
      // than by position, because a band end is allowed to land exactly on a
      // corner arc's endpoint and filtering by proximity silently loses it.
      const shoulder = 0.939 * depth;
      const idx = [];
      cmds.forEach((cmd, i) => {
        if (cmd[0] !== 'A') return;
        const n = (cmd.match(/-?[\d.]+/g) || []).map(Number);
        if (Math.abs(n[0] - shoulder) < 0.05) idx.push(i);
      });
      if (idx.length !== 4) return null;
      const lo = pts[idx[3]][1];
      const hi = pts[idx[0] - 1][1];
      return { h, c, lo, hi };
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

  // Clearance has to be read at the two extremes of the travel, which the
  // sampled stops do not necessarily hit; see clearAll below.

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

  // --- the corner radius survives the notch --------------------------------
  const corners = await page.evaluate(() => {
    const d = document.querySelector('clipPath path').getAttribute('d');
    const frame = document.querySelector('.estry__frame');
    const radius = parseFloat(getComputedStyle(frame).getPropertyValue('--estry-radius'));
    // Corner arcs are the ones whose radius equals the corner radius; the
    // notch shoulders use a radius derived from the notch depth.
    const depth = parseFloat(getComputedStyle(frame).getPropertyValue('--estry-notch')) || 0;
    const shoulder = 0.939 * depth;
    const arcs = (d.match(/A\s+([\d.]+),/g) || []).map(a => parseFloat(a.slice(1)));
    const atCorner = arcs.filter(a => Math.abs(a - radius) < 0.6).length;
    return { radius, depth, shoulder, arcs, atCorner, collides: Math.abs(shoulder - radius) < 1 };
  });
  check('a notched panel still has rounded corners',
    corners.radius > 0 && !corners.collides && corners.atCorner === 4,
    corners.collides
      ? 'fixture problem: corner radius ' + corners.radius + ' is the same as the notch shoulder ' +
        corners.shoulder.toFixed(2) + ', so the two cannot be told apart'
      : 'radius ' + corners.radius + 'px, ' + corners.atCorner + ' corner arcs in the path');

  const clearAll = await page.evaluate(async (max) => {
    const out = [];
    for (const f of [0, 0.5, 1]) {
      window.scrollTo(0, Math.round(f * max));
      await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
      out.push(window.__band());
    }
    return out;
  }, max);
  const radius = corners.radius;
  check('the notch band never runs into a rounded corner',
    clearAll.every(b => b.lo >= radius - 1 && b.hi <= b.h - radius + 1),
    clearAll.map(b => b.lo.toFixed(1) + '..' + b.hi.toFixed(1) + ' inside ' + radius + '..' + (b.h - radius)).join('  '));

  // clearAll is sampled at scroll 0, the middle, and the very bottom, so its
  // first and last entries are the band at each end of its travel.
  const clearTop = clearAll[0].lo - radius;
  const clearBottom = (clearAll[2].h - radius) - clearAll[2].hi;
  check('the notch keeps a straight stretch of edge above and below it',
    clearTop > 8 && clearBottom > 8,
    'clearance ' + clearTop.toFixed(1) + 'px top, ' + clearBottom.toFixed(1) + 'px bottom');

  check('that clearance is even at both ends',
    Math.abs(clearTop - clearBottom) < 2,
    clearTop.toFixed(1) + ' vs ' + clearBottom.toFixed(1));

  // --- a settled picture is not veiled -------------------------------------
  /*
   * The reveal masks the whole slide, so a stop in the wrong place leaves a
   * permanent gradient across part of a picture that has finished arriving.
   * It is subtle enough to read as "the image is just like that" and survived
   * a visual check once already, so this reads the pixels: the fixture's
   * pictures are flat colours, and any veil shows up as one row of the panel
   * being a different colour from another.
   */
  await page.evaluate(y => window.scrollTo(0, y), Math.round(0.2 * max));
  await new Promise(r => setTimeout(r, 1600));
  const box = await page.evaluate(() => {
    const r = document.querySelector('.estry__frame').getBoundingClientRect();
    return { x: Math.round(r.x), y: Math.round(r.y), width: Math.round(r.width), height: Math.round(r.height) };
  });
  // A plain viewport screenshot, sampled with the frame's own viewport
  // coordinates. `clip` is page-relative in some puppeteer versions and
  // viewport-relative in others, and getting that wrong samples the page
  // margin instead of the panel.
  const shot = await page.screenshot({ encoding: 'base64' });
  const veil = await page.evaluate(async (data, box) => {
    const img = new Image();
    img.src = 'data:image/png;base64,' + data;
    await img.decode();
    const cv = document.createElement('canvas');
    cv.width = img.width;
    cv.height = img.height;
    const ctx = cv.getContext('2d');
    ctx.drawImage(img, 0, 0);
    // A column down the middle, well inside the rounded corners.
    const x = Math.round(box.x + box.width / 2);
    const rows = [];
    for (let y = box.y + 10; y < box.y + box.height - 10; y += 4) {
      const d = ctx.getImageData(x, y, 1, 1).data;
      rows.push([d[0], d[1], d[2]]);
    }
    let worst = 0;
    for (const c of rows) {
      for (const o of [rows[0], rows[rows.length - 1]]) {
        worst = Math.max(worst, Math.abs(c[0] - o[0]), Math.abs(c[1] - o[1]), Math.abs(c[2] - o[2]));
      }
    }
    return { worst, top: rows[0], bottom: rows[rows.length - 1], n: rows.length };
  }, shot, box);
  check('a settled picture has no veil left across it',
    veil.worst <= 2,
    'largest channel difference down the panel: ' + veil.worst +
      ' (top ' + veil.top.join(',') + ' bottom ' + veil.bottom.join(',') + ')');

  // --- the reference's own numbers still come out ---------------------------
  /*
   * The geometry was read off terminal-industries.com: a 985px panel, a 30px
   * notch, a 329.5px straight run and no corner radius put the band at
   * 66.25..524.75 with the section unscrolled and 460.25..918.75 with it
   * finished. Feeding those same inputs in has to give those same numbers back
   * or the shape has drifted from what it was measured against.
   */
  const reference = await page.evaluate(async (max) => {
    const root = document.querySelector('.estry');
    const frame = document.querySelector('.estry__frame');
    const before = root.getAttribute('style') || '';
    root.setAttribute('style', before +
      ';--estry-height:985px;--estry-notch:30px;--estry-band-size:329.5px;' +
      '--estry-radius:0px;--estry-notch-travel:40');
    const read = async (y) => {
      window.scrollTo(0, y);
      await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
      window.dispatchEvent(new Event('resize'));
      await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
      return window.__band();
    };
    const start = await read(0);
    const end = await read(max);
    const h = Math.round(frame.getBoundingClientRect().height);
    root.setAttribute('style', before);
    window.dispatchEvent(new Event('resize'));
    return { h, start, end };
  }, max);

  check('the reference geometry reproduces to the decimal',
    reference.h === 985 &&
    Math.abs(reference.start.lo - 66.25) < 0.05 && Math.abs(reference.start.hi - 524.75) < 0.05 &&
    Math.abs(reference.end.lo - 460.25) < 0.05 && Math.abs(reference.end.hi - 918.75) < 0.05,
    'h=' + reference.h +
      ' start ' + reference.start.lo + '..' + reference.start.hi + ' (want 66.25..524.75)' +
      ' end ' + reference.end.lo + '..' + reference.end.hi + ' (want 460.25..918.75)');

  // --- the entrance actually animates --------------------------------------
  const entrance = await page.evaluate(async (max) => {
    window.scrollTo(0, Math.round(0.1 * max));
    await new Promise(r => setTimeout(r, 1400));
    const slides = [...document.querySelectorAll('.estry__slide')];
    const slide = slides[1];
    window.scrollTo(0, Math.round(0.45 * max));
    const seen = [];
    for (let i = 0; i < 14; i++) {
      await new Promise(r => requestAnimationFrame(r));
      const cs = getComputedStyle(slide);
      seen.push({
        mask: cs.maskPosition || cs.webkitMaskPosition,
        inner: getComputedStyle(slide.querySelector('.estry__inner')).transform,
        leaving: slides.map(s => s.classList.contains('is-leaving') ? 1 : 0).join(''),
        leaverInner: getComputedStyle(slides[0].querySelector('.estry__inner')).transform,
      });
      await new Promise(r => setTimeout(r, 45));
    }
    return seen;
  }, max);

  const masks = [...new Set(entrance.map(e => e.mask))];
  check('the reveal sweeps rather than snapping',
    masks.length > 2,
    masks.length + ' distinct mask positions, e.g. ' + masks.slice(0, 3).join(' / '));

  const inners = [...new Set(entrance.map(e => e.inner))];
  check('the picture parallax survives the drift animation',
    inners.length > 2,
    inners.length + ' distinct inner transforms');

  check('the outgoing picture eases away underneath',
    entrance.some(e => e.leaving.indexOf('1') === 0) &&
    new Set(entrance.map(e => e.leaverInner)).size > 2,
    'is-leaving seen on the slide being replaced, over ' +
      new Set(entrance.map(e => e.leaverInner)).size + ' distinct transforms');

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
