// Measures how long the first-visit preloader covers a live page.
// node tests/browser/preload-timing-probe.js <url> [runs] [profile: 4g|3g|none]
const puppeteer = require('puppeteer-core');
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const url = process.argv[2] || 'https://thermstar.com/';
const runs = +(process.argv[3] || 3);
const profile = process.argv[4] || '4g';
const local = process.argv.includes('--local'); // swap in this checkout's transitions.js
const LOCAL_JS = require('fs').readFileSync(__dirname + '/../../modules/transitions/assets/js/transitions.js', 'utf8');
const NET = {
  '4g': { latency: 170, downloadThroughput: 9e6 / 8, uploadThroughput: 1.5e6 / 8 },
  '3g': { latency: 560, downloadThroughput: 1.6e6 / 8, uploadThroughput: 750e3 / 8 },
};
(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'] });
  for (let r = 0; r < runs; r++) {
    const ctx = await browser.createBrowserContext();
    const page = await ctx.newPage();
    await page.setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1');
    await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 3 });
    if (local) {
      await page.setRequestInterception(true);
      page.on('request', req => /transitions\/assets\/js\/transitions\.js/.test(req.url())
        ? setTimeout(() => req.respond({ status: 200, contentType: 'application/javascript', body: LOCAL_JS }), 150)
        : req.continue());
    }
    const cdp = await page.createCDPSession();
    if (NET[profile]) { await cdp.send('Network.enable'); await cdp.send('Network.emulateNetworkConditions', { offline: false, ...NET[profile] }); }
    await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 });
    await page.evaluateOnNewDocument(() => {
      window.__t = {};
      const mark = k => { if (!window.__t[k]) window.__t[k] = Math.round(performance.now()); };
      document.addEventListener('DOMContentLoaded', () => mark('dcl'));
      window.addEventListener('load', () => mark('load'));
      const poll = () => {
        const c = document.documentElement ? document.documentElement.className : '';
        if (/etrn-preloading/.test(c)) mark('preloadOn');
        if (window.__t.preloadOn && !/etrn-preloading/.test(c)) mark('revealStart');
        if (/etrn-leaving/.test(c)) mark('leaving');
        if (window.__t.leaving && !/etrn-leaving/.test(c)) { mark('clear'); return; }
        setTimeout(poll, 10);
      };
      poll();
    });
    await page.goto(url, { waitUntil: 'load', timeout: 90000 }).catch(e => console.log('goto', e.message));
    await new Promise(r => setTimeout(r, 7000));
    const t = await page.evaluate(() => window.__t);
    const js = await page.evaluate(() => { const e = performance.getEntriesByType('resource').find(x => /transitions\.js/.test(x.name)); return e ? Math.round(e.responseEnd) : null; });
    console.log(JSON.stringify({ run: r + 1, profile, local, ...t, transitionsJsLoaded: js }));
    await ctx.close();
  }
  await browser.close();
})();
