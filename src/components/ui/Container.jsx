import { cn } from '../../lib/cn';

/**
 * Horizontal rhythm for the whole page: one max width, one gutter scale.
 */
export default function Container({ as: Component = 'div', className, children, ...rest }) {
  return (
    <Component className={cn('mx-auto w-full max-w-content px-5 sm:px-8 lg:px-10', className)} {...rest}>
      {children}
    </Component>
  );
}
