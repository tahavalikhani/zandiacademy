// Renders the navy ground (ground.png) and the title's frames (t_00000.png…,
// transparent) for a sample-lesson video.
//
//   node tools/video/preview/render-title.js CONFIG.json OUT_DIR
//
// See docs/video-editing.md §9.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
  const [cfgPath, out] = process.argv.slice(2);
  const cfg = JSON.parse(fs.readFileSync(cfgPath, 'utf8'));
  fs.mkdirSync(out, { recursive: true });
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1280, height: 720 }, deviceScaleFactor: 1 });
  const url = 'file://' + path.resolve(__dirname, 'title.html');

  await page.goto(url);
  await page.evaluate(() => ground());
  await page.screenshot({ path: path.join(out, 'ground.png') });

  await page.goto(url);
  await page.evaluate(async () => {
    await Promise.all(['700', '900'].map(w => document.fonts.load(`${w} 40px Peyda`, 'سلام A1')));
    await document.fonts.ready;
  });
  if (!(await page.evaluate(() => document.fonts.check('900 40px Peyda')))) throw new Error('Peyda did not load');
  const t = cfg.title;
  await page.evaluate(a => build(...a), [t.eyebrow, t.heading, t.level]);
  const n = Math.round(t.duration * 30);
  for (let f = 0; f < n; f++) {
    await page.evaluate(([x, o]) => seek(x, o), [f / 30, t.out]);
    await page.screenshot({ path: path.join(out, `t_${String(f).padStart(5, '0')}.png`), omitBackground: true });
  }
  await browser.close();
  console.log(`title: ${n} frames`);
})().catch(e => { console.error(e); process.exit(1); });
