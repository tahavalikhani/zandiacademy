import { journey } from '../../data/content';
import Section, { SectionHeading } from '../ui/Section';
import Timeline from '../ui/Timeline';

export default function LearningJourney() {
  return (
    <Section id="journey" tone="mist">
      <SectionHeading
        id="journey"
        eyebrow="مسیر یادگیری"
        title="پنج قدم تا صحبت کردن به فرانسه"
        description="مسیر شما از روز اول مشخص است؛ می‌دانید در هر مرحله چه اتفاقی می‌افتد و چه انتظاری باید داشته باشید."
        className="mb-14 sm:mb-16"
      />

      <Timeline steps={journey} />
    </Section>
  );
}
