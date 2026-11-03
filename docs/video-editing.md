# Course video editing — the house style

The owner records the course intro and sample videos herself, cuts them, and
hands them to a Claude session to finish. This file is how that session knows
what «finished» means. It was written on 2 November 2026 from the A1 intro the
owner chose as the reference — the edit an earlier session made, which she
liked — because the earlier session's method was never saved and could not be
repeated. **Read all of it before touching a video.**

The reference frames are in [`docs/video-style/`](video-style/). Open them.
Words describe this style; the pictures *are* it.

| File | What it shows |
| --- | --- |
| `reference-1.webp` | Name card · A1 badge · split-screen card · chip stack |
| `reference-2.webp` | Speech bubble · key-line caption · chip stack · ۲۴/۷ stat |
| `reference-3.webp` | Navy chip with eyebrow · split card opening · checklist · end card |
| `motion-badge.webp` | The A1 badge building in, 1/8 s per frame |
| `motion-split.webp` | The split-screen card, eyebrow first, then the heading |
| `timeline-1.webp`, `timeline-2.webp` | The whole reference, one frame per ½ s |

---

## 1. What the owner asked for

Her words, 2 November 2026, for the B1 main intro and every video after it:

- **The footage is already cut.** Do not re-edit the talking. Trim nothing
  unless she asks.
- **Add sound effects, and keep them quiet.** «not very loud».
- **A lot of motion graphics, but it must not feel crowded.** Both halves are
  the instruction. §5 is how the reference manages both.
- **The Persian on screen must be exactly what she says.** An earlier edit put
  text on screen that differed from her words and had spelling mistakes. That
  is the failure this whole file exists to prevent — §3 is not optional.

---

## 2. Delivery format

| | |
| --- | --- |
| Container / codecs | **MP4, H.264 (High) + AAC.** Not HEVC: the reference arrived as HEVC because the phone re-encoded it, and HEVC does not play in Firefox or in Chrome on many Windows machines. |
| Resolution / fps | **Output 1920×1080** whatever the source, at the source's fps. The graphics are rasterised natively at 1080 (`"output"` in the timeline) and each video frame is resampled once, Lanczos, straight from the source pixels. The first B1 edit went out at 720p and the owner called it «trash» (3 November 2026): the source was already a soft 720p phone copy, 12% punch-ins enlarged it further, and 720p text blurred on a laptop. |
| Video settings | `-c:v libx264 -preset slow -crf 18 -tune film -x264-params aq-mode=3 -pix_fmt yuv420p -profile:v high -movflags +faststart` — about 4.7 Mbps. **Never above CRF 18**: CRF 22 came out at less than half the source's bitrate and banded the navy gradient. Punch-ins stay at 1.06 or less on a 720p source. |
| Audio | `-c:a aac -b:a 128k`, voice normalised to **−16 LUFS integrated, −1.5 dBTP** (the reference measured −15.8 / −16.2). |
| `+faststart` | Required. The page uses `preload="none"`; without the index at the front the browser must fetch the end of the file before the first frame. |
| File name | `course-{slug}-{kind}.mp4` — `course-b1-intro.mp4`, `course-conversation-a2-sample.mp4`. That name is the whole publishing step (CLAUDE.md, «Course videos are self-hosted»). |
| Poster | Already in the theme for all twelve (`assets/images/course-{slug}-{kind}.webp`). Do not make a new one unless asked. |
| Never | Commit the video. It goes to the owner via `SendUserFile`, and she uploads it through رسانه ← افزودن. |

After she uploads: if a page still says «به‌زودی», LiteSpeed is serving an old
copy — LiteSpeed Cache ← جعبه ابزار ← «پاکسازی همه». If she is *replacing* a
video, the old attachment should be deleted permanently (including from the
trash) first.

---

**Ask for the original file first.** A phone's share sheet re-encodes video to
720p before it is uploaded (the files arrive named `copy_….mov`), and nothing in
the edit can put back detail that copy threw away. On iPhone: Photos → the
video → Share → «Save to Files», then upload from Files; split it into parts if
it is too big to upload, and `ffmpeg -f concat` joins them losslessly.

---

## 3. The transcript — exact words, confirmed by the owner, before any caption

This is the step that went wrong before. The order is fixed:

1. **Draft** with `tools/video/transcribe.py` (setup in its header — Whisper
   turbo via sherpa-onnx, downloaded from GitHub because huggingface.co is
   blocked from the session). It prints two passes offset by half a chunk.
