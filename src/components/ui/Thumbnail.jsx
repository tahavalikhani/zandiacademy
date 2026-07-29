import { cn } from '../../lib/cn';

/**
 * Course-card media slot.
 *
 * With no `src` it draws a considered abstract composition instead of a grey
 * box, so the grid looks finished before real photography exists. Pass `src`
 * later and the layout does not shift — the aspect ratio is reserved either way.
 */
export default function Thumbnail({ src, alt = '', level, tone = 'navy', className }) {
  const tones = {
    navy: 'from-navy-700 via-navy-600 to-navy-800',
    deep: 'from-navy-800 via-navy-700 to-navy-950',
    soft: 'from-navy-500 via-navy-600 to-navy-700',
  };

  return (
    <div className={cn('relative aspect-[16/10] overflow-hidden rounded-soft bg-navy-700', className)}>
      {src ? (
        <img
          src={src}
          alt={alt}
          loading="lazy"
          decoding="async"
          className="h-full w-full object-cover transition-transform duration-500 ease-premium group-hover:scale-[1.03]"
        />
      ) : (
        <div
          aria-hidden="true"
          className={cn('absolute inset-0 bg-gradient-to-bl', tones[tone])}
        >
          {/* Concentric arcs — an abstract nod to French guilloché engraving. */}
          <svg
            viewBox="0 0 320 200"
            preserveAspectRatio="xMidYMid slice"
            className="absolute inset-0 h-full w-full"
          >
            <g fill="none" stroke="white" strokeOpacity="0.16" strokeWidth="1">
              <circle cx="255" cy="42" r="34" />
              <circle cx="255" cy="42" r="58" />
              <circle cx="255" cy="42" r="84" />
              <circle cx="255" cy="42" r="112" />
            </g>
            <path d="M0 168 C 80 140, 150 190, 320 132 L320 200 L0 200 Z" fill="white" fillOpacity="0.06" />
            <path d="M0 186 C 90 160, 170 204, 320 156 L320 200 L0 200 Z" fill="white" fillOpacity="0.05" />
          </svg>

          {level && (
            <span className="absolute bottom-4 start-4 font-bold text-white/25 [font-size:clamp(2.5rem,6vw,3.5rem)] [line-height:1]">
              {level}
            </span>
          )}
        </div>
      )}
    </div>
  );
}
