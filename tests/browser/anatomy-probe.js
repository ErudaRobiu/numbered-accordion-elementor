/**
 * Measures Case Anatomy in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 *   php tests/browser/anatomy-fixture.php > tests/browser/anatomy.html
 *   node tests/browser/anatomy-probe.js [screenshot-dir]
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];
const URL = 'http://127.0.0.1:8732/tests/browser/anatomy.html';
const SHOTS = process.argv[2] || '';
const sleep = ms => new Promise(r => setTimeout(r, ms));

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

const state = sel => {
  const r = document.querySelector(sel);
  const tabs = [...r.querySelectorAll('.ecan__tab')];
  const panels = [...r.querySelectorAll('.ecan__panel')];
  const open = panels.findIndex(p => !p.hidden);
  return {
    selected: tabs.findIndex(t => t.getAttribute('aria-selected') === 'true'),
    open,
    openCount: panels.filter(p => !p.hidden).length,
    first: open >= 0 ? panels[open].querySelector('.ecan__answer').textContent.slice(0, 20) : '',
    tabindex: tabs.map(t => t.tabIndex).join(','),
    focus: document.activeElement && (document.activeElement.querySelector('img') || {}).alt,
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

  let s = await page.evaluate(state, '#a .ecan');
  check('CWS opens', s.selected === 0 && s.open === 0 && s.openCount === 1 && /^Hot, very moist/.test(s.first), JSON.stringify(s));
  check('only the selected tab is in the tab order', s.tabindex === '0,-1,-1', s.tabindex);

  const dom = await page.evaluate(() => {
    const r = document.querySelector('#a .ecan');
    const tiles = [...r.querySelectorAll('.ecan__panel:not([hidden]) .ecan__tile')];
    const cs = el => getComputedStyle(el);
    const t = tiles[0];
    const last = tiles[5];
    const tab = r.querySelector('.ecan__tab[aria-selected="true"]');
    const dark = r.querySelector('.ecan__logo--dark');
    const logos = [...r.querySelectorAll('.ecan__logo')].map(i => i.getBoundingClientRect());
    return {
      panels: r.querySelectorAll('.ecan__panel').length,
      tiles: tiles.length,
      columns: new Set(tiles.map(x => Math.round(x.getBoundingClientRect().left))).size,
      num: [t.querySelector('.ecan__num').textContent, cs(t.querySelector('.ecan__num')).fontFamily.split(',')[0], cs(t.querySelector('.ecan__num')).fontSize, cs(t.querySelector('.ecan__num')).color].join('|'),
      label: [cs(t.querySelector('.ecan__label')).fontSize, cs(t.querySelector('.ecan__label')).textTransform].join('|'),
      answer: [cs(t.querySelector('.ecan__answer')).fontSize, cs(t.querySelector('.ecan__answer')).fontWeight, cs(t.querySelector('.ecan__answer')).color].join('|'),
      result: [cs(last).backgroundColor, cs(last.querySelector('.ecan__label')).color, cs(last.querySelector('.ecan__answer')).color, cs(last.querySelector('.ecan__num')).color].join('|'),
      tab: [Math.round(tab.getBoundingClientRect().height), cs(tab).backgroundColor, cs(tab).borderTopColor].join('|'),
      tabShadow: cs(tab).boxShadow,
      dark: cs(dark).backgroundColor,
      logoMax: [Math.max(...logos.map(l => l.width)), Math.max(...logos.filter((l, i) => !r.querySelectorAll('.ecan__logo')[i].classList.contains('ecan__logo--dark')).map(l => l.height))].join('x'),
      link: (r.querySelector('.ecan__panel:not([hidden]) .ecan__link') || {}).textContent,
      card: [cs(r).backgroundColor, cs(r).borderTopLeftRadius, cs(r).paddingTop].join('|'),
    };
  });
  check('all panels stay in the page', dom.panels === 3);
  check('six tiles in two columns', dom.tiles === 6 && dom.columns === 2, JSON.stringify(dom));
  check('numbers are Bebas, 28px, green', dom.num === '01|"Bebas Neue"|28px|rgb(7, 134, 79)', dom.num);
  check('labels are 11px uppercase', dom.label === '11px|uppercase', dom.label);
  check('answers are 14px, 600, navy', dom.answer === '14px|600|rgb(15, 57, 97)', dom.answer);
  check('the result tile is navy with green-light label and number, white answer', dom.result === 'rgb(15, 57, 97)|rgb(77, 207, 141)|rgb(255, 255, 255)|rgb(77, 207, 141)', dom.result);
  check('tabs are 62px, white, green when selected, and the theme reset does not leak in', dom.tab === '62|rgb(255, 255, 255)|rgb(7, 134, 79)', dom.tab);
  check('the selected tab has its ring and glow', /inset/.test(dom.tabShadow), dom.tabShadow);
  check('Bruzaholms sits on the dark chip', dom.dark === 'rgb(30, 42, 51)', dom.dark);
  check('logos stay inside 100 x 30', +dom.logoMax.split('x')[0] <= 100 && +dom.logoMax.split('x')[1] <= 30, dom.logoMax);
  check('the case link shows under the grid', /^Read the CWS case \(PDF\)/.test(dom.link || ''), dom.link);
  check('the card is off-white, 25px, 18px padding', dom.card === 'rgb(245, 246, 247)|25px|18px', dom.card);

  /* ------------------------------------------------------ interaction --- */

  await page.click('#a .ecan__tab:nth-child(2)');
  const fade = [];
  for (let i = 0; i < 8; i++) {
    fade.push(await page.evaluate(() => [...document.querySelectorAll('#a .ecan__panel:not([hidden]) .ecan__tile')].map(t => +(+getComputedStyle(t).opacity).toFixed(2))));
    await sleep(45);
  }
  s = await page.evaluate(state, '#a .ecan');
  check('clicking a tab switches the project', s.selected === 1 && s.open === 1 && s.openCount === 1 && /^Clogging/.test(s.first), JSON.stringify(s));
  check('the tiles rise in turn: the last is behind the first at some point',
    fade.some(f => f[0] > f[5]), fade.map(f => f.join('/')).slice(0, 4).join('  '));
  await sleep(100);
  const settled = await page.evaluate(() => [...document.querySelectorAll('#a .ecan__panel:not([hidden]) .ecan__tile')].every(t => getComputedStyle(t).opacity === '1'));
  check('and all are in by 300ms (sampled at ~460ms after the click)', settled);

  await page.focus('#a .ecan__tab[aria-selected="true"]');
  await page.keyboard.press('ArrowRight');
  s = await page.evaluate(state, '#a .ecan');
  check('ArrowRight moves focus and selection', s.selected === 2 && s.focus === 'Bruzaholms', JSON.stringify(s));
  await page.keyboard.press('ArrowRight');
  s = await page.evaluate(state, '#a .ecan');
  check('and wraps', s.selected === 0);
  await page.keyboard.press('End');
  s = await page.evaluate(state, '#a .ecan');
  check('End goes to the last', s.selected === 2);
  await page.keyboard.press('Home');
  s = await page.evaluate(state, '#a .ecan');
  check('Home goes to the first', s.selected === 0 && s.tabindex === '0,-1,-1', JSON.stringify(s));

  await page.hover('#a .ecan__tab:nth-child(3)');
  await sleep(300);
  const lift = await page.evaluate(() => getComputedStyle(document.querySelector('#a .ecan__tab:nth-child(3)')).transform);
  check('hovering a tab lifts it', /-2\)$/.test(lift), lift);

  /* ---------------------------------------------------- second widget --- */

  const b = await page.evaluate(() => {
    const r = document.querySelector('#b .ecan');
    const panels = [...r.querySelectorAll('.ecan__panel')];
    return {
      tabs: r.querySelectorAll('.ecan__tab').length,
      open: panels.findIndex(p => !p.hidden),
      result: r.querySelectorAll('.ecan__tile--result').length,
      lantNums: [...panels[1].querySelectorAll('.ecan__num')].map(n => n.textContent).join(','),
      noLogo: r.querySelectorAll('.ecan__tab')[3].textContent.trim(),
      bkNums: [...panels[3].querySelectorAll('.ecan__num')].map(n => n.textContent).join(','),
      across: new Set([...r.querySelectorAll('.ecan__tab')].map(t => Math.round(t.getBoundingClientRect().top))).size,
    };
  });
  check('four projects fit one row', b.tabs === 4 && b.across === 1, JSON.stringify(b));
  check('the default project can be picked', b.open === 2);
  check('highlight off leaves no navy tile', b.result === 0);
  check('a missing answer keeps the other numbers', b.lantNums === '01,02,04,05,06', b.lantNums);
  check('a project without a logo shows its name', b.noLogo === 'Burger King' && b.bkNums === '01,06', JSON.stringify(b));

  if (SHOTS) await (await page.$('#a .ecan')).screenshot({ path: SHOTS + '/an-desktop.png' });

  /* ------------------------------------------------------------ sizes --- */

  for (const [w, name] of [[1024, 'tablet'], [767, 'phone-767'], [360, 'phone-360']]) {
    await page.setViewport({ width: w, height: 900 });
    await sleep(300);
    const lay = await page.evaluate(() => {
      const r = document.querySelector('#a .ecan');
      const tiles = [...r.querySelectorAll('.ecan__panel:not([hidden]) .ecan__tile')];
      const tabs = [...r.querySelectorAll('.ecan__tab')].map(t => t.getBoundingClientRect());
      return {
        scroll: document.documentElement.scrollWidth,
        cols: new Set(tiles.map(t => Math.round(t.getBoundingClientRect().left))).size,
        tabsAcross: new Set(tabs.map(t => Math.round(t.top))).size === 1,
        spill: [...r.querySelectorAll('.ecan__tab, .ecan__tile')].some(e => e.scrollWidth > e.clientWidth + 1),
        pad: getComputedStyle(r).paddingTop,
      };
    });
    check(`${name}: no sideways page scroll`, lay.scroll <= w, lay.scroll + ' > ' + w);
    check(`${name}: nothing spills out of a tab or tile`, !lay.spill);
    check(`${name}: tabs stay three across`, lay.tabsAcross);
    if (w <= 767) check(`${name}: one column of tiles, tighter padding`, lay.cols === 1 && lay.pad === '12px', JSON.stringify(lay));
    else check(`${name}: two columns of tiles`, lay.cols === 2, JSON.stringify(lay));
    if (SHOTS) await (await page.$('#a .ecan')).screenshot({ path: `${SHOTS}/an-${name}.png` });
  }

  /* ---------------------------------------------------- reduced motion --- */

  const calm = await browser.newPage();
  await calm.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await calm.setViewport({ width: 1440, height: 900 });
  await calm.goto(URL, { waitUntil: 'networkidle0' });
  await calm.click('#a .ecan__tab:nth-child(3)');
  await sleep(20);
  const rm = await calm.evaluate(() => [...document.querySelectorAll('#a .ecan__panel:not([hidden]) .ecan__tile')].map(t => getComputedStyle(t).opacity + '/' + getComputedStyle(t).animationName));
  check('reduced motion: answers appear at once', rm.length === 6 && rm.every(v => v === '1/none'), rm.join(','));

  check('no script errors', errs.length === 0, errs.join(' | '));
  await browser.close();

  console.log(PASS.map(p => '  ok   ' + p).join('\n'));
  if (FAIL.length) console.log(FAIL.map(f => '  FAIL ' + f).join('\n'));
  console.log(`\n${PASS.length} passed, ${FAIL.length} failed`);
  process.exit(FAIL.length ? 1 : 0);
})();
