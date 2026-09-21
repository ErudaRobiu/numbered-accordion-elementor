/**
 * Asserts that the Split Slab stacks.
 *
 * The slab is two panels beside a green bar, and below a certain width that
 * has to become one panel over the other with the bar lying between them.
 * It did not: the "weight of the light panel" control writes a rule carrying
 * the element id, which outranked the stylesheet's stacking rule, so the
 * slab held two columns down to a phone and the ink panel was cut off by the
 * slab's own overflow: hidden. Reading the stylesheet could not catch that --
 * the rule is correct, it just loses. Measuring catches it.
 *
 * So this probe injects what Elementor writes for that control before it
 * measures anything, which is the whole point of it.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 */
const puppeteer = require('puppeteer-core');

const URL = 'http://127.0.0.1:8732/tests/browser/explainer.html';

// The panel's control, as Elementor writes it on the page.
const PANEL = '.host .eexp{--eexp-split:56%;}';

const fails = [];

function check(ok, what) {
  console.log((ok ? '  ok   ' : '  FAIL ') + what);
  if (!ok) fails.push(what);
}

/**
 * Where the panels sit, and whether anything ran past its panel.
 *
 * @param {object} page   Puppeteer page.
 * @param {number} column Width to squeeze the host to, or 0 for the page's own.
 */
async function measure(page, column) {
  return page.evaluate((column) => {
    const host = document.querySelector('.host');

    if (column) {
      host.style.maxWidth = column + 'px';
    }

    const slab = host.querySelector('.eexp-slab');
    const light = slab.querySelector('.eexp-panel--light').getBoundingClientRect();
    const ink = slab.querySelector('.eexp-panel--ink').getBoundingClientRect();
    const bar = slab.querySelector('.eexp-bar').getBoundingClientRect();

    // Anything reaching past the panel it lives in is being clipped by the
    // slab, which is what an unstacked slab on a phone does to panel two.
    let clipped = 0;

    slab.querySelectorAll('.eexp-panel').forEach((panel) => {
      const box = panel.getBoundingClientRect();

      panel.querySelectorAll('*').forEach((el) => {
        const b = el.getBoundingClientRect();

        if (b.width && b.right > box.right + 1.5) {
          clipped++;
        }
      });
    });

    return {
      slab: Math.round(slab.getBoundingClientRect().width),
      light: { x: Math.round(light.x), w: Math.round(light.width) },
      ink: { x: Math.round(ink.x), w: Math.round(ink.width) },
      bar: { w: Math.round(bar.width), h: Math.round(bar.height) },
      stacked: Math.round(ink.y) >= Math.round(light.y + light.height) - 1,
      clipped,
      overflow: document.documentElement.scrollWidth > window.innerWidth,
    };
  }, column);
}

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: 'new', args: ['--no-sandbox'],
  });
  const page = await browser.newPage();

  const at = async (width, column) => {
    await page.setViewport({ width, height: 900 });
    await page.goto(URL, { waitUntil: 'load' });
    await page.addStyleTag({ content: PANEL });

    return measure(page, column || 0);
  };

  // Wide: two panels, sharing the width the way the control asked.
  const wide = await at(1440);
  console.log('=== 1440, full width');
  check(!wide.stacked, 'stays in two columns');
  check(Math.abs(wide.light.w / wide.slab - 0.56) < 0.02, 'the light panel keeps its 56%');
  check(wide.bar.h > wide.bar.w, 'the bar stands between the panels');
  check(!wide.clipped, 'nothing is clipped');

  // Narrow: one panel over the other, the bar lying between them.
  for (const width of [1100, 820, 390]) {
    const phone = await at(width);
    console.log('=== ' + width + ', full width');
    check(phone.stacked, 'the panels stack');
    check(phone.light.x === phone.ink.x, 'both panels start at the same edge');
    check(phone.light.w === phone.ink.w, 'both panels are the same width');
    check(phone.bar.w > phone.bar.h, 'the bar lies across, as a rule');
    check(!phone.clipped, 'nothing is clipped');
    check(!phone.overflow, 'the page does not scroll sideways');
  }

  // The case a media query cannot see: a wide window, a narrow column.
  const column = await at(1440, 600);
  console.log('=== 1440, in a 600px column');
  check(column.stacked, 'the panels stack on the slab\'s own width');
  check(!column.clipped, 'nothing is clipped');

  await browser.close();

  console.log(fails.length ? '\n' + fails.length + ' FAILED' : '\nall good');
  process.exit(fails.length ? 1 : 0);
})();