2. **Correct the draft by reading both passes together.** Whisper is a draft,
   not an answer. On the reference it wrote «زبط» for ضبط, «سبتان»/«سفر تا» for
   ثبت‌نام, «مهوره» for محوره, «آی ایک» for A1, «فرانسایت» for فرانسه, and it drops
   words at chunk edges. Claude cannot hear the audio; it can only reconcile
   the two passes and the context. Anything still uncertain is marked `[؟]`.
3. **Send the owner the full transcript and wait for her confirmation.** In
   Persian, numbered by line, with timestamps, `[؟]` on every doubt. She is the
   only source of truth for what she said. **No caption is drawn from an
   unconfirmed transcript**, however confident the draft looks.
   **She may waive this for a video** — she did for the B1 intro on
   2 November 2026 («I don't need to confirm the transcribe… you do everything
   and send tomorrow»). Then: quote *less* (prefer labels and short verbatim
   fragments over long quotes), never quote a phrase the draft did not settle,
   say in the transcript file that it is unconfirmed, and send the transcript
   with the finished video so she can check both together.
4. **Every on-screen string is checked against the confirmed transcript by
   script**, not by eye (§7).

### How on-screen text relates to speech

There are two kinds of text and they follow different rules.

- **Quotes** — the speech bubble, the key-line caption, the closing line. These
  are her words, **verbatim, in her colloquial spelling**: «آفلاینه»,
  «می‌تونی», «مناسب توئه اگه…», «روی ماهتونو می‌بینم». Never formalise
  («می‌توانی») and never paraphrase. A quote may be a contiguous *part* of a
  sentence; it may not join words she did not say together.
- **Labels** — chips, badges, headings («دوره پایه», «پشتیبانی ۲۴/۷», «ویدیوهای
  کوتاه», «جلسه آنلاین تصویری با خودم»). Short names for what she is saying
  *at that moment*. They should reuse her own words wherever possible (every
  label in the reference does), and **must never state anything she does not
  say** — no invented features, numbers or promises (CLAUDE.md, «Facts only»).

Spelling rules for both: ZWNJ in «می‌», «نمی‌», «‌ها», «ثبت‌نام», «مکالمه‌محور»;
Persian «ی» and «ک», never Arabic ي/ك; Persian digits («۲۴/۷», «۳۰ روز»);
CEFR codes stay Latin («A1», «B1») and never take Persian digits.

**Course facts matter here too.** The reference is a *main* course, which has
support and an end-of-course session — so it shows «۲۴/۷» and «جلسه آنلاین».
The conversation courses have neither (CLAUDE.md). Never carry a graphic from
one video to another without hearing it said in that video.

### Worked example — the reference, as confirmed text vs. what was on screen

| She says | On screen |
| --- | --- |
| سلام، من شیما زندی هستم | name card «شیما زندی» + «مدرس و بنیان‌گذار آکادمی · ساکن پاریس» |
| …در مورد دوره آفلاین سطح A1 باهاتون صحبت کنم | badge «دوره پایه» / «A1.» / «آفلاین · ویدیویی» |
| این دوره آفلاینه و آنلاین نیست، یعنی ویدیوها از قبل ضبط شده و … بعد از ثبت‌نام … دسترسی داشته باشین | split card: eyebrow «این دوره چطوریه؟», heading «آفلاینه، نه آنلاین», ✓ «ویدیوها از قبل ضبط شده», ✓ «بعد از ثبت‌نام، راحت بهشون دسترسی داری» |
| فضای آموزش مکالمه‌محوره | chips «فضای آموزش» → «مکالمه‌محور» |
| اما به این معنی نیست که ما روی چهار مهارت تمرکز نداریم یا گرامر تدریس نمی‌شه | ✓ «چهار مهارت», ✓ «گرامر» added to the stack |
| به این معنی هست که آخر دوره نمی‌گی «وای، من که نمی‌تونم صحبت کنم!» | caption «به این معنیه که آخر دوره نمی‌گی…», then bubble «وای، من که نمی‌تونم صحبت کنم!», struck through, chip «آخر این دوره نه» |
| …بتونی احساس خوبی نسبت به آموزش زبان فرانسه داشته باشی | caption with «احساس خوبی» in bold navy |
| زمان هر ویدیو کوتاهه … هر زمان و هر مکان … آموزش ببینی، تکرار و تمرین کنی | stack «ویدیوهای کوتاه» → «هر زمان، هر مکان» → «آموزش ببین» → «تکرار و تمرین کن» |
| دوره پشتیبانی ۲۴ ساعته و ۷ روز هفته، فنی و آموزشی داره | stat «پشتیبانی» / «۲۴/۷» / «فنی» «آموزشی» |
| آخر دوره یه جلسه آنلاین تصویری با خودم داری | eyebrow «آخر دوره» + navy chip «جلسه آنلاین تصویری با خودم» |
| این دوره برای زبان‌آموزهایی هست که کاملاً سطح صفر … حتی اگه قبلاً یه کوچولو فرانسه خوندی و الان کلاً یادت رفته | split card: «این دوره برای کیه؟» / «مناسب توئه اگه…» / ✓ «از صفر شروع می‌کنی» / ✓ «یه کوچولو خوندی ولی یادت رفته» |
| مرسی از اعتمادتون، روی ماهتونو می‌بینم | end card + closing caption, verbatim |

