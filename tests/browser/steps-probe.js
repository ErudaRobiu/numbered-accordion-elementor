/**
 * Measures Process Steps in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 *   php tests/browser/steps-fixture.php > tests/browser/steps.html
 *   node tests/browser/steps-probe.js
 *
 * Headless on purpose, like the rest: Chrome does not run
 * requestAnimationFrame in a tab that is not visible, and everything this
 * widget does on scroll is driven by one.
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];
const URL = 'http://127.0.0.1:8732/tests/browser/steps.html';
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

  const page = await browser.newPage();
  page.on('pageerror', e => errs.push(String(e)));
  await page.setViewport({ width: 1280, height: 900 });
  await page.goto(URL, { waitUntil: 'load' });
  await sleep(600);

  /* ------------------------------------------------------ the markup --- */

  const shape = await page.evaluate(() => {
    const first = document.querySelector('.estp');
    const steps = [...first.querySelectorAll('.estp__step')];
    return {
      steps: steps.length,
      numbers: steps.map(s => s.querySelector('.estp__disc').textContent.trim()),
      figures: steps.map(s => {
        const f = s.querySelector('.estp__fig');
        return (f.className.match(/estp__fig--(\w+)/) || [])[1];
      }),
      keys: steps.map(s => s.querySelectorAll('.estp__part--key').length),
      hidden: steps.every(s => s.querySelector('.estp__fig').getAttribute('aria-hidden') === 'true'),
      deck: !!steps[1].querySelector('.estp__deck .estp__node'),
      note: steps[2].querySelector('[data-estp-note]').getAttribute('data-estp-note'),
    };
  });

  check('the list renders one row per step', shape.steps === 4, shape.steps + ' steps');
  check('and numbers them, padded to two digits',
    shape.numbers.join(' ') === '01 02 03 04', shape.numbers.join(' '));
  check('each step draws the figure it asked for',
    shape.figures.join(' ') === 'converge probe datum sheets', shape.figures.join(' '));

  /*
   * Exactly one accent per figure. An accent spent on three elements out of
   * five is not an accent, it is a second body colour -- and the first draft
   * of the converge figure filled a 104px plane with it and stopped being
   * about the choice it was drawn to show.
   */
  check('exactly one element per figure carries the accent',
    shape.keys.every(n => n === 1), shape.keys.join(' '));

  // The figure says the heading again without words, so describing it would
  // read the step twice.
  check('the figures are hidden from a screen reader', shape.hidden);

  // The sensors have to be in the surface's own rotated frame, or they sit in
  // a vertical plane in front of it and read as dots on glass.
  check('the sensors sit inside the surface, not beside it', shape.deck);

  check('the baseline label reaches the element that draws it',
    shape.note === 'Baseline', shape.note);

  /* ------------------------------------------------------- the camera --- */

  /*
   * The whole idea: scrolling moves the camera rather than fading anything
   * in. One number per step, and every figure reads it.
   */
  const arc = await page.evaluate(async () => {
    const step = document.querySelectorAll('.estp__step')[1];
    const scene = step.querySelector('.estp__scene');
    const out = [];
    for (const y of [0, 400, 800, 1200]) {
      window.scrollTo(0, y);
      await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
      out.push({
        p: parseFloat(getComputedStyle(step).getPropertyValue('--estp-p')),
        yaw: Math.round(Math.asin(Math.min(1, Math.max(-1,
          new DOMMatrix(getComputedStyle(scene).transform).m13))) * 180 / Math.PI),
      });
    }
    window.scrollTo(0, 0);
    return out;
  });

  const ps = arc.map(a => a.p);
  check('scrolling writes a nought-to-one number onto every step',
    ps[0] < 0.3 && ps[ps.length - 1] > 0.95 && ps.every((v, i) => i === 0 || v >= ps[i - 1]),
    ps.join(' -> '));

  check('and the figure turns through an arc as it does',
    Math.abs(arc[0].yaw - arc[arc.length - 1].yaw) > 8,
    arc.map(a => a.yaw + 'deg').join(' -> '));

  /* -------------------------------------------------------- the spine --- */

  /*
   * The line has to reach from one disc to the next. It is positioned against
   * the rail, and the rail inherited the grid's `align-items: start` -- so it
   * came out exactly as tall as its own disc and every spine was the same
   * short stub whatever the step above it contained.
   */
  const spine = await page.evaluate(() => {
    document.querySelectorAll('.estp__step').forEach(s => s.style.setProperty('--estp-draw', '1'));
    const steps = [...document.querySelectorAll('.estp')[0].querySelectorAll('.estp__step')];
    const rail = steps[0].querySelector('.estp__rail').getBoundingClientRect();
    const discA = steps[0].querySelector('.estp__disc').getBoundingClientRect();
    const discB = steps[1].querySelector('.estp__disc').getBoundingClientRect();
    return {
      rail: Math.round(rail.height),
      row: Math.round(steps[0].getBoundingClientRect().height),
      gap: Math.round(discB.top - discA.bottom),
    };
  });

  check('the spine stretches to the full height of its step',
    spine.rail > spine.row * 0.6,
    'rail ' + spine.rail + 'px in a step of ' + spine.row + 'px, next disc ' + spine.gap + 'px below');

  /* --------------------------------------------------------- the hover --- */

  const fig = await page.evaluate(() => {
    const b = document.querySelectorAll('.estp__fig')[0].getBoundingClientRect();
    return { x: b.left + b.width / 2, y: b.top + b.height / 2 };
  });

  const restZ = await page.evaluate(() =>
    Math.round(new DOMMatrix(getComputedStyle(
      document.querySelectorAll('.estp__fig')[0].querySelector('.estp__part--key')).transform).m43));

  await page.mouse.move(fig.x, fig.y);
  await sleep(800);

  const hoverZ = await page.evaluate(() => {
    const f = document.querySelectorAll('.estp__fig')[0];
    return {
      z: Math.round(new DOMMatrix(getComputedStyle(f.querySelector('.estp__part--key')).transform).m43),
      ghost: parseFloat(getComputedStyle(f.querySelector('.estp__part:not(.estp__part--key)')).opacity),
    };
  });

  check('pointing at a figure brings its answer forward',
    hoverZ.z > restZ + 15, restZ + 'px -> ' + hoverZ.z + 'px');

  check('and sets the options it was chosen over back',
    hoverZ.ghost < 0.6, 'opacity ' + hoverZ.ghost);

  // The sensors rise one after another rather than all at once, which is what
  // a scan looks like and what a single shared transition does not.
  const probeBox = await page.evaluate(() => {
    const b = document.querySelectorAll('.estp__fig')[1].getBoundingClientRect();
    return { x: b.left + b.width / 2, y: b.top + b.height / 2 };
  });
  await page.mouse.move(probeBox.x, probeBox.y);
  await sleep(300);
  const delays = await page.evaluate(() =>
    [...document.querySelectorAll('.estp__fig')[1].querySelectorAll('.estp__node')]
      .map(n => getComputedStyle(n).transitionDelay.split(',')[0].trim()));

  check('the sensors light in sequence, not together',
    new Set(delays).size === delays.length, delays.join(' '));

  await page.mouse.move(4, 4);
  await sleep(300);

  /* ------------------------------------------------------ flattening --- */

  /*
   * `overflow` on the element carrying preserve-3d forces it back to flat,
   * and the failure is silent. An ancestor does NOT do it -- measured, not
   * assumed, after the first version of this guard tested the wrong thing and
   * reported everything healthy.
   */
  const flat = await page.evaluate(() => {
    const all = [...document.querySelectorAll('.estp')];
    return {
      ordinary: all.filter(e => !e.closest('.flatten')).every(e => !e.classList.contains('is-flat')),
      trapped: document.querySelector('.flatten .estp').classList.contains('is-flat'),
    };
  });

  check('a widget that can draw depth is left alone', flat.ordinary);
  check('and one that cannot is switched to its flat arrangement', flat.trapped);

  /* ------------------------------------------------------------ dark --- */

  /*
   * The widget carries no background, so a dark section has to be able to
   * repaint it entirely through its custom properties. Read off the edge and
   * the accent rather than the face: the first part in this figure is a ring,
   * and a ring is deliberately unfilled.
   */
  const dark = await page.evaluate(() => {
    const part = document.querySelector('.dark .estp .estp__part');
    const key = document.querySelector('.dark .estp .estp__part--key');
    const disc = document.querySelector('.dark .estp .estp__disc');
    return {
      edge: getComputedStyle(part).borderTopColor,
      key: getComputedStyle(key).backgroundColor,
      disc: getComputedStyle(disc).backgroundColor,
      ink: getComputedStyle(document.querySelector('.dark .estp .estp__title')).color,
    };
  });

  check('a dark section can repaint the edges',
    /rgba\(255,\s*255,\s*255/.test(dark.edge), dark.edge);

  check('and the accent, on the disc and on the figure together',
    dark.key === dark.disc && /rgb\(77,\s*190,\s*126\)/.test(dark.disc),
    'disc ' + dark.disc + ', key ' + dark.key);

  check('and the headings with them',
    /rgb\(234,\s*242,\s*248\)/.test(dark.ink), dark.ink);

  /* ----------------------------------------------------- the phone --- */

  const phone = await browser.newPage();
  phone.on('pageerror', e => errs.push(String(e)));
  await phone.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
  await phone.goto(URL, { waitUntil: 'load' });
  await sleep(500);

  const narrow = await phone.evaluate(() => {
    const step = document.querySelector('.estp__step');
    return {
      cols: getComputedStyle(step).gridTemplateColumns.split(' ').length,
      figCol: getComputedStyle(step.querySelector('.estp__fig')).gridColumnStart,
      sideways: document.documentElement.scrollWidth > window.innerWidth + 1,
    };
  });

  check('on a phone the figure drops under the words', narrow.cols === 2 && narrow.figCol === '2',
    narrow.cols + ' columns, figure in column ' + narrow.figCol);

  check('and nothing runs off the side of the screen',
    !narrow.sideways, narrow.sideways ? 'the page scrolls sideways' : 'no sideways scroll');

  /* --------------------------------------------- without the script --- */

  /*
   * The cardinal rule. With the script blocked the camera stops following the
   * scroll, and that is all it costs: the steps, the numbers, the spine and
   * the figures are all still drawn, at the angle they were designed at.
   */
  const bare = await browser.newPage();
  await bare.setRequestInterception(true);
  bare.on('request', r => {
    if (/process-steps\.js/.test(r.url())) r.abort(); else r.continue();
  });
  await bare.setViewport({ width: 1280, height: 900 });
  await bare.goto(URL, { waitUntil: 'load' });
  await sleep(400);

  const noJs = await bare.evaluate(() => {
    const step = document.querySelector('.estp__step');
    const scene = step.querySelector('.estp__scene');
    const rail = step.querySelector('.estp__rail');
    return {
      ran: document.querySelector('.estp').hasAttribute('data-estp-ready'),
      p: getComputedStyle(step).getPropertyValue('--estp-p').trim(),
      turned: getComputedStyle(scene).transform !== 'none',
      spine: getComputedStyle(rail, '::after').transform,
      figures: document.querySelectorAll('.estp__part').length,
    };
  });

  check('with the script blocked the figures are still drawn',
    !noJs.ran && noJs.turned && noJs.figures > 10,
    'script ran=' + noJs.ran + ', ' + noJs.figures + ' parts, scene turned');

  check('and they hold the angle they were designed at',
    noJs.p === '0.5', '--estp-p is ' + (noJs.p || 'unset'));

  check('the spine is drawn rather than left at nothing',
    !/matrix\(1, 0, 0, 0,/.test(noJs.spine), noJs.spine);

  check('no script errors', errs.length === 0, errs.join(' | ') || 'clean');

  await browser.close();

  console.log('PASS');
  PASS.forEach(n => console.log('  + ' + n));

  if (FAIL.length) {
    console.log('\nFAIL');
    FAIL.forEach(n => console.log('  - ' + n));
  }

  console.log('\n' + PASS.length + ' passed, ' + FAIL.length + ' failed');
  process.exit(FAIL.length ? 1 : 0);
})();
