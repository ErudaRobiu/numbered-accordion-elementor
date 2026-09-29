/**
 * Measures Customer Logo Tabs in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 *   php tests/browser/logotabs-fixture.php > tests/browser/logotabs.html
 *   node tests/browser/logotabs-probe.js [screenshot-dir]
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];
const URL = 'http://127.0.0.1:8732/tests/browser/logotabs.html';
const SHOTS = process.argv[2] || '';
const sleep = ms => new Promise(r => setTimeout(r, ms));

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

const state = sel => {
  const r = document.querySelector(sel);
  const tabs = [...r.querySelectorAll('.eclt__tab')];
  const panels = [...r.querySelectorAll('.eclt__panel')];
  const open = panels.findIndex(p => !p.hidden);
  const p = panels[open];
  return {
    selected: tabs.findIndex(t => t.getAttribute('aria-selected') === 'true'),
    open,
    openCount: panels.filter(p => !p.hidden).length,
    visible: p ? [...p.querySelectorAll('.eclt__tile')].filter(t => !t.hidden).length : 0,
    chips: p ? [...p.querySelectorAll('.eclt__chip')].map(c => c.textContent.trim() + (c.getAttribute('aria-pressed') === 'true' ? '*' : '')) : [],
    focus: document.activeElement && document.activeElement.textContent.trim().slice(0, 30),
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

  /* -------------------------------------------------------- defaults --- */

  let s = await page.evaluate(state, '#a .eclt');
  check('Industrial is selected on load', s.selected === 0 && s.open === 0 && s.openCount === 1, JSON.stringify(s));
  check('All comes first and is on', s.chips[0] === 'All*', s.chips.join(','));
  check('every Industrial logo shows', s.visible === 13, String(s.visible));

  const dom = await page.evaluate(() => ({
    tiles: document.querySelectorAll('#a .eclt__tile').length,
    roles: [...document.querySelectorAll('#a [role=tab]')].every(t => document.getElementById(t.getAttribute('aria-controls')).getAttribute('aria-labelledby') === t.id),
    digits: /\d/.test([...document.querySelectorAll('#a .eclt__tab, #a .eclt__chip')].map(e => e.textContent).join(' ')),
    hospChips: document.querySelectorAll('#a [id$="-p-hosp"] .eclt__chip').length,
  }));
  check('hidden panels stay in the page', dom.tiles === 61, String(dom.tiles));
  check('tabs and panels label each other', dom.roles);
  check('no counts anywhere', !dom.digits);
  check('a one-segment tab has no chips', dom.hospChips === 0, String(dom.hospChips));

  /* ----------------------------------------------------------- sizing --- */

  const sizes = await page.evaluate(() => {
    const w = alt => { const i = document.querySelector(`#a img[alt="${alt}"]`); return i ? Math.round(i.getBoundingClientRect().width) : -1; };
    return { schuld: w('Schuld Bakkerij'), mondelez: w('Mondelēz International'), cws: w('CWS Workwear'),
      tallest: Math.max(...[...document.querySelectorAll('#a [id$="-p-ind"] img')].map(i => i.getBoundingClientRect().height)) };
  });
  check('a square badge gets 58px', sizes.schuld === 58, JSON.stringify(sizes));
  check('a mid-width mark gets the area rule', sizes.mondelez === 91, String(sizes.mondelez));
  check('a wide wordmark is capped at 118px', sizes.cws === 118, String(sizes.cws));
  check('nothing is taller than 58px', sizes.tallest <= 58.5, String(sizes.tallest));

  /* ------------------------------------------------------ interaction --- */

  const theme = await page.evaluate(() => {
    const t = document.querySelector('#a .eclt__tab');
    const cs = getComputedStyle(t);
    return cs.backgroundColor + ' ' + cs.borderTopColor + ' ' + getComputedStyle(t.querySelector('strong')).fontFamily.split(',')[0];
  });
  check('the theme button reset does not leak in', /^rgb\(15, 57, 97\) rgb\(15, 57, 97\) "?Bebas Neue/.test(theme), theme);

  await page.click('#a .eclt__chip[data-seg="manufacturing"]');
  await sleep(40);
  s = await page.evaluate(state, '#a .eclt');
  check('a chip filters its panel', s.visible === 4 && s.chips.includes('Manufacturing*') && s.chips[0] === 'All', JSON.stringify(s));

  const fade = await page.evaluate(() => {
    const tiles = [...document.querySelectorAll('#a [id$="-p-ind"] .eclt__tile')].filter(t => !t.hidden);
    return tiles.map(t => +getComputedStyle(t).opacity);
  });
  check('the tiles fade in after a chip', fade.some(o => o < 1), fade.join(','));
  await sleep(320);
  const done = await page.evaluate(() => [...document.querySelectorAll('#a [id$="-p-ind"] .eclt__tile')].filter(t => !t.hidden).every(t => getComputedStyle(t).opacity === '1'));
  check('and are fully in within 300ms', done);

  const word = await page.evaluate(() => { const w = document.querySelector('#a .eclt__word--serif'); return w.getAttribute('aria-label') + '|' + getComputedStyle(w).fontFamily.split(',')[0]; });
  check('a missing logo prints its wordmark in serif, named for a screen reader', word === 'Volvo|Georgia', word);

  await page.click('#a .eclt__tab[data-key="rest"]');
  await sleep(40);
  s = await page.evaluate(state, '#a .eclt');
  check('clicking a tab switches the panel', s.selected === 1 && s.open === 1 && s.openCount === 1);
  check('its chips start on All', s.chips[0] === 'All*', s.chips.join(','));

  await page.focus('#a .eclt__tab[data-key="rest"]');
  await page.keyboard.press('ArrowRight');
  await sleep(40);
  s = await page.evaluate(state, '#a .eclt');
  check('ArrowRight moves and selects', s.selected === 2 && s.open === 2 && /^Hospitality/.test(s.focus), JSON.stringify(s));
  await page.keyboard.press('ArrowRight');
  s = await page.evaluate(state, '#a .eclt');
  check('and wraps to the first', s.selected === 0);
  await page.keyboard.press('End');
  s = await page.evaluate(state, '#a .eclt');
  check('End goes to the last', s.selected === 2);
  const tabindex = await page.evaluate(() => [...document.querySelectorAll('#a .eclt__tab')].map(t => t.tabIndex).join(''));
  check('only the selected tab is in the tab order', tabindex === '-1-10', tabindex);

  await page.hover('#a [id$="-p-hosp"] .eclt__tile');
  await sleep(350);
  const lift = await page.evaluate(() => getComputedStyle(document.querySelector('#a [id$="-p-hosp"] .eclt__tile')).transform);
  check('hovering a tile lifts it', /-2\)$/.test(lift), lift);
  const dark = await page.evaluate(() => getComputedStyle(document.querySelector('#a .eclt__tile--dark')).backgroundColor);
  check('Battersea sits on a dark tile', dark === 'rgb(30, 42, 51)', dark);

  /* ----------------------------------------------------- second widget --- */

  s = await page.evaluate(state, '#b .eclt');
  check('the default tab can be picked', s.selected === 1, String(s.selected));
  check('without All, a tab opens on its first segment', s.chips[0] === 'QSR*' && s.visible === 9, JSON.stringify(s));
  // Lazy, and in a tab that is not showing, so it loads -- and is measured --
  // only once its tab opens.
  await page.click('#b .eclt__tab[data-key="hosp"]');
  await sleep(400);
  const svg = await page.evaluate(() => { const i = document.querySelector('#b img[alt^="Enjay"]'); return [i.hasAttribute('data-eclt-fit'), i.style.width]; });
  check('an SVG is sized by the script', !svg[0] && svg[1] === '118px', JSON.stringify(svg));
  const link = await page.evaluate(() => !!document.querySelector('#b .eclt__tile a.eclt__link[href="#burger-king"] img[alt="Burger King"]'));
  check('a logo can link', link);

  /* ------------------------------------------------------------- hash --- */

  // about:blank between, so each is a real page load and not a same-page
  // hash change -- the load is the case a link from the home page makes.
  await page.goto('about:blank');
  await page.goto(URL + '#hosp', { waitUntil: 'networkidle0' });
  s = await page.evaluate(state, '#a .eclt');
  check('#hosp opens Hospitality', s.selected === 2 && s.open === 2);
  await page.evaluate(() => { location.hash = 'ind'; });
  await sleep(60);
  s = await page.evaluate(state, '#a .eclt');
  check('changing the hash to #ind switches back', s.selected === 0);
  await page.goto('about:blank');
  await page.goto(URL + '#customers', { waitUntil: 'networkidle0' });
  s = await page.evaluate(state, '#a .eclt');
  check('#customers leaves Industrial showing', s.selected === 0);

  if (SHOTS) await (await page.$('#a .eclt')).screenshot({ path: SHOTS + '/lt-desktop.png' });

  /* ------------------------------------------------------------ sizes --- */

  for (const [w, name] of [[1024, 'tablet'], [767, 'phone-767'], [360, 'phone-360']]) {
    await page.setViewport({ width: w, height: 900 });
    await sleep(300);
    const lay = await page.evaluate(() => {
      const r = document.querySelector('#a .eclt');
      const tabs = [...r.querySelectorAll('.eclt__tab')].map(t => t.getBoundingClientRect());
      const tiles = [...r.querySelectorAll('[id$="-p-ind"] .eclt__tile')].filter(t => !t.hidden).slice(0, 4).map(t => t.getBoundingClientRect());
      const chips = r.querySelector('[id$="-p-ind"] .eclt__chips');
      return {
        scroll: document.documentElement.scrollWidth,
        tabsAcross: new Set(tabs.map(t => Math.round(t.top))).size === 1,
        tabsStacked: new Set(tabs.map(t => Math.round(t.left))).size === 1,
        perRow: tiles.filter(t => Math.round(t.top) === Math.round(tiles[0].top)).length,
        tileH: Math.round(tiles[0].height),
        chipsOneRow: new Set([...chips.children].map(c => Math.round(c.getBoundingClientRect().top))).size === 1,
        chipsScroll: chips.scrollWidth > chips.clientWidth,
      };
    });
    check(`${name}: no sideways page scroll`, lay.scroll <= w, lay.scroll + ' > ' + w);
    if (w > 767) {
      check(`${name}: tabs three across or one column, never two and one`, lay.tabsAcross || lay.tabsStacked, JSON.stringify(lay));
    } else {
      check(`${name}: tabs stack`, lay.tabsStacked, JSON.stringify(lay));
      check(`${name}: chips are one row`, lay.chipsOneRow, JSON.stringify(lay));
      check(`${name}: the wall is three across with 80px tiles`, lay.perRow === 3 && lay.tileH === 80, JSON.stringify(lay));
    }
    if (w === 360) check('phone-360: the chip row scrolls sideways on its own', lay.chipsScroll, JSON.stringify(lay));
    if (SHOTS) await (await page.$('#a .eclt')).screenshot({ path: `${SHOTS}/lt-${name}.png` });
  }

  /* ---------------------------------------------------- reduced motion --- */

  const calm = await browser.newPage();
  await calm.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await calm.setViewport({ width: 1440, height: 900 });
  await calm.goto(URL, { waitUntil: 'networkidle0' });
  await calm.click('#a .eclt__chip[data-seg="industrial-laundry"]');
  await sleep(20);
  const rm = await calm.evaluate(() => [...document.querySelectorAll('#a [id$="-p-ind"] .eclt__tile')].filter(t => !t.hidden).map(t => getComputedStyle(t).opacity + '/' + getComputedStyle(t).animationName));
  check('reduced motion: tiles appear at once', rm.length === 3 && rm.every(v => v === '1/none'), rm.join(','));

  check('no script errors', errs.length === 0, errs.join(' | '));
  await browser.close();

  console.log(PASS.map(p => '  ok   ' + p).join('\n'));
  if (FAIL.length) console.log(FAIL.map(f => '  FAIL ' + f).join('\n'));
  console.log(`\n${PASS.length} passed, ${FAIL.length} failed`);
  process.exit(FAIL.length ? 1 : 0);
})();
