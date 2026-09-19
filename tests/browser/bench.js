/**
 * Measures what the animations cost, in real Chrome.
 *
 * See README.md in this directory. Needs puppeteer-core, which is NOT a
 * plugin dependency -- tests/ is excluded from the release zip.
 *
 * Every widget is on one page at once, because that is the case that matters:
 * a section that animates is cheap on its own and expensive in company.
 *
 * What it reports, and why each one:
 *
 *   frame budget   a frame has 16.7ms at 60Hz. What counts is not the average
 *                  -- an average hides every stall that anyone notices -- but
 *                  how many frames went over, and how bad the worst were.
 *   long tasks     anything over 50ms, which is the point at which a tap stops
 *                  feeling instant.
 *   forced layout  reading a geometric property after writing a style makes
 *                  the browser lay the page out again on the spot. Doing it
 *                  once per frame per widget is the usual reason a scroll
 *                  animation is slower than it looks.
 *
 *   node tests/browser/bench.js
 */
const puppeteer = require('puppeteer-core');

const URL = 'http://127.0.0.1:8732/tests/browser/bench.html';
const FRAMES = 240;

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
  await page.evaluate(() => new Promise(r => setTimeout(r, 600)));

  // Count the calls that force the browser to lay the page out mid-frame.
  await page.evaluate(() => {
    window.__reads = 0;
    const count = () => { window.__reads += 1; };
    const el = Element.prototype;
    const raw = el.getBoundingClientRect;
    el.getBoundingClientRect = function () { count(); return raw.apply(this, arguments); };
    for (const prop of ['offsetHeight', 'offsetWidth', 'offsetTop', 'offsetLeft', 'scrollWidth', 'scrollLeft']) {
      const d = Object.getOwnPropertyDescriptor(el, prop) ||
        Object.getOwnPropertyDescriptor(HTMLElement.prototype, prop);
      if (!d || !d.get) continue;
      Object.defineProperty(el, prop, { ...d, get() { count(); return d.get.call(this); } });
    }
    window.__styles = 0;
    const gcs = window.getComputedStyle;
    window.getComputedStyle = function () { window.__styles += 1; return gcs.apply(this, arguments); };
  });

  /*
   * Chrome's own counters, not frame durations.
   *
   * Headless paces requestAnimationFrame at 30Hz, so every frame reads as
   * 33.3ms whether the work took one millisecond or fifteen, and "frames over
   * budget" is meaningless here. Layout and style recalculation counts are
   * not paced by anything: they go up exactly as often as the code forces
   * them, which is the thing being optimised.
   */
  const cdp = await page.target().createCDPSession();
  await cdp.send('Performance.enable');
  const before = Object.fromEntries(
    (await cdp.send('Performance.getMetrics')).metrics.map(m => [m.name, m.value])
  );

  const result = await page.evaluate(async (FRAMES) => {
    const longs = [];
    if (typeof PerformanceObserver === 'function') {
      try {
        new PerformanceObserver(list => {
          list.getEntries().forEach(e => longs.push(Math.round(e.duration)));
        }).observe({ entryTypes: ['longtask'] });
      } catch (e) { /* not everywhere */ }
    }

    const max = document.body.scrollHeight - innerHeight;
    const step = max / FRAMES;

    window.__reads = 0;
    window.__styles = 0;

    const frames = [];
    let last = performance.now();
    let y = 0;

    await new Promise(done => {
      function tick(now) {
        frames.push(now - last);
        last = now;
        y += step;
        window.scrollTo(0, y);
        if (frames.length < FRAMES) {
          requestAnimationFrame(tick);
        } else {
          done();
        }
      }
      requestAnimationFrame(tick);
    });

    // The first frame is the scheduling gap, not a frame.
    const f = frames.slice(1).sort((a, b) => a - b);
    const at = q => f[Math.min(f.length - 1, Math.floor(f.length * q))];

    return {
      frames: f.length,
      median: +at(0.5).toFixed(2),
      p95: +at(0.95).toFixed(2),
      worst: +f[f.length - 1].toFixed(2),
      over: f.filter(d => d > 16.7).length,
      way_over: f.filter(d => d > 33).length,
      longTasks: longs,
      reads: window.__reads,
      styles: window.__styles,
      perFrameReads: +(window.__reads / f.length).toFixed(1),
      perFrameStyles: +(window.__styles / f.length).toFixed(1),
    };
  }, FRAMES);

  const after = Object.fromEntries(
    (await cdp.send('Performance.getMetrics')).metrics.map(m => [m.name, m.value])
  );
  const since = k => +( ( after[k] || 0 ) - ( before[k] || 0 ) ).toFixed(3);

  const line = (k, v) => console.log('  ' + k.padEnd(28) + v);
  console.log('\nScrolling the whole page over ' + result.frames + ' frames\n');
  line('median frame', result.median + 'ms');
  line('95th percentile', result.p95 + 'ms');
  line('worst frame', result.worst + 'ms');
  line('frames over 16.7ms', result.over + '  (' + Math.round(100 * result.over / result.frames) + '%)');
  line('frames over 33ms', result.way_over);
  line('long tasks (>50ms)', result.longTasks.length + (result.longTasks.length ? '  ' + result.longTasks.join(', ') + 'ms' : ''));
  console.log('');
  line('layout reads', result.reads + '  (' + result.perFrameReads + ' per frame)');
  line('getComputedStyle calls', result.styles + '  (' + result.perFrameStyles + ' per frame)');
  console.log('');
  const ms = s => (s * 1000).toFixed(1) + 'ms';
  line('script time', ms(since('ScriptDuration')));
  line('style recalculations', since('RecalcStyleCount') + '  (' + ms(since('RecalcStyleDuration')) + ')');
  line('layouts', since('LayoutCount') + '  (' + ms(since('LayoutDuration')) + ')');
  line('total work', ms(since('ScriptDuration') + since('RecalcStyleDuration') + since('LayoutDuration')));

  if (errs.length) console.log('\nPAGE ERRORS:\n  ' + errs.join('\n  '));
  console.log('');

  await browser.close();
})();
