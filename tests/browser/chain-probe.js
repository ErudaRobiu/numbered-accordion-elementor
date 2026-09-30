/**
 * Measures the Company Chain in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 *   php tests/browser/chain-fixture.php > tests/browser/chain.html
 *   node tests/browser/chain-probe.js [screenshot-dir]
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];
const URL = 'http://127.0.0.1:8732/tests/browser/chain.html';
const SHOTS = process.argv[2] || '';
const sleep = ms => new Promise(r => setTimeout(r, ms));

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

const shape = sel => {
  const r = document.querySelector(sel + ' .echn');
  const cards = [...r.querySelectorAll('.echn__card')].map(c => {
    const b = c.getBoundingClientRect();
    return { x: Math.round(b.left), y: Math.round(b.top), w: Math.round(b.width), h: Math.round(b.height), name: Math.round(c.querySelector('.echn__name').getBoundingClientRect().top), labelLines: Math.round(c.querySelector('.echn__label').getBoundingClientRect().height / parseFloat(getComputedStyle(c.querySelector('.echn__label')).lineHeight)), spill: c.scrollWidth > c.clientWidth + 1 || [...c.children].some(k => k.scrollWidth > k.clientWidth + 1) };
  });
  const arrows = [...r.querySelectorAll('.echn__arrow')].map(a => { const b = a.getBoundingClientRect(); const s = a.querySelector('svg'); return { cx: Math.round(b.left + b.width / 2), cy: Math.round(b.top + b.height / 2), rot: getComputedStyle(s).transform }; });
  return { stacked: r.classList.contains('is-stacked'), cards, arrows, width: Math.round(r.getBoundingClientRect().width), scroll: document.documentElement.scrollWidth };
};

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

  /* ------------------------------------------------ half column row --- */

  let a = await page.evaluate(shape, '#a');
  check('a ~560px half column: a row of three', !a.stacked && new Set(a.cards.map(c => c.y)).size === 1 && a.width > 520 && a.width < 650, 'width ' + a.width);
  check('all three cards the same height', new Set(a.cards.map(c => c.h)).size === 1, a.cards.map(c => c.h).join(','));
  check('the middle label wraps to two lines…', a.cards[1].labelLines === 2 && a.cards[0].labelLines === 1, a.cards.map(c => c.labelLines).join(','));
  check('…and the three names still sit on one line', new Set(a.cards.map(c => c.name)).size === 1, a.cards.map(c => c.name).join(','));
  check('the highlighted card is a little wider', a.cards[1].w > a.cards[0].w && a.cards[0].w === a.cards[2].w, a.cards.map(c => c.w).join(','));
  check('arrows sit between the cards, centred on them', a.arrows.every((ar, i) => ar.cx > a.cards[i].x + a.cards[i].w && ar.cx < a.cards[i + 1].x) && a.arrows.every(ar => Math.abs(ar.cy - (a.cards[0].y + a.cards[0].h / 2)) < 2), JSON.stringify(a.arrows));
  check('nothing spills out of a card', !a.cards.some(c => c.spill));

  const look = await page.evaluate(() => {
    const r = document.querySelector('#a .echn');
    const cs = (el, p) => getComputedStyle(el)[p];
    const c = r.querySelector('.echn__card');
    const hi = r.querySelector('.is-hi');
    return {
      card: [cs(c, 'backgroundColor'), cs(c, 'borderTopColor'), cs(c, 'borderTopLeftRadius')].join('|'),
      label: [cs(c.querySelector('.echn__label'), 'fontFamily').split(',')[0], cs(c.querySelector('.echn__label'), 'fontSize'), cs(c.querySelector('.echn__label'), 'textTransform')].join('|'),
      name: [cs(c.querySelector('.echn__name'), 'fontSize'), cs(c.querySelector('.echn__name'), 'fontWeight')].join('|'),
      hi: [cs(hi, 'backgroundColor'), cs(hi.querySelector('.echn__label'), 'color'), cs(hi.querySelector('.echn__name'), 'color')].join('|'),
      arrow: cs(r.querySelector('.echn__arrow'), 'color'),
      armed: r.classList.contains('echn--armed'),
      hidden: cs(c, 'opacity'),
    };
  });
  check('white cards, grey border, 14px radius', look.card === 'rgb(255, 255, 255)|rgb(221, 226, 230)|14px', look.card);
  check('labels Urbanist 10.5px uppercase, names 16px bold', look.label === 'Urbanist|10.5px|uppercase' && look.name === '16px|700', look.label + ' ' + look.name);
  check('highlighted: navy, green-light label, white name', look.hi === 'rgb(15, 57, 97)|rgb(77, 207, 141)|rgb(255, 255, 255)', look.hi);
  check('green arrows', look.arrow === 'rgb(7, 134, 79)', look.arrow);
  check('below the fold it waits, hidden', look.armed && look.hidden === '0');

  /* --------------------------------------------------------- entrance --- */

  await page.evaluate(() => document.querySelector('#a').scrollIntoView({ block: 'center' }));
  const t0 = Date.now();
  const frames = [];
  for (let i = 0; i < 36; i++) {
    frames.push(await page.evaluate(() => {
      const r = document.querySelector('#a .echn');
      const els = [...r.children];
      return { ops: els.map(e => +(+getComputedStyle(e).opacity).toFixed(2)), hiShadow: getComputedStyle(r.querySelector('.is-hi')).boxShadow };
    }).then(f => Object.assign(f, { t: Date.now() - t0 })));
    await sleep(45);
  }
  const at = i => (frames.find(f => f.ops[i] > 0.5) || {}).t;
  const order = [0, 1, 2, 3, 4].map(at);
  check('card, arrow, card, arrow, card — in that order', order.every((t, i) => i === 0 || t >= order[i - 1]) && order[4] > order[0], order.join(' ≤ '));
  check('all in within about 1.2s', order[4] < 1300, order[4] + 'ms');
  const noShadowEarly = frames.filter(f => f.t < order[4]).some(f => !/0px 12px 30px/.test(f.hiShadow) || /rgba\(15, 57, 97, 0\)/.test(f.hiShadow));
  check('the highlighted card lifts after everything has landed', noShadowEarly, frames.map(f => f.hiShadow.slice(0, 22)).filter((v, i, a) => a.indexOf(v) === i).join(' / '));
  await sleep(1300);
  const done = await page.evaluate(() => { const r = document.querySelector('#a .echn'); return { armed: r.classList.contains('echn--armed'), shadow: getComputedStyle(r.querySelector('.is-hi')).boxShadow, all: [...r.children].every(e => getComputedStyle(e).opacity === '1') }; });
  check('then disarms with everything shown and the lift held', !done.armed && done.all && /0px 12px 30px/.test(done.shadow), JSON.stringify(done));

  await page.hover('#a .echn__card');
  await sleep(350);
  const hov = await page.evaluate(() => { const c = document.querySelector('#a .echn__card'); return getComputedStyle(c).transform + '|' + getComputedStyle(c).borderTopColor; });
  check('hover lifts 3px and turns the border green', hov === 'matrix(1, 0, 0, 1, 0, -3)|rgb(7, 134, 79)', hov);
  await page.hover('#a .echn__card.is-hi');
  await sleep(350);
  const hovHi = await page.evaluate(() => getComputedStyle(document.querySelector('#a .is-hi')).borderTopColor);
  check('on the navy card the border turns green-light', hovHi === 'rgb(77, 207, 141)', hovHi);
  await page.mouse.move(2, 2);

  /* -------------------------------------------------- narrow column --- */

  const b = await page.evaluate(shape, '#b');
  check('a 480px column: stacked', b.stacked && b.cards.every((c, i) => i === 0 || c.y > b.cards[i - 1].y), JSON.stringify(b.cards.map(c => c.y)));
  const lefts = await page.evaluate(() => [...document.querySelectorAll('#b .echn__card')].map(c => Math.round(c.querySelector('.echn__label').getBoundingClientRect().left - c.querySelector('.echn__name').getBoundingClientRect().left)));
  check('stacked labels sit on the left, over their names', lefts.every(d => d === 0), lefts.join(','));
  check('stacked cards run full width', new Set(b.cards.map(c => c.w)).size === 1 && b.cards[0].w === b.width, b.cards.map(c => c.w).join(',') + ' of ' + b.width);
  check('stacked arrows point down, centred between the cards', b.arrows.every(ar => /matrix\(0, 1, -1, 0|matrix\(6.12323e-17, 1, -1, 6.12323e-17/.test(ar.rot)) && b.arrows.every((ar, i) => ar.cy > b.cards[i].y + b.cards[i].h && ar.cy < b.cards[i + 1].y && Math.abs(ar.cx - (b.cards[0].x + b.cards[0].w / 2)) < 2), JSON.stringify(b.arrows));

  /* ----------------------------------------------------------- extras --- */

  const c = await page.evaluate(shape, '#c');
  const cx = await page.evaluate(() => {
    const r = document.querySelector('#c .echn');
    const hi = r.querySelector('.is-hi');
    return { logos: r.querySelectorAll('.echn__logo img').length, link: hi.tagName + '|' + hi.getAttribute('href') + '|' + getComputedStyle(hi).textDecorationLine, glow: getComputedStyle(hi, '::after').animationName, chevron: !!r.querySelector('.echn__arrow--chevron') };
  });
  check('logos above the labels, names still aligned', cx.logos === 2 && new Set(c.cards.map(k => k.name)).size === 1, JSON.stringify(c.cards.map(k => k.name)));
  check('a card with a link is the link, not underlined by the theme', cx.link === 'A|#norrel|none', cx.link);
  check('the glow runs when switched on', cx.glow === 'echn-glow', cx.glow);
  check('chevron arrows', cx.chevron);

  if (SHOTS) {
    await (await page.$('#a .echn')).screenshot({ path: SHOTS + '/chain-half.png' });
    await (await page.$('#b .echn')).screenshot({ path: SHOTS + '/chain-480.png' });
    await (await page.$('#c .echn')).screenshot({ path: SHOTS + '/chain-extras.png' });
  }

  /* ------------------------------------------------------------ sizes --- */

  for (const [w, name, stacked] of [[1100, '1100', null], [767, '767', true], [360, '360', true]]) {
    await page.setViewport({ width: w, height: 900 });
    await sleep(500);
    const s = await page.evaluate(shape, '#a');
    if (stacked !== null) check(`${name}px: stacked`, s.stacked === stacked, String(s.stacked));
    else check(`${name}px: a row if the column is wide enough, stacked if not`, s.stacked === (s.width < 520), s.width + 'px wide, stacked ' + s.stacked);
    check(`${name}px: no sideways scroll, nothing spills`, s.scroll <= w && !s.cards.some(k => k.spill), s.scroll + ' ' + JSON.stringify(s.cards.map(k => k.spill)));
    if (SHOTS && w === 360) await (await page.$('#a .echn')).screenshot({ path: SHOTS + '/chain-360.png' });
  }

  /* --------------------------------------------------------- no script --- */

  const bare = await browser.newPage();
  await bare.setJavaScriptEnabled(false);
  await bare.setViewport({ width: 360, height: 900 });
  await bare.goto(URL, { waitUntil: 'networkidle0' });
  const ns = await bare.evaluate(shape, '#a');
  check('without the script, a phone still gets the stacked chain', ns.cards.every((k, i) => i === 0 || k.y > ns.cards[i - 1].y) && ns.scroll <= 360, JSON.stringify(ns.cards.map(k => k.y)));

  /* --------------------------------------------------- reduced motion --- */

  const calm = await browser.newPage();
  await calm.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await calm.setViewport({ width: 1440, height: 900 });
  await calm.goto(URL, { waitUntil: 'networkidle0' });
  const rm = await calm.evaluate(() => { const r = document.querySelector('#a .echn'); return r.classList.contains('echn--armed') + '|' + getComputedStyle(r.querySelector('.echn__card')).opacity + '|' + getComputedStyle(document.querySelector('#c .is-hi'), '::after').display; });
  check('reduced motion: static and final, no glow', rm === 'false|1|none', rm);

  check('no script errors', errs.length === 0, errs.join(' | '));
  await browser.close();

  console.log(PASS.map(p => '  ok   ' + p).join('\n'));
  if (FAIL.length) console.log(FAIL.map(f => '  FAIL ' + f).join('\n'));
  console.log(`\n${PASS.length} passed, ${FAIL.length} failed`);
  process.exit(FAIL.length ? 1 : 0);
})();
