import { motion, useReducedMotion } from 'framer-motion';
import { fadeScale, fadeUp, stagger, viewportOnce } from '../../lib/motion';

const presets = { fadeUp, fadeScale };

/**
 * Scroll-triggered entrance wrapper.
 *
 * Collapses to a plain element when the user prefers reduced motion, so content
 * is never hidden behind an animation that will not run.
 */
export default function Reveal({
  as = 'div',
  preset = 'fadeUp',
  delay = 0,
  className,
  children,
  ...rest
}) {
  const prefersReducedMotion = useReducedMotion();
  const Component = motion[as] ?? motion.div;

  if (prefersReducedMotion) {
    const Plain = as;
    return (
      <Plain className={className} {...rest}>
        {children}
      </Plain>
    );
  }

  return (
    <Component
      initial="hidden"
      whileInView="visible"
      viewport={viewportOnce}
      variants={presets[preset]}
      transition={{ delay }}
      className={className}
      {...rest}
    >
      {children}
    </Component>
  );
}

/**
 * Parent that reveals its `Reveal`-like children one after another.
 */
export function RevealGroup({
  as = 'div',
  step = 0.08,
  delayChildren = 0,
  className,
  children,
  ...rest
}) {
  const prefersReducedMotion = useReducedMotion();
  const Component = motion[as] ?? motion.div;

  if (prefersReducedMotion) {
    const Plain = as;
    return (
      <Plain className={className} {...rest}>
        {children}
      </Plain>
    );
  }

  return (
    <Component
      initial="hidden"
      whileInView="visible"
      viewport={viewportOnce}
      variants={stagger(step, delayChildren)}
      className={className}
      {...rest}
    >
      {children}
    </Component>
  );
}

/**
 * Child of `RevealGroup` — inherits the parent's stagger timing.
 */
export function RevealItem({ as = 'div', preset = 'fadeUp', className, children, ...rest }) {
  const prefersReducedMotion = useReducedMotion();
  const Component = motion[as] ?? motion.div;

  if (prefersReducedMotion) {
    const Plain = as;
    return (
      <Plain className={className} {...rest}>
        {children}
      </Plain>
    );
  }

  return (
    <Component variants={presets[preset]} className={className} {...rest}>
      {children}
    </Component>
  );
}
