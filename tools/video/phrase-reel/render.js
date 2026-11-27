// Renders one phrase reel to JPEG frames, or a few stills to look at first.
//
//   node tools/video/phrase-reel/render.js REEL.json VOICE_DIR OUT_DIR plan
//   node tools/video/phrase-reel/render.js REEL.json VOICE_DIR OUT_DIR stills 1.5,5.5,34
//   node tools/video/phrase-reel/render.js REEL.json VOICE_DIR OUT_DIR frames FROM TO
//
// VOICE_DIR is split.py's output (voice.json + one WAV per phrase), or «-»
// for none: the slots then come from the spec. «plan» writes OUT_DIR/plan.json
// for audio.py. Frames are a pure function of time, so make.sh runs several
// «frames» ranges in parallel.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
  const [reel, voiceDir, out, mode, a, b] = process.argv.slice(2);
  fs.mkdirSync(out, { recursive: true });
  const spec = JSON.parse(fs.readFileSync(reel, 'utf8'));
  const voice = voiceDir && voiceDir !== '-' ? JSON.parse(fs.readFileSync(path.join(voiceDir, 'voice.json'), 'utf8')).clips : null;
  if (voice && voice.length !== spec.phrases.length) throw new Error(`${voice.length} voice clips for ${spec.phrases.length} phrases`);
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1080, height: 1920 }, deviceScaleFactor: 1 });
  await page.goto('file://' + path.resolve(__dirname, 'reel.html'));
  await page.evaluate(async () => {
    await Promise.all([
      ...['400', '700', '900'].map(w => document.fonts.load(`${w} 40px Peyda`, 'سلام')),
      document.fonts.load('700 40px Playfair', 'Bonjour ça'),
      document.fonts.load('italic 700 40px Playfair', 'Bonjour ça'),
    ]);
    await document.fonts.ready;
  });
  for (const f of ['900 40px Peyda', '700 40px Playfair', 'italic 700 40px Playfair']) {
    if (!(await page.evaluate(x => document.fonts.check(x), f))) throw new Error(`font did not load: ${f}`);
  }
  const plan = await page.evaluate(([s, v]) => build(s, v), [spec, voice]);

  if (mode === 'plan') {
    fs.writeFileSync(path.join(out, 'plan.json'), JSON.stringify(plan, null, 1));
    console.log(`${spec.slug}: ${plan.duration.toFixed(2)}s, ${plan.voice.length} voice clips, ${plan.sfx.length} effects`);
  } else if (mode === 'stills') {
    for (const t of a.split(',').map(Number)) {
      await page.evaluate(x => seek(x), t);
      await page.screenshot({ path: path.join(out, `still_${t}.png`) });
    }
  } else {
    const n = Math.round(plan.duration * plan.fps);
    const from = +(a || 0), to = Math.min(n, +(b || n));
    for (let f = from; f < to; f++) {
      await page.evaluate(x => seek(x), f / plan.fps);
      await page.screenshot({ path: path.join(out, `f_${String(f).padStart(5, '0')}.jpg`), type: 'jpeg', quality: 93 });
      if ((f - from) % 300 === 0) console.log(`${spec.slug} frame ${f}/${to}`);
    }
  }
  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
