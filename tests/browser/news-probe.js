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

// Where each card sits.
const layout = sel => {
  const list = document.querySelector(sel + ' .enws-cards');
  const b = list.getBoundingClientRect();
  const cards = [...list.children].filter(c => !c.hidden).map(c => {
    const r = c.getBoundingClientRect();
    const t = (c.querySelector('strong, .enws-card__title') || {}).textContent || '';
    return { v: c.className.match(/enws-card--(\w+)/)[1], t: t.trim().slice(0, 12), x: Math.round(r.left - b.left), y: Math.round(r.top - b.top), w: Math.round(r.width), h: Math.round(r.height) };
  });
  return { cards, cols: getComputedStyle(list).gridTemplateColumns.split(' ').length, scroll: document.documentElement.scrollWidth };
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

  /* ------------------------------------------------------ the cards --- */

  let L = await page.evaluate(layout, '#a');
  const order = L.cards.map(c => c.v + ':' + c.t).join(' / ');
  check('featured first, then newest first, podcast as its own card', order === 'image:Why there is / image:The ROI of W / image:Welcome to N / audio:Recycling he', order);
  check('three equal columns', L.cols === 3 && new Set(L.cards.slice(0, 3).map(c => c.w)).size === 1, JSON.stringify(L.cards.slice(0, 3)));
  check('cards in a row are one height', new Set(L.cards.slice(0, 3).map(c => c.h)).size === 1, L.cards.slice(0, 3).map(c => c.h).join(','));

  const a = await page.evaluate(() => {
    const r = document.querySelector('#a .enws-grid');
    const cs = (el, p) => getComputedStyle(el)[p];
    const au = r.querySelector('.enws-card--audio');
    const im = r.querySelectorAll('.enws-card--image')[1];
    const pl = au.querySelector('.enws-player');
    const btn = au.querySelector('.enws-listen');
    return {
      chips: [...r.querySelectorAll('.enws-chip')].map(c => c.textContent + (c.getAttribute('aria-pressed') === 'true' ? '*' : '')).join(','),
      audioTop: au.querySelector('.enws-pill').textContent + '|' + au.querySelector('.enws-card__top em').textContent,
      audioBg: cs(au, 'backgroundImage').includes('linear-gradient'),
      player: [pl.tagName, pl.getAttribute('href'), pl.target, pl.getAttribute('aria-label')].join('|'),
      bars: pl.querySelectorAll('.enws-player__bars:not(.enws-player__bars--on) i').length,
      lit: cs(pl.querySelector('.enws-player__bars--on'), 'clipPath'),
      playBtn: cs(pl.querySelector('.enws-player__btn'), 'backgroundColor') + '|' + Math.round(pl.querySelector('.enws-player__btn').getBoundingClientRect().width),
      listen: btn ? [btn.textContent.replace(/\s+/g, ' ').trim(), btn.getAttribute('href'), btn.target].join('|') : 'none',
      listenColour: btn ? cs(btn, 'color') + '|' + cs(btn, 'borderTopColor') : '',
      quote: [au.querySelector('blockquote').childNodes[0].textContent.slice(0, 20), au.querySelector('cite').textContent, cs(au.querySelector('blockquote'), 'fontStyle')].join('|'),
      date: au.querySelector('.enws-card__acts em').textContent.trim(),
      titleLink: au.querySelector('.enws-card__title a').getAttribute('href'),
      imgMeta: im.querySelector('em').textContent.replace(/\s+/g, ' ').trim(),
      excerptClamp: cs(im.querySelector('.enws-card__ex'), 'webkitLineClamp'),
      imgRatio: cs(im.querySelector('img'), 'aspectRatio'),
      chipBtn: cs(r.querySelector('.enws-chip[aria-pressed="true"]'), 'backgroundColor') + '|' + cs(r.querySelector('.enws-chip:not([aria-pressed="true"])'), 'borderTopColor'),
      noMore: !r.querySelector('.enws-more'),
    };
  });
  check('chips: All, then Insights, Updates, Media', a.chips === 'All*,Insights,Updates,Media', a.chips);
  check('podcast card: "Media · Podcast" and its source, dark gradient', a.audioTop === 'Media · Podcast|BBC Business Daily' && a.audioBg, a.audioTop);
  check('without a file, the player itself opens the BBC episode in a new tab', a.player === 'A|https://www.bbc.com/audio/play/w3ct4n3g|_blank|Listen on BBC Business Daily: Recycling heat from kitchens to keep restaurants warm (opens in a new tab)', a.player);
  check('a waveform of 36 bars, the first part lit', a.bars === 36 && /^inset\(0px 72% 0px 0px\)$/.test(a.lit), a.bars + ' ' + a.lit);
  check('a round green play button', a.playBtn === 'rgb(77, 207, 141)|52', a.playBtn);
  check('a "Listen on BBC Business Daily ↗" button to the episode page', a.listen === 'Listen on BBC Business Daily ↗|https://www.bbc.com/audio/play/w3ct4n3g|_blank', a.listen);
  check('the Listen button is green on the dark card, not the theme\'s pink', a.listenColour === 'rgb(77, 207, 141)|rgb(77, 207, 141)', a.listenColour);
  check('the quote and its credit, not italicised by the theme', a.quote === '“We were looking at |Matt Manfield, facilities manager, Turtle Bay (UK)|normal', a.quote);
  check('the podcast card shows its date and still links to its post', a.date === '2 May 2023' && a.titleLink === '#post-4', a.date + ' ' + a.titleLink);
  check('photo card: 16:9, two-line excerpt, date · type', a.imgRatio === '16 / 9' && a.excerptClamp === '2' && a.imgMeta === '20 Nov 2025 · Article ↗', [a.imgRatio, a.excerptClamp, a.imgMeta].join(' | '));
  check('chips: green when on, theme reset does not leak', a.chipBtn === 'rgb(7, 134, 79)|rgb(221, 226, 230)', a.chipBtn);
  check('no Load more with four posts', a.noMore);

  // The player's link and the Listen button sit above the card's own link.
  const onTop = await page.evaluate(() => {
    const au = document.querySelector('#a .enws-card--audio');
    au.scrollIntoView({ block: 'center' });
    const hit = el => { const r = el.getBoundingClientRect(); const e = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2); return el.contains(e); };
    return hit(au.querySelector('.enws-player')) && hit(au.querySelector('.enws-listen'));
  });
  check('clicking the player or the Listen button hits them, not the card', onTop);

  await page.hover('#a .enws-card--image');
  await sleep(350);
  const hov = await page.evaluate(() => getComputedStyle(document.querySelector('#a .enws-card--image')).transform);
  check('hover lifts a card 4px', hov === 'matrix(1, 0, 0, 1, 0, -4)', hov);
  await page.mouse.move(2, 2);

  /* ----------------------------------------------------- the player --- */

  const P = '#b .enws-player[data-audio-src]';
  const ps = () => page.evaluate(sel => {
    const p = document.querySelector(sel);
    const au = p.querySelector('audio');
    return { playing: p.classList.contains('is-playing'), paused: au.paused, t: +au.currentTime.toFixed(2), d: +(au.duration || 0).toFixed(1), p: p.style.getPropertyValue('--p'), label: p.querySelector('.enws-player__btn').getAttribute('aria-label'), pressed: p.querySelector('.enws-player__btn').getAttribute('aria-pressed'), time: p.querySelector('.enws-player__time').textContent, seek: p.querySelector('.enws-player__seek').value };
  }, P);
  // Python's test server cannot serve byte ranges, and without them Chrome
  // cannot seek in audio; a real web server can. Hand the player the same
  // file as a blob, which can.
  await page.evaluate(async sel => {
    const au = document.querySelector(sel + ' audio');
    const blob = await (await fetch(au.getAttribute('src'))).blob();
    au.src = URL.createObjectURL(blob);
  }, P);
  let st = await ps();
  check('with a file: a play button, not playing yet', !st.playing && st.paused && st.label === 'Play: Recycling heat from kitchens to keep restaurants warm' && st.pressed === 'false', JSON.stringify(st));
  await page.click(P + ' .enws-player__btn');
  await sleep(2300);
  st = await ps();
  check('play plays: the button turns to pause', st.playing && !st.paused && st.label.startsWith('Pause') && st.pressed === 'true', JSON.stringify(st));
  check('the waveform fills and the time runs', st.t > 1.5 && parseFloat(st.p) > 20 && /^0:0[1-9]$/.test(st.time), JSON.stringify(st));
  const bounce = await page.evaluate(sel => getComputedStyle(document.querySelector(sel + ' .enws-player__bars i')).animationName, P);
  check('the bars move while it plays', bounce === 'enws-wave', bounce);
  await page.click(P + ' .enws-player__btn');
  await sleep(200);
  st = await ps();
  check('pause pauses', !st.playing && st.paused, JSON.stringify(st));
  await page.evaluate(sel => { const s = document.querySelector(sel + ' .enws-player__seek'); s.value = '50'; s.dispatchEvent(new Event('input', { bubbles: true })); }, P);
  await sleep(300);
  st = await ps();
  check('the waveform seeks: half way is half the length', Math.abs(st.t - st.d / 2) < 0.4, JSON.stringify(st));
  await page.focus(P + ' .enws-player__seek');
  await page.keyboard.press('ArrowRight');
  await sleep(200);
  const moved = await ps();
  check('and arrow keys seek too', moved.t > st.t, st.t + ' -> ' + moved.t);

  /* ---------------------------------------------------------- filter --- */

  await page.click('#a .enws-chip[data-seg="media"]');
  await sleep(40);
  let vis = await page.evaluate(() => [...document.querySelectorAll('#a .enws-card')].filter(c => !c.hidden).map(c => c.className.match(/--(\w+)/)[1]).join(','));
  check('Media shows the article and the podcast', vis === 'image,audio', vis);
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

  let b = await page.evaluate(() => ({ n: document.querySelectorAll('#b .enws-card:not(.enws-card--next)').length, more: !!document.querySelector('#b .enws-more__btn'), last: [...document.querySelectorAll('#b .enws-cards > *')].pop().className }));
  check('three per load, Load more showing, Go deeper last', b.n === 3 && b.more && /--next/.test(b.last), JSON.stringify(b));
  await page.evaluate(() => { window.__newsEvent = 0; document.addEventListener('eruda:news-cards', e => { window.__newsEvent = e.detail.cards.length; }); });
  await page.click('#b .enws-more__btn');
  await sleep(500);
  b = await page.evaluate(() => ({ n: document.querySelectorAll('#b .enws-card:not(.enws-card--next)').length, more: !!document.querySelector('#b .enws-more__btn'), last: [...document.querySelectorAll('#b .enws-cards > *')].pop().className, next: document.querySelector('#b .enws-grid').dataset.next, ev: window.__newsEvent }));
  check('Load more adds the next three and then goes away', b.n === 6 && !b.more && b.next === '5', JSON.stringify(b));
  check('the new cards go before the Go deeper card', /--next/.test(b.last), b.last);
  check('anything else can hear about the new cards', b.ev === 3, String(b.ev));

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
      slot: !!r.querySelector('.enws-player[data-context="source"]'),
      rel: r.querySelector('.enws-src__rel strong').textContent + '|' + r.querySelector('.enws-src__rel').getAttribute('href'),
    };
  });
  check('source box: originally published by, and a Listen button out', s.by === 'Originally published by BBC Business Daily' && s.btn === 'Listen on BBC Business Daily ↗|_blank', s.by + ' / ' + s.btn);
  check('source box: the player', s.slot);
  check('source box: the related page', s.rel === 'Restaurants & Commercial Kitchens|/restaurants-commercial-kitchens/', s.rel);

  if (SHOTS) {
    await page.goto('about:blank');
    await page.goto(URL, { waitUntil: 'networkidle0' });
    await (await page.$('#a .enws-grid')).screenshot({ path: SHOTS + '/news-desktop.png' });
    await (await page.$('#b .enws-grid')).screenshot({ path: SHOTS + '/news-playable.png' });
    await (await page.$('#e')).screenshot({ path: SHOTS + '/news-press.png' });
  }

  /* ---------------------------------------------------------- sizes --- */

  for (const [w, name, cols] of [[1024, 'tablet', 2], [767, 'phone-767', 1], [360, 'phone-360', 1]]) {
    await page.setViewport({ width: w, height: 900 });
    await sleep(500);
    const lay = await page.evaluate(layout, '#a');
    check(`${name}: ${cols} column(s)`, lay.cols === cols, 'cols ' + lay.cols);
    check(`${name}: no sideways page scroll`, lay.scroll <= w, lay.scroll + ' > ' + w);
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
  const ping = await calm.evaluate(() => getComputedStyle(document.querySelector('#a .enws-player__btn')).animationName);
  check('reduced motion: the play button does not pulse', ping === 'none', ping);

  check('no script errors', errs.length === 0, errs.join(' | '));
  await browser.close();

  console.log(PASS.map(p => '  ok   ' + p).join('\n'));
  if (FAIL.length) console.log(FAIL.map(f => '  FAIL ' + f).join('\n'));
  console.log(`\n${PASS.length} passed, ${FAIL.length} failed`);
  process.exit(FAIL.length ? 1 : 0);
})();
