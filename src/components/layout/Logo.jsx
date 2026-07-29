import { cn } from '../../lib/cn';

/**
 * Wordmark: a monogram tile plus the academy name.
 *
 * The mark is an abstract "Z" cut from a rounded tile with a single red hairline
 * — French flag colours implied, never illustrated.
 */
export default function Logo({ onDark = false, className }) {
  return (
    <span className={cn('inline-flex items-center gap-3', className)}>
      <span
        aria-hidden="true"
        className={cn(
          'relative flex h-10 w-10 items-center justify-center overflow-hidden rounded-[13px] shadow-soft',
          onDark ? 'bg-white' : 'bg-navy-700',
        )}
      >
        <svg viewBox="0 0 40 40" className="h-full w-full">
          <path
            d="M13 13.5h14l-14 13h14"
            fill="none"
            strokeWidth="3"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={onDark ? 'stroke-navy-700' : 'stroke-white'}
          />
          <rect x="0" y="0" width="3.5" height="40" className="fill-rouge-500" />
        </svg>
      </span>

      <span className="flex flex-col leading-none">
        <span className={cn('text-lg font-bold', onDark ? 'text-white' : 'text-navy-700')}>
          آکادمی زندی
        </span>
        <span
          className={cn(
            'mt-1 text-[0.62rem] font-medium tracking-[0.22em]',
            onDark ? 'text-navy-100/70' : 'text-navy-400',
          )}
        >
          FRANÇAIS
        </span>
      </span>
    </span>
  );
}
