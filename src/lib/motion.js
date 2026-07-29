/**
 * Shared Framer Motion variants.
 *
 * The whole site uses the same two gestures — a short fade-up and a small scale —
 * so motion reads as one language instead of a collection of effects.
 */

export const EASE = [0.22, 1, 0.36, 1];

/** Fade up: the default entrance for any block of content. */
export const fadeUp = {
  hidden: { opacity: 0, y: 20 },
  visible: {
    opacity: 1,
    y: 0,
    transition: { duration: 0.55, ease: EASE },
  },
};

/** Fade in with a barely-there scale — for cards and media. */
export const fadeScale = {
  hidden: { opacity: 0, y: 16, scale: 0.98 },
  visible: {
    opacity: 1,
    y: 0,
    scale: 1,
    transition: { duration: 0.5, ease: EASE },
  },
};

/** Parent wrapper that walks its children in one at a time. */
export const stagger = (staggerChildren = 0.08, delayChildren = 0) => ({
  hidden: {},
  visible: {
    transition: { staggerChildren, delayChildren },
  },
});

/** Viewport config used by every scroll-triggered section. */
export const viewportOnce = { once: true, amount: 0.2, margin: '0px 0px -80px 0px' };
