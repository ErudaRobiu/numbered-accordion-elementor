/**
 * Measures the Assessment Form in real Chrome.
 *
 * The server is faked: every POST to the assessment route is answered here,
 * so running this never saves a request or sends an email.
 *
 *   php tests/browser/assess-fixture.php > tests/browser/assess.html
 *   node tests/browser/assess-probe.js [screenshot-dir]
 */
const puppeteer = require('puppeteer-core');

const PASS = [];
const FAIL = [];
const URL = 'http://127.0.0.1:8732/tests/browser/assess.html';
const SHOTS = process.argv[2] || '';
const sleep = ms => new Promise(r => setTimeout(r, ms));
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

function check(name, ok, detail) {
  (ok ? PASS : FAIL).push(name + (detail !== undefined && detail !== '' ? '  [' + detail + ']' : ''));
}

const ESTIMATE = { mmbtu: [1300, 2000], dollars: [15000, 22000], tco2: [85.4, 128.1], hours: 4160, price: 0.9 };

async function open(browser, viewport, reply) {
  const page = await browser.newPage();
  const errs = [];
  const posts = [];
  page.on('pageerror', e => errs.push(String(e)));
  await page.setViewport(viewport);
  await page.setRequestInterception(true);
  page.on('request', r => {
    if (r.url().includes('/wp-json/eruda/v1/assessment')) {
      posts.push(JSON.parse(r.postData() || '{}'));
      const out = reply(posts.length);
      if (out === 'abort') return r.abort();
      return r.respond({ status: out.status, contentType: 'application/json', body: JSON.stringify(out.body) });
    }
    r.continue();
  });
  await page.goto(URL, { waitUntil: 'networkidle0' });
  await page.evaluate(() => document.fonts.ready);
  return { page, errs, posts };
}

const state = sel => page => page.evaluate(sel => {
  const r = document.querySelector(sel);
  const d = r.closest('dialog');
  const vis = [...r.querySelectorAll('.eas__step')].find(s => !s.hidden);
  return {
    open: d ? d.open : null,
    locked: document.documentElement.classList.contains('eas-locked'),
    step: vis ? +vis.dataset.step : 0,
    result: !r.querySelector('.eas__result').hidden,
    focus: document.activeElement && (document.activeElement.name || document.activeElement.className || document.activeElement.tagName),
    errors: [...r.querySelectorAll('.eas__err')].filter(e => !e.hidden).map(e => e.id.replace(/^eas-[a-z0-9]+-/, '').replace(/-err$/, '')),
    status: r.querySelector('.eas__status').hidden ? '' : r.querySelector('.eas__status').textContent,
  };
}, sel);

