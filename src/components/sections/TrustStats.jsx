import { stats } from '../../data/content';
import Container from '../ui/Container';
import Stat from '../ui/Stat';
import { RevealGroup, RevealItem } from '../ui/Reveal';

/**
 * Trust band. Sits directly under the hero, overlapping the section seam so the
 * numbers read as part of the first impression.
 */
export default function TrustStats() {
  return (
    <section aria-label="آکادمی زندی در یک نگاه" className="relative pb-16 sm:pb-20">
      <Container>
        <RevealGroup step={0.09} className="grid grid-cols-2 gap-4 sm:gap-5 lg:grid-cols-4">
          {stats.map((stat) => (
            <RevealItem key={stat.label} preset="fadeScale">
              <Stat {...stat} className="h-full" />
            </RevealItem>
          ))}
        </RevealGroup>
      </Container>
    </section>
  );
}
