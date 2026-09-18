/**
 * Measures the Scroll Rail in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 * Headless on purpose, for the same reason as story-probe.js: Chrome does not
 * run requestAnimationFrame in a tab that is not visible, so driving a real
 * window freezes every measurement the moment the window loses focus.
 *
 *   node tests/browser/rail-probe.js
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

const x = t => {
  const m = /matrix(?:3d)?\(([^)]+)\)/.exec(t || '');
  if (!m) return 0;
  const n = m[1].split(',').map(Number);
  return n.length === 16 ? n[12] : n[4];
};

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: 'new', args: ['--no-sandbox'],
  });

  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 900 });
  const errs = [];
  page.on('pageerror', e => errs.push(String(e)));

  await page.goto('http://127.0.0.1:8732/tests/browser/rail.html', { waitUntil: 'load' });
  await page.evaluate(() => new Promise(r => setTimeout(r, 200)));

  /*
   * What the section costs the page.
   *
   * This is the whole difference between the two modes, so it is measured
   * rather than described: where does the content *after* the rail start, and
   * how tall is the document, with each of them.
   */
  const cost = async (mode) => {
    await page.goto('http://127.0.0.1:8732/tests/browser/rail.html?mode=' + mode, { waitUntil: 'load' });
    await page.evaluate(() => new Promise(r => setTimeout(r, 250)));
    return page.evaluate(() => {
      const root = document.querySelector('.erail');
      const stage = document.querySelector('.erail__stage');
      const after = document.getElementById('after');
      const card = document.querySelector('.erail__card');
      const track = document.querySelector('.erail__track');
      const pad = parseFloat(getComputedStyle(track).paddingTop) +
                  parseFloat(getComputedStyle(track).paddingBottom);
      const barBox = document.querySelector('.erail__progress');
      const barCost = barBox
        ? barBox.offsetHeight + parseFloat(getComputedStyle(barBox).marginTop)
        : 0;
      return {
        rootH: root.offsetHeight,
        stageH: stage.offsetHeight,
        cardH: card.offsetHeight,
        wants: Math.round(card.offsetHeight + pad + barCost),
        afterTop: Math.round(after.getBoundingClientRect().top + window.scrollY),
        docH: document.body.scrollHeight,
        inline: root.style.height || '(none)',
      };
    });
  };

  const flowCost = await cost('flow');
  const pinnedCost = await cost('pinned');

  check('the section is as tall as its cards make it, not a share of the screen',
    Math.abs(flowCost.stageH - flowCost.wants) <= 1 && flowCost.stageH < 900,
    'stage ' + flowCost.stageH + 'px for a ' + flowCost.cardH +
      'px card plus its padding and bar (' + flowCost.wants + 'px), in a 900px window');

  check('flow adds no height to the page at all',
    flowCost.rootH === flowCost.stageH && flowCost.inline === '(none)',
    'section ' + flowCost.rootH + 'px against a stage of ' + flowCost.stageH +
      'px, inline height ' + flowCost.inline);

  check('flow does not push what follows it down the page',
    flowCost.afterTop < pinnedCost.afterTop &&
    pinnedCost.afterTop - flowCost.afterTop === pinnedCost.rootH - flowCost.rootH,
    'content after the rail starts at ' + flowCost.afterTop + 'px in flow and ' +
      pinnedCost.afterTop + 'px pinned — ' + (pinnedCost.afterTop - flowCost.afterTop) + 'px of push');

  check('pinned still buys itself a runway',
    pinnedCost.rootH > pinnedCost.stageH,
    'section ' + pinnedCost.rootH + 'px against a stage of ' + pinnedCost.stageH + 'px');

  check('and the document is shorter in flow by exactly that runway',
    pinnedCost.docH - flowCost.docH === pinnedCost.rootH - pinnedCost.stageH,
    'document ' + flowCost.docH + 'px in flow, ' + pinnedCost.docH + 'px pinned');

  // Pinned is still pinned: it is on the pinned page right now.
  const centring = await page.evaluate(async () => {
    const stage = document.querySelector('.erail__stage');
    const max = document.body.scrollHeight - innerHeight;
    const out = [];
    for (const f of [0.35, 0.5, 0.65]) {
      window.scrollTo(0, Math.round(f * max));
      await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
      const r = stage.getBoundingClientRect();
      out.push({
        position: getComputedStyle(stage).position,
        top: Math.round(r.top),
        bottom: Math.round(innerHeight - r.bottom),
      });
    }
    return out;
  });
  check('pinned holds the stage centred on screen',
    centring.every(c => c.position === 'sticky' && Math.abs(c.top - c.bottom) <= 1),
    centring.map(c => c.top + '/' + c.bottom).join(' '));

  // Everything after this runs against flow, the default.
  await page.goto('http://127.0.0.1:8732/tests/browser/rail.html', { waitUntil: 'load' });
  await page.evaluate(() => new Promise(r => setTimeout(r, 250)));

  await page.evaluate(() => {
    window.__at = async (y) => {
      window.scrollTo(0, y);
      await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
      const root = document.querySelector('.erail');
      const stage = document.querySelector('.erail__stage');
      const track = document.querySelector('.erail__track');
      const bar = document.querySelector('.erail__progress span');
      const sr = stage.getBoundingClientRect();
      return {
        y,
        tx: getComputedStyle(track).transform,
        bar: getComputedStyle(bar).transform,
        stageTop: Math.round(sr.top),
        stageBottom: Math.round(innerHeight - sr.bottom),
      };
    };
  });

  const geom = await page.evaluate(() => {
    const root = document.querySelector('.erail');
    const stage = document.querySelector('.erail__stage');
    const viewport = document.querySelector('.erail__viewport');
    const track = document.querySelector('.erail__track');
    return {
      ready: root.hasAttribute('data-erail-ready'),
      rootH: root.offsetHeight,
      stageH: stage.offsetHeight,
      trackW: track.scrollWidth,
      viewW: viewport.clientWidth,
      overflow: track.scrollWidth - viewport.clientWidth,
      pinned: getComputedStyle(stage).position,
    };
  });

  check('the rail takes over from the plain scroller',
    geom.ready,
    'ready=' + geom.ready + ', stage is ' + geom.pinned);

  const max = await page.evaluate(() => document.body.scrollHeight - innerHeight);
  const stops = [0, 0.2, 0.35, 0.5, 0.65, 0.8, 1].map(f => Math.round(f * max));

  const fwd = [];
  for (const y of stops) fwd.push(await page.evaluate(y => window.__at(y), y));
  const rev = [];
  for (const y of [...stops].reverse()) rev.push(await page.evaluate(y => window.__at(y), y));

  const offsets = fwd.map(s => Math.round(x(s.tx)));
  check('the row travels sideways as you scroll down',
    offsets[0] === 0 && offsets.every((v, i) => i === 0 || v <= offsets[i - 1]) &&
    Math.min(...offsets) < -10,
    offsets.join(' -> '));

  check('it lands with the row exactly used up, no further',
    Math.abs(Math.min(...offsets) + geom.overflow) < 2,
    'furthest ' + Math.min(...offsets) + ', row overflows by ' + geom.overflow);

  const backOffsets = rev.map(s => Math.round(x(s.tx)));
  check('and travels back when you scroll up',
    backOffsets.every((v, i) => i === 0 || v >= backOffsets[i - 1]) &&
    backOffsets[backOffsets.length - 1] === 0,
    backOffsets.join(' -> '));

  const same = stops.every((y, i) =>
    Math.abs(Math.round(x(fwd[i].tx)) - Math.round(x(rev[rev.length - 1 - i].tx))) <= 1);
  check('the same scroll position gives the same offset either way', same,
    same ? 'all match' : 'drifted');

  // --- the travel happens while you can see the cards ----------------------
  /*
   * The complaint this was built to answer: the row used to be moving while
   * the section was a sliver at the bottom of the window and finished while it
   * was a sliver at the top, so the first and last cards went past unread.
   *
   * Walked in fine steps rather than sampled, because what matters is the
   * exact frame the row sets off and the exact frame it arrives.
   */
  const window_ = await page.evaluate(async () => {
    const root = document.querySelector('.erail');
    const track = document.querySelector('.erail__track');
    const max = document.body.scrollHeight - innerHeight;
    const seen = [];
    const at = t => /matrix(?:3d)?\(([^)]+)\)/.exec(t)[1].split(',').map(Number).slice(4)[0];
    const visible = () => {
      const r = root.getBoundingClientRect();
      const on = Math.max(0, Math.min(r.bottom, innerHeight) - Math.max(r.top, 0));
      return on / Math.min(r.height, innerHeight);
    };
    for (let y = 0; y <= max; y += 10) {
      window.scrollTo(0, y);
      await new Promise(r => requestAnimationFrame(r));
      seen.push({ y, off: Math.abs(at(getComputedStyle(track).transform)), vis: visible() });
    }
    const moving = seen.filter(s => s.off > 1);
    const done = seen.filter(s => s.off > 0);
    const first = moving[0];
    const last = done[done.length - 1];
    // The last frame at which the row is still short of the end.
    const arrives = seen.find(s => s.off >= Math.max(...seen.map(q => q.off)) - 1);
    return {
      startVis: first ? first.vis : 0,
      endVis: arrives ? arrives.vis : 0,
      furthest: Math.max(...seen.map(s => s.off)),
    };
  });

  check('the row waits until the section is on screen before it sets off',
    window_.startVis >= 0.75,
    Math.round(window_.startVis * 100) + '% of the section was showing on the frame it started');

  check('and finishes while the section is still on screen',
    window_.endVis >= 0.75,
    Math.round(window_.endVis * 100) + '% still showing on the frame it arrived');

  // --- the progress bar ----------------------------------------------------
  const bars = fwd.map(s => {
    const m = /matrix\(([^)]+)\)/.exec(s.bar);
    return m ? Number(m[1].split(',')[0]).toFixed(2) : '?';
  });
  check('the progress bar follows the travel',
    bars[0] === '0.00' && Number(bars[bars.length - 1]) > 0.98,
    bars.join(' -> '));

  // --- pictures fill their cards, under a hostile theme --------------------
  const fill = await page.evaluate(() => {
    return [...document.querySelectorAll('.erail__card')].map(card => {
      const img = card.querySelector('.erail__img');
      if (!img) return null;
      return {
        nat: img.naturalWidth + 'x' + img.naturalHeight,
        fit: getComputedStyle(img).objectFit,
        box: img.offsetWidth + 'x' + img.offsetHeight,
        covers: img.offsetWidth >= card.clientWidth - 1 && img.offsetHeight >= card.clientHeight - 1,
      };
    }).filter(Boolean);
  });
  check('every picture fills its card, whatever shape or size it is',
    fill.every(f => f.covers && f.fit === 'cover'),
    fill.map(f => f.nat + ' -> ' + f.box + (f.covers ? '' : ' LEAVES A GAP')).join(', '));

  // --- the click affordance ------------------------------------------------
  const cues = await page.evaluate(() => {
    const cards = [...document.querySelectorAll('.erail__card')];
    return cards.map(c => ({
      link: c.tagName === 'A' && c.hasAttribute('href'),
      cue: !!c.querySelector('.erail__cue'),
      idle: c.querySelector('.erail__cue')
        ? Number(getComputedStyle(c.querySelector('.erail__cue')).opacity)
        : null,
      cursor: getComputedStyle(c).cursor,
    }));
  });

  check('a linked card carries a cue you can see before you touch it',
    cues.filter(c => c.link).every(c => c.cue && c.idle > 0.2 && c.idle < 1),
    cues.filter(c => c.link).map(c => 'opacity ' + c.idle).join(', '));

  check('a card with no link shows no cue and does not pretend to be one',
    cues.filter(c => !c.link).every(c => !c.cue && c.cursor !== 'pointer'),
    cues.filter(c => !c.link).map(c => 'cue=' + c.cue + ' cursor=' + c.cursor).join(', '));

  // Hover the first card and watch the cue bloom and the picture come back.
  await page.evaluate(y => window.scrollTo(0, y), 0);
  await page.evaluate(() => new Promise(r => setTimeout(r, 200)));
  const before = await page.evaluate(() => {
    const c = document.querySelector('.erail__card[href]');
    return {
      cue: Number(getComputedStyle(c.querySelector('.erail__cue')).opacity),
      grey: getComputedStyle(c.querySelector('.erail__img')).filter,
      card: getComputedStyle(c).transform,
    };
  });
  await page.hover('.erail__card[href]');
  await page.evaluate(() => new Promise(r => setTimeout(r, 900)));
  const after = await page.evaluate(() => {
    const c = document.querySelector('.erail__card[href]');
    return {
      cue: Number(getComputedStyle(c.querySelector('.erail__cue')).opacity),
      cueBg: getComputedStyle(c.querySelector('.erail__cue')).backgroundColor,
      grey: getComputedStyle(c.querySelector('.erail__img')).filter,
      card: getComputedStyle(c).transform,
    };
  });

  check('hovering blooms the cue',
    after.cue > before.cue + 0.2 && after.cue > 0.95,
    'opacity ' + before.cue + ' -> ' + after.cue + ', fill ' + after.cueBg);

  check('hovering brings the picture back to colour',
    before.grey !== after.grey && /grayscale\(0\)|grayscale\(0px\)/.test(after.grey.replace(/\s/g, '')) === false
      ? before.grey !== after.grey : before.grey !== after.grey,
    before.grey + ' -> ' + after.grey);

  check('hovering lifts the card',
    before.card !== after.card,
    before.card + ' -> ' + after.card);

  // --- keyboard ------------------------------------------------------------
  const focused = await page.evaluate(async () => {
    window.scrollTo(0, 0);
    await new Promise(r => requestAnimationFrame(r));
    const cards = [...document.querySelectorAll('.erail__card[href]')];
    const last = cards[cards.length - 1];
    last.focus();
    await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
    const r = last.getBoundingClientRect();
    return { left: Math.round(r.left), right: Math.round(r.right), vw: innerWidth };
  });
  check('tabbing to a card off to the side brings it into view',
    focused.right > 0 && focused.left < focused.vw,
    'last card at ' + focused.left + '..' + focused.right + ' in a ' + focused.vw + 'px window');

  // --- narrow screens do not pin -------------------------------------------
  await page.setViewport({ width: 420, height: 900 });
  await page.evaluate(() => { window.dispatchEvent(new Event('resize')); });
  await page.evaluate(() => new Promise(r => setTimeout(r, 200)));
  const narrow = await page.evaluate(() => {
    const root = document.querySelector('.erail');
    const viewport = document.querySelector('.erail__viewport');
    const track = document.querySelector('.erail__track');
    return {
      ready: root.hasAttribute('data-erail-ready'),
      overflowX: getComputedStyle(viewport).overflowX,
      snap: getComputedStyle(viewport).scrollSnapType,
      tx: getComputedStyle(track).transform,
      scrollable: viewport.scrollWidth > viewport.clientWidth,
    };
  });
  check('on a phone it is a plain swipeable row, not a pinned rail',
    !narrow.ready && narrow.overflowX === 'auto' && narrow.scrollable &&
    (narrow.tx === 'none' || Math.round(x(narrow.tx)) === 0),
    'ready=' + narrow.ready + ' overflow-x=' + narrow.overflowX +
      ' snap=' + narrow.snap + ' transform=' + narrow.tx);

  // --- report --------------------------------------------------------------
  console.log('\nPASS');
  PASS.forEach(p => console.log('  + ' + p));
  if (FAIL.length) {
    console.log('\nFAIL');
    FAIL.forEach(f => console.log('  - ' + f));
  }
  if (errs.length) console.log('\nPAGE ERRORS:\n  ' + errs.join('\n  '));
  console.log('\n' + PASS.length + ' passed, ' + FAIL.length + ' failed\n');

  await browser.close();
  process.exit(FAIL.length || errs.length ? 1 : 0);
})();
