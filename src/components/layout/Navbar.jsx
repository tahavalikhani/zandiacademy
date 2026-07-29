import { useEffect, useMemo, useState } from 'react';
import { AnimatePresence, motion } from 'framer-motion';
import { cn } from '../../lib/cn';
import { EASE } from '../../lib/motion';
import { contact, navigation } from '../../data/content';
import useScrollSpy from '../../hooks/useScrollSpy';
import useScrolled from '../../hooks/useScrolled';
import Button from '../ui/Button';
import Container from '../ui/Container';
import Icon from '../ui/Icon';
import Logo from './Logo';

/**
 * Sticky header.
 *
 * In RTL the logo naturally lands on the right, the menu centres, and the CTA
 * sits on the left — no direction-specific overrides needed.
 */
export default function Navbar() {
  const [menuOpen, setMenuOpen] = useState(false);
  const scrolled = useScrolled(12);

  const sectionIds = useMemo(() => navigation.map((item) => item.href.replace('#', '')), []);
  const activeId = useScrollSpy(sectionIds);

  // Close on Escape, and stop the page scrolling behind the mobile panel.
  useEffect(() => {
    if (!menuOpen) return undefined;

    const onKeyDown = (event) => {
      if (event.key === 'Escape') setMenuOpen(false);
    };

    document.addEventListener('keydown', onKeyDown);
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    return () => {
      document.removeEventListener('keydown', onKeyDown);
      document.body.style.overflow = previousOverflow;
    };
  }, [menuOpen]);

  return (
    <header
      className={cn(
        'fixed inset-x-0 top-0 z-50 transition-all duration-300 ease-premium',
        scrolled || menuOpen
          ? 'border-b border-navy-100/80 bg-white/85 backdrop-blur-xl'
          : 'border-b border-transparent bg-transparent',
      )}
    >
      <a
        href="#main"
        className="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:start-4 focus:z-50 focus:rounded-pill focus:bg-navy-700 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white"
      >
        رفتن به محتوای اصلی
      </a>

      <Container>
        <nav aria-label="ناوبری اصلی" className="flex h-[4.5rem] items-center justify-between gap-4">
          <a href="#home" className="rounded-soft" aria-label={`${'آکادمی زندی'} — خانه`}>
            <Logo />
          </a>

          {/* Desktop menu */}
          <ul className="hidden items-center gap-1 lg:flex">
            {navigation.map((item) => {
              const isActive = activeId === item.href.replace('#', '');

              return (
                <li key={item.href}>
                  <a
                    href={item.href}
                    aria-current={isActive ? 'true' : undefined}
                    className={cn(
                      'relative rounded-pill px-4 py-2 text-[0.95rem] font-medium transition-colors duration-200',
                      isActive ? 'text-navy-700' : 'text-navy-600/80 hover:text-navy-700',
                    )}
                  >
                    {item.label}
                    {isActive && (
                      <motion.span
                        layoutId="nav-active"
                        transition={{ duration: 0.35, ease: EASE }}
                        className="absolute inset-x-3 -bottom-0.5 h-0.5 rounded-full bg-rouge-500"
                      />
                    )}
                  </a>
                </li>
              );
            })}
          </ul>

          <div className="flex items-center gap-2">
            {/* The primary action stays visible at every width — it is the whole
                point of the page, so it never hides behind the menu. */}
            <Button href="#register" size="sm">
              ثبت نام
            </Button>

            <button
              type="button"
              onClick={() => setMenuOpen((open) => !open)}
              aria-expanded={menuOpen}
              aria-controls="mobile-menu"
              aria-label={menuOpen ? 'بستن منو' : 'باز کردن منو'}
              className="flex h-11 w-11 items-center justify-center rounded-soft border border-navy-100 bg-white text-navy-700 shadow-soft transition-colors duration-200 hover:border-navy-200 lg:hidden"
            >
              <Icon name={menuOpen ? 'close' : 'menu'} className="h-5 w-5" />
            </button>
          </div>
        </nav>
      </Container>

      <AnimatePresence>
        {menuOpen && (
          <motion.div
            id="mobile-menu"
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: 'auto' }}
            exit={{ opacity: 0, height: 0 }}
            transition={{ duration: 0.3, ease: EASE }}
            className="overflow-hidden border-t border-navy-100/70 bg-white shadow-lift lg:hidden"
          >
            <Container className="py-5">
              <ul className="flex flex-col gap-1">
                {navigation.map((item) => (
                  <li key={item.href}>
                    <a
                      href={item.href}
                      onClick={() => setMenuOpen(false)}
                      className="flex items-center justify-between rounded-soft px-4 py-3 text-base font-medium text-navy-700 transition-colors duration-200 hover:bg-navy-50"
                    >
                      {item.label}
                      <Icon name="arrowLeft" className="h-4 w-4 text-navy-300" />
                    </a>
                  </li>
                ))}
              </ul>

              <Button
                href={contact.phoneHref}
                variant="secondary"
                size="md"
                className="mt-4 w-full"
                onClick={() => setMenuOpen(false)}
              >
                <Icon name="phone" className="h-4 w-4" />
                <span dir="ltr">{contact.phone}</span>
              </Button>
            </Container>
          </motion.div>
        )}
      </AnimatePresence>
    </header>
  );
}
