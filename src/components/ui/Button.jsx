import { forwardRef } from 'react';
import { cn } from '../../lib/cn';

const base =
  'inline-flex items-center justify-center gap-2 rounded-pill font-semibold ' +
  'transition-all duration-300 ease-premium select-none ' +
  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 ' +
  'disabled:pointer-events-none disabled:opacity-50 active:scale-[0.98]';

const variants = {
  primary:
    'bg-navy-700 text-white shadow-soft hover:bg-navy-800 hover:shadow-card ' +
    'hover:-translate-y-0.5 focus-visible:ring-navy-500',
  secondary:
    'bg-white text-navy-700 ring-1 ring-inset ring-navy-200 hover:bg-navy-50 ' +
    'hover:ring-navy-300 hover:-translate-y-0.5 focus-visible:ring-navy-500',
  // French red, used sparingly — a single high-intent action per view.
  accent:
    'bg-rouge-500 text-white shadow-soft hover:bg-rouge-600 hover:shadow-card ' +
    'hover:-translate-y-0.5 focus-visible:ring-rouge-500',
  ghost: 'text-navy-700 hover:bg-navy-50 focus-visible:ring-navy-500',
  onDark:
    'bg-white text-navy-700 shadow-soft hover:bg-navy-50 hover:-translate-y-0.5 ' +
    'focus-visible:ring-white focus-visible:ring-offset-navy-700',
  outlineOnDark:
    'text-white ring-1 ring-inset ring-white/35 hover:bg-white/10 hover:ring-white/60 ' +
    'focus-visible:ring-white focus-visible:ring-offset-navy-700',
};

const sizes = {
  sm: 'h-10 px-4 text-sm',
  md: 'h-12 px-6 text-[0.95rem]',
  lg: 'h-14 px-8 text-base',
};

/**
 * The single button primitive for the site.
 *
 * Renders an `<a>` when `href` is passed and a `<button>` otherwise, so links
 * stay links — keyboard and screen-reader behaviour comes for free.
 */
const Button = forwardRef(function Button(
  { as, href, variant = 'primary', size = 'md', className, children, ...rest },
  ref,
) {
  const Component = as ?? (href ? 'a' : 'button');
  const isNativeButton = Component === 'button';

  return (
    <Component
      ref={ref}
      href={href}
      type={isNativeButton ? rest.type ?? 'button' : undefined}
      className={cn(base, variants[variant], sizes[size], className)}
      {...rest}
    >
      {children}
    </Component>
  );
});

export default Button;
