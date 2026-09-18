/**
 * Measures the Hotspot Stats figure in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 * Headless on purpose, for the same reason as the other probes: Chrome does
 * not run requestAnimationFrame, and does not fire an IntersectionObserver,
 * in a tab that is not visible.
 *
 *   node tests/browser/spots-probe.js
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail ? '  [' + detail + ']' : ''));
}

const URL = 'http://127.0.0.1:8732/tests/browser/spots.html';

(async () => {
  const browser = await puppeteer.launch({
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: 'new', args: ['--no-sandbox'],
  });

  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 900 });
  const errs = [];
  page.on('pageerror', e => errs.push(String(e)));

  await page.goto(URL, { waitUntil: 'load' });
  await page.evaluate(() => new Promise(r => setTimeout(r, 250)));

  // --- one leader per hotspot, actually joining the two ends ---------------
  /*
   * A leader is only doing its job if it starts on the label's own edge and
   * finishes on the dot. Both are read from the rendered boxes rather than
   * from the percentages they were authored as, so an error anywhere between
   * the two -- the percentage, the viewBox, the frame's size -- shows up here.
   */
  const leaders = await page.evaluate(() => {
    const frame = document.querySelector('.espot__frame');
    const box = frame.getBoundingClientRect();
    const items = [...document.querySelectorAll('.espot__item')];
    const paths = [...document.querySelectorAll('.espot__lines path')];
    return {
      items: items.length,
      paths: paths.length,
      joins: items.map((item, i) => {
        const p = paths[i];
        if (!p) return null;
        const d = p.getAttribute('d') || '';
        const pts = (d.match(/-?[\d.]+,-?[\d.]+/g) || []).map(s => s.split(',').map(Number));
        if (pts.length < 2) return null;
        const start = pts[0];
        const end = pts[pts.length - 1];
        const dot = item.querySelector('.espot__dot').getBoundingClientRect();
        const label = item.querySelector('.espot__label').getBoundingClientRect();
        const side = item.getAttribute('data-espot-side');
        const wantStartX = (side === 'left' ? label.left : label.right) - box.left;
        const wantStartY = label.top - box.top + label.height / 2;
        const wantEndX = dot.left - box.left + dot.width / 2;
        const wantEndY = dot.top - box.top + dot.height / 2;
        return {
          startOff: Math.max(Math.abs(start[0] - wantStartX), Math.abs(start[1] - wantStartY)),
          endOff: Math.max(Math.abs(end[0] - wantEndX), Math.abs(end[1] - wantEndY)),
          len: p.getTotalLength(),
        };
      }),
    };
  });

  check('every hotspot gets a leader',
    leaders.paths === leaders.items && leaders.joins.every(Boolean),
    leaders.paths + ' leaders for ' + leaders.items + ' hotspots');

  check('each leader starts on its label and ends on its dot',
    leaders.joins.every(j => j && j.startOff <= 1.5 && j.endOff <= 1.5),
    'worst gap: ' + Math.max(...leaders.joins.map(j => Math.max(j.startOff, j.endOff))).toFixed(2) + 'px');

  check('and none of them is a stub of nothing',
    leaders.joins.every(j => j.len > 40),
    'shortest ' + Math.min(...leaders.joins.map(j => j.len)).toFixed(0) + 'px');

  // --- they follow the picture when it resizes -----------------------------
  const before = await page.evaluate(() =>
    [...document.querySelectorAll('.espot__lines path')].map(p => p.getAttribute('d')));
  await page.setViewport({ width: 1100, height: 900 });
  await page.evaluate(() => new Promise(r => setTimeout(r, 400)));
  const after = await page.evaluate(() => {
    const frame = document.querySelector('.espot__frame');
    const box = frame.getBoundingClientRect();
    const items = [...document.querySelectorAll('.espot__item')];
    const paths = [...document.querySelectorAll('.espot__lines path')];
    return {
      ds: paths.map(p => p.getAttribute('d')),
      joins: items.map((item, i) => {
        const d = paths[i].getAttribute('d') || '';
        const pts = (d.match(/-?[\d.]+,-?[\d.]+/g) || []).map(s => s.split(',').map(Number));
        const end = pts[pts.length - 1];
        const dot = item.querySelector('.espot__dot').getBoundingClientRect();
        return Math.max(
          Math.abs(end[0] - (dot.left - box.left + dot.width / 2)),
          Math.abs(end[1] - (dot.top - box.top + dot.height / 2))
        );
      }),
    };
  });

  check('the leaders are rebuilt when the picture changes size',
    after.ds.every((d, i) => d !== before[i]),
    after.ds.filter((d, i) => d === before[i]).length + ' of ' + after.ds.length + ' unchanged');

  check('and still land on their dots at the new size',
    after.joins.every(j => j <= 1.5),
    'worst gap: ' + Math.max(...after.joins).toFixed(2) + 'px');

  await page.setViewport({ width: 1440, height: 900 });

  // --- the figures count -----------------------------------------------------
  await page.goto(URL, { waitUntil: 'load' });
  const counting = await page.evaluate(async () => {
    const values = () => [...document.querySelectorAll('.espot__value')].map(v => v.textContent.trim());
    const written = [...document.querySelectorAll('.espot__value')].map(v => v.getAttribute('data-espot-value'));
    const before = values();
    document.querySelector('.espot').scrollIntoView({ block: 'center' });
    const seen = [];
    for (let i = 0; i < 12; i++) {
      await new Promise(r => setTimeout(r, 60));
      seen.push(values()[0]);
    }
    await new Promise(r => setTimeout(r, 1600));
    return { before, written, seen, after: values() };
  });

  check('the figures count up rather than appearing',
    new Set(counting.seen).size > 3,
    counting.seen.slice(0, 6).join(' -> ') + ' ...');

  check('and land on exactly what was written, punctuation and all',
    counting.after.join('|') === counting.written.join('|'),
    counting.after.join('  '));

  check('a grouped figure keeps its comma while it counts',
    counting.after[4] === '1,240',
    'ended at ' + counting.after[4]);

  // --- what the markup says on its own ---------------------------------------
  const noScript = await page.evaluate(() => {
    const el = document.querySelector('.espot__value');
    return { written: el.getAttribute('data-espot-value'), inMarkup: el.getAttribute('data-espot-value') };
  });
  check('the final figure is in the markup, not only in the script',
    noScript.inMarkup === noScript.written && noScript.written.length > 0,
    'markup carries "' + noScript.inMarkup + '"');

  // --- hovering one dims the rest --------------------------------------------
  await page.evaluate(() => document.querySelector('.espot').scrollIntoView({ block: 'center' }));
  await page.evaluate(() => new Promise(r => setTimeout(r, 1800)));
  const rest = await page.evaluate(() =>
    [...document.querySelectorAll('.espot__item')].map(i => Number(getComputedStyle(i).opacity)));
  await page.hover('.espot__item:nth-child(2) .espot__label');
  await page.evaluate(() => new Promise(r => setTimeout(r, 600)));
  const hovered = await page.evaluate(() => ({
    all: [...document.querySelectorAll('.espot__item')].map(i => Number(getComputedStyle(i).opacity)),
    dot: getComputedStyle(document.querySelector('.espot__item:nth-child(2) .espot__dot')).transform,
  }));

  check('every hotspot is at full strength until one is hovered',
    rest.every(o => o > 0.99),
    rest.join(' '));

  check('hovering one dims the others and leaves it alone',
    hovered.all[1] > 0.99 && hovered.all.filter((o, i) => i !== 1).every(o => o < 0.5),
    hovered.all.map(o => o.toFixed(2)).join(' '));

  check('and its dot grows',
    hovered.dot !== 'none' && hovered.dot !== 'matrix(1, 0, 0, 1, 0, 0)',
    hovered.dot);

  // --- a phone gets a list, not a diagram ------------------------------------
  await page.setViewport({ width: 420, height: 900 });
  await page.goto(URL, { waitUntil: 'load' });
  await page.evaluate(() => new Promise(r => setTimeout(r, 300)));
  const narrow = await page.evaluate(() => {
    const list = document.querySelector('.espot__list');
    const item = document.querySelector('.espot__item');
    const label = document.querySelector('.espot__label');
    return {
      lines: getComputedStyle(document.querySelector('.espot__lines')).display,
      dot: getComputedStyle(document.querySelector('.espot__dot')).display,
      list: getComputedStyle(list).display,
      columns: getComputedStyle(list).gridTemplateColumns.split(' ').length,
      itemPos: getComputedStyle(item).position,
      labelPos: getComputedStyle(label).position,
      overflows: document.documentElement.scrollWidth > window.innerWidth + 1,
    };
  });
  check('a phone gets the figures as a list under the picture',
    narrow.lines === 'none' && narrow.dot === 'none' && narrow.list === 'grid' &&
    narrow.columns === 2 && narrow.itemPos === 'static' && narrow.labelPos === 'static',
    'lines ' + narrow.lines + ', list ' + narrow.list + ' in ' + narrow.columns + ' columns');

  check('and nothing runs off the side of it',
    !narrow.overflows,
    narrow.overflows ? 'the page scrolls sideways' : 'no sideways scroll');

  // --- reduced motion --------------------------------------------------------
  await page.setViewport({ width: 1440, height: 900 });
  await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await page.goto(URL, { waitUntil: 'load' });
  await page.evaluate(() => {
    document.querySelector('.espot').scrollIntoView({ block: 'center' });
  });
  await page.evaluate(() => new Promise(r => setTimeout(r, 400)));
  const calm = await page.evaluate(() => ({
    values: [...document.querySelectorAll('.espot__value')].map(v => v.textContent.trim()),
    written: [...document.querySelectorAll('.espot__value')].map(v => v.getAttribute('data-espot-value')),
    dash: getComputedStyle(document.querySelector('.espot__lines path')).strokeDashoffset,
    label: Number(getComputedStyle(document.querySelector('.espot__label')).opacity),
  }));
  check('reduced motion gets the finished figure, straight away',
    calm.values.join('|') === calm.written.join('|') && calm.label > 0.99 &&
    (calm.dash === '0px' || calm.dash === 'none' || calm.dash === '0'),
    'figures final, labels at ' + calm.label + ', dash offset ' + calm.dash);

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
