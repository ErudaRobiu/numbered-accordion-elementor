/**
 * Measures the Journey Timeline in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 *   php tests/browser/journey-fixture.php > tests/browser/journey.html
 *   node tests/browser/journey-probe.js [screenshot-dir]
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];
const URL = 'http://127.0.0.1:8732/tests/browser/journey.html';
const SHOTS = process.argv[2] || '';
const sleep = ms => new Promise(r => setTimeout(r, ms));

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

// Geometry: where the line runs, where each circle and text block sit.
const geo = sel => {
  const r = document.querySelector(sel + ' .ejny');
  const top = r.getBoundingClientRect().top;
  const before = getComputedStyle(r, '::before');
  const lineTop = parseFloat(before.top);
  const lineLen = parseFloat(before.height);
  const rows = [...r.querySelectorAll('.ejny__item')].map(li => {
    const d = li.querySelector('.ejny__dot').getBoundingClientRect();
    const b = li.querySelector('.ejny__body').getBoundingClientRect();
    const t = li.querySelector('.ejny__title').getBoundingClientRect();
    const x = li.querySelector('.ejny__text').getBoundingClientRect();
    return { dotC: d.top + d.height / 2 - top, dotX: d.left + d.width / 2, bodyC: b.top + b.height / 2 - top, gapTitleText: Math.round((x.top - t.bottom) * 10) / 10, size: Math.round(d.width) };
  });
  return { lineTop, lineLen, lineX: r.getBoundingClientRect().left + parseFloat(before.left) + 1, rows, scroll: document.documentElement.scrollWidth };
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

  /* ------------------------------------------------------ geometry --- */

  for (const sel of ['#a', '#b']) {
    const g = await page.evaluate(geo, sel);
    const first = g.rows[0].dotC, last = g.rows[g.rows.length - 1].dotC;
    check(`${sel}: the line runs from the first circle's centre to the last's`, Math.abs(g.lineTop - first) < 1 && Math.abs(g.lineTop + g.lineLen - last) < 1, `line ${g.lineTop}→${g.lineTop + g.lineLen}, circles ${first}→${last}`);
    check(`${sel}: the line runs through the circles' centres`, g.rows.every(r => Math.abs(r.dotX - g.lineX) < 1), g.lineX + ' vs ' + g.rows.map(r => r.dotX).join(','));
    check(`${sel}: each text block is centred on its circle`, g.rows.every(r => Math.abs(r.bodyC - r.dotC) < 1.5), g.rows.map(r => Math.round(r.bodyC - r.dotC)).join(','));
    check(`${sel}: title-to-description spacing is identical on every row`, new Set(g.rows.map(r => r.gapTitleText)).size === 1, g.rows.map(r => r.gapTitleText).join(','));
  }

  const look = await page.evaluate(() => {
    const r = document.querySelector('#a .ejny');
    const cs = (el, p) => getComputedStyle(el)[p];
    const dot = r.querySelector('.ejny__dot');
    const cur = r.querySelector('.is-current .ejny__dot');
    return {
      dot: [Math.round(dot.getBoundingClientRect().width), cs(dot, 'borderTopWidth'), cs(dot, 'borderTopColor'), cs(dot, 'backgroundColor')].join('|'),
      year: [cs(r.querySelector('.ejny__year'), 'fontFamily').split(',')[0], cs(r.querySelector('.ejny__year'), 'fontSize'), cs(r.querySelector('.ejny__year'), 'color')].join('|'),
      title: [cs(r.querySelector('.ejny__title'), 'fontSize'), cs(r.querySelector('.ejny__title'), 'fontWeight'), cs(r.querySelector('.ejny__title'), 'color')].join('|'),
      line: getComputedStyle(r, '::before').backgroundImage,
      current: r.querySelector('.is-current').getAttribute('aria-current') + '|' + r.querySelector('.is-current .ejny__year').textContent,
      bullets: cs(r, 'listStyleType') + '|' + cs(r, 'paddingLeft'),
      armed: r.classList.contains('ejny--armed'),
      hidden: +cs(dot, 'opacity'),
    };
  });
  check('circles are 74px, 2px grey border, white', look.dot === '74|2px|rgb(201, 210, 216)|rgb(255, 255, 255)', look.dot);
  check('years are Bebas 26px navy', look.year === '"Bebas Neue"|26px|rgb(15, 57, 97)', look.year);
  check('titles are 18px/700 navy', look.title === '18px|700|rgb(15, 57, 97)', look.title);
  check('the line fades grey to green', /linear-gradient\(rgb\(201, 210, 216\), rgb\(7, 134, 79\)\)/.test(look.line), look.line);
  check('the current step is marked for screen readers', look.current === 'step|2025', look.current);
  check('the theme cannot add bullets or its own indent (14px is the halo\'s room)', look.bullets === 'none|14px', look.bullets);
  check('below the fold it waits, hidden', look.armed && look.hidden === 0, JSON.stringify(look));

  /* ------------------------------------------------------ entrance --- */

  await page.evaluate(() => document.querySelector('#a').scrollIntoView({ block: 'center' }));
  const t0 = Date.now();
  const frames = [];
  for (let i = 0; i < 44; i++) {
    frames.push(await page.evaluate(() => {
      const r = document.querySelector('#a .ejny');
      const clip = getComputedStyle(r, '::before').clipPath;
      const m = clip.match(/inset\(0px 0px ([\d.]+)%/);
      return {
        drawn: clip === 'none' ? 100 : m ? 100 - parseFloat(m[1]) : 100,
        dots: [...r.querySelectorAll('.ejny__dot')].map(d => +(+getComputedStyle(d).opacity).toFixed(2)),
        bodyX: [...r.querySelectorAll('.ejny__body')].map(b => Math.round(new DOMMatrix(getComputedStyle(b).transform).e)),
        curBg: getComputedStyle(r.querySelector('.is-current .ejny__dot')).backgroundColor,
        curOp: +getComputedStyle(r.querySelector('.is-current .ejny__dot')).opacity,
      };
    }).then(f => Object.assign(f, { t: Date.now() - t0 })));
    await sleep(50);
  }
  const partial = frames.find(f => f.drawn > 10 && f.drawn < 90);
  check('the line draws down gradually', !!partial, partial && partial.drawn.toFixed(0) + '% at ' + partial.t + 'ms');
  const done = frames.find(f => f.drawn >= 99.9);
  check('and finishes in about 1.2s', done && done.t > 900 && done.t < 1700, done && done.t + 'ms');
  const landed = i => (frames.find(f => f.dots[i] > 0.5) || {}).t;
  const drawnAt = i => { const f = frames.find(fr => fr.dots[i] > 0.5); return f ? f.drawn : null; };
  check('the rows land in order, top to bottom', landed(0) < landed(1) && landed(1) < landed(2), [landed(0), landed(1), landed(2)].join(' < '));
  check('the middle row lands as the line passes it', drawnAt(1) > 35 && drawnAt(1) < 85, drawnAt(1) + '% drawn when row 2 appeared');
  check('the last row lands as the line reaches it', drawnAt(2) > 85, drawnAt(2) + '% drawn when row 3 appeared');
  check('the text slides in from the right', frames.some(f => f.bodyX.some(x => x > 2)), frames.map(f => f.bodyX[1]).filter(x => x > 0).slice(0, 4).join(','));
  // Only while the circle is showing: before it lands it is invisible, and
  // what colour an invisible circle is does not matter.
  const whiteLate = frames.filter(f => f.drawn < 99 && f.curOp > 0.05).every(f => f.curBg !== 'rgb(7, 134, 79)');
  check('the current step turns green only after the line arrives', whiteLate, frames.map(f => f.curBg.slice(4, 12)).join(' '));

  await sleep(1200);
  const after = await page.evaluate(() => {
    const r = document.querySelector('#a .ejny');
    const cur = r.querySelector('.is-current .ejny__dot');
    return {
      armed: r.classList.contains('ejny--armed'),
      cur: [getComputedStyle(cur).backgroundColor, getComputedStyle(cur).borderTopColor, getComputedStyle(r.querySelector('.is-current .ejny__year')).color, getComputedStyle(cur).boxShadow].join('|'),
      pulse: getComputedStyle(cur, '::after').animationName + '|' + getComputedStyle(cur, '::after').animationDuration,
      all: [...r.querySelectorAll('.ejny__dot, .ejny__body')].every(e => getComputedStyle(e).opacity === '1'),
    };
  });
  check('then disarms, everything shown', !after.armed && after.all, JSON.stringify(after));
  check('the current step: solid green, white year, 8px halo', after.cur === 'rgb(7, 134, 79)|rgb(7, 134, 79)|rgb(255, 255, 255)|rgba(7, 134, 79, 0.12) 0px 0px 0px 8px', after.cur);
  check('its halo pulses slowly', after.pulse === 'ejny-pulse|2.4s', after.pulse);
  const rings = [];
  for (let i = 0; i < 12; i++) {
    rings.push(await page.evaluate(() => { const c = getComputedStyle(document.querySelector('#a .is-current .ejny__dot'), '::after'); const m = c.boxShadow.match(/0px 0px 0px ([\d.]+)px/); return [m ? +(+m[1]).toFixed(1) : 0, +(+c.opacity).toFixed(2)]; }));
    await sleep(200);
  }
  check('the pulse grows from 8px towards 14px and fades', Math.min(...rings.map(r => r[0])) >= 8 && Math.max(...rings.map(r => r[0])) > 11 && Math.min(...rings.map(r => r[1])) < 0.5, rings.map(r => r.join('/')).join(' '));

  /* ---------------------------------------------------------- hover --- */

  await page.hover('#a .ejny__item:first-child .ejny__row');
  await sleep(350);
  const hov = await page.evaluate(() => { const li = document.querySelector('#a .ejny__item'); return getComputedStyle(li.querySelector('.ejny__dot')).borderTopColor + '|' + getComputedStyle(li.querySelector('.ejny__title')).color; });
  check('hover turns the circle border and the title green', hov === 'rgb(7, 134, 79)|rgb(7, 134, 79)', hov);

  /* -------------------------------------------------- second list --- */

  const b = await page.evaluate(() => {
    const r = document.querySelector('#b .ejny');
    const a = r.querySelector('a.ejny__row');
    return { armed: r.classList.contains('ejny--armed'), shown: getComputedStyle(r.querySelector('.ejny__dot')).opacity, link: a ? a.getAttribute('href') + '|' + getComputedStyle(a).textDecorationLine : 'none', curGreen: getComputedStyle(r.querySelector('.is-current .ejny__dot')).backgroundColor };
  });
  check('animation off: never hidden, current step already green', !b.armed && b.shown === '1' && b.curGreen === 'rgb(7, 134, 79)', JSON.stringify(b));
  check('a row with a link is one link, not underlined by the theme', b.link === '#story|none', b.link);

  if (SHOTS) {
    await page.mouse.move(2, 2);
    await sleep(300);
    await (await page.$('#a .ejny')).screenshot({ path: SHOTS + '/jny-desktop.png' });
    await (await page.$('#b .ejny')).screenshot({ path: SHOTS + '/jny-uneven.png' });
  }

  /* ---------------------------------------------------------- phone --- */

  // Robiu: check 1440 / 1100 / 767 / 360, and in a narrow half column.
  for (const w of [1100]) {
    await page.setViewport({ width: w, height: 900 });
    await sleep(400);
    const g = await page.evaluate(geo, '#a');
    check(`${w}px: the line still meets the circles`, Math.abs(g.lineTop - g.rows[0].dotC) < 1 && Math.abs(g.lineTop + g.lineLen - g.rows[2].dotC) < 1, g.lineTop + ' ' + g.rows[0].dotC);
    check(`${w}px: no sideways scroll`, g.scroll <= w, g.scroll + ' > ' + w);
  }
  await page.setViewport({ width: 1440, height: 900 });
  await page.evaluate(() => { document.querySelector('#b').style.width = '320px'; });
  await sleep(400);
  const narrow = await page.evaluate(geo, '#b');
  const spill = await page.evaluate(() => [...document.querySelectorAll('#b .ejny__row')].some(r => r.scrollWidth > r.clientWidth + 1));
  check('a 320px column: line meets the circles, text blocks centred, nothing spills', Math.abs(narrow.lineTop - narrow.rows[0].dotC) < 1 && Math.abs(narrow.lineTop + narrow.lineLen - narrow.rows[2].dotC) < 1 && narrow.rows.every(r => Math.abs(r.bodyC - r.dotC) < 1.5) && !spill, JSON.stringify(narrow.rows.map(r => Math.round(r.bodyC - r.dotC))));
  await page.evaluate(() => { document.querySelector('#b').style.width = ''; });

  for (const w of [767, 360]) {
    await page.setViewport({ width: w, height: 900 });
    await sleep(500);
    const g = await page.evaluate(geo, '#a');
    const t = await page.evaluate(() => getComputedStyle(document.querySelector('#a .ejny__year')).fontSize + '|' + getComputedStyle(document.querySelector('#a .ejny__title')).fontSize);
    check(`${w}px: 56px circles, 20px years, 16px titles`, g.rows[0].size === 56 && t === '20px|16px', g.rows[0].size + ' ' + t);
    check(`${w}px: the line follows the circles`, Math.abs(g.lineTop - g.rows[0].dotC) < 1 && g.rows.every(r => Math.abs(r.dotX - g.lineX) < 1), g.lineX + ' ' + g.rows.map(r => r.dotX).join(','));
    check(`${w}px: no sideways scroll`, g.scroll <= w, g.scroll + ' > ' + w);
    if (SHOTS && w === 360) await (await page.$('#a .ejny')).screenshot({ path: SHOTS + '/jny-360.png' });
  }

  /* ------------------------------------------------- reduced motion --- */

  const calm = await browser.newPage();
  await calm.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await calm.setViewport({ width: 1440, height: 900 });
  await calm.goto(URL, { waitUntil: 'networkidle0' });
  const rm = await calm.evaluate(() => {
    const r = document.querySelector('#a .ejny');
    const cur = r.querySelector('.is-current .ejny__dot');
    return [r.classList.contains('ejny--armed'), getComputedStyle(r.querySelector('.ejny__dot')).opacity, getComputedStyle(r, '::before').clipPath, getComputedStyle(cur).backgroundColor, getComputedStyle(cur, '::after').display].join('|');
  });
  check('reduced motion: static and final, current green, no pulse', rm === 'false|1|none|rgb(7, 134, 79)|none', rm);

  check('no script errors', errs.length === 0, errs.join(' | '));
  await browser.close();

  console.log(PASS.map(p => '  ok   ' + p).join('\n'));
  if (FAIL.length) console.log(FAIL.map(f => '  FAIL ' + f).join('\n'));
  console.log(`\n${PASS.length} passed, ${FAIL.length} failed`);
  process.exit(FAIL.length ? 1 : 0);
})();
