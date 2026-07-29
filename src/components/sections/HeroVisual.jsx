import { motion, useReducedMotion } from 'framer-motion';
import { EASE } from '../../lib/motion';
import { toPersianDigits } from '../../lib/format';
import Icon from '../ui/Icon';
import Rating from '../ui/Rating';

const levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

/**
 * A card that drifts over the panel.
 *
 * Two nested elements on purpose: the outer one plays the one-shot entrance and
 * the inner one loops the drift. On a single element the entrance `animate`
 * would replace the looping one and the drift would never run.
 */
function FloatCard({ className, from = { y: 16 }, delay = 0, duration = 7, distance = 8, children }) {
  const prefersReducedMotion = useReducedMotion();

  return (
    <motion.div
      initial={prefersReducedMotion ? false : { opacity: 0, ...from }}
      whileInView={{ opacity: 1, x: 0, y: 0 }}
      viewport={{ once: true }}
      transition={{ duration: 0.7, ease: EASE, delay }}
      className={className}
    >
      <motion.div
        animate={prefersReducedMotion ? undefined : { y: [0, -distance, 0] }}
        transition={{ duration, repeat: Infinity, ease: 'easeInOut', delay }}
      >
        {children}
      </motion.div>
    </motion.div>
  );
}

/**
 * Hero artwork.
 *
 * A navy panel engraved with concentric guilloché arcs — the geometry of French
 * banknotes and Sèvres porcelain rather than a landmark — with product-style
 * cards floating over it. Entirely SVG and DOM, so it costs no image bytes and
 * stays sharp at every density.
 */
