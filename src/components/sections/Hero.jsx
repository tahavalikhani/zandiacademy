import { motion, useReducedMotion } from 'framer-motion';
import { EASE, fadeUp, stagger } from '../../lib/motion';
import { hero } from '../../data/content';
import Badge from '../ui/Badge';
import Button from '../ui/Button';
import Container from '../ui/Container';
import Icon from '../ui/Icon';
import HeroVisual from './HeroVisual';

export default function Hero() {
  const prefersReducedMotion = useReducedMotion();
  const motionProps = prefersReducedMotion
    ? {}
    : { initial: 'hidden', animate: 'visible', variants: stagger(0.09, 0.05) };
  const itemProps = prefersReducedMotion ? {} : { variants: fadeUp };

  return (
    <section id="home" aria-labelledby="hero-title" className="relative overflow-hidden pt-[4.5rem]">
      {/* Background: a soft navy wash that fades into the page, plus a faint grid. */}
      <div aria-hidden="true" className="pointer-events-none absolute inset-0 -z-10">
        <div className="absolute inset-0 bg-gradient-to-b from-navy-50/70 via-white to-white" />
        <div className="absolute inset-x-0 top-0 h-[38rem] bg-[radial-gradient(50%_50%_at_80%_0%,rgba(27,54,93,0.10),transparent_65%)]" />
        <div className="absolute inset-x-0 top-0 h-[38rem] bg-[radial-gradient(40%_40%_at_10%_10%,rgba(200,16,46,0.05),transparent_70%)]" />
      </div>

      <Container className="py-14 sm:py-20 lg:py-24">
        <div className="grid items-center gap-14 lg:grid-cols-2 lg:gap-16">
          <motion.div className="flex flex-col items-start gap-7" {...motionProps}>
            <motion.div {...itemProps}>
              <Badge variant="rouge">
                <span className="h-1.5 w-1.5 rounded-full bg-rouge-500" aria-hidden="true" />
                {hero.badge}
              </Badge>
            </motion.div>

            <motion.h1
              id="hero-title"
              {...itemProps}
              className="text-display-lg font-bold text-navy-700"
            >
              {hero.title}
            </motion.h1>

            <motion.p
              {...itemProps}
              className="max-w-prose text-pretty text-base leading-[2.1] text-navy-600/85 sm:text-lg"
            >
              {hero.description}
            </motion.p>

            <motion.div {...itemProps} className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
              <Button href={hero.primaryCta.href} size="lg" className="w-full sm:w-auto">
                {hero.primaryCta.label}
                <Icon name="arrowLeft" className="h-5 w-5" />
              </Button>
              <Button
                href={hero.secondaryCta.href}
                variant="secondary"
                size="lg"
                className="w-full sm:w-auto"
              >
                {hero.secondaryCta.label}
              </Button>
            </motion.div>

            <motion.ul {...itemProps} className="flex flex-col gap-3 pt-2 sm:flex-row sm:flex-wrap sm:gap-x-6">
              {hero.highlights.map((highlight) => (
                <li key={highlight} className="flex items-center gap-2 text-sm text-navy-600">
                  <span className="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50 text-success">
                    <Icon name="check" className="h-3.5 w-3.5" strokeWidth={2.4} />
                  </span>
                  {highlight}
                </li>
              ))}
            </motion.ul>
          </motion.div>

          <motion.div
            initial={prefersReducedMotion ? false : { opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.6, ease: EASE }}
            className="lg:ps-6"
          >
            <HeroVisual />
          </motion.div>
        </div>
      </Container>
    </section>
  );
}
