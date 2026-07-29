import { cn } from '../../lib/cn';

/**
 * Surface primitive. Every panel on the page is a Card so radius, border and
 * shadow stay identical everywhere.
 *
 * @param {{
 *   as?: React.ElementType,
 *   interactive?: boolean,   // adds the shared hover lift
 *   padded?: boolean,
 *   className?: string
 * }} props
 */
export default function Card({
  as: Component = 'div',
  interactive = false,
  padded = true,
  className,
  children,
  ...rest
}) {
  return (
    <Component
      className={cn(
        'group relative rounded-card border border-navy-100/80 bg-white shadow-soft',
        'transition-all duration-300 ease-premium',
        padded && 'p-6 sm:p-7',
        interactive && 'hover:-translate-y-1 hover:border-navy-200 hover:shadow-lift',
        className,
      )}
      {...rest}
    >
      {children}
    </Component>
  );
}
