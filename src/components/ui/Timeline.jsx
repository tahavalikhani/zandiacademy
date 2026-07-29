import { cn } from '../../lib/cn';
import { toPersianDigits } from '../../lib/format';
import Icon from './Icon';
import { RevealGroup, RevealItem } from './Reveal';

/**
 * Learning-journey timeline.
 *
 * Horizontal from `lg` up, vertical below — both directions drawn from the same
 * ordered list, so the reading order stays correct in every layout and for
 * assistive tech.
 *
 * @param {{ steps: { title: string, description: string, icon: string }[] }} props
 */
export default function Timeline({ steps, className }) {
  return (
    <RevealGroup as="ol" step={0.1} className={cn('relative', className)}>
      {/*
        Connector rails — decorative only, hidden from assistive tech.
        Two elements rather than one responsive element: the vertical rail is
        offset to the icon column (1.625rem = half the 3.25rem marker), while the
        horizontal one spans the full row. Mixing logical `start-*` with physical
        `inset-x-*` on a single node fights itself under RTL.
      */}
      <span
        aria-hidden="true"
        className="absolute bottom-2 start-[1.625rem] top-2 w-px bg-gradient-to-b from-navy-100 via-navy-200 to-navy-100 lg:hidden"
      />
      <span
        aria-hidden="true"
        className="absolute inset-x-0 top-[1.625rem] hidden h-px bg-gradient-to-l from-navy-100 via-navy-200 to-navy-100 lg:block"
      />

      <div className="relative flex flex-col gap-8 lg:grid lg:grid-cols-5 lg:gap-6">
        {steps.map((step, index) => (
          <RevealItem
            as="li"
            key={step.title}
            className="group flex items-start gap-5 lg:flex-col lg:items-center lg:gap-4 lg:text-center"
          >
            <span className="relative flex h-[3.25rem] w-[3.25rem] shrink-0 items-center justify-center rounded-full border border-navy-100 bg-white shadow-soft transition-all duration-300 ease-premium group-hover:-translate-y-1 group-hover:border-navy-300 group-hover:shadow-card">
              <Icon name={step.icon} className="h-6 w-6 text-navy-700" />
              <span className="absolute -top-1.5 -end-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-navy-700 text-[0.7rem] font-bold text-white">
                {toPersianDigits(index + 1)}
              </span>
            </span>

            <div className="flex flex-col gap-1.5 pt-1 lg:items-center">
              <h3 className="text-base font-bold text-navy-700">{step.title}</h3>
              <p className="max-w-[22rem] text-pretty text-sm leading-relaxed text-navy-600/85">
                {step.description}
              </p>
            </div>
          </RevealItem>
        ))}
      </div>
    </RevealGroup>
  );
}
