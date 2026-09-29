/**
 * Measures the Case Studies widget in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 *   php tests/browser/casestudies-fixture.php > tests/browser/casestudies.html
 *   node tests/browser/casestudies-probe.js [screenshot-dir]
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];
const URL = 'http://127.0.0.1:8732/tests/browser/casestudies.html';
const SHOTS = process.argv[2] || '';
const sleep = ms => new Promise(r => setTimeout(r, ms));

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

const visible = sel => [...document.querySelectorAll(sel + ' .ecs__card')].filter(c => !c.hidden).map(c => c.querySelector('.ecs__name').textContent);

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

  /* -------------------------------------------------------- page 25 --- */

  const a = await page.evaluate(() => {
    const r = document.querySelector('#a .ecs');
    const cards = [...r.querySelectorAll('.ecs__card')];
    const cs = (el, p) => getComputedStyle(el)[p];
    const cws = cards[0];
    return {
      names: cards.map(c => c.querySelector('.ecs__name').textContent),
      chips: [...r.querySelectorAll('.ecs__chip')].map(c => c.textContent + (c.getAttribute('aria-pressed') === 'true' ? '*' : '')),
      perRow: cards.filter(c => Math.round(c.getBoundingClientRect().top) === Math.round(cards[0].getBoundingClientRect().top)).length,
      tag: cws.querySelector('.ecs__tag').textContent,
      big: [cws.querySelector('.ecs__big b').textContent, cs(cws.querySelector('.ecs__big b'), 'fontFamily').split(',')[0], cs(cws.querySelector('.ecs__big b'), 'fontSize'), cs(cws.querySelector('.ecs__big b'), 'color')].join('|'),
      basis: cws.querySelector('.ecs__big small').textContent,
      flow: cws.querySelector('.ecs__flow').textContent.replace(/\s+/g, ' ').trim(),
      pdf: [cws.querySelector('.ecs__go').getAttribute('href'), cws.querySelector('.ecs__go').target],
      est: cws.querySelector('.ecs__est').getAttribute('href'),
      estStyle: cs(cws.querySelector('.ecs__est'), 'backgroundColor') + '|' + cs(cws.querySelector('.ecs__est'), 'color'),
      darkLogo: cs(cards[2].querySelector('.ecs__logo'), 'backgroundColor'),
      logoH: Math.max(...cards.map(c => { const l = c.querySelector('.ecs__logo'); return l.getBoundingClientRect().height - parseFloat(getComputedStyle(l).paddingTop) - parseFloat(getComputedStyle(l).paddingBottom); })),
      ongoing: {
        big: !!cards[3].querySelector('.ecs__big'),
        now: cards[3].querySelector('.ecs__now') && cards[3].querySelector('.ecs__now').textContent,
        track: [...cards[3].querySelectorAll('.ecs__track li')].map(l => l.textContent + (l.className ? ':' + l.className : '')).join(','),
        link: cards[3].querySelector('.ecs__go').getAttribute('href') + '|' + cards[3].querySelector('.ecs__go').target,
        est: cards[3].querySelector('.ecs__est').getAttribute('href'),
      },
      photo: cs(cws.querySelector('.ecs__photo'), 'aspectRatio') + '|' + Math.round(cws.querySelector('.ecs__photo').getBoundingClientRect().width),
      nameHidden: cws.querySelector('.ecs__name').getBoundingClientRect().width <= 1,
      heights: new Set(cards.slice(0, 3).map(c => Math.round(c.getBoundingClientRect().height))).size,
      chipStyle: cs(r.querySelector('.ecs__chip[aria-pressed="true"]'), 'backgroundColor') + '|' + cs(r.querySelector('.ecs__chip:not([aria-pressed="true"])'), 'borderTopColor'),
    };
  });
  check('five cards in Order: industrial first, restaurants last', a.names.join(',') === 'CWS Workwear,Lantmännen,Bruzaholms,Sports & Leisure Group,Burger King UK', a.names.join(','));
  check('chips: All, then the sectors in card order', a.chips.join(',') === 'All*,Industrial laundry,Food production,Foundries,Manufacturing,Restaurants', a.chips.join(','));
  check('no chip for a sector without a case (pet food)', !a.chips.some(c => /Pet/.test(c)));
  check('three across on desktop', a.perRow === 3, String(a.perRow));
  check('the row of three is one height', a.heights === 1, String(a.heights));
  check('the tag reads sector · location', a.tag === 'Industrial laundry · Den Bosch, Netherlands', a.tag);
  check('the figure is Bebas 60px green', a.big === '63%|"Bebas Neue"|60px|rgb(7, 134, 79)', a.big);
  check('the basis line is there, prefixed', a.basis === 'Basis: gas per kg of laundry, before vs after · 6-month trial, 2023', a.basis);
  check('the flow line joins source and use', a.flow === 'Finisher exhaust → to washer process water', a.flow);
  check('the PDF opens in a new tab', /Case-Study-CWS-laundry\.pdf$/.test(a.pdf[0]) && a.pdf[1] === '_blank', a.pdf.join(' '));
  check('Estimate carries the sector to the form', a.est === '/request-assessment/?sector=industrial-laundry', a.est);
  check('the theme cannot recolour the button', a.estStyle === 'rgb(7, 134, 79)|rgb(255, 255, 255)', a.estStyle);
  check('Bruzaholms has the dark badge', a.darkLogo === 'rgb(30, 42, 51)', a.darkLogo);
  check('no logo taller than 34px inside its badge', a.logoH <= 34.5, String(a.logoH));
  check('an ongoing case shows the track, not a number', !a.ongoing.big && a.ongoing.now === 'Now measuring' && a.ongoing.track === 'Installed:is-done,Measuring:is-now,Results', JSON.stringify(a.ongoing));
  check('and links to its industry page in the same tab', a.ongoing.link === '/manufacturing/|', a.ongoing.link);
  check('and still offers the estimate for its sector', a.ongoing.est === '/request-assessment/?sector=manufacturing', a.ongoing.est);
  check('photos are 16:9 and fill the card', /^16 \/ 9\|/.test(a.photo), a.photo);
  check('the customer name is there for screen readers but not seen', a.nameHidden);
  check('chips are green when on and the theme button reset does not leak', a.chipStyle === 'rgb(7, 134, 79)|rgb(221, 226, 230)', a.chipStyle);

  /* ---------------------------------------------------------- filter --- */

  await page.click('#a .ecs__chip[data-seg="foundries"]');
  await sleep(30);
  let v = await page.evaluate(visible, '#a');
  check('a chip filters to its sector', v.join(',') === 'Bruzaholms', v.join(','));
  const fading = await page.evaluate(() => +getComputedStyle(document.querySelector('#a .ecs__card:not([hidden])')).opacity);
  check('the card fades in', fading < 1, String(fading));
  await sleep(300);
  const opaque = await page.evaluate(() => getComputedStyle(document.querySelector('#a .ecs__card:not([hidden])')).opacity);
  check('and is fully in soon after', opaque === '1', opaque);
  await page.click('#a .ecs__chip[data-seg="all"]');
  await sleep(30);
  v = await page.evaluate(visible, '#a');
  check('All brings every card back', v.length === 5);
  const pressed = await page.evaluate(() => [...document.querySelectorAll('#a .ecs__chip')].filter(c => c.getAttribute('aria-pressed') === 'true').length);
  check('exactly one chip is pressed', pressed === 1);

  await page.hover('#a .ecs__card');
  await sleep(300);
  const lift = await page.evaluate(() => getComputedStyle(document.querySelector('#a .ecs__card')).transform);
  check('hovering a card lifts it', /-3\)$/.test(lift), lift);

  /* ------------------------------------------------- the other pages --- */

  const others = await page.evaluate(() => ({
    b: [...document.querySelectorAll('#b .ecs__card')].map(c => c.querySelector('.ecs__name').textContent).join(','),
    bChips: document.querySelectorAll('#b .ecs__chip').length,
    c: [...document.querySelectorAll('#c .ecs__card')].map(c => c.querySelector('.ecs__name').textContent).join(','),
    d: (document.querySelector('#d') || {}).textContent.trim(),
  }));
  check('an industry page shows only its sector, no chips', others.b === 'Bruzaholms' && others.bChips === 0, JSON.stringify(others));
  check('the home page shows the first three', others.c === 'CWS Workwear,Lantmännen,Bruzaholms', others.c);
  check('an empty sector shows the empty line', others.d === 'Pet food case studies are coming soon.', others.d);

  // Off the card, or its 3px hover lift reads as a new row.
  await page.mouse.move(2, 2);
  await sleep(300);
  if (SHOTS) await (await page.$('#a .ecs')).screenshot({ path: SHOTS + '/cs-desktop.png' });

  /* ----------------------------------------------------------- sizes --- */

  for (const [w, name, cols] of [[1024, 'tablet', 2], [767, 'phone-767', 1], [360, 'phone-360', 1]]) {
    await page.setViewport({ width: w, height: 900 });
    await sleep(600);
    const lay = await page.evaluate(() => {
      const r = document.querySelector('#a .ecs');
      const cards = [...r.querySelectorAll('.ecs__card')];
      const chips = r.querySelector('.ecs__chips');
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
    if (w <= 767) check(`${name}: chips are one row`, lay.chipsOneRow, JSON.stringify(lay));
    if (w === 360) check('phone-360: the chip row scrolls sideways on its own', lay.chipsScroll);
    if (SHOTS) await (await page.$('#a .ecs')).screenshot({ path: `${SHOTS}/cs-${name}.png` });
  }

  /* -------------------------------------------------- reduced motion --- */

  const calm = await browser.newPage();
  await calm.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await calm.setViewport({ width: 1440, height: 900 });
  await calm.goto(URL, { waitUntil: 'networkidle0' });
  await calm.click('#a .ecs__chip[data-seg="industrial-laundry"]');
  await sleep(20);
  const rm = await calm.evaluate(() => {
    const c = document.querySelector('#a .ecs__card:not([hidden])');
    const dot = document.querySelector('#a .ecs__now i');
    return getComputedStyle(c).opacity + '/' + getComputedStyle(dot).animationName;
  });
  check('reduced motion: no fade and no pulse', rm === '1/none', rm);

  check('no script errors', errs.length === 0, errs.join(' | '));
  await browser.close();

  console.log(PASS.map(p => '  ok   ' + p).join('\n'));
  if (FAIL.length) console.log(FAIL.map(f => '  FAIL ' + f).join('\n'));
  console.log(`\n${PASS.length} passed, ${FAIL.length} failed`);
  process.exit(FAIL.length ? 1 : 0);
})();
