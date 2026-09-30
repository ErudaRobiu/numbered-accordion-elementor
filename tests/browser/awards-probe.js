/**
 * Measures the Award Wall in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 *   php tests/browser/awards-fixture.php > tests/browser/awards.html
 *   node tests/browser/awards-probe.js [screenshot-dir]
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];
const URL = 'http://127.0.0.1:8732/tests/browser/awards.html';
const SHOTS = process.argv[2] || '';
const sleep = ms => new Promise(r => setTimeout(r, ms));

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

const shape = sel => {
  const r = document.querySelector(sel + ' .eaw');
  const lead = r.querySelector('.eaw__lead');
  const grid = r.querySelector('.eaw__grid');
  const cards = [...r.querySelectorAll('.eaw__card')];
  const num = r.querySelector('.eaw__num');
  const line = r.querySelector('.eaw__line');
  const nb = num.getBoundingClientRect(), lb = line.getBoundingClientRect(), gb = grid.getBoundingClientRect();
  return {
    width: Math.round(r.getBoundingClientRect().width),
    leadBeside: lead.getBoundingClientRect().right <= gb.left + 1,
    numberBesideLine: lb.left >= nb.right - 1 && Math.abs((lb.top + lb.height / 2) - (nb.top + nb.height / 2)) < 30,
    cols: new Set(cards.map(c => Math.round(c.getBoundingClientRect().left))).size,
    badgeH: Math.round(cards[0].querySelector('.eaw__badge').getBoundingClientRect().height),
    spill: cards.some(c => c.scrollWidth > c.clientWidth + 1 || [...c.children].some(k => k.scrollWidth > k.clientWidth + 1)),
    scroll: document.documentElement.scrollWidth,
  };
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

  /* ---------------------------------------------------------- wide --- */

  let a = await page.evaluate(shape, '#a');
  check('wide: the number on the left, four columns', a.leadBeside && a.cols === 4, JSON.stringify(a));
  check('wide: 110px badge areas, nothing spills', a.badgeH === 110 && !a.spill, a.badgeH + ' ' + a.spill);

  const look = await page.evaluate(() => {
    const r = document.querySelector('#a .eaw');
    const cs = (el, p) => getComputedStyle(el)[p];
    const card = r.querySelector('.eaw__card');
    const dark = r.querySelector('.is-dark');
    const di = dark.querySelector('img').getBoundingClientRect();
    const others = [...r.querySelectorAll('.eaw__card:not(.is-dark) img')].map(i => i.getBoundingClientRect());
    return {
      panel: [cs(r, 'backgroundColor'), cs(r, 'borderTopLeftRadius'), cs(r, 'paddingTop')].join('|'),
      num: [cs(r.querySelector('.eaw__num'), 'fontFamily').split(',')[0], cs(r.querySelector('.eaw__num'), 'fontSize'), cs(r.querySelector('.eaw__num'), 'color')].join('|'),
      line: r.querySelector('.eaw__line').textContent.replace(/\u2060/g, ''),
      rangeOneLine: (() => { const l = r.querySelector('.eaw__line'); const range = document.createRange(); const t = l.firstChild; const i = t.textContent.indexOf('2018'); range.setStart(t, i); range.setEnd(t, t.textContent.length); return range.getClientRects().length === 1; })(),
      numLabel: r.querySelector('.eaw__num').getAttribute('aria-label') + '|' + r.querySelector('.eaw__num').textContent,
      card: [cs(card, 'backgroundColor'), cs(card, 'borderTopLeftRadius'), cs(card, 'paddingTop')].join('|'),
      year: [cs(card.querySelector('.eaw__year'), 'fontFamily').split(',')[0], cs(card.querySelector('.eaw__year'), 'fontSize'), cs(card.querySelector('.eaw__year'), 'color')].join('|'),
      name: [cs(card.querySelector('.eaw__name'), 'fontSize'), cs(card.querySelector('.eaw__name'), 'fontWeight')].join('|'),
      darkArea: cs(dark.querySelector('.eaw__badge'), 'backgroundColor'),
      darkChip: [cs(dark.querySelector('img'), 'backgroundColor'), cs(dark.querySelector('img'), 'paddingTop'), cs(dark.querySelector('img'), 'borderTopLeftRadius')].join('|'),
      chipH: Math.round(di.height), chipW: Math.round(di.width), area: Math.round(dark.querySelector('.eaw__badge').getBoundingClientRect().height),
      otherH: Math.round(Math.max(...others.map(b => b.height))),
      alt: dark.querySelector('img').alt,
      armed: r.classList.contains('eaw--armed'),
      bullets: cs(r.querySelector('.eaw__grid'), 'listStyleType') + '|' + cs(r.querySelector('.eaw__grid'), 'paddingLeft'),
    };
  });
  check('off-white panel, 25px radius, 30px padding', look.panel === 'rgb(245, 246, 247)|25px|30px', look.panel);
  check('the number: Bebas 110px green', look.num === '"Bebas Neue"|110px|rgb(7, 134, 79)', look.num);
  check('the line fills in the year range', look.line === 'international innovation awards for Lepido® technology, 2018–2022', look.line);
  check('and the range never splits across two lines', look.rangeOneLine);
  check('the count starts at 0 but stays readable as 8', look.numLabel === '8|0', look.numLabel);
  check('white cards, 16px radius, 14px padding', look.card === 'rgb(255, 255, 255)|16px|14px', look.card);
  check('years Bebas 20px green, names 13.5px/600', look.year === '"Bebas Neue"|20px|rgb(7, 134, 79)' && look.name === '13.5px|600', look.year + ' ' + look.name);
  check('the dark badge: no dark fill across the badge area', look.darkArea === 'rgba(0, 0, 0, 0)', look.darkArea);
  check('…but a padded, rounded dark chip hugging the badge', look.darkChip === 'rgb(36, 36, 37)|10px|12px', look.darkChip);
  check('…no taller than the other badges, well inside its area', look.chipH <= look.otherH + 2 && look.chipH < look.area - 8, `chip ${look.chipW}x${look.chipH}, tallest other ${look.otherH}, area ${look.area}`);
  check('badges are named for a screen reader', look.alt === 'Perpetuum Energy Efficiency Prize badge', look.alt);
  check('the theme cannot add bullets or indent', look.bullets === 'none|0px', look.bullets);
  check('below the fold it waits', look.armed);

  /* ------------------------------------------------------ entrance --- */

  await page.evaluate(() => document.querySelector('#a').scrollIntoView({ block: 'center' }));
  const t0 = Date.now();
  const frames = [];
  for (let i = 0; i < 26; i++) {
    frames.push(await page.evaluate(() => {
      const r = document.querySelector('#a .eaw');
      return { n: +r.querySelector('.eaw__num').textContent, ops: [...r.querySelectorAll('.eaw__card')].map(c => +(+getComputedStyle(c).opacity).toFixed(2)) };
    }).then(f => Object.assign(f, { t: Date.now() - t0 })));
    await sleep(45);
  }
  const ns = frames.map(f => f.n);
  check('the number counts up from 0 to 8', ns.some(n => n > 0 && n < 8) && ns[ns.length - 1] === 8 && ns.every((n, i) => i === 0 || n >= ns[i - 1]), ns.join(','));
  const hit8 = (frames.find(f => f.n === 8) || {}).t;
  check('in about 0.9s', hit8 > 500 && hit8 < 1300, hit8 + 'ms');
  const seen = i => (frames.find(f => f.ops[i] > 0.5) || {}).t;
  const order = [0, 1, 2, 3, 4, 5, 6, 7].map(seen);
  check('the cards fade in one after another', order.every((t, i) => i === 0 || t >= order[i - 1]) && order[7] > order[0], order.join(' ≤ '));
  check('all in by about 700ms', order[7] < 900, order[7] + 'ms');
  await sleep(800);
  check('then disarms', !(await page.evaluate(() => document.querySelector('#a .eaw').classList.contains('eaw--armed'))));

  await page.hover('#a .eaw__card');
  await sleep(400);
  const hov = await page.evaluate(() => { const c = document.querySelector('#a .eaw__card'); return getComputedStyle(c).transform + '|' + getComputedStyle(c.querySelector('img')).transform + '|' + getComputedStyle(c.querySelector('img')).opacity; });
  check('hover lifts the card 3px and scales the badge to 1.04, theme hover ignored', hov === 'matrix(1, 0, 0, 1, 0, -3)|matrix(1.04, 0, 0, 1.04, 0, 0)|1', hov);
  await page.mouse.move(2, 2);

  /* --------------------------------------------- half column, other --- */

  const b = await page.evaluate(shape, '#b');
  check('a half column: the number on top, beside its line; four columns', !b.leadBeside && b.numberBesideLine && (b.width < 640 ? b.cols === 2 : b.cols === 4), JSON.stringify(b));
  const c = await page.evaluate(shape, '#c');
  const cx = await page.evaluate(() => { const r = document.querySelector('#c .eaw'); const a = r.querySelector('a.eaw__card'); return { num: r.querySelector('.eaw__num').textContent, armed: r.classList.contains('eaw--armed'), link: a ? a.getAttribute('href') + '|' + getComputedStyle(a).textDecorationLine : 'none' }; });
  check('a typed number is used as typed', cx.num === '12' && !cx.armed, JSON.stringify(cx));
  check('a card with a link is the link, not underlined by the theme', cx.link === '#horecava|none', cx.link);
  check('a 560px wall: two columns', c.cols === 2 && !c.spill, JSON.stringify(c));

  if (SHOTS) {
    await (await page.$('#a .eaw')).screenshot({ path: SHOTS + '/aw-wide.png' });
    await (await page.$('#b .eaw')).screenshot({ path: SHOTS + '/aw-half.png' });
  }

  /* ---------------------------------------------------------- sizes --- */

  for (const [w, cols] of [[1100, null], [767, null], [360, 2]]) {
    await page.setViewport({ width: w, height: 900 });
    await sleep(500);
    const s = await page.evaluate(shape, '#a');
    const expect = cols || (s.width < 640 ? 2 : 4);
    check(`${w}px: ${expect} columns, number ${s.width < 900 ? 'on top' : 'beside'}`, s.cols === expect && s.leadBeside === (s.width >= 900), s.width + 'px wall, ' + s.cols + ' cols');
    check(`${w}px: no sideways scroll, nothing spills`, s.scroll <= w && !s.spill, s.scroll + ' ' + s.spill);
    if (w === 360) check('360px: smaller badge areas', s.badgeH === 84, String(s.badgeH));
    if (SHOTS && w === 360) await (await page.$('#a .eaw')).screenshot({ path: SHOTS + '/aw-360.png' });
  }

  /* ------------------------------------------------- reduced motion --- */

  const calm = await browser.newPage();
  await calm.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await calm.setViewport({ width: 1440, height: 900 });
  await calm.goto(URL, { waitUntil: 'networkidle0' });
  const rm = await calm.evaluate(() => { const r = document.querySelector('#a .eaw'); return r.classList.contains('eaw--armed') + '|' + r.querySelector('.eaw__num').textContent + '|' + getComputedStyle(r.querySelector('.eaw__card')).opacity; });
  check('reduced motion: static and final', rm === 'false|8|1', rm);

  check('no script errors', errs.length === 0, errs.join(' | '));
  await browser.close();

  console.log(PASS.map(p => '  ok   ' + p).join('\n'));
  if (FAIL.length) console.log(FAIL.map(f => '  FAIL ' + f).join('\n'));
  console.log(`\n${PASS.length} passed, ${FAIL.length} failed`);
  process.exit(FAIL.length ? 1 : 0);
})();
