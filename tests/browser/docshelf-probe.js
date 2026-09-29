/**
 * Measures the Document Shelf in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 *   php tests/browser/docshelf-fixture.php > tests/browser/docshelf.html
 *   node tests/browser/docshelf-probe.js [screenshot-dir]
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];
const URL = 'http://127.0.0.1:8732/tests/browser/docshelf.html';
const SHOTS = process.argv[2] || '';
const sleep = ms => new Promise(r => setTimeout(r, ms));

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

const shown = sel => [...document.querySelectorAll(sel + ' .edoc__card')].filter(c => !c.hidden).map(c => c.querySelector('.edoc__title').textContent);
const pressed = sel => [...document.querySelectorAll(sel + ' .edoc__chip')].filter(c => c.getAttribute('aria-pressed') === 'true').map(c => c.dataset.seg).join(',');

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

  const a = await page.evaluate(() => {
    const r = document.querySelector('#a .edoc');
    const cards = [...r.querySelectorAll('.edoc__card')];
    const cs = (el, p) => getComputedStyle(el)[p];
    const first = cards[0];
    const img = first.querySelector('.edoc__cover img');
    const cover = first.querySelector('.edoc__cover');
    return {
      count: cards.length,
      chips: [...r.querySelectorAll('.edoc__chip')].map(c => c.textContent + (c.getAttribute('aria-pressed') === 'true' ? '*' : '')).join(','),
      perRow: cards.filter(c => Math.round(c.getBoundingClientRect().top) === Math.round(cards[0].getBoundingClientRect().top)).length,
      links: cards.filter(c => c.tagName === 'A').length,
      soon: cards.filter(c => c.tagName === 'DIV').map(c => c.querySelector('.edoc__title').textContent + '|' + c.querySelector('.edoc__meta em').textContent),
      label: first.getAttribute('aria-label'),
      target: first.target + '|' + first.rel + '|' + first.hasAttribute('download'),
      coverBox: [Math.round(cover.getBoundingClientRect().height), cs(cover, 'paddingTop'), cs(cover, 'backgroundImage').includes('linear-gradient')].join('|'),
      // 72% of the padded box; its top 22px down, its foot cut off by the
      // cover's bottom edge, so it reads as a sheet rising out of the card.
      sheet: [Math.round(img.getBoundingClientRect().width / (cover.clientWidth - 44) * 100), cs(img, 'borderTopLeftRadius'), cs(img, 'borderBottomLeftRadius'), Math.round(img.getBoundingClientRect().top - cover.getBoundingClientRect().top), img.getBoundingClientRect().bottom >= cover.getBoundingClientRect().bottom].join('|'),
      card: [cs(first, 'borderTopLeftRadius'), cs(first, 'borderTopColor'), cs(first, 'textDecorationLine'), cs(first.querySelector('.edoc__title'), 'color')].join('|'),
      type: [cs(first.querySelector('.edoc__type'), 'fontSize'), cs(first.querySelector('.edoc__type'), 'textTransform'), cs(first.querySelector('.edoc__type'), 'color')].join('|'),
      title: [cs(first.querySelector('.edoc__title'), 'fontSize'), cs(first.querySelector('.edoc__title'), 'fontWeight')].join('|'),
      meta: [first.querySelector('.edoc__meta i').textContent, cs(first.querySelector('.edoc__meta i'), 'backgroundColor'), first.querySelector('.edoc__meta em').textContent, cs(first.querySelector('.edoc__meta em'), 'color')].join('|'),
      metaBottom: new Set(cards.slice(0, 4).map(c => Math.round(c.querySelector('.edoc__meta').getBoundingClientRect().bottom))).size,
      gap: cs(r.querySelector('.edoc__grid'), 'columnGap'),
    };
  });
  check('twelve documents', a.count === 12, String(a.count));
  check('chips: All first and on, then the filters in first-use order', a.chips === 'All*,White papers,Product literature,Industry guides,Performance evidence,Company', a.chips);
  check('four across on desktop, 16px gap', a.perRow === 4 && a.gap === '16px', a.perRow + ' ' + a.gap);
  check('all twelve cards link to their files', a.links === 12 && a.soon.length === 0, a.links + ' ' + a.soon.join(' / '));
  check('a card link is named title + PDF + pages + new tab', a.label === 'Waste heat recovery for commercial and industrial facilities, PDF, 12 pages · May 2026, opens in a new tab', a.label);
  check('new tab on, download off by default', a.target === '_blank|noopener|false', a.target);
  check('the cover area is 200px with 22px padding and the gradient', a.coverBox === '200|22px|true', a.coverBox);
  check('the sheet is 72% wide, rounded on top, 22px down, running off the bottom', a.sheet === '72|6px|0px|22|true', a.sheet);
  check('the card is white-bordered, 20px, not underlined by the theme', a.card === '20px|rgb(221, 226, 230)|none|rgb(15, 57, 97)', a.card);
  check('type label is 11px uppercase green', a.type === '11px|uppercase|rgb(7, 134, 79)', a.type);
  check('title is 16px/600', a.title === '16px|600', a.title);
  check('meta: navy PDF badge and green Download', a.meta === 'PDF|rgb(15, 57, 97)|Download ↓|rgb(7, 134, 79)', a.meta);
  check('meta rows line up across a row', a.metaBottom === 1, String(a.metaBottom));

  /* ----------------------------------------------------------- hover --- */

  await page.hover('#a a.edoc__card');
  await sleep(350);
  const hov = await page.evaluate(() => {
    const c = document.querySelector('#a a.edoc__card');
    const m = new DOMMatrix(getComputedStyle(c.querySelector('.edoc__cover img')).transform);
    return [getComputedStyle(c).transform, Math.round(Math.atan2(m.b, m.a) * 180 / Math.PI * 10) / 10, Math.round(m.f)].join('|');
  });
  check('hover lifts the card 3px and tilts the sheet -1.5° up 6px', hov === 'matrix(1, 0, 0, 1, 0, -3)|-1.5|-6', hov);
  await page.mouse.move(2, 2);

  /* ---------------------------------------------------------- filter --- */

  await page.click('#a .edoc__chip[data-seg="perf"]');
  await sleep(40);
  let v = await page.evaluate(shown, '#a');
  check('Performance evidence shows the five studies', v.length === 5 && /^22-month/.test(v[0]), v.join(' / '));
  const midway = await page.evaluate(() => [...document.querySelectorAll('#a .edoc__card:not([hidden])')].map(c => +(+getComputedStyle(c).opacity).toFixed(2)));
  check('the cards fade in', midway.some(o => o < 1), midway.join(','));
  await sleep(300);
  const done = await page.evaluate(() => [...document.querySelectorAll('#a .edoc__card:not([hidden])')].every(c => getComputedStyle(c).opacity === '1'));
  check('and are all in within 300ms (sampled at ~340ms)', done);
  const inDom = await page.evaluate(() => document.querySelectorAll('#a .edoc__card').length);
  check('hidden cards stay in the page', inDom === 12);
  check('exactly one chip is pressed', (await page.evaluate(pressed, '#a')) === 'perf');

  /* ------------------------------------------------------------ hash --- */

  await page.goto('about:blank');
  await page.goto(URL + '#docs-industry', { waitUntil: 'networkidle0' });
  v = await page.evaluate(shown, '#a');
  check('#docs-industry opens with Industry guides on', (await page.evaluate(pressed, '#a')) === 'industry' && v.length === 3, v.join(' / '));
  await page.evaluate(() => { location.hash = 'docs-perf'; });
  await sleep(60);
  check('changing the hash switches the filter', (await page.evaluate(pressed, '#a')) === 'perf');
  await page.goto('about:blank');
  await page.goto(URL + '#docs', { waitUntil: 'networkidle0' });
  check('an unrelated hash leaves All on', (await page.evaluate(pressed, '#a')) === 'all');

  /* --------------------------------------------------- second widget --- */

  const b = await page.evaluate(() => {
    const r = document.querySelector('#b .edoc');
    const first = r.querySelector('.edoc__card');
    return {
      chips: [...r.querySelectorAll('.edoc__chip')].map(c => c.textContent + (c.getAttribute('aria-pressed') === 'true' ? '*' : '')).join(','),
      shown: [...r.querySelectorAll('.edoc__card')].filter(c => !c.hidden).length,
      link: first.target + '|' + first.hasAttribute('download') + '|' + first.getAttribute('aria-label'),
      soon: [...r.querySelectorAll('.edoc__card')].filter(c => c.tagName === 'DIV').map(c => c.querySelector('.edoc__title').textContent + '|' + c.querySelector('.edoc__meta em').textContent + '|' + !!c.querySelector('a')),
    };
  });
  check('without All, the shelf opens on the first filter', b.chips.startsWith('White papers*') && b.shown === 2, JSON.stringify(b));
  check('a filter with no label still gets a chip', /Press kit/.test(b.chips), b.chips);
  check('a document without a file is a plain card saying Coming soon', b.soon.join(' / ') === 'ThermStar System brochure|Coming soon|false / ThermStar System for pet food|Coming soon|false', b.soon.join(' / '));
  check('a same-tab download link', b.link === '|true|Waste heat recovery for commercial and industrial facilities, PDF, 12 pages · May 2026', b.link);

  if (SHOTS) await (await page.$('#a .edoc')).screenshot({ path: SHOTS + '/doc-desktop.png' });

  /* ----------------------------------------------------------- sizes --- */

  for (const [w, name, cols] of [[1100, 'tablet-1100', 2], [760, 'phone-760', 1], [360, 'phone-360', 1]]) {
    await page.setViewport({ width: w, height: 900 });
    await sleep(500);
    const lay = await page.evaluate(() => {
      const r = document.querySelector('#a .edoc');
      const cards = [...r.querySelectorAll('.edoc__card')];
      const chips = r.querySelector('.edoc__chips');
      return {
        scroll: document.documentElement.scrollWidth,
        perRow: cards.filter(c => Math.round(c.getBoundingClientRect().top) === Math.round(cards[0].getBoundingClientRect().top)).length,
        chipsOneRow: new Set([...chips.children].map(c => Math.round(c.getBoundingClientRect().top))).size === 1,
        chipsScroll: chips.scrollWidth > chips.clientWidth,
        spill: cards.some(c => c.scrollWidth > c.clientWidth + 1),
      };
    });
    check(`${name}: no sideways page scroll`, lay.scroll <= w, lay.scroll + ' > ' + w);
    check(`${name}: ${cols} per row`, lay.perRow === cols, JSON.stringify(lay));
    check(`${name}: nothing spills out of a card`, !lay.spill);
    if (w <= 760) check(`${name}: chips are one row`, lay.chipsOneRow);
    if (w === 360) check('phone-360: the chip row scrolls sideways on its own', lay.chipsScroll);
    if (SHOTS) await (await page.$('#a .edoc')).screenshot({ path: `${SHOTS}/doc-${name}.png` });
  }

  /* -------------------------------------------------- reduced motion --- */

  const calm = await browser.newPage();
  await calm.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await calm.setViewport({ width: 1440, height: 900 });
  await calm.goto(URL, { waitUntil: 'networkidle0' });
  await calm.click('#a .edoc__chip[data-seg="white"]');
  await sleep(20);
  await calm.hover('#a a.edoc__card');
  await sleep(250);
  const rm = await calm.evaluate(() => {
    const c = document.querySelector('#a .edoc__card:not([hidden])');
    return getComputedStyle(c).opacity + '/' + getComputedStyle(c).animationName + '/' + getComputedStyle(c).transform;
  });
  check('reduced motion: no fade and no lift', rm === '1/none/none', rm);

  check('no script errors', errs.length === 0, errs.join(' | '));
  await browser.close();

  console.log(PASS.map(p => '  ok   ' + p).join('\n'));
  if (FAIL.length) console.log(FAIL.map(f => '  FAIL ' + f).join('\n'));
  console.log(`\n${PASS.length} passed, ${FAIL.length} failed`);
  process.exit(FAIL.length ? 1 : 0);
})();
