import { useEffect, useState } from 'react';

/**
 * True once the page has scrolled past `threshold` pixels.
 * Used to give the sticky header its border and blur only when it overlaps content.
 *
 * @param {number} threshold
 */
export default function useScrolled(threshold = 12) {
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > threshold);

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, [threshold]);

  return scrolled;
}
