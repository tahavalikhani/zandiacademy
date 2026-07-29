import { cn } from '../../lib/cn';

const variants = {
  navy: 'bg-navy-50 text-navy-700 ring-navy-100',
  rouge: 'bg-rouge-50 text-rouge-600 ring-rouge-100',
  success: 'bg-emerald-50 text-success ring-emerald-100',
  neutral: 'bg-mist text-navy-600 ring-navy-100',
  onDark: 'bg-white/10 text-white ring-white/20 backdrop-blur',
};

/**
 * Small label used for levels, section eyebrows and status chips.
 */
export default function Badge({ variant = 'navy', className, children, ...rest }) {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 rounded-pill px-3 py-1 text-xs font-semibold',
        'ring-1 ring-inset',
        variants[variant],
        className,
      )}
      {...rest}
    >
      {children}
    </span>
  );
}