(The left column is reconstructed from the Whisper draft and the edit itself,
not confirmed by the owner — it is here to show the *mapping*, not as a source
of her words.)

---

## 4. Visual language

Everything is the site's own palette and type. No new colours, no stock
templates, no emoji, no flags, no Eiffel Tower graphics (there is a real one
behind her — that is enough).

| | |
| --- | --- |
| Navy | `#1B365D` — pills, headings on white, the split-screen ground (a vertical gradient, `#1B365D` at the top to about `#0F2140` at the bottom) |
| White | `#FFFFFF` — chips, the card border, the rings, type on navy |
| Red | `#C8102E` — **the dot only**: after «A1», on the name card's corner. Never a fill, never text (CLAUDE.md, «Red is only ever the dot»). |
| Type | **Peyda**, from `assets/fonts/peyda/*.woff2`. Black/Bold for headings and the badge, SemiBold for chips, Regular for small eyebrows. Latin («A1», the URL) in Peyda too — it carries a Latin set. |
| Shapes | Fully rounded pills (`border-radius: 999px`) for chips; ~18px-radius cards; the split-screen video card has ~24px corners and a 3px white border (at 1280 wide). Soft navy-tinted shadow, never black. |
| Icons | Thin line icons, same weight as the site's: ✓, clock, play-in-circle, refresh, video camera, speech bubble. Sit on the reading-end (left) side of the label. |

### The component kit (all in the reference)

1. **Name card** — navy pill «شیما زندی» in Bold, red dot on its upper-left
   corner, a white chip under it «مدرس و بنیان‌گذار آکادمی · ساکن پاریس». To her
   right (screen right of centre), in the first seconds. Every video opens with
   it.