export default function HeroVisual() {
  const prefersReducedMotion = useReducedMotion();

  return (
    <div className="relative mx-auto w-full max-w-[34rem] lg:max-w-none">
      {/* Ambient wash behind the panel */}
      <div
        aria-hidden="true"
        className="absolute -inset-8 -z-10 rounded-[3rem] bg-[radial-gradient(60%_60%_at_70%_25%,rgba(27,54,93,0.12),transparent_70%),radial-gradient(45%_45%_at_20%_80%,rgba(200,16,46,0.07),transparent_70%)] blur-2xl"
      />

      <motion.div
        initial={prefersReducedMotion ? false : { opacity: 0, scale: 0.97, y: 24 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        transition={{ duration: 0.8, ease: EASE, delay: 0.15 }}
        className="relative aspect-[4/5] w-full overflow-hidden rounded-[2rem] bg-gradient-to-bl from-navy-600 via-navy-700 to-navy-900 shadow-lift sm:aspect-[5/5]"
      >
        {/* Guilloché engraving */}
        <svg
          viewBox="0 0 400 460"
          preserveAspectRatio="xMidYMid slice"
          aria-hidden="true"
          className="absolute inset-0 h-full w-full"
        >
          <g fill="none" stroke="white" strokeOpacity="0.14">
            {[44, 76, 110, 146, 184, 224, 266].map((r) => (
              <circle key={r} cx="320" cy="70" r={r} />
            ))}
          </g>
          <g fill="none" stroke="white" strokeOpacity="0.09">
            {[30, 58, 90, 126, 166].map((r) => (
              <circle key={r} cx="60" cy="400" r={r} />
            ))}
          </g>
          <path d="M0 330 C 110 292, 210 372, 400 288 L400 460 L0 460 Z" fill="white" fillOpacity="0.05" />
          <path d="M0 372 C 120 336, 240 408, 400 340 L400 460 L0 460 Z" fill="white" fillOpacity="0.04" />
        </svg>

        {/* Tricolore mark — the flag as three quiet rules, not a graphic.
            Sits on the top edge, clear of the floating cards. */}
        <div
          aria-hidden="true"
          className="absolute top-0 start-7 h-1.5 w-24 overflow-hidden rounded-b-pill sm:start-9"
        >
          {/* `dir` belongs on the inner row, not the positioned box: `start-*`
              resolves against the element's own direction, so setting it above
              would flip the mark to the opposite edge of the panel. */}
          <div dir="ltr" className="flex h-full w-full">
            <span className="flex-1 bg-navy-200" />
            <span className="flex-1 bg-white" />
            <span className="flex-1 bg-rouge-500" />
          </div>
        </div>

        <div className="relative flex h-full flex-col justify-between p-7 sm:p-9">
          <div className="flex flex-col gap-2" dir="ltr">
            <span className="text-[0.7rem] font-semibold uppercase tracking-[0.32em] text-white/50">
              Académie Zandi
            </span>
            <p className="text-3xl font-bold leading-tight text-white sm:text-4xl">Bonjour&nbsp;!</p>
            <p className="text-sm text-white/60">On commence&nbsp;?</p>
          </div>

          {/* CEFR ladder */}
          <div className="flex flex-col gap-3" dir="ltr">
            <div className="flex items-center gap-1.5">
              {levels.map((level, index) => (
                <span
                  key={level}
                  className={
                    index <= 2
                      ? 'flex-1 rounded-pill bg-white/90 py-1.5 text-center text-[0.7rem] font-bold text-navy-700'
                      : 'flex-1 rounded-pill bg-white/10 py-1.5 text-center text-[0.7rem] font-semibold text-white/45'
                  }
                >
                  {level}
                </span>
              ))}
            </div>
            <p dir="rtl" className="text-xs text-white/55">
              مسیر استاندارد اروپایی، از پایه تا تسلط کامل
            </p>
          </div>
        </div>
      </motion.div>

      {/* Floating card — weekly progress */}
      <FloatCard
        from={{ x: 20, y: 10 }}
        delay={0.5}
        duration={6.5}
        className="absolute start-0 top-36 w-[13.5rem] rounded-card border border-navy-100/80 bg-white/95 p-4 shadow-lift backdrop-blur sm:-start-8 sm:top-16"
      >
        <div className="flex items-center justify-between gap-2">
          <span className="text-xs font-semibold text-navy-600">پیشرفت این ترم</span>
          <span className="text-xs font-bold text-success">{toPersianDigits('۸۲٪')}</span>
        </div>
        <div className="mt-3 h-2 overflow-hidden rounded-pill bg-navy-50">
          <motion.span
            initial={prefersReducedMotion ? false : { width: 0 }}
            whileInView={{ width: '82%' }}
            viewport={{ once: true }}
            transition={{ duration: 1.2, ease: EASE, delay: 0.8 }}
            className="block h-full rounded-pill bg-gradient-to-l from-navy-700 to-navy-500"
          />
        </div>
        <p className="mt-3 text-[0.7rem] leading-relaxed text-navy-500">
          سطح A2 — آماده ورود به B1
        </p>
      </FloatCard>

      {/* Floating card — live class */}
      <FloatCard
        from={{ x: -20, y: 10 }}
        delay={0.65}
        duration={8}
        distance={10}
        className="absolute bottom-24 end-0 hidden w-[12.5rem] sm:block rounded-card border border-navy-100/80 bg-white/95 p-4 shadow-lift backdrop-blur sm:-end-7"
      >
        <div className="flex items-center gap-2">
          <span className="relative flex h-2.5 w-2.5">
            <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-success opacity-60" />
            <span className="relative inline-flex h-2.5 w-2.5 rounded-full bg-success" />
          </span>
          <span className="text-xs font-semibold text-navy-700">کلاس مکالمه زنده</span>
        </div>
        <p className="mt-2 text-[0.7rem] text-navy-500">۵ زبان‌آموز · استاد سمیه زندی</p>
        <div className="mt-3 flex items-center gap-2">
          <Icon name="chat" className="h-4 w-4 text-navy-400" />
          <span className="text-[0.7rem] text-navy-500">۴۵ دقیقه گفت‌وگو</span>
        </div>
      </FloatCard>

      {/* Floating card — rating */}
      <FloatCard
        from={{ y: 24 }}
        delay={0.8}
        duration={7.5}
        distance={7}
        className="absolute -bottom-6 start-6 sm:start-12"
      >
        <div className="flex items-center gap-3 rounded-pill border border-navy-100/80 bg-white/95 py-3 pe-5 ps-4 shadow-lift backdrop-blur">
          <Rating value={5} />
          <span className="text-xs font-semibold text-navy-700">{toPersianDigits('۴٫۹ از ۵')}</span>
        </div>
      </FloatCard>
    </div>
  );
}
