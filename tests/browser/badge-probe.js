/**
 * Measures the Spin Badge in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 * Headless on purpose, for the same reason as the other probes: Chrome does
 * not run requestAnimationFrame in a tab that is not visible, and the
 * spin-down is a rAF ramp.
 *
 *   node tests/browser/badge-probe.js
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

const URL = 'http://127.0.0.1:8732/tests/browser/badge.html';

// The rotation in degrees, whatever form the transform comes back in.
const angleOf = (t) => {
  const m = /matrix\(([^)]+)\)/.exec(t || '');
  if (!m) return null;
  const n = m[1].split(',').map(Number);
  return (Math.atan2(n[1], n[0]) * 180) / Math.PI;
};

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: 'new', args: ['--no-sandbox'],
  });

  const page = await browser.newPage();
  await page.setViewport({ width: 1200, height: 900 });
  const errs = [];
  page.on('pageerror', e => errs.push(String(e)));

  await page.evaluateOnNewDocument(() => {
    window.__angles = () => getComputedStyle(document.querySelector('.ebdg__ring')).transform;
  });
  await page.goto(URL, { waitUntil: 'load' });
  await page.evaluate(() => new Promise(r => setTimeout(r, 300)));

  // --- the ring meets itself -------------------------------------------------
  /*
   * The phrase is repeated a whole number of times, so on its own it either
   * falls short of the circle or laps itself. The fix is textLength set to the
   * circle's own circumference, which is worth checking rather than trusting:
   * a ring that overlaps its own first letter is the single most obvious way
   * this widget can look wrong.
   */
  const ring = await page.evaluate(() => {
    const path = document.querySelector('.ebdg__ring path');
    const tp = document.querySelector('.ebdg__ring textPath');
    return {
      circumference: path.getTotalLength(),
      textLength: Number(tp.getAttribute('textLength')),
      adjust: tp.getAttribute('lengthAdjust'),
      /*
       * How far round the circle the text actually got.
       *
       * getComputedTextLength() is no use here: by spec it reports the advance
       * the text would have *without* any textLength adjustment, so it comes
       * back as the unstretched width and says nothing about whether the
       * stretch worked. The rendered positions of the first and last glyphs do
       * say: turned into angles about the centre, the gap between where the
       * text starts and where it ends is how far it is from closing. A ring
       * that closes ends where it began, so the gap is nearly nothing.
       *
       * Unstretched this text is 410 units against a 490 circle, which would
       * leave it 59 degrees short -- so a small gap here is evidence the
       * stretch actually happened, not just that it was asked for.
       */
      gap: (() => {
        const n = tp.getNumberOfChars ? tp.getNumberOfChars() : 0;
        if (!n) return null;
        const at = p => (Math.atan2(p.y - 100, p.x - 100) * 180) / Math.PI;
        const first = at(tp.getStartPositionOfChar(0));
        const last = at(tp.getEndPositionOfChar(n - 1));
        let d = last - first;
        while (d < 0) d += 360;
        return Math.min(d, 360 - d);
      })(),
    };
  });
  check('the ring text is fitted to the circle exactly once round',
    Math.abs(ring.textLength - ring.circumference) < 0.5 && ring.adjust === 'spacing',
    'text set to ' + ring.textLength.toFixed(1) + ' against a circle of ' +
      ring.circumference.toFixed(1));

  check('and the stretch really closed the ring, not just been asked for',
    ring.gap === null || ring.gap < 16,
    ring.gap === null ? 'not measurable' : 'the text ends ' + ring.gap.toFixed(1) +
      ' degrees from where it starts, against 59 unstretched');

  // --- nothing is blurred ----------------------------------------------------
  const sharp = await page.evaluate(() => {
    const bad = [];
    document.querySelectorAll('.ebdg, .ebdg *').forEach(el => {
      const cs = getComputedStyle(el);
      if (cs.filter && cs.filter !== 'none') bad.push(el.className + ' filter:' + cs.filter);
      if (cs.backdropFilter && cs.backdropFilter !== 'none') bad.push(el.className + ' backdrop');
    });
    return { bad, images: document.querySelectorAll('.ebdg img').length };
  });
  check('nothing in the badge is blurred, and no part of it is a bitmap',
    sharp.bad.length === 0 && sharp.images === 0,
    sharp.bad.length ? sharp.bad.join(', ') : 'no filters, ' + sharp.images + ' images');

  // --- the picture layer and the inner shadow --------------------------------
  /*
   * The colour sits over the picture as a pseudo-element rather than as
   * another background layer, because CSS gives a background layer no opacity
   * of its own -- only the whole box gets one, which would take the picture
   * and the ring text down with it. So the picture must be on the disc, the
   * colour on its ::before, and the text at full strength regardless.
   */
  const layers = await page.evaluate(() => {
    const disc = document.querySelector('.ebdg__disc');
    const cs = getComputedStyle(disc);
    const wash = getComputedStyle(disc, '::before');
    return {
      picture: cs.backgroundImage,
      size: cs.backgroundSize,
      washOpacity: Number(wash.opacity),
      washImage: wash.backgroundImage,
      shadow: cs.boxShadow,
      textOpacity: Number(getComputedStyle(document.querySelector('.ebdg__ring text')).opacity),
    };
  });

  check('a background picture fills the disc under the colour',
    /url\(/.test(layers.picture) && layers.size === 'cover',
    'picture set, ' + layers.size);

  check('the colour is a layer of its own, so it can be faded over it',
    layers.washOpacity > 0 && layers.washOpacity < 1 && /gradient/.test(layers.washImage),
    'wash at ' + layers.washOpacity + ' opacity');

  check('and fading it does not fade the ring text',
    layers.textOpacity === 1,
    'text at ' + layers.textOpacity);

  check('the inner shadow is cast inside the disc, alongside the ring and the drop',
    /inset/.test(layers.shadow) && layers.shadow.split('inset').length === 2 &&
      layers.shadow.split('rgb').length >= 4,
    layers.shadow.slice(0, 110) + '...');

  // --- it turns --------------------------------------------------------------
  const turning = await page.evaluate(async () => {
    const seen = [];
    for (let i = 0; i < 8; i++) {
      seen.push(window.__angles());
      await new Promise(r => setTimeout(r, 90));
    }
    return seen;
  });
  check('the ring turns on its own', new Set(turning).size >= 6,
    new Set(turning).size + ' distinct angles over 8 frames');

  check('and it is the Web Animations one doing it, not the stylesheet',
    await page.evaluate(() => document.querySelector('.ebdg').hasAttribute('data-ebdg-ready') &&
      getComputedStyle(document.querySelector('.ebdg__ring')).animationName === 'none'),
    'badge ready, CSS animation off');

  // --- it slows to a stop rather than stopping dead --------------------------
  /*
   * The whole reason this widget has a script. Sampled every 60ms across the
   * hover: the angle has to keep changing for a while and by less each time,
   * then settle. A badge that jammed would go from a full step to nothing
   * between two samples.
   */
  const stopping = await page.evaluate(async () => {
    const el = document.querySelector('.ebdg');
    const read = () => {
      const a = /matrix\(([^)]+)\)/.exec(window.__angles());
      const n = a[1].split(',').map(Number);
      return (Math.atan2(n[1], n[0]) * 180) / Math.PI;
    };
    el.dispatchEvent(new MouseEvent('mouseenter'));
    const seen = [];
    for (let i = 0; i < 20; i++) {
      seen.push(read());
      await new Promise(r => setTimeout(r, 60));
    }
    return seen;
  });

  const steps = stopping.slice(1).map((a, i) => {
    let d = a - stopping[i];
    while (d > 180) d -= 360;
    while (d < -180) d += 360;
    return Math.abs(d);
  });
  const early = steps.slice(0, 3).reduce((a, b) => a + b, 0) / 3;
  const late = steps.slice(-4).reduce((a, b) => a + b, 0) / 4;

  check('hovering slows the ring down',
    early > late * 3 && early > 0.2,
    'moving ' + early.toFixed(2) + ' deg per frame at first, ' + late.toFixed(3) + ' at the end');

  check('and it comes to a full stop',
    late < 0.05,
    'final frames moved ' + late.toFixed(4) + ' deg');

  check('it took time to stop rather than stopping on the spot',
    steps.findIndex(s => s < 0.05) > 3,
    'still moving ' + steps.findIndex(s => s < 0.05) + ' frames in');

  // --- and starts again ------------------------------------------------------
  const restart = await page.evaluate(async () => {
    const el = document.querySelector('.ebdg');
    el.dispatchEvent(new MouseEvent('mouseleave'));
    await new Promise(r => setTimeout(r, 900));
    const seen = [];
    for (let i = 0; i < 5; i++) {
      seen.push(window.__angles());
      await new Promise(r => setTimeout(r, 80));
    }
    return new Set(seen).size;
  });
  check('taking the pointer away starts it again', restart >= 4,
    restart + ' distinct angles over 5 frames');

  // --- the lift --------------------------------------------------------------
  const lift = await page.evaluate(() => getComputedStyle(document.querySelector('.ebdg')).transform);
  await page.hover('.ebdg');
  await page.evaluate(() => new Promise(r => setTimeout(r, 800)));
  const lifted = await page.evaluate(() => ({
    badge: getComputedStyle(document.querySelector('.ebdg')).transform,
    centre: getComputedStyle(document.querySelector('.ebdg__centre')).transform,
  }));
  const y = (t) => {
    const m = /matrix\(([^)]+)\)/.exec(t || '');
    return m ? Number(m[1].split(',')[5]) : 0;
  };
  check('hovering lifts the badge', y(lifted.badge) <= -8,
    'moved ' + y(lifted.badge) + 'px, from ' + (lift === 'none' ? '0' : y(lift)));

  check('and the middle grows a little',
    lifted.centre !== 'none' && lifted.centre !== 'matrix(1, 0, 0, 1, 0, 0)',
    lifted.centre);

  // --- keyboard --------------------------------------------------------------
  const keyboard = await page.evaluate(async () => {
    const el = document.querySelector('.ebdg');
    el.focus();
    await new Promise(r => setTimeout(r, 900));
    return {
      focused: document.activeElement === el,
      href: el.getAttribute('href'),
      label: el.getAttribute('aria-label'),
      ringHidden: document.querySelector('.ebdg__ring').getAttribute('aria-hidden'),
    };
  });
  check('it is a real link, reachable and labelled',
    keyboard.focused && !!keyboard.href && !!keyboard.label && keyboard.ringHidden === 'true',
    'label "' + keyboard.label + '", ring hidden from screen readers');

  // --- reduced motion --------------------------------------------------------
  await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await page.goto(URL, { waitUntil: 'load' });
  await page.evaluate(() => new Promise(r => setTimeout(r, 400)));
  const calm = await page.evaluate(async () => {
    const seen = [];
    for (let i = 0; i < 5; i++) {
      seen.push(window.__angles());
      await new Promise(r => setTimeout(r, 80));
    }
    return {
      still: new Set(seen).size === 1,
      ready: document.querySelector('.ebdg').hasAttribute('data-ebdg-ready'),
      css: getComputedStyle(document.querySelector('.ebdg__ring')).animationName,
      fitted: Number(document.querySelector('.ebdg textPath').getAttribute('textLength')) > 0,
    };
  });
  check('reduced motion gets a badge that does not turn at all',
    calm.still && !calm.ready && calm.css === 'none' && calm.fitted,
    'still=' + calm.still + ', css animation ' + calm.css + ', ring still fitted');

  // --- report ----------------------------------------------------------------
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
