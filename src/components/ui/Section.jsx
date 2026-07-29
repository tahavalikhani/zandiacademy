import { cn } from '../../lib/cn';
import Container from './Container';
import Badge from './Badge';
import Reveal from './Reveal';

const tones = {
  white: 'bg-white',
  mist: 'bg-mist',
  navy: 'bg-navy-700 text-white',
};

/**
 * Vertical rhythm wrapper. Sections are landmarks, so each one is a `<section>`
 * labelled by its own heading.
 */
export default function Section({
  id,
  tone = 'white',
  className,
  containerClassName,
  children,
  ...rest
}) {
  return (
    <section
      id={id}
      aria-labelledby={id ? `${id}-title` : undefined}
      className={cn('scroll-mt-28 py-16 sm:py-20 lg:py-28', tones[tone], className)}
      {...rest}
    >
      <Container className={containerClassName}>{children}</Container>
    </section>
  );
}

/**
 * Eyebrow + title + description block, shared by every section.
 */
export function SectionHeading({
  id,
  eyebrow,
  title,
  description,
  align = 'center',
  onDark = false,
  className,
}) {
  return (
    <Reveal
      className={cn(
        'flex flex-col gap-4',
        align === 'center' ? 'items-center text-center' : 'items-start text-start',
        className,
      )}
    >
      {eyebrow && <Badge variant={onDark ? 'onDark' : 'rouge'}>{eyebrow}</Badge>}

      <h2
        id={id ? `${id}-title` : undefined}
        className={cn('text-display-sm font-bold', onDark ? 'text-white' : 'text-navy-700')}
      >
        {title}
      </h2>

      {description && (
        <p
          className={cn(
            'max-w-prose text-pretty text-base sm:text-lg',
            onDark ? 'text-navy-100/85' : 'text-navy-600/85',
          )}
        >
          {description}
        </p>
      )}
    </Reveal>
  );
}
