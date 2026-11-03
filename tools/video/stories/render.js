// Renders one story to JPEG frames, or a few stills to look at first.
//
//   node tools/video/stories/render.js STORY CLIPS_DIR OUT_DIR plan
//   node tools/video/stories/render.js STORY CLIPS_DIR OUT_DIR stills 1.2,4.5,30
//   node tools/video/stories/render.js STORY CLIPS_DIR OUT_DIR frames FROM TO
//
// CLIPS_DIR is prep.py's output. «plan» writes OUT_DIR/plan.json (duration,
// when each clip plays, every sound effect) for audio.py. Frames are a pure
// function of time, so make.sh runs several «frames» ranges in parallel.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
  const [story, clipsDir, out, mode, a, b] = process.argv.slice(2);
  fs.mkdirSync(out, { recursive: true });
  const metas = {};
  for (const id of fs.readdirSync(clipsDir)) {
    const m = path.join(clipsDir, id, 'meta.json');
    if (fs.existsSync(m)) metas[id] = JSON.parse(fs.readFileSync(m, 'utf8'));
  }
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1080, height: 1920 }, deviceScaleFactor: 1 });
  await page.goto('file://' + path.resolve(__dirname, 'story.html'));
  await page.evaluate(async () => {
    await Promise.all(['400', '600', '700', '900'].map(w => document.fonts.load(`${w} 40px Peyda`, 'سلام A1')));
    await document.fonts.ready;
  });
  if (!(await page.evaluate(() => document.fonts.check('900 40px Peyda')))) throw new Error('Peyda did not load — check assets/fonts/peyda/');
  const plan = await page.evaluate(([s, base, m]) => build(s, base, m), [story, 'file://' + path.resolve(clipsDir), metas]);

  if (mode === 'plan') {
    fs.writeFileSync(path.join(out, 'plan.json'), JSON.stringify(plan, null, 1));
    console.log(`${story}: ${plan.duration}s, ${plan.sfx.length} effects`);
  } else if (mode === 'stills') {
    for (const t of a.split(',').map(Number)) {
      await page.evaluate(x => seek(x), t);
      await page.screenshot({ path: path.join(out, `still_${story}_${t}.jpg`), type: 'jpeg', quality: 88 });
    }
  } else {
    const n = Math.round(plan.duration * plan.fps);
    const from = +(a || 0), to = Math.min(n, +(b || n));
    for (let f = from; f < to; f++) {
      await page.evaluate(x => seek(x), f / plan.fps);
      await page.screenshot({ path: path.join(out, `f_${String(f).padStart(5, '0')}.jpg`), type: 'jpeg', quality: 93 });
      if ((f - from) % 300 === 0) console.log(`${story} frame ${f}/${to}`);
    }
  }
  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
