import { useId, useState } from 'react';
import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import { cn } from '../../lib/cn';
import { EASE } from '../../lib/motion';
import Icon from './Icon';

/**
 * Accessible single-open accordion.
 *
 * Each header is a real `<button>` carrying `aria-expanded` / `aria-controls`,
 * and each panel is a labelled region — so it works with a keyboard and a
 * screen reader without extra wiring.
 *
 * @param {{ items: { question: string, answer: string }[], defaultOpen?: number|null }} props
 */
export default function Accordion({ items, defaultOpen = 0, className }) {
  const [openIndex, setOpenIndex] = useState(defaultOpen);
  const baseId = useId();
  const prefersReducedMotion = useReducedMotion();

  return (
    <div className={cn('flex flex-col gap-3', className)}>
      {items.map((item, index) => {
        const isOpen = openIndex === index;
        const buttonId = `${baseId}-trigger-${index}`;
        const panelId = `${baseId}-panel-${index}`;

        return (
          <div
            key={item.question}
            className={cn(
              'overflow-hidden rounded-card border bg-white transition-all duration-300 ease-premium',
              isOpen ? 'border-navy-200 shadow-card' : 'border-navy-100/80 shadow-soft hover:border-navy-200',
            )}
          >
            <h3>
              <button
                type="button"
                id={buttonId}
                aria-expanded={isOpen}
                aria-controls={panelId}
                onClick={() => setOpenIndex(isOpen ? null : index)}
                className={cn(
                  'flex w-full items-center justify-between gap-4 p-5 text-start sm:p-6',
                  'text-base font-semibold text-navy-700 transition-colors duration-200',
                  'hover:text-navy-800 focus-visible:ring-2 focus-visible:ring-navy-500',
                )}
              >
                <span className="text-pretty">{item.question}</span>
                <span
                  className={cn(
                    'flex h-9 w-9 shrink-0 items-center justify-center rounded-full transition-all duration-300 ease-premium',
                    isOpen ? 'rotate-180 bg-navy-700 text-white' : 'bg-mist text-navy-500',
                  )}
                >
                  <Icon name="chevronDown" className="h-5 w-5" />
                </span>
              </button>
            </h3>

            <AnimatePresence initial={false}>
              {isOpen && (
                <motion.div
                  key="panel"
                  id={panelId}
                  role="region"
                  aria-labelledby={buttonId}
                  initial={prefersReducedMotion ? false : { height: 0, opacity: 0 }}
                  animate={{ height: 'auto', opacity: 1 }}
                  exit={prefersReducedMotion ? { opacity: 0 } : { height: 0, opacity: 0 }}
                  transition={{ duration: 0.32, ease: EASE }}
                  className="overflow-hidden"
                >
                  <p className="px-5 pb-6 text-pretty text-[0.95rem] leading-loose text-navy-600/90 sm:px-6">
                    {item.answer}
                  </p>
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        );
      })}
    </div>
  );
}
