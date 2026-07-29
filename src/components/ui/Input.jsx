import { useId } from 'react';
import { cn } from '../../lib/cn';

const fieldStyles =
  'w-full rounded-soft border border-navy-100 bg-white px-4 py-3 text-[0.95rem] text-ink ' +
  'placeholder:text-navy-300 shadow-soft transition-all duration-300 ease-premium ' +
  'hover:border-navy-200 focus:border-navy-400 focus:outline-none focus:ring-2 focus:ring-navy-500/25';

/**
 * Labelled text field. The label is always rendered — `hideLabel` moves it to
 * screen readers rather than dropping it, so placeholders never stand in for a
 * label.
 */
export default function Input({
  label,
  id,
  type = 'text',
  hint,
  hideLabel = false,
  className,
  inputClassName,
  ...rest
}) {
  const autoId = useId();
  const fieldId = id ?? autoId;
  const hintId = hint ? `${fieldId}-hint` : undefined;

  return (
    <div className={cn('flex flex-col gap-2', className)}>
      <label
        htmlFor={fieldId}
        className={cn('text-sm font-semibold text-navy-700', hideLabel && 'sr-only')}
      >
        {label}
      </label>

      <input
        id={fieldId}
        type={type}
        aria-describedby={hintId}
        className={cn(fieldStyles, inputClassName)}
        {...rest}
      />

      {hint && (
        <p id={hintId} className="text-xs text-navy-500">
          {hint}
        </p>
      )}
    </div>
  );
}