(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'] });
  const M = state('.eas-dialog .eas');

  /* ------------------------------------------------ desktop, happy path --- */

  let { page, errs, posts } = await open(browser, { width: 1440, height: 900 }, () => ({ status: 200, body: { ok: true, estimate: ESTIMATE } }));
  let s = await M(page);
  check('pop-up starts closed', s.open === false, JSON.stringify(s));

  await page.click('#btn1 a');
  await sleep(350);
  s = await M(page);
  const nav = await page.evaluate(() => window.__navigated.length);
  check('a .ts-assess button opens it instead of navigating, even with a page-transition script taking link clicks', s.open === true && s.locked && page.url() === URL && nav === 0, page.url() + ' navigations:' + nav);
  check('opens on step 1', s.step === 1);
  const look = await page.evaluate(() => {
    const n = document.querySelector('.eas-dialog [data-eas-next]');
    const c = getComputedStyle(n);
    const i = document.querySelector('.eas-dialog .eas__range');
    return { nextBg: c.backgroundImage.slice(0, 15), nextColor: c.color, range: getComputedStyle(i).appearance, out: document.querySelector('.eas-dialog output[for$="-temp"]').textContent };
  });
  check('Hello Elementor pink and lazy-loaded section backgrounds do not reach the buttons', /gradient/.test(look.nextBg) && look.nextColor === 'rgb(255, 255, 255)', JSON.stringify(look));
  const home = await page.evaluate(() => document.querySelector('.eas-dialog').parentNode === document.body && !document.querySelector('#modal .eas-dialog'));
  check('the pop-up moves itself out of its section to the end of the page', home);
  check('slider readouts start at the typical values', look.out === '230°F', look.out);
  if (SHOTS) await page.screenshot({ path: `${SHOTS}/assess-1440-step1.png` });

  await page.focus('.eas-dialog input[name="temp"]');
  for (let i = 0; i < 70; i++) await page.keyboard.press('ArrowRight');
  const t = await page.evaluate(() => [document.querySelector('.eas-dialog output[for$="-temp"]').textContent, getComputedStyle(document.querySelector('.eas-dialog input[name="temp"]')).getPropertyValue('--p')]);
  check('slider moves by keyboard; readout and fill follow', t[0] === '300°F' && parseFloat(t[1]) > 55, t.join(' '));
  await page.click('.eas-dialog input[name="days"][value="7"] + span');

  await page.click('.eas-dialog [data-eas-next]');
  await sleep(200);
  s = await M(page);
  check('Next goes to step 2 and focuses its title', s.step === 2 && /eas__step-title/.test(s.focus), s.focus);
  await page.click('.eas-dialog [data-eas-next]');
  await sleep(100);
  s = await M(page);
  check('step 2 needs an industry: error shown, focus on it, stays put', s.step === 2 && s.errors.includes('industry') && s.focus === 'industry', JSON.stringify(s.errors));
  await page.select('.eas-dialog select[name="industry"]', 'industrial-laundry');
  await page.click('.eas-dialog input[value="lint"] + span');
  await page.click('.eas-dialog input[value="humidity"] + span');
  await page.click('.eas-dialog input[value="process-water"] + span');
  s = await M(page);
  check('the error clears once fixed', !s.errors.includes('industry'), JSON.stringify(s.errors));
  if (SHOTS) await page.screenshot({ path: `${SHOTS}/assess-1440-step2.png` });

  await page.click('.eas-dialog [data-eas-back]');
  await sleep(100);
  const kept = await page.evaluate(() => document.querySelector('.eas-dialog output[for$="-temp"]').textContent);
  check('Back keeps what was entered', kept === '300°F', kept);
  await page.click('.eas-dialog [data-eas-next]');
  await sleep(150);
  await page.click('.eas-dialog [data-eas-next]');
  await sleep(150);

  await page.click('.eas-dialog [data-eas-send]');
  await sleep(100);
  s = await M(page);
  check('step 3 checks name, email and company before sending', s.step === 3 && ['first', 'last', 'email', 'company'].every(k => s.errors.includes(k)) && posts.length === 0 && s.focus === 'first', JSON.stringify(s.errors));
  await page.type('.eas-dialog input[name="first"]', 'Dana');
  await page.type('.eas-dialog input[name="last"]', 'Reyes');
  await page.type('.eas-dialog input[name="email"]', 'dana@example');
  await page.type('.eas-dialog input[name="company"]', 'Acme Laundry');
  await page.click('.eas-dialog [data-eas-send]');
  s = await M(page);
  check('a malformed email is caught', s.errors.includes('email') && posts.length === 0, JSON.stringify(s.errors));
  await page.type('.eas-dialog input[name="email"]', '.com');
  await page.type('.eas-dialog input[name="location"]', 'Laval, QC');
  if (SHOTS) await page.screenshot({ path: `${SHOTS}/assess-1440-step3.png` });
  await sleep(3200);
  await page.click('.eas-dialog [data-eas-send]');
  await sleep(500);
  const p0 = posts[0] || {};
  check('sends one request with every answer', posts.length === 1 && p0.temp === 300 && p0.days === 7 && p0.industry === 'industrial-laundry' && p0.contaminants.join() === 'lint,humidity' && p0.uses.join() === 'process-water' && p0.email === 'dana@example.com' && p0.location === 'Laval, QC', JSON.stringify(p0).slice(0, 220));
  check('sends which widget it is, the page, and time on form', p0.doc === 42 && p0.el === 'mod1' && /assess\.html/.test(p0.source) && p0.elapsed > 3000 && p0.website === '', `${p0.doc} ${p0.el} ${p0.elapsed}`);
  s = await M(page);
  const res = await page.evaluate(() => ['dollars', 'mmbtu', 'tco2', 'hours'].map(k => document.querySelector('.eas-dialog [data-r="' + k + '"]').textContent));
  check('shows the estimate: dollars, heat, CO2, hours', s.result && res[0] === '$15,000–$22,000' && res[1] === '1,300–2,000 MMBtu/yr' && res[2] === '85.4–128.1 t/yr' && res[3] === '4,160 h/yr', res.join(' | '));
  check('focus moves to the result', /eas__result/.test(s.focus), s.focus);
  const basis = await page.evaluate(() => document.querySelector('.eas-dialog [data-r="basis"]').textContent);
  check('the assumptions are stated under it', /40% to 60%/.test(basis) && /\$0\.90 per therm/.test(basis), basis.slice(0, 80));
  if (SHOTS) await page.screenshot({ path: `${SHOTS}/assess-1440-result.png` });

  await page.click('.eas-dialog .eas__result [data-eas-close]');
  await sleep(200);
  s = await M(page);
  check('Done closes it and unlocks the page', s.open === false && !s.locked, JSON.stringify(s));
  await page.click('#btn2');
  await sleep(300);
  s = await M(page);
  check('a link ending #request opens it too, fresh at step 1', s.open && s.step === 1 && !s.result, JSON.stringify(s));
  await page.keyboard.press('Escape');
  await sleep(200);
  s = await M(page);
  check('Escape closes it', s.open === false && !s.locked);
  await page.click('#btn1 a');
  await sleep(300);
  await page.mouse.click(8, 450);
  await sleep(200);
  s = await M(page);
  check('clicking the backdrop closes it', s.open === false);
  await page.click('#plain');
  await sleep(200);
  s = await M(page);
  check('ordinary links are left alone', s.open === false && page.url().endsWith('#plain-target'), page.url());
  check('no script errors (desktop)', errs.length === 0, errs.join(' | '));
  await page.close();

  /* ------------------------------------------ server says a field is off --- */

  ({ page, errs, posts } = await open(browser, { width: 1440, height: 900 }, n => (n === 1 ? { status: 422, body: { ok: false, errors: { industry: 'Choose your industry.' } } } : 'abort')));
  const fill = async () => {
    await page.click('#btn1 a'); await sleep(300);
    await page.click('.eas-dialog [data-eas-next]');
    await page.select('.eas-dialog select[name="industry"]', 'foundries');
    await page.click('.eas-dialog [data-eas-next]');
    for (const [k, v] of [['first', 'A'], ['last', 'B'], ['email', 'a@b.co'], ['company', 'C']]) {
      await page.$eval('.eas-dialog input[name="' + k + '"]', (el, v) => { el.value = v; }, v);
    }
    await sleep(3100);
    await page.click('.eas-dialog [data-eas-send]');
    await sleep(500);
  };
  await fill();
  s = await M(page);
  check('a server field error takes the visitor back to that step', s.step === 2 && s.errors.includes('industry') && s.focus === 'industry', JSON.stringify(s));
  await page.click('.eas-dialog [data-eas-next]');
  await page.click('.eas-dialog [data-eas-send]');
  await sleep(500);
  s = await M(page);
  check('a network failure says so and offers email and phone', s.step === 3 && /solutions@thermstar\.com/.test(s.status) && /833 667 7359/.test(s.status), s.status);
  const btn = await page.evaluate(() => [document.querySelector('.eas-dialog [data-eas-send]').textContent, document.querySelector('.eas-dialog [data-eas-send]').getAttribute('aria-busy')]);
  check('the send button recovers', btn[0] === 'Get my estimate' && btn[1] === null, btn.join(' '));
  check('no script errors (errors path)', errs.length === 0, errs.join(' | '));
  await page.close();

  /* ------------------------------------------------------------- phone --- */

  ({ page, errs, posts } = await open(browser, { width: 390, height: 844, isMobile: true, hasTouch: true }, () => ({ status: 200, body: { ok: true, estimate: ESTIMATE } })));
  await page.tap('#btn1 a');
  await sleep(400);
  const ph = await page.evaluate(() => {
    const d = document.querySelector('.eas-dialog');
    const r = d.getBoundingClientRect();
    const nav = document.querySelector('.eas-dialog .eas__nav').getBoundingClientRect();
    return { w: Math.round(r.width), h: Math.round(r.height), scroll: document.documentElement.scrollWidth, inner: d.scrollWidth <= d.clientWidth + 1, navBottom: Math.round(nav.bottom) };
  });
  check('phone: full screen, nothing wider than it', ph.w === 390 && ph.h === 844 && ph.inner && ph.scroll === 390, JSON.stringify(ph));
  check('phone: the buttons stay in reach at the bottom', ph.navBottom <= 844, ph.navBottom);
  if (SHOTS) await page.screenshot({ path: `${SHOTS}/assess-390-step1.png` });
  await page.tap('.eas-dialog [data-eas-next]');
  await page.select('.eas-dialog select[name="industry"]', 'manufacturing');
  await page.tap('.eas-dialog [data-eas-next]');
  await sleep(300);
  const grid = await page.evaluate(() => new Set([...document.querySelectorAll('.eas-dialog .eas__grid .eas__field')].map(f => Math.round(f.getBoundingClientRect().left))).size);
  check('phone: contact fields in one column', grid === 1, grid);
  if (SHOTS) await page.screenshot({ path: `${SHOTS}/assess-390-step3.png` });
  check('no script errors (phone)', errs.length === 0, errs.join(' | '));
  await page.close();

  /* ------------------------------------------------------------ inline --- */

  ({ page, errs, posts } = await open(browser, { width: 1100, height: 900 }, () => ({ status: 200, body: { ok: true, estimate: ESTIMATE } })));
  const inl = await page.evaluate(() => ({ visible: document.querySelector('#inline .eas').getBoundingClientRect().height > 300, dialog: !!document.querySelector('#inline dialog'), close: !!document.querySelector('#inline .eas__close') }));
  check('inline: shows on the page, no dialog, no close button', inl.visible && !inl.dialog && !inl.close, JSON.stringify(inl));
  await page.click('#inline [data-eas-next]');
  await sleep(150);
  const both = await page.evaluate(() => [document.querySelector('#inline .eas__step:not([hidden])').dataset.step, document.querySelector('.eas-dialog').open]);
  check('inline steps independently of the pop-up', both[0] === '2' && both[1] === false, both.join(' '));
  check('no script errors (inline)', errs.length === 0, errs.join(' | '));
  await page.close();

  await browser.close();
  console.log(PASS.map(p => 'PASS  ' + p).join('\n'));
  console.log(FAIL.map(f => 'FAIL  ' + f).join('\n'));
  console.log(`\n${PASS.length} passed, ${FAIL.length} failed`);
  process.exit(FAIL.length ? 1 : 0);
})();
