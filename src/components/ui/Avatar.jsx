import { cn } from '../../lib/cn';

const palettes = [
  'bg-navy-100 text-navy-700',
  'bg-rouge-50 text-rouge-600',
  'bg-emerald-50 text-success',
  'bg-mist text-navy-600',
];

const sizes = {
  sm: 'h-10 w-10 text-sm',
  md: 'h-12 w-12 text-base',
  lg: 'h-14 w-14 text-lg',
};

/**
 * Avatar with an initials fallback.
 *
 * Renders initials until a real `src` is supplied, so the layout is final from
 * day one and no broken-image frames appear before photography lands.
 */
export default function Avatar({ name, src, size = 'md', index = 0, className }) {
  const words = name.split(' ').filter(Boolean);

  // Arabic-script letters join when placed side by side, so two initials read as
  // a garbled word ("سمیه زندی" → "سم"). Persian names therefore get a single
  // letter; Latin names keep the familiar two.
  const isArabicScript = /[؀-ۿ]/.test(name);
  const initials = isArabicScript
    ? words[0][0]
    : words.slice(0, 2).map((word) => word[0]).join('');

  if (src) {
    return (
      <img
        src={src}
        alt={name}
        loading="lazy"
        decoding="async"
        className={cn('shrink-0 rounded-full object-cover ring-2 ring-white', sizes[size], className)}
      />
    );
  }

  return (
    <span
      aria-hidden="true"
      className={cn(
        'flex shrink-0 items-center justify-center rounded-full font-bold ring-2 ring-white',
        palettes[index % palettes.length],
        sizes[size],
        className,
      )}
    >
      {initials}
    </span>
  );
}
