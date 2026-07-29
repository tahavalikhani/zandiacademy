import { features } from '../../data/content';
import Card from '../ui/Card';
import Icon from '../ui/Icon';
import Section, { SectionHeading } from '../ui/Section';
import { RevealGroup, RevealItem } from '../ui/Reveal';

export default function WhyChooseUs() {
  return (
    <Section id="about" tone="mist">
      <SectionHeading
        id="about"
        eyebrow="چرا آکادمی زندی"
        title="یک آکادمی، یک زبان، تمام تمرکز"
        description="ما دوره‌های چندزبانه ارائه نمی‌دهیم. همه انرژی، اساتید و منابع آکادمی صرف یک چیز می‌شود: اینکه شما فرانسه را درست یاد بگیرید."
        className="mb-12 sm:mb-14"
      />

      <RevealGroup step={0.08} className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {features.map((feature) => (
          <RevealItem key={feature.title} preset="fadeScale" className="h-full">
            <Card interactive className="flex h-full flex-col gap-4">
              <span className="flex h-12 w-12 items-center justify-center rounded-soft bg-navy-50 text-navy-700 transition-all duration-300 ease-premium group-hover:bg-navy-700 group-hover:text-white">
                <Icon name={feature.icon} className="h-6 w-6" />
              </span>

              <h3 className="text-lg font-bold text-navy-700">{feature.title}</h3>

              <p className="text-pretty text-[0.92rem] leading-loose text-navy-600/85">
                {feature.description}
              </p>

              {/* Hairline that draws itself on hover — the only decorative flourish. */}
              <span
                aria-hidden="true"
                className="mt-auto block h-0.5 w-0 rounded-full bg-rouge-500 transition-all duration-500 ease-premium group-hover:w-10"
              />
            </Card>
          </RevealItem>
        ))}
      </RevealGroup>
    </Section>
  );
}
