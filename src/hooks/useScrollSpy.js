import { useEffect, useState } from 'react';

/**
 * Returns the id of the section currently occupying the middle of the viewport.
 *
 * Drives the active state in the header. Uses one IntersectionObserver rather
 * than a scroll handler, so it costs nothing on the main thread.
 *
 * @param {string[]} ids section ids, in document order
 * @returns {string|null}
 */
export default function useScrollSpy(ids) {
  const [activeId, setActiveId] = useState(null);

  useEffect(() => {
    const elements = ids
      .map((id) => document.getElementById(id))
      .filter((element) => element !== null);

    if (elements.length === 0) return undefined;

    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((entry) => entry.isIntersecting)
          .sort((a, b) => b.intersectionRatio - a.intersectionRatio);

        if (visible.length > 0) setActiveId(visible[0].target.id);
      },
      // A band across the middle of the viewport: a section is "active" while it
      // sits under the reader's eye, not merely when its edge appears.
      { rootMargin: '-45% 0px -45% 0px', threshold: [0, 0.25, 0.5, 1] },
    );

    elements.forEach((element) => observer.observe(element));
    return () => observer.disconnect();
  }, [ids]);

  return activeId;
}
