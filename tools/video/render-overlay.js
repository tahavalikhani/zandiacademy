// Renders the motion-graphics layer of a course video to transparent PNGs.
//
//   node tools/video/render-overlay.js TIMELINE.json OUTDIR [t1,t2,...]
//
// Writes OUTDIR/under.png (the navy ground of the split-screen scenes),
// OUTDIR/o_NNNNN.png for every frame whose overlay changed, and
// OUTDIR/manifest.json mapping each frame to its PNG (or null when empty), so
// held frames are not screenshotted twice. With a third argument it renders
// only those times, as still_<t>.png, for reviewing a layout quickly.
// Uses the session's preinstalled Playwright Chromium. See docs/video-editing.md.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
  const [tlPath, out, stills] = process.argv.slice(2);
  const tl = JSON.parse(fs.readFileSync(tlPath, 'utf8'));
  fs.mkdirSync(out, { recursive: true });
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: tl.size[0], height: tl.size[1] }, deviceScaleFactor: (tl.output || tl.size)[0] / tl.size[0] });
  // The page is laid out at tl.size and rasterised at tl.output: text and
  // shapes are drawn natively at the output resolution, never upscaled.
  await page.goto('file://' + path.resolve(__dirname, 'overlay.html'));

  await page.evaluate(() => buildUnder());
  await page.evaluate(() => document.fonts.ready);
  await page.screenshot({ path: path.join(out, 'under.png') });

  await page.evaluate(t => { document.body.className = ''; build(t); }, tl);
  await page.evaluate(async () => {
    await Promise.all(['200', '400', '600', '700', '900'].map(w => document.fonts.load(`${w} 20px Peyda`)));
    await document.fonts.ready;
  });
  const missing = await page.evaluate(() => !document.fonts.check('900 20px Peyda'));
  if (missing) throw new Error('Peyda did not load — check assets/fonts/peyda/');
  // Re-run build now that the fonts are in, so ring positions use real metrics.
  await page.evaluate(t => build(t), tl);

  if (stills) {
    for (const t of stills.split(',').map(Number)) {
      await page.evaluate(x => seek(x), t);
      await page.screenshot({ path: path.join(out, `still_${t}.png`), omitBackground: true });
    }
    await browser.close();
    return;
  }

  const fps = tl.fps, n = Math.round(tl.duration * fps);
  const manifest = [];
  let last = null, lastFile = null, shots = 0;
  for (let f = 0; f < n; f++) {
    const sig = await page.evaluate(x => seek(x), f / fps);
    const empty = sig.startsWith('0|');
    if (empty) { manifest.push(null); last = null; continue; }
    if (sig !== last) {
      lastFile = `o_${String(f).padStart(5, '0')}.png`;
      await page.screenshot({ path: path.join(out, lastFile), omitBackground: true });
      last = sig; shots++;
    }
    manifest.push(lastFile);
    if (f % 240 === 0) console.log(`frame ${f}/${n} (${shots} screenshots)`);
  }
  fs.writeFileSync(path.join(out, 'manifest.json'), JSON.stringify(manifest));
  console.log(`done: ${n} frames, ${shots} screenshots`);
  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
