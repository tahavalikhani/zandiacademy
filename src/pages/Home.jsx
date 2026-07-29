import CoursesPreview from '../components/sections/CoursesPreview';
import Faq from '../components/sections/Faq';
import FinalCta from '../components/sections/FinalCta';
import Hero from '../components/sections/Hero';
import LearningJourney from '../components/sections/LearningJourney';
import Teachers from '../components/sections/Teachers';
import Testimonials from '../components/sections/Testimonials';
import TrustStats from '../components/sections/TrustStats';
import WhyChooseUs from '../components/sections/WhyChooseUs';

/**
 * Homepage composition. Each section owns its own data and spacing, so the page
 * is only an ordering decision.
 */
export default function Home() {
  return (
    <main id="main">
      <Hero />
      <TrustStats />
      <WhyChooseUs />
      <CoursesPreview />
      <LearningJourney />
      <Teachers />
      <Testimonials />
      <Faq />
      <FinalCta />
    </main>
  );
}
