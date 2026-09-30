/**
 * Measures the Process Stepper in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 *   php tests/browser/process-fixture.php > tests/browser/process.html
 *   node tests/browser/process-probe.js [screenshot-dir]
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];
const URL = 'http://127.0.0.1:8732/tests/browser/process.html';
const SHOTS = process.argv[2] || '';
const sleep = ms => new Promise(r => setTimeout(r, ms));
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

// Everything worth knowing about one stepper's state.
const state = sel => {
  const r = document.querySelector(sel + ' .eps');
  const items = [...r.querySelectorAll('.eps__item')];
  const k = items.findIndex(i => i.classList.contains('is-sel'));
  const slides = [...r.querySelectorAll('.eps__slide')].filter(s => !s.hidden);
  const panel = r.querySelector('.eps__panel');
  const pb = panel.getBoundingClientRect();
  const dot = items[k].querySelector('.eps__dot').getBoundingClientRect();
  const caretX = pb.left + parseFloat(getComputedStyle(panel, '::before').left);
  const rail = r.querySelector('.eps__rail');
  const fill = getComputedStyle(rail, '::after');
  const fillLeft = rail.getBoundingClientRect().left + parseFloat(fill.left);
  const fillW = parseFloat(fill.width) * (new DOMMatrix(fill.transform).a);
  const link = slides[0] && slides[0].querySelector('.eps__link');
  return {
    mode: r.dataset.mode, k,
    done: items.map(i => i.classList.contains('is-done') ? 1 : 0).join(''),
    tabs: [...r.querySelectorAll('.eps__stage')].map(s => s.tabIndex).filter(t => t === 0).length,
    current: [...r.querySelectorAll('[aria-current="step"]')].length,
    focusK: [...r.querySelectorAll('.eps__stage')].indexOf(document.activeElement),
    slides: slides.length, slideI: slides[0] ? +slides[0].dataset.i : -1,
    title: slides[0] ? slides[0].querySelector('.eps__title').textContent : '',
    link: link ? link.textContent + ' ' + link.getAttribute('href') + (link.classList.contains('eps__link--quiet') ? ' quiet' : '') : '',
    dotX: dot.left + dot.width / 2, caretX, caretIn: caretX >= pb.left + 20 && caretX <= pb.right - 20,
    fillEnd: fillLeft + fillW,
    fl: r.style.getPropertyValue('--eps-fl'), fr: r.style.getPropertyValue('--eps-fr'),
    dotIn: (() => { const s = r.querySelector('.eps__scroller').getBoundingClientRect(); return dot.left >= s.left - 1 && dot.right <= s.right + 1; })(),
  };
};

const tidy = () => {
  const bad = [];
  document.querySelectorAll('.eps__label, .eps__title, .eps__desc, .eps__link, .eps__name').forEach(e => {
    if (e.offsetParent && e.scrollWidth > e.clientWidth + 1 && getComputedStyle(e).display !== 'inline') bad.push(e.className + ':' + e.textContent.slice(0, 20));
  });
  document.querySelectorAll('.eps').forEach(r => {
    const rr = r.getBoundingClientRect();
    r.querySelectorAll('.eps__panel, .eps__item').forEach(e => {
      const b = e.getBoundingClientRect();
      if (!b.width) return; // hidden in this layout
      if (r.dataset.mode !== 'scroll' && (b.left < rr.left - 1 || b.right > rr.right + 1)) bad.push('outside:' + e.className);
    });
  });
  return { scroll: document.documentElement.scrollWidth, bad };
};

(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'] });
  const errs = [];
  const page = await browser.newPage();
  page.on('pageerror', e => errs.push(String(e)));
  await page.setViewport({ width: 1440, height: 900 });
  await page.goto(URL, { waitUntil: 'networkidle0' });
  await page.evaluate(() => document.fonts.ready);
  await sleep(200);

  /* -------------------------------------------------------- entrance --- */

  let cls = await page.evaluate(() => [document.querySelector('#a .eps').className, document.querySelector('#d .eps').className]);
  check('entrance: armed off screen; a stepper without it never arms', /eps--armed/.test(cls[0]) && !/is-in/.test(cls[0]) && !/armed/.test(cls[1]), cls.join(' | '));
  await page.evaluate(() => document.querySelector('#a').scrollIntoView({ block: 'center' }));
  await sleep(250);
  cls = await page.evaluate(() => document.querySelector('#a .eps').className);
  check('entrance: plays on arrival', /is-in/.test(cls), cls);
  await sleep(1800);
  const settled = await page.evaluate(() => ({ cls: document.querySelector('#a .eps').className, op: [...document.querySelectorAll('#a .eps__item, #a .eps__panel')].every(e => getComputedStyle(e).opacity === '1') }));
  check('entrance: disarmed after, everything shown', !/armed/.test(settled.cls) && settled.op, JSON.stringify(settled));

  /* ------------------------------------------------------- wide rail --- */

  let s = await page.evaluate(state, '#a');
  check('wide: horizontal, opens on the start stage', s.mode === 'h' && s.k === 0 && s.done === '00000000', JSON.stringify({ m: s.mode, k: s.k }));
  check('wide: one panel slide, the right one, its own link', s.slides === 1 && s.slideI === 0 && s.link === 'Learn more → /thermal-energy-opportunity-screen/', s.link);
  check('wide: caret under the circle', Math.abs(s.caretX - s.dotX) < 1.5, Math.round(s.caretX) + ' vs ' + Math.round(s.dotX));
  const cols = await page.evaluate(() => { const d = [...document.querySelectorAll('#a .eps__dot')].map(e => e.getBoundingClientRect()); return { size: Math.round(d[0].width), tops: new Set(d.map(x => Math.round(x.top))).size, gaps: new Set(d.slice(1).map((x, i) => Math.round(x.left - d[i].left))).size }; });
  check('wide: 54px circles, one row, equal columns', cols.size === 54 && cols.tops === 1 && cols.gaps === 1, JSON.stringify(cols));
  const rows = await page.evaluate(() => new Set([...document.querySelectorAll('#a .eps__label')].map(l => Math.round(l.getBoundingClientRect().height))).size);
  check('wide: labels reserve two lines, so rows align', rows === 1, rows);
  const badge = await page.evaluate(() => { const b = document.querySelector('#a .eps__badge').getBoundingClientRect(), d = document.querySelector('#a .eps__dot').getBoundingClientRect(); return { above: b.bottom <= d.top, centred: Math.abs((b.left + b.right) / 2 - (d.left + d.right) / 2) < 1 }; });
  check('wide: the badge sits above circle 01, centred', badge.above && badge.centred, JSON.stringify(badge));

  await page.hover('#a .eps__item:nth-child(5) .eps__dot');
  await sleep(600);
  s = await page.evaluate(state, '#a');
  check('hover selects: stage 05, the four before it done', s.k === 4 && s.done === '11110000' && s.current === 1 && s.tabs === 1, JSON.stringify({ k: s.k, d: s.done }));
  check('hover: the fill reaches the picked centre', Math.abs(s.fillEnd - s.dotX) < 2, Math.round(s.fillEnd) + ' vs ' + Math.round(s.dotX));
  check('hover: the caret slides under it', Math.abs(s.caretX - s.dotX) < 1.5, Math.round(s.caretX) + ' vs ' + Math.round(s.dotX));
  check('no link: the quiet fallback button keeps the card balanced', s.title === 'Proposal' && s.link === 'Request an Assessment /request-assessment/ quiet', s.link);
  const look = await page.evaluate(() => { const i = document.querySelector('#a .eps__item:nth-child(5)'), d = i.querySelector('.eps__dot'), done = document.querySelector('#a .eps__item:nth-child(2) .eps__dot'); const c = getComputedStyle(d), e = getComputedStyle(done); return { bg: c.backgroundColor, num: c.color, halo: c.boxShadow, label: getComputedStyle(i.querySelector('.eps__label')).color, doneB: e.borderTopColor, doneN: e.color, stageBg: getComputedStyle(i.querySelector('.eps__stage')).backgroundColor }; });
  check('selected: solid green, white number, 6px halo, navy label', look.bg === 'rgb(7, 134, 79)' && look.num === 'rgb(255, 255, 255)' && /6px/.test(look.halo) && look.label === 'rgb(15, 57, 97)', JSON.stringify(look));
  check('done: green border and number', look.doneB === 'rgb(7, 134, 79)' && look.doneN === 'rgb(7, 134, 79)', look.doneB + ' ' + look.doneN);
  check('hostile theme: hovered stage button stays unpainted', look.stageBg === 'rgba(0, 0, 0, 0)', look.stageBg);

  // Keyboard, with roving tabindex.
  await page.mouse.move(5, 5);
  await page.focus('#a .eps__item:nth-child(5) .eps__stage');
  await page.keyboard.press('ArrowRight');
  await sleep(50);
  s = await page.evaluate(state, '#a');
  check('ArrowRight: next stage, focus follows', s.k === 5 && s.focusK === 5 && s.tabs === 1, JSON.stringify({ k: s.k, f: s.focusK }));
  await page.keyboard.press('End');
  await sleep(50);
  s = await page.evaluate(state, '#a');
  check('End: the last stage', s.k === 7 && s.focusK === 7 && s.link === 'Learn more → /thermstar-power-intelligence/', s.link);
  await page.keyboard.press('ArrowRight');
  await sleep(50);
  s = await page.evaluate(state, '#a');
  check('ArrowRight on the last wraps to the first', s.k === 0 && s.focusK === 0, s.k);
  await page.keyboard.press('Home');
  await page.keyboard.press('ArrowLeft');
  await sleep(50);
  s = await page.evaluate(state, '#a');
  check('ArrowLeft on the first wraps to the last', s.k === 7, s.k);
  await page.keyboard.press('Tab');
  await sleep(50);
  const tabbed = await page.evaluate(() => document.activeElement.className + ' ' + document.activeElement.getAttribute('href'));
  check('Tab leaves the rail for the panel link, a real <a>', tabbed === 'eps__link /thermstar-power-intelligence/', tabbed);
  const live = await page.evaluate(() => document.querySelector('#a .eps__panel').getAttribute('aria-live'));
  check('the panel is a polite live region', live === 'polite', live);

  /* ------------------------------------------------------ scrolling --- */

  await page.evaluate(() => document.querySelector('#b').scrollIntoView({ block: 'center' }));
  await sleep(300);
  s = await page.evaluate(state, '#b');
  check('half column: scrolls sideways; only the far edge fades', s.mode === 'scroll' && s.fl === '0px' && s.fr === '40px', JSON.stringify({ m: s.mode, fl: s.fl, fr: s.fr }));
  await page.focus('#b .eps__item:nth-child(1) .eps__stage');
  await page.keyboard.press('End');
  await sleep(900);
  s = await page.evaluate(state, '#b');
  check('scroll: the picked stage is brought into view', s.k === 7 && s.dotIn, JSON.stringify({ k: s.k, in: s.dotIn }));
  check('scroll: at the end only the near edge fades', s.fl === '40px' && s.fr === '0px', s.fl + ' ' + s.fr);
  check('scroll: the caret follows the circle, inside the card', Math.abs(s.caretX - s.dotX) < 1.5 && s.caretIn, Math.round(s.caretX) + ' vs ' + Math.round(s.dotX));
  await page.keyboard.press('Home');
  await sleep(900);
  s = await page.evaluate(state, '#b');
  check('scroll: back to the start', s.k === 0 && s.dotIn && s.fl === '0px', JSON.stringify({ k: s.k, fl: s.fl }));
  // Hovering must not scroll the rail under the pointer.
  const before = await page.evaluate(() => document.querySelector('#b .eps__scroller').scrollLeft);
  await page.hover('#b .eps__item:nth-child(3) .eps__dot');
  await sleep(600);
  const after = await page.evaluate(() => [document.querySelector('#b .eps__scroller').scrollLeft, document.querySelector('#b .is-sel') === document.querySelector('#b .eps__item:nth-child(3)')]);
  check('scroll: hover picks without moving the rail', after[1] && after[0] === before, before + ' -> ' + after[0]);
  await page.mouse.move(5, 5);

  /* ------------------------------------------------------- vertical --- */

  await page.evaluate(() => { document.activeElement.blur(); document.querySelector('#c').scrollIntoView({ block: 'center' }); });
  await sleep(300);
  const open = () => page.evaluate(() => [...document.querySelectorAll('#c .eps__body')].map(b => Math.round(b.getBoundingClientRect().height)));
  let h = await open();
  s = await page.evaluate(() => ({ mode: document.querySelector('#c .eps').dataset.mode, panel: getComputedStyle(document.querySelector('#c .eps__panel')).display }));
  check('340px: vertical, no separate panel', s.mode === 'v' && s.panel === 'none', JSON.stringify(s));
  check('vertical: only the start row is open', h[0] > 40 && h.slice(1).every(x => x === 0), h.join(','));
  const vb = await page.evaluate(() => { const b = document.querySelector('#c .eps__badge').getBoundingClientRect(), l = document.querySelector('#c .eps__label').getBoundingClientRect(), d = document.querySelector('#c .eps__dot').getBoundingClientRect(); return { beside: b.left > d.right && b.top < l.bottom + 30 }; });
  check('vertical: the badge sits with stage 01\'s title', vb.beside, JSON.stringify(vb));
  await page.hover('#c .eps__item:nth-child(4) .eps__label');
  await sleep(400);
  h = await open();
  check('vertical: hover does not open rows', h[0] > 40 && h[3] === 0, h.join(','));
  await page.click('#c .eps__item:nth-child(4) .eps__stage');
  await sleep(500);
  h = await open();
  check('vertical: a click opens that row and closes the other', h[3] > 40 && h.filter(x => x > 0).length === 1, h.join(','));
  const vlink = await page.evaluate(() => { const a = document.querySelector('#c .eps__item:nth-child(4) .eps__link'); return a.textContent + ' ' + a.getAttribute('href') + ' ' + getComputedStyle(a).visibility; });
  check('vertical: the open row carries its own link', vlink === 'Learn more → /configuration-implementation-verification/ visible', vlink);
  const hiddenLinks = await page.evaluate(() => [...document.querySelectorAll('#c .eps__item:not(.is-sel) .eps__link')].every(a => getComputedStyle(a).visibility === 'hidden'));
  check('vertical: closed rows hide their links from the tab order', hiddenLinks, '');
  const line = await page.evaluate(() => { const it = [...document.querySelectorAll('#c .eps__item')]; const sc = el => new DOMMatrix(getComputedStyle(el, '::after').transform).d; const a = it[0].querySelector('.eps__dot').getBoundingClientRect(), b = it[1].querySelector('.eps__dot').getBoundingClientRect(), bef = getComputedStyle(it[0], '::before'); return { fill: it.slice(0, 7).map(sc).map(x => x > .99 ? 1 : 0).join(''), reach: Math.abs(it[0].getBoundingClientRect().top + parseFloat(bef.top) + parseFloat(bef.height) - (b.top + b.height / 2)), x: Math.abs(it[0].getBoundingClientRect().left + parseFloat(bef.left) + 1 - (a.left + a.width / 2)) }; });
  check('vertical: the green line runs down to the picked stage', line.fill === '1110000', line.fill);
  check('vertical: each segment runs centre to centre', line.reach < 1.5 && line.x < 1.5, JSON.stringify(line));
  await page.keyboard.press('ArrowDown');
  await sleep(500);
  h = await open();
  check('vertical: ArrowDown opens the next row', h[4] > 40 && h.filter(x => x > 0).length === 1, h.join(','));

  /* ---------------------------------------------- five, auto, no fb --- */

  await page.evaluate(() => { document.activeElement.blur(); document.querySelector('#d').scrollIntoView({ block: 'center' }); });
  s = await page.evaluate(state, '#d');
  const dn = await page.evaluate(() => [...document.querySelectorAll('#d .eps__dot')].map(d => d.textContent).join(' '));
  check('custom number, counting carries on around it', dn === '01 02 A 04 05', dn);
  await sleep(2400);
  const k1 = (await page.evaluate(state, '#d')).k;
  check('opens on stage 2, then auto-advances', s.k === 1 && k1 === 2, s.k + ' -> ' + k1);
  const wide = await page.evaluate(() => { const sl = document.querySelector('#d .eps__slide[data-i="2"]'); return { cls: sl.className, link: !!sl.querySelector('.eps__link'), copyW: Math.round(sl.querySelector('.eps__copy').getBoundingClientRect().width), slideW: Math.round(sl.getBoundingClientRect().width) }; });
  check('no fallback: the text spans the card', !wide.link && /--wide/.test(wide.cls) && wide.copyW > wide.slideW * .8, JSON.stringify(wide));
  await page.hover('#d .eps__item:nth-child(1) .eps__dot');
  await page.mouse.move(5, 5);
  await sleep(4500);
  s = await page.evaluate(state, '#d');
  check('auto-advance stops for good once the visitor points', s.k === 0, s.k);

  /* ------------------------------------------------------- responsive --- */

  for (const [w, hgt] of [[1440, 900], [1100, 900], [767, 1000], [360, 800], [320, 700]]) {
    await page.setViewport({ width: w, height: hgt });
    await sleep(400);
    const t = await page.evaluate(tidy);
    const modes = await page.evaluate(() => ['a', 'b', 'c', 'd'].map(i => document.querySelector('#' + i + ' .eps').dataset.mode).join(','));
    check(`${w}: no sideways page scroll, no text spill`, t.scroll === w && !t.bad.length, JSON.stringify(t) + ' ' + modes);
    if (w <= 360) check(`${w}: everything vertical`, modes === 'v,v,v,v', modes);
    if (w <= 360) {
  const top = await page.evaluate(() => [...document.querySelectorAll('#d .eps__item')].map(i => Math.round(i.querySelector('.eps__dot').getBoundingClientRect().top - i.getBoundingClientRect().top)));
  check(`${w} vertical: circles sit at the same height in every row, long titles too`, new Set(top).size === 1, top.join(','));
    }
    if (w === 767) check('767: the full-width rails scroll sideways', modes.startsWith('scroll,scroll'), modes);
    if (SHOTS) {
      for (const id of ['a', 'b', 'c', 'd']) {
        await page.evaluate(id => document.querySelector('#' + id).scrollIntoView({ block: 'center' }), id);
        await sleep(150);
        await page.screenshot({ path: `${SHOTS}/process-${w}-${id}.png` });
      }
    }
  }
  await page.close();

  /* ------------------------------------------- mode before first paint --- */

  const n = await browser.newPage();
  await n.setRequestInterception(true);
  n.on('request', r => (/process-stepper\.js/.test(r.url()) ? r.abort() : r.continue()));
  await n.setViewport({ width: 360, height: 800 });
  await n.goto(URL, { waitUntil: 'networkidle0' });
  const early = await n.evaluate(() => [...document.querySelectorAll('.eps')].map(r => r.dataset.mode).join(','));
  check('the inline snippet picks the layout with no script loaded', early === 'v,v,v,v', early);
  await n.close();

  /* ----------------------------------------------------------- touch --- */

  const t = await browser.newPage();
  t.on('pageerror', e => errs.push(String(e)));
  await t.setViewport({ width: 390, height: 844, hasTouch: true, isMobile: true });
  await t.goto(URL, { waitUntil: 'networkidle0' });
  await t.evaluate(() => document.querySelector('#a').scrollIntoView({ block: 'center' }));
  await sleep(1800);
  await t.tap('#a .eps__item:nth-child(6) .eps__stage');
  await sleep(500);
  const th = await t.evaluate(() => [...document.querySelectorAll('#a .eps__body')].map(b => Math.round(b.getBoundingClientRect().height) > 0 ? 1 : 0).join(''));
  check('touch: a tap opens that row', th === '00000100', th);
  await t.close();

  /* ----------------------------------------------------- less motion --- */

  const r = await browser.newPage();
  r.on('pageerror', e => errs.push(String(e)));
  await r.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await r.setViewport({ width: 1440, height: 900 });
  await r.goto(URL, { waitUntil: 'networkidle0' });
  const rm = await r.evaluate(() => ({ armed: document.querySelectorAll('.eps--armed').length, fill: getComputedStyle(document.querySelector('#a .eps__rail'), '::after').transitionDuration, caret: getComputedStyle(document.querySelector('#a .eps__panel'), '::before').transitionDuration }));
  check('less motion: never armed; fill and caret jump', rm.armed === 0 && rm.fill === '0s' && rm.caret === '0s', JSON.stringify(rm));
  await r.evaluate(() => document.querySelector('#d').scrollIntoView({ block: 'center' }));
  await sleep(2600);
  const rk = await r.evaluate(state, '#d');
  check('less motion: no auto-advance', rk.k === 1, rk.k);
  await r.close();

  check('no script errors', errs.length === 0, errs.join(' | '));

  await browser.close();
  console.log(PASS.map(p => 'PASS  ' + p).join('\n'));
  console.log(FAIL.map(f => 'FAIL  ' + f).join('\n'));
  console.log(`\n${PASS.length} passed, ${FAIL.length} failed`);
  process.exit(FAIL.length ? 1 : 0);
})();
