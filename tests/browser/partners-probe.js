/**
 * Measures the Partner Diagram in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 *   php tests/browser/partners-fixture.php > tests/browser/partners.html
 *   node tests/browser/partners-probe.js [screenshot-dir]
 *
 * Headless on purpose: an unfocused tab throttles animation, and what this
 * proves is that the entrance and the pulse actually move over time.
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];
const URL = 'http://127.0.0.1:8732/tests/browser/partners.html';
const SHOTS = process.argv[2] || '';
const sleep = ms => new Promise(r => setTimeout(r, ms));

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

const probe = () => {
  const root = document.querySelector('#a .epdg');
  const css = (sel, prop, scope) => getComputedStyle((scope || root).querySelector(sel))[prop];
  return {
    armed: root.classList.contains('epdg--armed'),
    in: root.classList.contains('is-in'),
    source: +css('.epdg__card--source', 'opacity'),
    last: +css('.epdg__card--partner-two', 'opacity'),
    line: css('.epdg__arrow--in .epdg__line', 'clipPath'),
    stem: +css('.epdg__stem--across', 'opacity'),
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
  await sleep(300);

  /* ------------------------------------------------ before it arrives --- */

  let s = await page.evaluate(probe);
  check('the animated diagram is armed', s.armed);
  check('and waits: nothing revealed below the fold', !s.in && s.source === 0 && s.last === 0, JSON.stringify(s));

  const still = await page.evaluate(() => {
    const r = document.querySelector('#b .epdg');
    return { armed: r.classList.contains('epdg--armed'), op: +getComputedStyle(r.querySelector('.epdg__card')).opacity,
      filter: getComputedStyle(r.querySelector('.epdg__backdrop')).filter };
  });
  check('the still one is never hidden', !still.armed && still.op === 1, JSON.stringify(still));
  check('and its photograph is sharp', still.filter === 'none', still.filter);

  /* ------------------------------------------------------- entrance --- */

  await page.evaluate(() => document.querySelector('#a').scrollIntoView({ block: 'center' }));
  const samples = [];
  for (let t = 0; t <= 2400; t += 100) {
    samples.push(Object.assign({ t }, await page.evaluate(probe)));
    await sleep(100);
  }
  const firstUp = samples.find(x => x.source > 0.5);
  const lastUp = samples.find(x => x.last > 0.5);
  check('it goes in once scrolled to', samples[samples.length - 1].in);
  check('the first card rises', !!firstUp, firstUp && 't=' + firstUp.t);
  check('the partners rise after it (stagger)', firstUp && lastUp && lastUp.t > firstUp.t, (firstUp && firstUp.t) + ' < ' + (lastUp && lastUp.t));
  check('the arrow draws: it passes through a partial clip',
    samples.some(x => /inset\(0px [0-9.]+% 0px 0px\)/.test(x.line) && !/ 100% /.test(x.line)), samples.map(x => x.line).filter(v => v !== 'none').join(' | '));
  await sleep(300);
  const end = await page.evaluate(probe);
  check('it disarms once the entrance is over', !end.armed);
  check('everything ends complete', end.source === 1 && end.last === 1 && end.line === 'none' && end.stem === 1, JSON.stringify(end));
  const midway = samples.find(x => /inset\(0px [0-9.]+% 0px 0px\)/.test(x.line) && !/ 100% /.test(x.line) && !/ 0% /.test(x.line));
  if (midway) console.log('  (arrow midway: ' + midway.line + ' at t=' + midway.t + ')');

  // Measuring is not enough for a line: read its pixels, from the left half
  // where the head is not. A viewport capture, because an element capture of
  // a composited animation can show a frame the screen never does.
  const png = await page.screenshot({ encoding: 'base64' });
  const green = await page.evaluate(src => new Promise(done => {
    const box = document.querySelector('#a .epdg__arrow--in').getBoundingClientRect();
    const img = new Image();
    img.onload = () => {
      const c = document.createElement('canvas');
      c.width = img.width; c.height = img.height;
      const g = c.getContext('2d');
      g.drawImage(img, 0, 0);
      const d = g.getImageData(Math.round(box.x), Math.round(box.y), Math.floor(box.width / 2), Math.round(box.height)).data;
      let n = 0;
      for (let i = 0; i < d.length; i += 4) if (d[i + 1] > d[i] + 40 && d[i + 1] > d[i + 2] + 20) n++;
      done(n);
    };
    img.src = 'data:image/png;base64,' + src;
  }), png);
  check('the drawn arrow is actually painted', green >= 4, green + ' green pixels');

  /* ---------------------------------------------------------- pulse --- */

  // One whole loop, so the sample cannot land entirely in the resting half.
  const pulse = [];
  for (let i = 0; i < 38; i++) {
    pulse.push(await page.evaluate(() => {
      const p = document.querySelector('#a .epdg__arrow--in .epdg__pulse');
      return { left: parseFloat(getComputedStyle(p).left), op: +getComputedStyle(p).opacity };
    }));
    await sleep(100);
  }
  const lefts = new Set(pulse.map(p => Math.round(p.left)));
  check('the pulse travels along the arrow', lefts.size > 4, [...lefts].join(','));
  check('and is visible while it does', pulse.some(p => p.op > 0.9));

  /* ---------------------------------------------------------- hover --- */

  const before = await page.evaluate(() => getComputedStyle(document.querySelector('#a .epdg__arrow--in .epdg__line')).backgroundColor);
  await page.hover('#a .epdg__card--core');
  await sleep(450);
  const hov = await page.evaluate(() => {
    const r = document.querySelector('#a .epdg');
    return {
      line: getComputedStyle(r.querySelector('.epdg__arrow--in .epdg__line')).backgroundColor,
      stem: getComputedStyle(r.querySelector('.epdg__stem--up')).borderLeftColor,
      lift: getComputedStyle(r.querySelector('.epdg__card--core')).transform,
      colour: getComputedStyle(r.querySelector('.epdg__card--core .epdg__title')).color,
    };
  });
  check('hovering the core card highlights its arrows', hov.line === 'rgb(0, 165, 93)' && before !== hov.line, before + ' -> ' + hov.line);
  check('and its bracket', hov.stem === 'rgb(7, 134, 79)', hov.stem);
  check('and lifts it 3px', /matrix\(1, 0, 0, 1, 0, -3\)/.test(hov.lift), hov.lift);
  check('a hostile a:hover does not recolour it', hov.colour === 'rgb(255, 255, 255)', hov.colour);

  await page.hover('#a .epdg__card--partner-one');
  await sleep(450);
  const branch = await page.evaluate(() => {
    const r = document.querySelector('#a .epdg');
    const c = s => getComputedStyle(r.querySelector(s));
    return { left: c('.epdg__stem--left').borderLeftColor, right: c('.epdg__stem--right').borderRightColor, arrow: c('.epdg__arrow--in .epdg__line').backgroundColor };
  });
  check('a partner lights only its own branch', branch.left === 'rgb(7, 134, 79)' && branch.right !== branch.left && branch.arrow === 'rgb(7, 134, 79)', JSON.stringify(branch));

  const glass = await page.evaluate(() => getComputedStyle(document.querySelector('#a .epdg__card--source')).backdropFilter);
  check('side cards are frosted glass', glass === 'blur(10px)', glass);

  await page.mouse.move(5, 5);
  await sleep(400);
  if (SHOTS) await (await page.$('#a .epdg')).screenshot({ path: SHOTS + '/desktop.png' });

  /* ---------------------------------------------------------- sizes --- */

  for (const [w, name] of [[1024, 'tablet'], [767, 'phone-767'], [360, 'phone-360']]) {
    await page.setViewport({ width: w, height: 800 });
    await sleep(500);
    const lay = await page.evaluate(() => {
      const r = document.querySelector('#a .epdg');
      const box = s => r.querySelector(s).getBoundingClientRect();
      const a = box('.epdg__arrow--in');
      return {
        scroll: document.documentElement.scrollWidth,
        rootW: Math.round(r.getBoundingClientRect().width),
        sameRow: Math.abs(box('.epdg__card--source').top - box('.epdg__card--site').top) < 2,
        stacked: box('.epdg__card--site').top > box('.epdg__card--core').bottom,
        partnersStacked: box('.epdg__card--partner-two').top > box('.epdg__card--partner-one').bottom,
        arrowTall: a.height > a.width,
        stems: getComputedStyle(r.querySelector('.epdg__stems')).display,
        join: getComputedStyle(r.querySelector('.epdg__join')).display,
        overflow: [...r.querySelectorAll('.epdg__card')].some(c => c.scrollWidth > c.clientWidth + 1),
      };
    });
    check(`${name}: no sideways scroll`, lay.scroll <= w, lay.scroll + ' > ' + w);
    check(`${name}: nothing spills out of a card`, !lay.overflow);
    if (w > 767) {
      check(`${name}: three cards in a row`, lay.sameRow && lay.stems === 'block' && lay.join === 'none', JSON.stringify(lay));
    } else {
      check(`${name}: one column, arrows pointing down`, lay.stacked && lay.partnersStacked && lay.arrowTall, JSON.stringify(lay));
      check(`${name}: the Partners label replaces the bracket`, lay.stems === 'none' && lay.join === 'flex', JSON.stringify(lay));
    }
    if (SHOTS) await (await page.$('#a .epdg')).screenshot({ path: `${SHOTS}/${name}.png` });
  }

  /* -------------------------------------------------- reduced motion --- */

  const calm = await browser.newPage();
  await calm.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await calm.setViewport({ width: 1440, height: 900 });
  await calm.goto(URL, { waitUntil: 'networkidle0' });
  const rm = await calm.evaluate(() => {
    const r = document.querySelector('#a .epdg');
    return { armed: r.classList.contains('epdg--armed'), op: +getComputedStyle(r.querySelector('.epdg__card--partner-two')).opacity,
      pulse: getComputedStyle(r.querySelector('.epdg__pulse')).display };
  });
  check('reduced motion: never armed, complete from the start', !rm.armed && rm.op === 1, JSON.stringify(rm));
  check('reduced motion: no pulse', rm.pulse === 'none', rm.pulse);

  check('no script errors', errs.length === 0, errs.join(' | '));
  await browser.close();

  console.log(PASS.map(p => '  ok   ' + p).join('\n'));
  if (FAIL.length) console.log(FAIL.map(f => '  FAIL ' + f).join('\n'));
  console.log(`\n${PASS.length} passed, ${FAIL.length} failed`);
  process.exit(FAIL.length ? 1 : 0);
})();
