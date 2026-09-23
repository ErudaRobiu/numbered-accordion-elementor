/**
 * Measures the Mega Header in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 *   node tests/browser/header-probe.js
 *
 * Headless on purpose, like the rest: Chrome does not run requestAnimationFrame
 * in a tab that is not visible, and this header's scroll state is driven by one.
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];
const URL = 'http://127.0.0.1:8732/tests/browser/header.html';
const sleep = ms => new Promise(r => setTimeout(r, ms));

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: 'new', args: ['--no-sandbox'],
  });
  const errs = [];

  /* ------------------------------------------------------------ desktop --- */

  const page = await browser.newPage();
  page.on('pageerror', e => errs.push(String(e)));
  await page.setViewport({ width: 1440, height: 900 });
  await page.goto(URL, { waitUntil: 'load' });
  await sleep(400);

  const bar = () => page.evaluate(() => {
    const el = document.querySelector('.ehdr__bar');
    const cs = getComputedStyle(el);
    return {
      bg: cs.backgroundColor,
      backdrop: cs.backdropFilter || cs.webkitBackdropFilter,
      border: cs.borderBottomColor,
      shadow: cs.boxShadow,
      stuck: document.querySelector('.ehdr').hasAttribute('data-ehdr-stuck'),
    };
  });

  const clear = c => /rgba\(\s*0,\s*0,\s*0,\s*0\s*\)|transparent/.test(c);

  const rest = await bar();
  check('at the top the bar is completely transparent',
    clear(rest.bg) && !rest.stuck && /blur\(0px\)|none/.test(rest.backdrop),
    'fill ' + rest.bg + ', backdrop ' + rest.backdrop);

  check('and it carries no border or shadow there either',
    clear(rest.border) && rest.shadow === 'none',
    'border ' + rest.border + ', shadow ' + rest.shadow);

  // Folding happens on the way back up now, so get down the page first and
  // then come up: a single jump down is the full-width state by design.
  await page.evaluate(() => window.scrollTo(0, 1800));
  await sleep(700);
  await page.evaluate(() => window.scrollTo(0, 1200));
  await sleep(800);
  const stuck = await bar();

  check('scrolling away from the top frosts it',
    stuck.stuck && !clear(stuck.bg) && /blur\((?!0px)/.test(stuck.backdrop),
    'fill ' + stuck.bg + ', backdrop ' + stuck.backdrop);

  check('the frost saturates as well as blurring',
    /saturate/.test(stuck.backdrop),
    stuck.backdrop);

  check('and the hairline arrives with it',
    !clear(stuck.border),
    stuck.border);

  // It has to arrive over time. A frost that snaps is the thing this widget
  // exists to avoid, and a missing transition looks identical in a screenshot.
  const eased = await page.evaluate(() => {
    const cs = getComputedStyle(document.querySelector('.ehdr__bar'));
    return { props: cs.transitionProperty, ms: cs.transitionDuration };
  });
  /*
   * Sized to what is in it, not to a share of the window.
   *
   * A percentage is arbitrary: wrong on a wide monitor, wrong again on a site
   * whose header is wider than the cap. The compact bar should measure the
   * logo, the menu and the button and stop there.
   */
  const geom = await page.evaluate(() => {
    const b = document.querySelector('.ehdr__bar').getBoundingClientRect();
    const inner = document.querySelector('.ehdr__inner').getBoundingClientRect();
    const cs = getComputedStyle(document.querySelector('.ehdr__bar'));
    return { x: Math.round(b.x), y: Math.round(b.y), w: Math.round(b.width),
      content: Math.round(inner.width), padX: parseFloat(cs.paddingLeft), vw: window.innerWidth };
  });
  check('once frosted it shrinks to fit its own contents',
    geom.w < geom.vw - 40 && Math.abs(geom.w - (geom.content + geom.padX * 2)) <= 2,
    geom.w + 'px of a ' + geom.vw + 'px window, holding ' + geom.content +
      'px of content plus ' + geom.padX * 2 + 'px of padding');

  check('and centres itself, away from the top edge',
    geom.y === 16 && Math.abs(geom.x - (geom.vw - geom.w) / 2) <= 1,
    'x=' + geom.x + ' (window ' + geom.vw + '), y=' + geom.y);

  /*
   * Direction, not position. Traced off the reference: at 600px down while
   * scrolling down their bar is compact; at the same 600px while scrolling up
   * it is full width again. A threshold on scroll position cannot do that, and
   * it is what made the old one fight you on the way back up.
   */
  const direction = await page.evaluate(async () => {
    const bar = document.querySelector('.ehdr__bar');
    const w = () => Math.round(bar.getBoundingClientRect().width);
    const settle = () => new Promise(r => setTimeout(r, 800));

    const box = () => bar.getBoundingClientRect();

    for (let y = 900; y <= 1800; y += 150) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 90)); }
    await settle();
    const b1 = box();
    const down = { w: w(), offScreen: b1.bottom <= 0 };

    for (let y = 1650; y >= 900; y -= 150) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 90)); }
    await settle();
    const b2 = box();
    const up = { w: w(), top: Math.round(b2.top), onScreen: b2.top >= 0 };

    return { down, up, at: 900, full: window.innerWidth };
  });
  /*
   * The fixture asks for "only at the top", which is the steadier of the two
   * settings: once compacted the bar stays compact until you are back where
   * you started, rather than changing every time the wheel is nudged.
   */
  /*
   * The folded bar belongs to the way back up. Reading down the page gets the
   * plain full-width header; turning round to go back -- which is what you do
   * when you want the menu -- is what summons the folded one.
   */
  check('scrolling down takes the header off the screen entirely',
    direction.down.offScreen,
    direction.down.offScreen ? 'gone at y=1800' : 'still showing');

  check('and scrolling back up brings it in, already folded',
    direction.up.onScreen && direction.up.w < direction.full - 40,
    direction.up.w + 'px at top=' + direction.up.top + ', y=' + direction.at);

  const backAtTop = await page.evaluate(async () => {
    window.scrollTo(0, 0);
    await new Promise(r => setTimeout(r, 900));
    return Math.round(document.querySelector('.ehdr__bar').getBoundingClientRect().width);
  });
  /*
   * It slides, it does not jump. The hide is a transform, so this is the one
   * part of the movement that is free -- but it still has to be eased rather
   * than snapped, and a missing transition looks identical in a screenshot.
   */
  const reveal = await page.evaluate(async () => {
    window.scrollTo(0, 1400);
    await new Promise(r => setTimeout(r, 900));
    const bar = document.querySelector('.ehdr__bar');
    const rows = [];
    let stop = false;
    const tick = () => { rows.push(bar.getBoundingClientRect().top); if (!stop) requestAnimationFrame(tick); };
    requestAnimationFrame(tick);
    await new Promise(r => setTimeout(r, 60));
    window.scrollTo(0, 900);
    await new Promise(r => setTimeout(r, 1200));
    stop = true;
    const u = [];
    rows.forEach(v => { if (!u.length || Math.abs(u[u.length - 1] - v) > 0.4) u.push(v); });
    const steps = u.slice(1).map((v, i) => Math.abs(v - u[i]));
    return { count: u.length, biggest: Math.max.apply(null, steps),
      span: Math.abs(u[0] - u[u.length - 1]) };
  });
  check('and it slides in rather than appearing',
    reveal.count > 8 && reveal.biggest < reveal.span * 0.3,
    reveal.count + ' positions over ' + Math.round(reveal.span) +
      'px, biggest single frame ' + Math.round(reveal.biggest) + 'px');

  /*
   * Opening a panel has to bring a hidden header back with it.
   *
   * Driven at the state level rather than by scrolling a panel out of view:
   * scrolling moves elements under a stationary cursor, which fires real
   * mouseleave events and closes the panel before the assertion is reached --
   * and a hidden header cannot be hovered at all, which is rather the point.
   */
  await page.evaluate(() => window.scrollTo(0, 0));
  await sleep(800);
  const held = await page.evaluate(async () => {
    const root = document.querySelector('.ehdr');
    const item = document.querySelectorAll('.ehdr__item')[2];

    root.setAttribute('data-ehdr-hidden', '');
    item.dispatchEvent(new MouseEvent('mouseenter', { bubbles: false }));
    await new Promise(r => setTimeout(r, 500));

    const out = { open: root.hasAttribute('data-ehdr-open'),
      hidden: root.hasAttribute('data-ehdr-hidden'),
      top: Math.round(root.querySelector('.ehdr__bar').getBoundingClientRect().top) };

    item.dispatchEvent(new MouseEvent('mouseleave', { bubbles: false }));
    await new Promise(r => setTimeout(r, 400));

    return out;
  });
  check('opening a panel brings a hidden header back with it',
    held.open && !held.hidden && held.top >= 0,
    'open=' + held.open + ', hidden=' + held.hidden + ', top=' + held.top);

  check('the very top is always full width',
    backAtTop === direction.full,
    backAtTop + 'px of ' + direction.full);

  /*
   * The fold is never something you watch happen.
   *
   * The bar leaves at whatever width it had and folds once it is gone, with
   * transitions frozen for the frame it takes. However well a width change is
   * eased it still reads as the header rearranging itself in front of you,
   * which is the thing that looked wrong. So this samples the width only while
   * some part of the bar is on screen, and expects to see one value.
   */
  await page.evaluate(() => window.scrollTo(0, 0));
  await sleep(900);
  const leaving = await page.evaluate(async () => {
    const bar = document.querySelector('.ehdr__bar');
    const seen = [];
    let stop = false;
    const tick = () => {
      const r = bar.getBoundingClientRect();
      if (r.bottom > 0) seen.push(Math.round(r.width));
      if (!stop) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
    for (let y = 0; y <= 1200; y += 150) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 90)); }
    await new Promise(r => setTimeout(r, 1400));
    stop = true;
    const u = [];
    seen.forEach(v => { if (!u.length || u[u.length - 1] !== v) u.push(v); });
    return u;
  });
  check('the fold happens off screen, never in front of you',
    leaving.length === 1,
    leaving.length === 1 ? 'it left at ' + leaving[0] + 'px and folded once gone'
      : 'widths seen while leaving: ' + leaving.join(' '));

  /*
   * Letting it out again is the opposite case. At the top the bar is on screen
   * and in front of you, so the expansion is the point rather than the
   * problem, and it still has to ease.
   */
  const letOut = await page.evaluate(async () => {
    for (let y = 1050; y >= 700; y -= 150) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 90)); }
    await new Promise(r => setTimeout(r, 900));
    const bar = document.querySelector('.ehdr__bar');
    const rows = [];
    let stop = false;
    const tick = () => { rows.push(bar.getBoundingClientRect().width); if (!stop) requestAnimationFrame(tick); };
    requestAnimationFrame(tick);
    window.scrollTo(0, 0);
    await new Promise(r => setTimeout(r, 1300));
    stop = true;
    const u = [];
    rows.forEach(v => { if (!u.length || Math.abs(u[u.length - 1] - v) > 0.5) u.push(v); });
    const steps = u.slice(1).map((v, i) => Math.abs(v - u[i]));
    return { count: u.length, biggest: Math.max.apply(null, steps), span: Math.abs(u[0] - u[u.length - 1]) };
  });
  check('but letting it out at the top still eases',
    letOut.count > 8 && letOut.biggest < letOut.span * 0.3,
    letOut.count + ' widths over ' + Math.round(letOut.span) +
      'px, biggest single frame ' + Math.round(letOut.biggest) + 'px');

  await page.evaluate(() => window.scrollTo(0, 0));
  await sleep(700);
  await page.evaluate(() => window.scrollTo(0, 1800));
  await sleep(500);
  await page.evaluate(() => window.scrollTo(0, 1200));
  await sleep(800);

  check('the frost is transitioned rather than snapping',
    /background-color/.test(eased.props) && /backdrop-filter/.test(eased.props) &&
      !/^0s/.test(eased.ms),
    eased.props.split(',').length + ' properties over ' + eased.ms.split(',')[0]);

  await page.evaluate(() => window.scrollTo(0, 0));
  await sleep(500);

  /* ------------------------------------------------------------- panels --- */

  const svc = await page.$('.ehdr__item:nth-of-type(3) .ehdr__link');
  await svc.hover();
  await sleep(900);

  const open = await page.evaluate(() => {
    const root = document.querySelector('.ehdr');
    const barBox = document.querySelector('.ehdr__bar').getBoundingClientRect();
    const items = [...document.querySelectorAll('.ehdr__item')];
    const shown = items.filter(it => {
      const p = it.querySelector('.ehdr__panel');
      return p && getComputedStyle(p).visibility === 'visible' && +getComputedStyle(p).opacity > 0.5;
    });
    const panel = shown[0] && shown[0].querySelector('.ehdr__panel');
    const box = panel ? panel.getBoundingClientRect() : null;
    const scrim = document.querySelector('.ehdr__scrim');
    const scs = getComputedStyle(scrim);
    return {
      openAttr: root.hasAttribute('data-ehdr-open'),
      shown: shown.length,
      withPanel: items.filter(it => it.querySelector('.ehdr__panel')).length,
      panelLeft: box ? Math.round(box.left) : null,
      panelWidth: box ? Math.round(box.width) : null,
      barLeft: Math.round(barBox.left),
      barWidth: Math.round(barBox.width),
      panelTop: box ? Math.round(box.top) : null,
      barBottom: Math.round(barBox.bottom),
      expanded: [...document.querySelectorAll('.ehdr__link[aria-expanded]')].map(a => a.getAttribute('aria-expanded')),
      scrim: { backdrop: scs.backdropFilter || scs.webkitBackdropFilter, bg: scs.backgroundColor,
        top: Math.round(scrim.getBoundingClientRect().top), vis: scs.visibility,
        z: parseInt(scs.zIndex, 10) },
      barZ: parseInt(getComputedStyle(document.querySelector('.ehdr__bar')).zIndex, 10),
      // A filter on page content would make every fixed element inside it
      // scroll with the page -- this header included.
      filtered: [...document.querySelectorAll('body > *')]
        .filter(e => getComputedStyle(e).filter !== 'none').length,
    };
  });

  check('hovering an item opens its panel', open.openAttr && open.shown === 1,
    open.shown + ' of ' + open.withPanel + ' panels showing');

  /*
   * And the bar keeps its shape while it does. It used to snap back to full
   * width, so the panel faded in at the same moment the bar resized underneath
   * it and two animations fought over the same corner of the screen.
   */
  const steady = await page.evaluate(async () => {
    for (let y = 0; y <= 1200; y += 150) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 80)); }
    await new Promise(r => setTimeout(r, 900));
    for (let y = 1050; y >= 700; y -= 150) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 80)); }
    await new Promise(r => setTimeout(r, 900));
    const w = () => Math.round(document.querySelector('.ehdr__bar').getBoundingClientRect().width);
    const before = w();
    document.querySelectorAll('.ehdr__item')[2].dispatchEvent(new MouseEvent('mouseenter'));
    await new Promise(r => setTimeout(r, 800));
    const after = w();
    document.querySelectorAll('.ehdr__item')[2].dispatchEvent(new MouseEvent('mouseleave'));
    await new Promise(r => setTimeout(r, 400));
    return { before, after };
  });
  check('the folded bar keeps its shape when a panel opens',
    steady.before === steady.after && steady.before < 1440,
    steady.before + 'px -> ' + steady.after + 'px');

  await page.evaluate(() => window.scrollTo(0, 0));
  await sleep(900);

  /*
   * A mega panel, not a dropdown -- but no longer the width of the window.
   *
   * At the top of the page the bar is full bleed, and the panel used to span
   * it: a 1440px sheet at the top and a 1055px card once the bar had folded,
   * from the same menu. The folded one is the one worth keeping, so the panel
   * carries that width in both and is centred on the bar to get it.
   */
  check('the panel is far wider than the word that opened it',
    open.panelWidth > 600,
    open.panelWidth + 'px against a trigger of about 70px');

  check('but not as wide as the full-bleed bar it hangs off',
    open.panelWidth < open.barWidth - 100,
    'panel ' + open.panelWidth + 'px inside a bar of ' + open.barWidth + 'px');

  check('and it is centred on it',
    Math.abs((open.panelLeft + open.panelWidth / 2) - (open.barLeft + open.barWidth / 2)) <= 2,
    'panel centre ' + Math.round(open.panelLeft + open.panelWidth / 2) +
      ', bar centre ' + Math.round(open.barLeft + open.barWidth / 2));

  /*
   * The width is the point: the same dropdown has to open in the same place
   * whether or not the page has been scrolled. Measured at the top, then
   * again once the bar has folded, and the two boxes have to agree.
   */
  const sameBox = await page.evaluate(async () => {
    const r = () => {
      const p = [...document.querySelectorAll('.ehdr__panel')]
        .find(x => getComputedStyle(x).visibility === 'visible');
      const b = p.getBoundingClientRect();
      return [Math.round(b.left), Math.round(b.width)];
    };
    const item = document.querySelectorAll('.ehdr__item')[2];
    const hover = async on => {
      item.dispatchEvent(new MouseEvent(on ? 'mouseenter' : 'mouseleave'));
      await new Promise(x => setTimeout(x, 700));
    };

    window.scrollTo(0, 0);
    await new Promise(x => setTimeout(x, 700));
    await hover(true);
    const top = r();
    await hover(false);

    // Down and back up, which is what leaves the bar folded and visible.
    for (let y = 0; y <= 1200; y += 150) { window.scrollTo(0, y); await new Promise(x => setTimeout(x, 80)); }
    await new Promise(x => setTimeout(x, 900));
    for (let y = 1050; y >= 700; y -= 150) { window.scrollTo(0, y); await new Promise(x => setTimeout(x, 80)); }
    await new Promise(x => setTimeout(x, 900));
    await hover(true);
    const stuck = r();
    const bar = document.querySelector('.ehdr__bar');
    const barBg = getComputedStyle(bar).backgroundColor;
    const scrimTop = Math.round(document.querySelector('.ehdr__scrim').getBoundingClientRect().top);
    const barBottom = Math.round(bar.getBoundingClientRect().bottom);
    await hover(false);

    window.scrollTo(0, 0);
    await new Promise(x => setTimeout(x, 900));
    return { top, stuck, barBg, scrimTop, barBottom };
  });

  check('the dropdown opens at the same width and in the same place either way',
    Math.abs(sameBox.top[0] - sameBox.stuck[0]) <= 2 &&
      Math.abs(sameBox.top[1] - sameBox.stuck[1]) <= 2,
    'at the top ' + sameBox.top.join('/') + ', folded ' + sameBox.stuck.join('/'));

  check('and once folded the bar under it is opaque, as it always was',
    !/rgba\(\s*0,\s*0,\s*0,\s*0\s*\)/.test(sameBox.barBg), sameBox.barBg);

  /*
   * The thing that started all this: opening a panel at the top of the page
   * used to turn the whole bar opaque white over the hero. The panel gets a
   * fill; the bar does not.
   */
  const restOpen = await page.evaluate(async () => {
    const item = document.querySelectorAll('.ehdr__item')[2];
    item.dispatchEvent(new MouseEvent('mouseenter'));
    await new Promise(x => setTimeout(x, 700));
    const bar = getComputedStyle(document.querySelector('.ehdr__bar'));
    const panel = [...document.querySelectorAll('.ehdr__panel')]
      .find(x => getComputedStyle(x).visibility === 'visible');
    const inner = panel.querySelector('.ehdr__panel-inner');
    const out = {
      barBg: bar.backgroundColor,
      barShadow: bar.boxShadow,
      panelBg: getComputedStyle(panel).backgroundColor,
      cols: getComputedStyle(inner).gridTemplateColumns.split(' ').length,
      aside: !!inner.querySelector('.ehdr__panel-aside .ehdr__figure') &&
        !!inner.querySelector('.ehdr__panel-aside .ehdr__blurb'),
      solo: getComputedStyle(
        document.querySelectorAll('.ehdr__item')[3].querySelector('.ehdr__panel-inner')
      ).gridTemplateColumns.split(' ').length,
    };

    // Left open on purpose: the checks below this read the same panel.
    return out;
  });

  check('a panel opening at the top of the page leaves the bar transparent',
    /rgba\(\s*0,\s*0,\s*0,\s*0\s*\)|transparent/.test(restOpen.barBg) &&
      restOpen.barShadow === 'none',
    'bar ' + restOpen.barBg + ', shadow ' + restOpen.barShadow);

  check('while the panel itself is a solid card',
    /rgb\(255,\s*255,\s*255\)/.test(restOpen.panelBg), restOpen.panelBg);

  /* Two sides, and the picture and its caption are one of them. */
  check('the panel is two columns, not three',
    restOpen.cols === 2, restOpen.cols + ' tracks');

  check('and the picture and the blurb share the second one',
    restOpen.aside, 'figure and blurb both inside .ehdr__panel-aside');

  check('a panel with nothing but links is one column',
    restOpen.solo === 1, restOpen.solo + ' track');

  /*
   * And it is a dropdown rather than a mega panel: four short rows stretched
   * across the width of the header is the other half of what looked junky.
   */
  const drop = await page.evaluate(async () => {
    const wide = document.querySelectorAll('.ehdr__item')[2];
    const item = document.querySelector('.ehdr__item--drop');
    wide.dispatchEvent(new MouseEvent('mouseleave'));
    item.dispatchEvent(new MouseEvent('mouseenter'));
    await new Promise(x => setTimeout(x, 700));

    const b = item.querySelector('.ehdr__panel').getBoundingClientRect();
    const label = item.querySelector('.ehdr__link').getBoundingClientRect();
    const bar = document.querySelector('.ehdr__bar').getBoundingClientRect();

    // What the last item would do, which is the case the flip exists for.
    const last = document.querySelectorAll('.ehdr__item')[5];
    last.classList.add('ehdr__item--drop');
    // Forced wide, because the last item in this fixture is nowhere near the
    // edge at 1440 and the flip is what is being tested, not the arithmetic.
    last.style.setProperty('--ehdr-drop-min', '900px');
    const clone = item.querySelector('.ehdr__panel').cloneNode(true);
    last.appendChild(clone);
    window.dispatchEvent(new Event('resize'));
    await new Promise(x => setTimeout(x, 400));
    const flipped = last.classList.contains('ehdr__item--end');
    const lastRight = clone.getBoundingClientRect().right;
    last.removeChild(clone);
    last.style.removeProperty('--ehdr-drop-min');
    last.classList.remove('ehdr__item--drop', 'ehdr__item--end');

    // The wide panel goes back up, because the checks below read that one.
    item.dispatchEvent(new MouseEvent('mouseleave'));
    wide.dispatchEvent(new MouseEvent('mouseenter'));
    await new Promise(x => setTimeout(x, 700));

    return {
      width: Math.round(b.width), barWidth: Math.round(bar.width),
      left: Math.round(b.left), labelLeft: Math.round(label.left),
      flipped, overflow: Math.round(lastRight - bar.right),
    };
  });

  /*
   * The one thing the picture does, and proof that it does it.
   *
   * It used to inherit whatever the theme did to an `img` on hover -- a fade,
   * a filter, a cursor that promised a lightbox -- which in a menu panel reads
   * as a fault. Those are off; what replaces them is a slow drift that belongs
   * to the panel being open rather than to the pointer crossing the picture.
   */
  // The real pointer, parked well clear of the bar: `:hover` is half of what
  // opens a panel, and a cursor left sitting on an item holds it open behind
  // the synthetic events below.
  await page.mouse.move(20, 600);

  const drift = await page.evaluate(async () => {
    const item = document.querySelectorAll('.ehdr__item')[2];
    const img = item.querySelector('.ehdr__figure img');
    const scale = () => {
      const m = new DOMMatrix(getComputedStyle(img).transform);
      return Math.round(m.a * 1000) / 1000;
    };

    item.dispatchEvent(new MouseEvent('mouseleave'));
    await new Promise(x => setTimeout(x, 1400));
    const shut = scale();

    item.dispatchEvent(new MouseEvent('mouseenter'));
    await new Promise(x => setTimeout(x, 1400));
    const open = scale();

    const cs = getComputedStyle(img);
    return { shut, open, opacity: cs.opacity, filter: cs.filter, ms: cs.transitionDuration };
  });

  check('the picture drifts in while the panel is open, and only then',
    drift.shut === 1 && drift.open > 1.01,
    'scale ' + drift.shut + ' -> ' + drift.open + ' over ' + drift.ms);

  check('and the theme cannot fade or filter it on the way',
    drift.opacity === '1' && drift.filter === 'none',
    'opacity ' + drift.opacity + ', filter ' + drift.filter);

  check('a links-only panel is a dropdown, sized to its links',
    drop.width < 420 && drop.width < drop.barWidth / 2,
    drop.width + 'px inside a bar of ' + drop.barWidth + 'px');

  check('and it hangs under the word that opened it',
    Math.abs(drop.left - drop.labelLeft) <= 4,
    'panel at ' + drop.left + ', label at ' + drop.labelLeft);

  check('one that would run off the side opens the other way instead',
    drop.flipped && drop.overflow <= 0,
    'flipped=' + drop.flipped + ', right edge ' + drop.overflow + 'px past the bar');

  /*
   * Detached, but never disconnected.
   *
   * The gap is a transparent top border rather than an offset, so the panel's
   * box still touches the bar: move the pointer down into the panel and the
   * item never stops being hovered. Offsetting it with `top` opens a dead
   * strip and the panel closes under your hand on the way to it.
   */
  const detached = await page.evaluate(() => {
    const bar = document.querySelector('.ehdr__bar').getBoundingClientRect();
    const panel = [...document.querySelectorAll('.ehdr__panel')]
      .find(x => getComputedStyle(x).visibility === 'visible');
    const cs = getComputedStyle(panel);
    const r = panel.getBoundingClientRect();
    return { barBottom: Math.round(bar.bottom), boxTop: Math.round(r.top),
      fillTop: Math.round(r.top + parseFloat(cs.borderTopWidth)),
      radius: parseFloat(cs.borderRadius), clip: cs.backgroundClip };
  });
  check('the panel sits off the bar with a gap, and keeps its corners',
    detached.fillTop - detached.barBottom >= 8 && detached.radius > 0 &&
      detached.clip === 'padding-box',
    (detached.fillTop - detached.barBottom) + 'px gap, ' + detached.radius +
      'px radius, clipped to the ' + detached.clip);

  check('but its box still touches the bar, so the hover never breaks',
    Math.abs(detached.boxTop - detached.barBottom) <= 2,
    'panel box starts at ' + detached.boxTop + ', bar ends at ' + detached.barBottom);

  check('and it hangs off the bottom of the bar rather than floating loose',
    Math.abs(open.panelTop - open.barBottom) <= 2,
    'panel top ' + open.panelTop + ', bar bottom ' + open.barBottom);

  check('the trigger says it is expanded, and only that one does',
    open.expanded.filter(v => v === 'true').length === 1 && open.expanded.length === open.withPanel,
    open.expanded.join(' '));

  /*
   * The colour controls have to beat the theme.
   *
   * The fixture carries `.elementor a { color: #8a8a8a }`, which is two
   * classes -- the shape every Elementor kit ships. A bare `.ehdr__link` loses
   * to it, and the menu's colour settings appear to do nothing at all while
   * the custom property they write is overridden downstream.
   */
  const ink = await page.evaluate(() => {
    const root = document.querySelector('.ehdr');
    root.style.setProperty('--ehdr-ink', 'rgb(15, 56, 96)');
    root.style.setProperty('--ehdr-ink-hover', 'rgb(0, 165, 93)');
    const link = document.querySelectorAll('.ehdr__item')[1].querySelector('.ehdr__link');
    return {
      link: getComputedStyle(link).color,
      panel: getComputedStyle(document.querySelector('.ehdr__panel-link')).color,
      theme: getComputedStyle(document.querySelector('.band a') || document.body).color,
    };
  });
  check('the menu takes the colour it was given, over the theme\'s own',
    ink.link === 'rgb(15, 56, 96)' && ink.panel === 'rgb(15, 56, 96)',
    'menu ' + ink.link + ', panel ' + ink.panel + ' (theme sets rgb(138, 138, 138))');

  const hovered = await page.evaluate(async () => {
    const link = document.querySelectorAll('.ehdr__item')[1].querySelector('.ehdr__link');
    link.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));
    return null;
  });
  const about = await page.$('.ehdr__item:nth-of-type(2) .ehdr__link');
  await about.hover();
  await sleep(400);
  const inkHover = await page.evaluate(() =>
    getComputedStyle(document.querySelectorAll('.ehdr__item')[1].querySelector('.ehdr__link')).color);
  check('and the hover colour too',
    inkHover === 'rgb(0, 165, 93)',
    inkHover + ' (theme sets rgb(138, 138, 138) on hover as well)');

  /* -------------------------------------------------------------- scrim --- */

  check('the page behind is blurred back',
    open.scrim.vis === 'visible' && /blur\((?!0px)/.test(open.scrim.backdrop),
    open.scrim.backdrop + ' over ' + open.scrim.bg);

  /*
   * Where it starts is the bar's state, not a constant.
   *
   * Frosted, the bar is already blurring what is behind it and the scrim has
   * to stop at its bottom edge: blurring the blur and then darkening it comes
   * out muddy and a shade off every other frosted surface on the page. At the
   * top the bar has no fill to protect, and stopping below it left a crisp
   * strip of hero across the top of the window with the rest of the picture
   * pushed back underneath -- a line drawn by nothing.
   */
  check('at the top the scrim runs the full height of the window',
    open.scrim.top === 0, 'scrim starts at ' + open.scrim.top);

  check('and the bar is painted over it rather than through it',
    open.scrim.z < open.barZ, 'scrim z-index ' + open.scrim.z + ', bar ' + open.barZ);

  check('frosted, it starts below the bar so the frost is not blurred twice',
    Math.abs(sameBox.scrimTop - sameBox.barBottom) <= 2,
    'scrim starts at ' + sameBox.scrimTop + ', bar ends at ' + sameBox.barBottom);

  /*
   * The scrim must be a backdrop-filter on a sheet over the page, never a
   * filter on the page itself. A filtered element becomes the containing block
   * for every position:fixed descendant, so the obvious implementation drags
   * the fixed header into the scroll and repaints the whole document.
   */
  check('the blur is a sheet over the page, not a filter on it',
    open.filtered === 0,
    open.filtered + ' filtered elements in the body');

  // The panel must not be blurred by its own scrim. backdrop-filter makes the
  // bar a stacking context, so this needs the bar raised above the scrim.
  const sharp = await page.evaluate(() => {
    const panel = [...document.querySelectorAll('.ehdr__panel')]
      .find(p => getComputedStyle(p).visibility === 'visible');
    const box = panel.getBoundingClientRect();
    const mid = document.elementFromPoint(box.left + box.width / 2, box.top + box.height / 2);
    return { inPanel: !!(mid && panel.contains(mid)), hit: mid ? mid.className.toString().slice(0, 40) : null };
  });
  check('the panel is drawn above the scrim rather than under it',
    sharp.inPanel, 'the middle of the panel hits ' + sharp.hit);

  /* ------------------------------------------------- one at a time, esc --- */

  const sys = await page.$('.ehdr__item:nth-of-type(4) .ehdr__link');
  await sys.hover();
  await sleep(600);
  const swapped = await page.evaluate(() => {
    const on = [...document.querySelectorAll('.ehdr__item[data-ehdr-on]')];
    return { count: on.length, label: on[0] ? on[0].querySelector('.ehdr__link').textContent.trim() : null };
  });
  check('moving to the next item swaps the panel rather than adding one',
    swapped.count === 1 && /Our System/.test(swapped.label || ''),
    swapped.count + ' open, showing ' + swapped.label);

  await page.keyboard.press('Escape');
  await sleep(400);
  const escaped = await page.evaluate(() => ({
    open: document.querySelector('.ehdr').hasAttribute('data-ehdr-open'),
    focus: document.activeElement ? document.activeElement.textContent.trim().slice(0, 20) : null,
  }));
  check('Escape closes the panel and gives focus back to its trigger',
    !escaped.open && /Our System/.test(escaped.focus || ''),
    'open=' + escaped.open + ', focus on "' + escaped.focus + '"');

  /* --------------------------------------------------------- the button --- */

  const cta = await page.evaluate(() => {
    const el = document.querySelector('.ehdr__inner > .ehdr__cta');
    const cs = getComputedStyle(el);
    return { bg: cs.backgroundImage, shadow: cs.boxShadow, track: cs.letterSpacing, radius: cs.borderRadius };
  });

  check('the button keeps its gradient',
    /linear-gradient/.test(cta.bg), cta.bg.slice(0, 80));

  // Two shadows, and exactly one of them inset: the inner one is what gives
  // the pill its thickness and the outer one is the lift off the page.
  const insets = (cta.shadow.match(/inset/g) || []).length;
  const parts = cta.shadow.split(/,(?![^(]*\))/).length;
  check('and both shadows, one inset and one not',
    parts === 2 && insets === 1,
    parts + ' shadows, ' + insets + ' inset');

  /*
   * Tracking has to compute to a real length. Elementor will happily write
   * `letter-spacing: 5%`, which is not valid anywhere -- browsers drop the
   * declaration and the tracking silently does nothing. The live site this was
   * measured from has exactly that on its button.
   */
  check('the letter spacing is a real length, not a dropped percentage',
    /px$/.test(cta.track) && parseFloat(cta.track) > 0,
    cta.track);

  /*
   * Exactly one button on a desktop bar.
   *
   * The drawer carries a second copy for a phone, and `.ehdr .ehdr__cta` had
   * to be two classes to beat the theme's link colour -- which meant the
   * one-class rule hiding that copy lost to it and the bar showed the button
   * twice, side by side.
   */
  const buttons = await page.evaluate(() => [...document.querySelectorAll('.ehdr__cta')]
    .filter(el => getComputedStyle(el).display !== 'none').length);
  check('the bar carries the button once, not twice',
    buttons === 1, buttons + ' visible');

  /*
   * The roll. Two copies of the label stacked in a box that clips: the
   * visible one goes up and out, the one below arrives in its place.
   */
  const roll = await page.evaluate(() => {
    const link = document.querySelectorAll('.ehdr__item')[0].querySelector('.ehdr__link');
    const wrap = link.querySelector('.ehdr__roll');
    if (!wrap) return null;
    const [a, b] = wrap.querySelectorAll('span');
    return {
      clips: getComputedStyle(wrap).overflow,
      copies: wrap.querySelectorAll('span').length,
      hidden: b.getAttribute('aria-hidden'),
      restA: getComputedStyle(a).transform,
      restB: getComputedStyle(b).transform,
      text: link.textContent.replace(/\s+/g, ' ').trim(),
    };
  });
  check('a menu label is two copies in a box that clips',
    roll && roll.copies === 2 && roll.clips === 'hidden' && roll.hidden === 'true',
    roll ? roll.copies + ' copies, overflow ' + roll.clips + ', second aria-hidden=' + roll.hidden : 'no roll markup');

  const home = await page.$('.ehdr__item:nth-of-type(1) .ehdr__link');
  await home.hover();
  await sleep(600);
  const rolled = await page.evaluate(() => {
    const wrap = document.querySelectorAll('.ehdr__item')[0].querySelector('.ehdr__roll');
    const [a, b] = wrap.querySelectorAll('span');
    return { a: getComputedStyle(a).transform, b: getComputedStyle(b).transform };
  });
  check('and hovering rolls one out as the other arrives',
    rolled.a !== roll.restA && rolled.b !== roll.restB && /matrix/.test(rolled.a),
    'top copy ' + roll.restA + ' -> ' + rolled.a);

  /*
   * The shuffle, and what makes it the reference's rather than a generic one:
   * every frame is an anagram of the label. The front locks in one character
   * at a time from the left and the tail is the remaining real characters in
   * some other order -- never random glyphs, which is why it reads as the word
   * sorting itself out instead of as static.
   */
  const sorted = str => str.split('').sort().join('');
  const shuffled = await page.evaluate(async () => {
    const el = document.querySelector('.ehdr__scramble');
    if (!el) return null;
    const link = el.closest('.ehdr__link');
    const frames = [];
    const timer = setInterval(() => frames.push(el.textContent), 20);
    link.dispatchEvent(new MouseEvent('mouseenter', { bubbles: false }));
    await new Promise(r => setTimeout(r, 900));
    clearInterval(timer);
    const seen = [];
    frames.forEach(f => { if (!seen.length || seen[seen.length - 1] !== f) seen.push(f); });
    const cs = getComputedStyle(el);
    return { label: el.getAttribute('data-ehdr-text'), seen,
      pinned: cs.width === cs.minWidth && cs.width === cs.maxWidth && cs.width !== 'auto' };
  });

  check('hovering shuffles the label and settles back on it',
    shuffled && shuffled.seen.length > 3 &&
      shuffled.seen[shuffled.seen.length - 1] === shuffled.label,
    shuffled ? shuffled.seen.length + ' frames, ending on "' +
      shuffled.seen[shuffled.seen.length - 1] + '"' : 'no scramble markup');

  check('and every frame is an anagram of it, never random glyphs',
    shuffled && shuffled.seen.every(f => sorted(f) === sorted(shuffled.label)),
    shuffled ? shuffled.seen.slice(0, 5).map(f => JSON.stringify(f)).join(' ') : 'n/a');

  // Same letters in a different order measure differently in a proportional
  // font; without this the label jitters its neighbours about.
  check('the label pins its own width first',
    shuffled && shuffled.pinned,
    shuffled ? ( shuffled.pinned ? 'width, min-width and max-width all locked' : 'not pinned' ) : 'n/a');

  /* ------------------------------------------------------- held states --- */

  /*
   * The preview holds, which exist so the thing can be looked at without
   * scrolling or hovering -- in the editor, where you cannot do either.
   */
  for (const [state, expect] of [['stuck', 'frosted at the top of the page'], ['open', 'a panel open']]) {
    const held = await browser.newPage();
    await held.setViewport({ width: 1440, height: 900 });
    await held.goto(URL, { waitUntil: 'load' });
    await held.evaluate(s => {
      const root = document.querySelector('.ehdr');
      root.setAttribute('data-ehdr-preview', s);
      root.eanmHeaderReady = false;
      root.removeAttribute('data-ehdr-ready');
    }, state);
    // Re-init against the attribute, as a fresh render would.
    await held.evaluate(() => {
      const ev = new Event('DOMContentLoaded');
      document.dispatchEvent(ev);
    });
    await sleep(600);
    const got = await held.evaluate(() => {
      const root = document.querySelector('.ehdr');
      const cs = getComputedStyle(document.querySelector('.ehdr__bar'));
      return { scrollY: window.scrollY, bg: cs.backgroundColor,
        open: root.hasAttribute('data-ehdr-open'),
        on: document.querySelectorAll('.ehdr__item[data-ehdr-on]').length };
    });
    const ok = state === 'stuck'
      ? got.scrollY === 0 && !/rgba\(0, 0, 0, 0\)/.test(got.bg)
      : got.open && got.on === 1;
    check('"' + expect + '" can be held for a look',
      ok, JSON.stringify(got));
    await held.close();
  }

  /* -------------------------------------------------------------- phone --- */

  const phone = await browser.newPage();
  phone.on('pageerror', e => errs.push('phone: ' + String(e)));
  await phone.setViewport({ width: 390, height: 780, isMobile: true, hasTouch: true });
  await phone.goto(URL, { waitUntil: 'load' });
  await sleep(500);

  const closed = await phone.evaluate(() => {
    const nav = document.querySelector('.ehdr__nav');
    return {
      burger: getComputedStyle(document.querySelector('.ehdr__burger')).display,
      barCta: getComputedStyle(document.querySelector('.ehdr__inner > .ehdr__cta')).display,
      navHeight: Math.round(nav.getBoundingClientRect().height),
      expanded: document.querySelector('.ehdr__burger').getAttribute('aria-expanded'),
    };
  });
  check('a phone gets a burger and loses the bar button',
    closed.burger === 'block' && closed.barCta === 'none',
    'burger ' + closed.burger + ', bar button ' + closed.barCta);

  // One pixel, not nought: the hairline is always there so it has something
  // to transition from. Anything above that is the drawer failing to collapse.
  check('and the drawer starts shut',
    closed.navHeight <= 1 && closed.expanded === 'false',
    'drawer ' + closed.navHeight + 'px, aria-expanded=' + closed.expanded);

  await phone.click('.ehdr__burger');
  await sleep(700);
  const drawer = await phone.evaluate(() => ({
    height: Math.round(document.querySelector('.ehdr__nav').getBoundingClientRect().height),
    expanded: document.querySelector('.ehdr__burger').getAttribute('aria-expanded'),
    cta: getComputedStyle(document.querySelector('.ehdr__drawer-cta')).display,
    sideways: document.documentElement.scrollWidth > window.innerWidth,
  }));
  check('tapping the burger opens the drawer',
    drawer.height > 200 && drawer.expanded === 'true',
    drawer.height + 'px, aria-expanded=' + drawer.expanded);

  check('the button comes with it, full width',
    drawer.cta === 'flex', 'display ' + drawer.cta);

  check('and nothing runs off the side of the screen',
    !drawer.sideways, drawer.sideways ? 'the page scrolls sideways' : 'no sideways scroll');

  /*
   * The row is two targets, and which is which is the whole point.
   *
   * The label used to be the toggle: one tap opened the panel, the next closed
   * it, and the page the word names could not be reached from the menu at all.
   * The chevron beside it opens the list now; the word goes where it says.
   */
  const split = await phone.evaluate(() => {
    const it = document.querySelectorAll('.ehdr__item')[2];
    const link = it.querySelector('.ehdr__link');
    const toggle = it.querySelector('.ehdr__toggle');
    const w = e => Math.round(e.getBoundingClientRect().width);
    const h = e => Math.round(e.getBoundingClientRect().height);
    return {
      toggle: getComputedStyle(toggle).display,
      linkCaret: getComputedStyle(link.querySelector('.ehdr__caret')).display,
      carets: [...it.querySelectorAll(':scope > * .ehdr__caret')]
        .filter(c => getComputedStyle(c).display !== 'none').length,
      overlap: Math.round(link.getBoundingClientRect().right) >
        Math.round(toggle.getBoundingClientRect().left),
      target: Math.min(w(toggle), h(toggle)),
      controls: toggle.getAttribute('aria-controls') === link.getAttribute('aria-controls'),
    };
  });

  check('the drawer row carries a chevron button beside the label',
    split.toggle !== 'none' && !split.overlap,
    'toggle display ' + split.toggle + ', overlapping the link: ' + split.overlap);

  check('and exactly one caret shows on the row',
    split.carets === 1 && split.linkCaret === 'none',
    split.carets + ' visible, the link\'s own is ' + split.linkCaret);

  check('the chevron is a thumb-sized target pointed at the same panel',
    split.target >= 44 && split.controls,
    split.target + 'px, aria-controls matches the link\'s: ' + split.controls);

  /*
   * The tap that broke this once.
   *
   * focusin fires on the way to a click -- mousedown, focus, mouseup, click --
   * so a handler that opened on any focus at all had the panel open before the
   * click arrived, and the click then found it open and toggled it shut. The
   * caret flipped, the label lit, and the links never appeared.
   */
  const before = await phone.evaluate(() =>
    Math.round(document.querySelectorAll('.ehdr__item')[2].querySelector('.ehdr__panel').getBoundingClientRect().height));

  // The label first, which must not open anything and must not be cancelled.
  const followed = await phone.evaluate(() => new Promise(resolve => {
    const link = document.querySelectorAll('.ehdr__item')[2].querySelector('.ehdr__link');
    link.addEventListener('click', function once(e) {
      // Read before cancelling, or the answer is always "cancelled". This
      // listener is added last, so anything the widget did to the event has
      // already happened by the time it runs.
      const reached = !e.defaultPrevented;
      e.preventDefault();
      link.removeEventListener('click', once);
      resolve(reached);
    });
    link.click();
  }));
  const afterLabel = await phone.evaluate(() =>
    document.querySelectorAll('.ehdr__item')[2].hasAttribute('data-ehdr-on'));

  check('tapping the label in the drawer follows the link to its page',
    followed && !afterLabel,
    'the click reached the link with nothing cancelling it, panel open=' + afterLabel);

  await phone.click('.ehdr__item:nth-of-type(3) .ehdr__toggle');
  await sleep(800);
  const after = await phone.evaluate(() => {
    const it = document.querySelectorAll('.ehdr__item')[2];
    const panel = it.querySelector('.ehdr__panel');
    return {
      h: Math.round(panel.getBoundingClientRect().height),
      on: it.hasAttribute('data-ehdr-on'),
      links: panel.querySelectorAll('.ehdr__panel-link').length,
      expanded: it.querySelector('.ehdr__toggle').getAttribute('aria-expanded'),
    };
  });
  check('tapping the chevron opens its links and leaves them open',
    after.on && after.h > before + 100 && after.expanded === 'true',
    'panel went from ' + before + 'px to ' + after.h + 'px, holding ' + after.links + ' links');

  // The picture and the blurb are desktop luxuries; on a phone they would push
  // the links people came for off the bottom of the screen.
  const trimmed = await phone.evaluate(() => {
    const panel = document.querySelectorAll('.ehdr__item')[2].querySelector('.ehdr__panel');
    const fig = panel.querySelector('.ehdr__figure');
    const blurb = panel.querySelector('.ehdr__blurb');
    return { fig: fig ? getComputedStyle(fig).display : 'absent',
      blurb: blurb ? getComputedStyle(blurb).display : 'absent' };
  });
  check('but not the picture or the blurb',
    trimmed.fig === 'none' && trimmed.blurb === 'none',
    'picture ' + trimmed.fig + ', blurb ' + trimmed.blurb);

  /*
   * And the caret comes back down.
   *
   * A tap leaves :hover latched on a touch screen -- there is no pointer to
   * move away -- so every hover rule in the header stayed on after the thing
   * it described had finished. The panel really had closed; the caret was
   * being held upside down by the stylesheet, which is what "the arrows don't
   * flip back" was.
   */
  await phone.click('.ehdr__item:nth-of-type(3) .ehdr__toggle');
  await sleep(900);
  const flipped = await phone.evaluate(() => {
    const it = document.querySelectorAll('.ehdr__item')[2];
    const caret = it.querySelector('.ehdr__toggle .ehdr__caret');
    const m = new DOMMatrix(getComputedStyle(caret).transform);
    return {
      on: it.hasAttribute('data-ehdr-on'),
      hovered: it.matches(':hover'),
      turn: Math.round(Math.atan2(m.b, m.a) * 180 / Math.PI),
      colour: getComputedStyle(it.querySelector('.ehdr__link')).color,
      expanded: it.querySelector('.ehdr__toggle').getAttribute('aria-expanded'),
      h: Math.round(it.querySelector('.ehdr__panel').getBoundingClientRect().height),
    };
  });

  check('tapping the chevron again closes the panel',
    !flipped.on && flipped.h < 40 && flipped.expanded === 'false',
    'panel ' + flipped.h + 'px, aria-expanded=' + flipped.expanded);

  check('and the caret flips back even though the tap left :hover behind',
    flipped.turn === 0 && flipped.hovered,
    'caret at ' + flipped.turn + ' degrees, still matching :hover: ' + flipped.hovered);

  check('the label loses its hover colour with it',
    /rgb\(15,\s*56,\s*96\)/.test(flipped.colour), flipped.colour);

  await phone.click('.ehdr__burger');
  await sleep(600);
  const shut = await phone.evaluate(() => ({
    height: Math.round(document.querySelector('.ehdr__nav').getBoundingClientRect().height),
    open: document.querySelector('.ehdr').hasAttribute('data-ehdr-open'),
  }));
  check('tapping it again shuts the drawer and everything in it',
    shut.height <= 1 && !shut.open,
    shut.height + 'px, panel open=' + shut.open);

  /* ------------------------------------------------ without the script --- */

  /*
   * The cardinal rule: the navigation has to work with the script removed.
   * The panels are opened by plain CSS hover and focus-within, and this loads
   * the page with the script blocked to prove it.
   */
  const bare = await browser.newPage();
  await bare.setRequestInterception(true);
  bare.on('request', r => {
    if (/mega-header\.js/.test(r.url())) r.abort(); else r.continue();
  });
  await bare.setViewport({ width: 1440, height: 900 });
  await bare.goto(URL, { waitUntil: 'load' });
  await sleep(400);
  const noJsBefore = await bare.evaluate(() =>
    getComputedStyle(document.querySelectorAll('.ehdr__item')[2].querySelector('.ehdr__panel')).visibility);
  const svc2 = await bare.$('.ehdr__item:nth-of-type(3) .ehdr__link');
  await svc2.hover();
  await sleep(600);
  const noJs = await bare.evaluate(() => {
    const root = document.querySelector('.ehdr');
    const panel = document.querySelectorAll('.ehdr__item')[2].querySelector('.ehdr__panel');
    const cs = getComputedStyle(panel);
    return { ready: root.hasAttribute('data-ehdr-ready'), vis: cs.visibility, op: cs.opacity,
      links: panel.querySelectorAll('.ehdr__panel-link').length };
  });
  check('with the script blocked the panels still open on hover',
    !noJs.ready && noJsBefore === 'hidden' && noJs.vis === 'visible' && +noJs.op > 0.5,
    'script ran=' + noJs.ready + ', panel ' + noJsBefore + ' -> ' + noJs.vis +
      ' with ' + noJs.links + ' links');

  /* ------------------------------------------------------------- report --- */

  check('no script errors', errs.length === 0, errs.length ? errs.join(' | ') : 'clean');

  console.log('\nPASS');
  PASS.forEach(p => console.log('  + ' + p));

  if (FAIL.length) {
    console.log('\nFAIL');
    FAIL.forEach(f => console.log('  - ' + f));
  }

  console.log('\n' + PASS.length + ' passed, ' + FAIL.length + ' failed');
  await browser.close();
  process.exit(FAIL.length ? 1 : 0);
})();