2. **Level badge** — a huge white Latin «A1» with the red dot as its full stop,
   a navy pill above («دوره پایه» for A1 · «دوره متوسط» A2 · «دوره پیشرفته» B1 —
   these are the site's own `eyebrow` names in `inc/courses.php`), a small white
   chip below («آفلاین · ویدیویی»). Behind it, thin white concentric rings that
   expand slowly outward. Left third of the frame.
3. **Split-screen card** — the scene for a list of two or three points. The
   video shrinks into a rounded card on the right (~45% of the width, white
   border) over the navy gradient with faint rings in the top-left corner. On
   the left: a white eyebrow chip asking the question («این دوره چطوریه؟»,
   «این دوره برای کیه؟»), a large white Bold heading answering it, then white
   ✓ pills with navy text, one per point, each appearing as she says it. The
   reference uses it twice in ~68 s; do not use it more than about once every
   30 s.
4. **Chip stack** — top-left, a column of pills that grows as she lists things:
   a navy eyebrow pill, then white or navy pills with an icon. It holds while
   she is on that topic and clears as a group.
5. **Speech bubble + strike** — a white bubble, tilted 2–3°, with the
   imagined sentence verbatim and «!». When she says «not this», a navy line
   draws across it right-to-left and a small chip appears under it
   («آخر این دوره نه»). For «you won't feel X» moments.
6. **Key-line caption** — bottom-centre white pill, one sentence, one or two
   words in Bold navy. **Not continuous subtitles**: the reference uses it for
   the two or three lines that matter most.
7. **Big stat** — a number in a navy box in very large Persian digits
   («۲۴/۷»), eyebrow above, small chips below, rings behind.
8. **Eyebrow + navy chip** — a small white eyebrow («آخر دوره») over a navy pill
   with an icon («جلسه آنلاین تصویری با خودم»). For a single fact.
9. **End card** — a white rounded card with the ز/Z mark (as drawn by
   `zandi_logo()` — navy strokes, red dot), «آکادمی زندی», «FRANÇAIS» spaced
   underneath, and a white chip «zandiacademy.com» below it. Builds while she
   says goodbye, with her closing line as a key-line caption.

---

## 5. Density — «a lot, but never crowded»

How the reference does both, measured from its timeline:

- **Something new appears every 2–5 seconds.** Across ~68 s there are about
  25 separate graphic events. That is the «a lot».
- **Only one group is on screen at a time.** A group (stack, badge, card,
  bubble) builds item by item, holds, and leaves completely before the next
  group arrives. Never two groups in two corners. That is the «not crowded».
- **Gaps are allowed.** 1–3 s of clean frame between groups lets the picture
  breathe; the reference has one about every 10 s.
- **Never over her face or hands' main area.** Graphics live in the left third
  (she sits centre-right) or, for the name card, just right of her. The
  split-screen card is the only time the frame itself changes.
- **Text is short.** Chips are 1–5 words. The longest line in the reference is
  the caption «بتونی احساس خوبی نسبت به آموزش زبان فرانسه داشته باشی».
- **Each item appears on the word it names** (±0.1 s), from the transcript
  timestamps. A chip that arrives before she says it reads as a slide deck.

### Camera

The footage is jump-cut. Between cuts the frame alternates scale — 100% and
~108% punch-in — so a cut reads as a camera change rather than a skip. Within
a long take, a very slow push-in (100% → 104% over the take) keeps it alive.
No shake, no rotation, no zoom that moves her face toward an edge.

---

## 6. Motion and sound

### Motion

| Move | Timing |
| --- | --- |
| Item in | scale 0.85 → 1.0 with a small overshoot (ease-out-back), plus fade, **300 ms** |
| Stagger within a group | 120–150 ms, or on the spoken word if later |
| Group out | fade + scale to 0.95, **200 ms**, all items together |
| Split-screen in/out | the full frame scales and slides to the card position, **500 ms** ease-in-out; the card's content follows 150 ms later |
| Rings | continuous slow expansion and fade, ~2.5 s per ring, 3 rings offset |
| Strike-through | drawn right to left, 350 ms |
| Badge number | rises 20 px and fades in, the red dot lands last with a small pop |

Nothing bounces more than once. Nothing spins. RTL throughout: items enter
from the right, lists build downward, the strike runs right to left.

### Sound effects — quiet

The reference has **no music** (the gaps between phrases measure −58 dBFS:
voice only) — keep it that way unless the owner asks for music. Sound effects
are new, from her 2 November 2026 instruction.

| Event | Sound |
| --- | --- |
| Chip / pill in | a soft, short pop or tick (~60 ms) |
| Group out | nothing |
| Split-screen in/out | a soft airy whoosh (~400 ms) |
| Level badge, big stat, end card | a gentle bright chime (~600 ms) |
| Strike-through | a light swipe |

- **Level: peaks 18–22 dB under the voice** (about −34 to −38 dBFS with voice
  at −16 LUFS). If a sound is noticeable *as a sound* on laptop speakers, it is
  too loud. Never louder than her voice at any frame.
- No sound on every item when items come fast: at most one effect per ~0.4 s.
- Stock libraries (freesound, pixabay, mixkit) are unreachable from the session,
  so effects are **synthesised with ffmpeg / numpy** — filtered noise for
  whooshes, short sine/triangle blips with fast decay for pops, two detuned
  sines with a long tail for chimes. No licensing question arises.

---

## 7. Pipeline and checks

The whole pipeline is in `tools/video/` and was used for the B1 intro. One
timeline JSON per video, in `tools/video/timelines/`, with its transcript
beside it — `course-b1-intro.json` is the worked example to copy. Run, with
`$S` a scratch directory and `$M` the models directory from transcribe.py:

```
python3 tools/video/transcribe.py $M audio.wav               # draft transcript
python3 tools/video/check_text.py  tools/video/timelines/X.json  # must print 0 failures
node    tools/video/render-overlay.js tools/video/timelines/X.json $S/ov 2.5,14.6   # stills
python3 tools/video/compose.py tools/video/timelines/X.json SRC.mov $S/ov $S/comp --stills 2.5,14.6
node    tools/video/render-overlay.js tools/video/timelines/X.json $S/ov           # all frames
python3 tools/video/sfx.py     tools/video/timelines/X.json SRC.mov $S/mix.wav
python3 tools/video/compose.py tools/video/timelines/X.json SRC.mov $S/ov $S/X.mp4 --audio $S/mix.wav
```

For 97 s at 720p the overlay render takes ~35 s and the composite ~1 min.
The timeline holds the camera (cut times from
`ffmpeg -vf "select='gt(scene,0.08)',showinfo"`, a zoom per shot), the
split-screen windows, and the groups; every item has an `at` time taken from
the transcript, every group one `out`. Item kinds and how they look are all in
`overlay.html`; sounds per kind are in `sfx.py`.

Two traps already hit, so they are fixed in the files: the overlay document is
LTR with RTL on the items (an RTL document turned the rings' off-frame overflow
into a scroll and pushed every right-anchored element ~150 px off screen), and
a caption is `inline-block` (as a flex row its bold word got gaps either side).

- **Overlays are HTML/CSS, rendered with the session's Playwright Chromium** to
  transparent PNG frames, then composited with ffmpeg. HTML because Persian
  shaping, ZWNJ, bidi with Latin codes and Peyda all come right for free in a
  browser; ffmpeg's `drawtext` has harfbuzz here but gets mixed-direction runs
  wrong easily. Use the repo's Peyda woff2 — the owner also has desktop TTFs,
  which are not needed and are **not** committed.
- One JSON timeline per video (`start`, `end`, component, text, position), with
  every text field copied from the confirmed transcript.
- **Text check, by script, before rendering:** every *quote* string must occur
  in the confirmed transcript after normalising whitespace and ZWNJ; every
  string is checked for Arabic ي/ك, Latin digits in Persian text, and a missing
  ZWNJ after «می»/«نمی». A failure stops the render.
- **Before sending:** a ½-second contact sheet of the whole output, read frame
  by frame — face never covered, nothing clipped at the frame edge, one group
  at a time; `ebur128` loudness of the final mix; `ffprobe` shows h264 + aac
  and the `moov` atom first.
- Send the MP4 plus the contact sheet. If the file is over ~50 MB, say so and
  lower the CRF one step at a time rather than the resolution.

Scratch work (frames, renders, models) lives in the session scratchpad, never
in the repo.

---

## 8. Instagram stories from lesson clips

Added 3 November 2026, when the owner sent five مکالمه A1 lesson previews and
asked for 9:16 stories «with motion», upbeat music and sound effects, «be
creative». The pipeline is `tools/video/stories/`; the five clips' crops are
in `clips-conversation-a1.json`.

**The source shape.** Each clip is a screen recording: her camera on the
left, a slide on the right, a black band under the slide. Shown whole in 9:16
the slide text is unreadable on a phone, so a story **re-lays it out**: the
slide alone in a 1000×760 card at the top, her camera as a 400×500 card
beside it, overlapping its lower edge, with a «شیما زندی» name pill. Crops are
per clip, in source pixels; pad a crop into the slide's own margin rather
than cut a line of text.

**Sixty seconds per story file.** Instagram splits a longer upload into
segments wherever 60 s falls, which can cut a clip mid-sentence. So a story
that plays several clips is delivered as parts that break *between* clips
(story 1 is `1a` + `1b`); part two reopens on the same screen with the
finished clips ticked.

**What is on screen.** Labels only, and no quotes: «نمونه تدریس», «دوره
مکالمه A1», the slide's own French («La ville · Le village», «Aller chez le
coiffeur», copied from the slide), «شیما زندی», the end card with the ز/Z mark,
«آکادمی زندی», «ثبت‌نام» and «zandiacademy.com». Nothing she says is put on
screen, so no transcript is needed. If a later story quotes her, §3 applies
in full. Red is still only the dot. The end card leaves the band from about
y = 1400 to 1650 empty, so the owner can put Instagram's link sticker there.

**Sound.** Unlike the course videos, these have music, because the owner
asked for it. `audio.py` synthesises a 120 BPM loop, because no stock library
is reachable. The bed drops 17 dB and the melody drops out while she speaks.
Voice is at −16 LUFS. Effects peak 22–27 dB below full scale.

```
python3 tools/video/stories/prep.py tools/video/stories/clips-conversation-a1.json SRC_DIR $S/clips
node    tools/video/stories/render.js 1a $S/clips $S/w stills 2,4.4,30      # look first
tools/video/stories/make.sh 1a $S/clips $S/w1a $S/story-1a.mp4             # ~2 min for 60 s
```

A story is a function in `STORIES` in `story.html`, built from modules
(`TitleIntro`, `VersusIntro`, `TypeIntro`, `Header`, `Card`, `Pip`, `Rows`,
`Brand`, `EndCard`). Every frame is a pure function of `t`, which is what
lets `make.sh` render four ranges in parallel.
