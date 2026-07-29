/*
 * A scrolling region must be reachable by keyboard (WCAG 2.1.1) — without
 * `tabIndex` a keyboard user cannot scroll this shelf at all. The rule below
 * does not model scroll containers, so it is switched off for this file: the
 * element carries `role="region"` and a label, and is not a fake control.
 */
/* eslint-disable jsx-a11y/no-noninteractive-tabindex */
import { Children, useCallback, useEffect, useRef, useState } from 'react';
import { cn } from '../../lib/cn';
import Icon from './Icon';

/**
 * RTL-aware horizontal carousel built on native scroll + CSS scroll-snap.
 *
 * Native scrolling means touch, trackpad, keyboard arrows and screen-reader
 * navigation all work without JavaScript re-implementing them; the buttons are
 * a convenience layer on top.
 *
 * `scrollLeft` runs 0 → max in LTR and −max → 0 in RTL, so every bound check
 * below goes through `Math.abs`.
 */
export default function Carousel({ label, children, className, itemClassName }) {
  const scrollerRef = useRef(null);
  const [bounds, setBounds] = useState({ atStart: true, atEnd: false });

  const syncBounds = useCallback(() => {
    const el = scrollerRef.current;
    if (!el) return;

    const max = el.scrollWidth - el.clientWidth;
    const offset = Math.abs(el.scrollLeft);

    setBounds({
      atStart: offset <= 4,
      atEnd: max <= 4 || offset >= max - 4,
    });
  }, []);

  useEffect(() => {
    const el = scrollerRef.current;
    if (!el) return undefined;

    syncBounds();
    el.addEventListener('scroll', syncBounds, { passive: true });

    const observer = new ResizeObserver(syncBounds);
    observer.observe(el);

    return () => {
      el.removeEventListener('scroll', syncBounds);
      observer.disconnect();
    };
  }, [syncBounds]);

  /** @param {'next'|'prev'} direction */
  const scrollByPage = (direction) => {
    const el = scrollerRef.current;
    if (!el) return;

    const firstItem = el.firstElementChild;
    const step = firstItem ? firstItem.getBoundingClientRect().width + 24 : el.clientWidth * 0.8;
    const isRtl = getComputedStyle(el).direction === 'rtl';

    // In RTL the "next" item lives at a smaller (more negative) scrollLeft.
    const sign = (direction === 'next' ? 1 : -1) * (isRtl ? -1 : 1);
    el.scrollBy({ left: step * sign, behavior: 'smooth' });
  };

  return (
    <div className={cn('relative', className)}>
      <ul
        ref={scrollerRef}
        tabIndex={0}
        role="region"
        aria-label={label}
        className={cn(
          'no-scrollbar flex snap-x snap-mandatory gap-6 overflow-x-auto pb-4',
          // Bleed to the viewport edge on mobile so cards feel like a real shelf.
          '-mx-5 scroll-px-5 px-5 sm:mx-0 sm:scroll-px-0 sm:px-0',
        )}
      >
        {Children.toArray(children).map((child, index) => (
          <li
            // Static, non-reordering list: the index is a stable key here.
            key={index}
            className={cn('w-[85vw] shrink-0 snap-start sm:w-[24rem]', itemClassName)}
          >
            {child}
          </li>
        ))}
      </ul>

      <div className="mt-6 flex items-center justify-center gap-3 sm:justify-start">
        <CarouselButton
          label="نمایش مورد قبلی"
          icon="arrowRight"
          disabled={bounds.atStart}
          onClick={() => scrollByPage('prev')}
        />
        <CarouselButton
          label="نمایش مورد بعدی"
          icon="arrowLeft"
          disabled={bounds.atEnd}
          onClick={() => scrollByPage('next')}
        />
      </div>
    </div>
  );
}

function CarouselButton({ label, icon, disabled, onClick }) {
  return (
    <button
      type="button"
      onClick={onClick}
      disabled={disabled}
      aria-label={label}
      className={cn(
        'flex h-12 w-12 items-center justify-center rounded-full border bg-white',
        'transition-all duration-300 ease-premium focus-visible:ring-2 focus-visible:ring-navy-500',
        disabled
          ? 'cursor-not-allowed border-navy-100 text-navy-300'
          : 'border-navy-200 text-navy-700 shadow-soft hover:-translate-y-0.5 hover:border-navy-300 hover:shadow-card',
      )}
    >
      <Icon name={icon} className="h-5 w-5" />
    </button>
  );
}
