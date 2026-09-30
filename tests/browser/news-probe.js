/**
 * Measures the news widgets in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 *   php tests/browser/news-fixture.php > tests/browser/news.html
 *   node tests/browser/news-probe.js [screenshot-dir]
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];
const URL = 'http://127.0.0.1:8732/tests/browser/news.html';
const SHOTS = process.argv[2] || '';
const sleep = ms => new Promise(r => setTimeout(r, ms));

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

// Where each card sits, and whether the grid has a hole.
const layout = sel => {
  const bento = document.querySelector(sel + ' .enws-bento');
  const b = bento.getBoundingClientRect();
  const cards = [...bento.children].filter(c => !c.hidden).map(c => {
    const r = c.getBoundingClientRect();
    const t = (c.querySelector('strong, .enws-card__title') || {}).textContent || '';
    return { v: c.className.match(/enws-card--(\w+)/)[1], t: t.trim().slice(0, 12), x: Math.round(r.left - b.left), y: Math.round(r.top - b.top), w: Math.round(r.width), h: Math.round(r.height) };
  });
  // Sample the grid area on a lattice; any point inside the bento's height
  // not covered by a card (allowing for gaps) is a hole.
  const gap = parseFloat(getComputedStyle(bento).rowGap) || 0;
  let holes = 0;
  for (let y = 10; y < b.height - 10; y += 20) {
    for (let x = 10; x < b.width - 10; x += 20) {
      const covered = cards.some(c => x >= c.x - gap && x <= c.x + c.w + gap && y >= c.y - gap && y <= c.y + c.h + gap);
      if (!covered) holes++;
    }
  }
  return { cards, holes, cols: getComputedStyle(bento).gridTemplateColumns.split(' ').length, scroll: document.documentElement.scrollWidth };
};

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: 'new', args: ['--no-sandbox', '--autoplay-policy=no-user-gesture-required'],
  });
  const errs = [];
  const page = await browser.newPage();
  page.on('pageerror', e => errs.push(String(e)));
  await page.setViewport({ width: 1440, height: 900 });
  await page.goto(URL, { waitUntil: 'networkidle0' });

  /* ------------------------------------------------------ the bento --- */

  let L = await page.evaluate(layout, '#a');
  const order = L.cards.map(c => c.v + ':' + c.t).join(' / ');
  check('featured first and big, podcast next to it, photo cards newest first', order === 'wide:Why there is / audio:Recycling he / image:The ROI of W / image:Welcome to N', order);
  const [wide, audio, roi, welcome] = L.cards;
  check('big card in column 1 over two rows, podcast in column 2 over two rows', wide.x === 0 && audio.x > wide.x && Math.abs(audio.h - wide.h) < 2, JSON.stringify([wide, audio]));
  check('ROI above Welcome in column 3', roi.x === welcome.x && roi.x > audio.x && welcome.y > roi.y, JSON.stringify([roi, welcome]));
  check('the four posts fill the 3x2 bento with no hole', L.holes === 0 && L.cols === 3, 'holes ' + L.holes + ', cols ' + L.cols);

  const a = await page.evaluate(() => {
    const r = document.querySelector('#a .enws-grid');
    const cs = (el, p) => getComputedStyle(el)[p];
    const w = r.querySelector('.enws-card--wide');
    const au = r.querySelector('.enws-card--audio');
    const im = r.querySelector('.enws-card--image');
    const slot = au.querySelector('.enws-audio');
    return {
      chips: [...r.querySelectorAll('.enws-chip')].map(c => c.textContent + (c.getAttribute('aria-pressed') === 'true' ? '*' : '')).join(','),
      wideTitle: [cs(w.querySelector('strong'), 'fontFamily').split(',')[0], cs(w.querySelector('strong'), 'textTransform'), cs(w.querySelector('strong'), 'color')].join('|'),
      wideMeta: w.querySelector('em').textContent.trim(),
      widePill: w.querySelector('.enws-pill').textContent,
      go: cs(w.querySelector('.enws-card__go'), 'backgroundColor'),
      audioPill: au.querySelector('.enws-pill').textContent + '|' + au.querySelector('.enws-card__top em').textContent,
      audioBg: cs(au, 'backgroundImage').includes('linear-gradient'),
      slot: [slot.dataset.audioSrc, slot.dataset.audioTitle, !!slot.querySelector('audio[controls][preload="none"]')].join('|'),
      quote: [au.querySelector('blockquote').childNodes[0].textContent.slice(0, 20), au.querySelector('cite').textContent, cs(au.querySelector('blockquote'), 'fontStyle')].join('|'),
      audioLink: au.querySelector('.enws-card__title a').getAttribute('href'),
      imgMeta: im.querySelector('em').textContent.replace(/\s+/g, ' ').trim(),
      excerptClamp: cs(im.querySelector('.enws-card__ex'), 'webkitLineClamp'),
      imgRatio: cs(im.querySelector('img'), 'aspectRatio'),
      chipBtn: cs(r.querySelector('.enws-chip[aria-pressed="true"]'), 'backgroundColor') + '|' + cs(r.querySelector('.enws-chip:not([aria-pressed="true"])'), 'borderTopColor'),
      noMore: !r.querySelector('.enws-more'),
    };
  });
  check('chips: All, then Insights, Updates, Media', a.chips === 'All*,Insights,Updates,Media', a.chips);
  check('big card title is Bebas, uppercase, white', a.wideTitle === '"Bebas Neue"|uppercase|rgb(255, 255, 255)', a.wideTitle);
  check('big card: pill, date · source, green arrow', a.widePill === 'Media' && a.wideMeta === '2 May 2023 · BBC News' && a.go === 'rgb(77, 207, 141)', [a.widePill, a.wideMeta, a.go].join(' | '));
  check('podcast card: "Media · Podcast" pill and source, dark gradient', a.audioPill === 'Media · Podcast|BBC Business Daily' && a.audioBg, a.audioPill);
  check('the audio slot carries the file and title, with a plain player inside', a.slot === 'news-img/silence.mp3|Recycling heat from kitchens to keep restaurants warm|true', a.slot);
  check('the quote and its credit, not italicised by the theme', a.quote === '“We were looking at |Matt Manfield, facilities manager, Turtle Bay (UK)|normal', a.quote);
  check('the podcast card still links to its post', a.audioLink === '#post-4', a.audioLink);
  check('photo card: 16:9, two-line excerpt, date · Article', a.imgRatio === '16 / 9' && a.excerptClamp === '2' && a.imgMeta === '20 Nov 2025 · Article ↗', [a.imgRatio, a.excerptClamp, a.imgMeta].join(' | '));
  check('chips: green when on, theme reset does not leak', a.chipBtn === 'rgb(7, 134, 79)|rgb(221, 226, 230)', a.chipBtn);
  check('no Load more with four posts', a.noMore);

  // A player taking the slot over hides the plain one.
  const handover = await page.evaluate(() => {
    const slot = document.querySelector('#a .enws-audio');
    slot.setAttribute('data-player', 'ready');
    const hidden = getComputedStyle(slot.querySelector('audio')).display;
    slot.removeAttribute('data-player');
    return hidden;
  });
  check('data-player="ready" hides the plain player', handover === 'none', handover);

  // Hover.
  await page.hover('#a .enws-card--wide');
  await sleep(350);
  const hov = await page.evaluate(() => {
    const w = document.querySelector('#a .enws-card--wide');
    const m = new DOMMatrix(getComputedStyle(w.querySelector('.enws-card__go')).transform);
    return getComputedStyle(w).transform + '|' + Math.round(Math.atan2(m.b, m.a) * 180 / Math.PI);
  });
  check('hover lifts the card 4px and turns the arrow 45°', hov === 'matrix(1, 0, 0, 1, 0, -4)|45', hov);
  await page.mouse.move(2, 2);

  /* ---------------------------------------------------------- filter --- */

  await page.click('#a .enws-chip[data-seg="media"]');
  await sleep(40);
  let vis = await page.evaluate(() => [...document.querySelectorAll('#a .enws-card')].filter(c => !c.hidden).map(c => c.className.match(/--(\w+)/)[1]).join(','));
  check('Media shows the article and the podcast', vis === 'wide,audio', vis);
  const fading = await page.evaluate(() => [...document.querySelectorAll('#a .enws-card:not([hidden])')].map(c => +(+getComputedStyle(c).opacity).toFixed(2)));
  check('they fade in', fading.some(o => o < 1), fading.join(','));
  await sleep(300);
  const settled = await page.evaluate(() => [...document.querySelectorAll('#a .enws-card:not([hidden])')].every(c => getComputedStyle(c).opacity === '1'));
  check('and are in within 300ms', settled);

  await page.goto('about:blank');
  await page.goto(URL + '#news-updates', { waitUntil: 'networkidle0' });
  vis = await page.evaluate(() => [...document.querySelectorAll('#a .enws-card')].filter(c => !c.hidden).map(c => (c.querySelector('strong') || {}).textContent.slice(0, 10)).join(','));
  check('#news-updates opens with Updates on', vis === 'Welcome to', vis);
  const pressed = await page.evaluate(() => [...document.querySelectorAll('#a .enws-chip')].filter(c => c.getAttribute('aria-pressed') === 'true').map(c => c.dataset.seg).join());
  check('and its chip pressed', pressed === 'updates', pressed);

  /* ------------------------------------------------------ Load more --- */

  let b = await page.evaluate(() => ({ n: document.querySelectorAll('#b .enws-card:not(.enws-card--next)').length, more: !!document.querySelector('#b .enws-more__btn'), last: [...document.querySelectorAll('#b .enws-bento > *')].pop().className }));
  check('three per load, Load more showing, Go deeper last', b.n === 3 && b.more && /--next/.test(b.last), JSON.stringify(b));
  // A short last row whose last tile sits in column 2: it stretches from
  // column 2 to the edge, not from column 1.
  const stretch = await page.evaluate(() => { const t = document.querySelector('#b .enws-card--next'); return t.style.gridColumn; });
  L = await page.evaluate(layout, '#b');
  check('a short last row: the last tile stretches from where it sits', stretch === '2 / -1' && L.holes === 0, stretch + ', holes ' + L.holes);
  await page.evaluate(() => { window.__newsEvent = 0; document.addEventListener('eruda:news-cards', e => { window.__newsEvent = e.detail.cards.length; }); });
  await page.click('#b .enws-more__btn');
  await sleep(500);
  b = await page.evaluate(() => ({ n: document.querySelectorAll('#b .enws-card:not(.enws-card--next)').length, more: !!document.querySelector('#b .enws-more__btn'), last: [...document.querySelectorAll('#b .enws-bento > *')].pop().className, next: document.querySelector('#b .enws-grid').dataset.next, ev: window.__newsEvent }));
  check('Load more adds the next three and then goes away', b.n === 6 && !b.more && b.next === '5', JSON.stringify(b));
  check('the new cards go before the Go deeper tile', /--next/.test(b.last), b.last);
  check('a player hears about the new cards', b.ev === 3, String(b.ev));
  L = await page.evaluate(layout, '#b');
  check('no hole after loading more', L.holes === 0, 'holes ' + L.holes);

  /* ------------------------------------------------------- carousel --- */

  const c = await page.evaluate(() => {
    const w = document.querySelector('#c .enws-car-wrap');
    const t = w.querySelector('.enws-car');
    const card = t.querySelector('.enws-car__card').getBoundingClientRect().width;
    const btns = [...w.querySelectorAll('.enws-car__btn')];
    return { visible: Math.round(t.clientWidth / (card + 16) * 10) / 10, prev: btns[0].disabled, next: btns[1].disabled, all: (w.querySelector('.enws-car__all') || {}).getAttribute('href'), podcast: [...t.querySelectorAll('.enws-car__meta i')].map(i => i.textContent).join(), snap: getComputedStyle(t).scrollSnapType };
  });
  check('about 3.2 cards in view', c.visible >= 3.1 && c.visible <= 3.3, String(c.visible));
  check('prev off at the start, next on', c.prev && !c.next, JSON.stringify(c));
  check('an "All posts" link to /news/', c.all === '/news/', c.all);
  check('the podcast shows a Podcast pill, no player', c.podcast === 'Podcast' && !(await page.$('#c .enws-audio')), c.podcast);
  check('the row snaps', /x mandatory/.test(c.snap), c.snap);
  await page.click('#c .enws-car__btn[data-dir="1"]');
  await sleep(700);
  let sc = await page.evaluate(() => document.querySelector('#c .enws-car').scrollLeft);
  check('next scrolls by a card', sc > 200, String(sc));
  await page.focus('#c .enws-car');
  await page.keyboard.press('ArrowLeft');
  await sleep(700);
  sc = await page.evaluate(() => document.querySelector('#c .enws-car').scrollLeft);
  check('the left arrow key scrolls back', sc < 5, String(sc));

  await page.click('#ext .enws-car-next a');
  await sleep(700);
  sc = await page.evaluate(() => document.querySelector('#d .enws-car').scrollLeft);
  check('"My own buttons" in the section drive the row', sc > 200, String(sc));
  const noHash = await page.evaluate(() => location.hash);
  check('and do not follow their href', noHash === '#docs' || noHash === '' || noHash === '#news-updates', noHash);

  /* ---------------------------------------------------------- press --- */

  const p = await page.evaluate(() => {
    const r = document.querySelector('#e .enws-press');
    const qs = [...r.querySelectorAll('.enws-press__q')];
    const cs = (el, k) => getComputedStyle(el)[k];
    return {
      n: qs.length,
      bbc: [...qs[0].querySelectorAll('.enws-press__logo i')].map(i => i.textContent).join(''),
      label: qs[0].querySelector('.enws-press__logo').getAttribute('aria-label'),
      bbcLink: (qs[0].querySelector('figcaption a') || {}).href,
      others: qs.slice(1).map(q => !!q.querySelector('figcaption a')).join(),
      quote: qs[1].querySelector('blockquote').textContent,
      glass: [cs(qs[0], 'backdropFilter'), cs(qs[0], 'backgroundColor')].join('|'),
      premier: qs[2].querySelector('.enws-press__logo small').textContent,
      row: new Set(qs.map(q => Math.round(q.getBoundingClientRect().top))).size,
    };
  });
  check('three press cards in a row', p.n === 3 && p.row === 1, JSON.stringify(p));
  check('BBC as letter blocks, named for a screen reader', p.bbc === 'BBC' && p.label === 'BBC News', p.bbc + ' ' + p.label);
  check('BBC links to the article; Forbes and Premier have no link yet', p.bbcLink === 'https://www.bbc.com/news/business-65328579' && p.others === 'false,false', p.bbcLink + ' ' + p.others);
  check('the Forbes quote verbatim, in curly quotes', p.quote === '“By deploying the Enjay product, customers use less energy and emit less CO2. Enjay focuses on the ROI, couched in terms of the number of kilowatt hours that customers can save.”', p.quote);
  check('frosted cards', p.glass === 'blur(4px)|rgba(255, 255, 255, 0.04)', p.glass);
  check('Premier with its small line', p.premier === 'CONSTRUCTION');

  /* ----------------------------------------------------- source box --- */

  const s = await page.evaluate(() => {
    const r = document.querySelector('#f .enws-src');
    return {
      by: r.querySelector('.enws-src__from p').textContent.replace(/\s+/g, ' ').trim(),
      btn: r.querySelector('.enws-src__btn').textContent.replace(/\s+/g, ' ').trim() + '|' + r.querySelector('.enws-src__btn').target,
      slot: !!r.querySelector('.enws-audio[data-context="source"] audio'),
      rel: r.querySelector('.enws-src__rel strong').textContent + '|' + r.querySelector('.enws-src__rel').getAttribute('href'),
    };
  });
  check('source box: originally published by, and a button out', s.by === 'Originally published by BBC Business Daily' && s.btn === 'Read on BBC Business Daily ↗|_blank', s.by + ' / ' + s.btn);
  check('source box: the audio slot', s.slot);
  check('source box: the related page', s.rel === 'Restaurants & Commercial Kitchens|/restaurants-commercial-kitchens/', s.rel);

  if (SHOTS) {
    await page.goto('about:blank');
    await page.goto(URL, { waitUntil: 'networkidle0' });
    await (await page.$('#a .enws-grid')).screenshot({ path: SHOTS + '/news-desktop.png' });
    await (await page.$('#e')).screenshot({ path: SHOTS + '/news-press.png' });
  }

  /* ---------------------------------------------------------- sizes --- */

  for (const [w, name, cols] of [[1100, 'tablet-1100', 2], [760, 'phone-760', 1], [360, 'phone-360', 1]]) {
    await page.setViewport({ width: w, height: 900 });
    await sleep(500);
    const lay = await page.evaluate(layout, '#a');
    check(`${name}: ${cols} column(s), no hole`, lay.cols === cols && lay.holes === 0, 'cols ' + lay.cols + ', holes ' + lay.holes);
    check(`${name}: no sideways page scroll`, lay.scroll <= w, lay.scroll + ' > ' + w);
    if (w === 1100) check('tablet: the big card spans both columns', lay.cards[0].w > lay.cards[1].w * 1.8, JSON.stringify(lay.cards.slice(0, 2)));
    if (SHOTS) await (await page.$('#a .enws-grid')).screenshot({ path: `${SHOTS}/news-${name}.png` });
  }
  const phoneCar = await page.evaluate(() => { const t = document.querySelector('#c .enws-car'); return Math.round(t.clientWidth / t.querySelector('.enws-car__card').getBoundingClientRect().width * 10) / 10; });
  check('phone: about 1.1 carousel cards in view', phoneCar >= 1.05 && phoneCar <= 1.2, String(phoneCar));

  /* ------------------------------------------------- reduced motion --- */

  const calm = await browser.newPage();
  await calm.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await calm.setViewport({ width: 1440, height: 900 });
  await calm.goto(URL, { waitUntil: 'networkidle0' });
  await calm.click('#a .enws-chip[data-seg="insights"]');
  await sleep(20);
  await calm.hover('#a .enws-card:not([hidden])');
  await sleep(200);
  const rm = await calm.evaluate(() => { const c = document.querySelector('#a .enws-card:not([hidden])'); return getComputedStyle(c).opacity + '/' + getComputedStyle(c).animationName + '/' + getComputedStyle(c).transform + '/' + getComputedStyle(document.querySelector('#c .enws-car')).scrollBehavior; });
  check('reduced motion: no fade, no lift, no smooth scroll', rm === '1/none/none/auto', rm);

  check('no script errors', errs.length === 0, errs.join(' | '));
  await browser.close();

  console.log(PASS.map(p => '  ok   ' + p).join('\n'));
  if (FAIL.length) console.log(FAIL.map(f => '  FAIL ' + f).join('\n'));
  console.log(`\n${PASS.length} passed, ${FAIL.length} failed`);
  process.exit(FAIL.length ? 1 : 0);
})();
