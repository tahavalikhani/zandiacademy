import { cn } from '../../lib/cn';
import { toPersianDigits } from '../../lib/format';

/**
 * Star rating. The stars are decorative; the accessible value is announced once
 * as text so screen readers hear "امتیاز ۵ از ۵" rather than five icon names.
 */
export default function Rating({ value = 5, max = 5, className }) {
  return (
    <div className={cn('flex items-center gap-1', className)}>
      <span className="sr-only">{`امتیاز ${toPersianDigits(value)} از ${toPersianDigits(max)}`}</span>

      {Array.from({ length: max }, (_, index) => (
        <svg
          key={index}
          viewBox="0 0 20 20"
          aria-hidden="true"
          className={cn('h-4 w-4', index < value ? 'text-amber-400' : 'text-navy-100')}
          fill="currentColor"
        >
          <path d="M10 1.8l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L2.2 7.5l5.4-.8L10 1.8z" />
        </svg>
      ))}
    </div>
  );
}
