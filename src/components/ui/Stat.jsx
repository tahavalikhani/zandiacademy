import { useEffect, useRef, useState } from 'react';
import { animate, useInView, useReducedMotion } from 'framer-motion';
import { cn } from '../../lib/cn';
import { EASE } from '../../lib/motion';
import { formatNumber, toPersianDigits } from '../../lib/format';
import Icon from './Icon';

/**
 * Counts from zero to `value` the first time the stat scrolls into view.
 * Returns the final value immediately when reduced motion is preferred.
 */
function useCountUp(value, isInView) {
  const prefersReducedMotion = useReducedMotion();
  const [animated, setAnimated] = useState(0);

  useEffect(() => {
    if (!isInView || prefersReducedMotion) return undefined;

    const controls = animate(0, value, {
      duration: 1.4,
      ease: EASE,
      onUpdate: (latest) => setAnimated(Math.round(latest)),
    });

    return () => controls.stop();
  }, [isInView, prefersReducedMotion, value]);

  // Derived at render rather than pushed through an effect: with reduced motion
  // the final figure is simply what we show.
  return prefersReducedMotion ? value : animated;
}

/**
 * Trust metric card: icon, animated figure and label.
 *
 * @param {{ icon: string, value: number, suffix?: string, label: string, caption?: string }} props
 */
export default function Stat({ icon, value, suffix, label, caption, className }) {
  const ref = useRef(null);
  const isInView = useInView(ref, { once: true, amount: 0.4 });
  const display = useCountUp(value, isInView);

  return (
    <div
      ref={ref}
      className={cn(
        'group flex flex-col items-center gap-3 rounded-card border border-navy-100/80 bg-white p-6 text-center',
        'shadow-soft transition-all duration-300 ease-premium hover:-translate-y-1 hover:shadow-lift',
        className,
      )}
    >
      <span className="flex h-12 w-12 items-center justify-center rounded-soft bg-navy-50 text-navy-700 transition-colors duration-300 group-hover:bg-navy-700 group-hover:text-white">
        <Icon name={icon} className="h-6 w-6" />
      </span>

      <p className="flex items-baseline gap-1 text-3xl font-bold text-navy-700 sm:text-4xl">
        <span>{formatNumber(display)}</span>
        {suffix && <span className="text-xl font-bold text-rouge-500 sm:text-2xl">{toPersianDigits(suffix)}</span>}
      </p>

      <p className="text-sm font-semibold text-navy-700">{label}</p>
      {caption && <p className="text-xs leading-relaxed text-navy-500">{caption}</p>}
    </div>
  );
}
