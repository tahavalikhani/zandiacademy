import { faqs } from '../../data/content';
import Accordion from '../ui/Accordion';
import Button from '../ui/Button';
import Icon from '../ui/Icon';
import Reveal from '../ui/Reveal';
import Section, { SectionHeading } from '../ui/Section';

export default function Faq() {
  return (
    <Section id="faq" tone="mist">
      <div className="grid gap-10 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:gap-16">
        <div className="lg:sticky lg:top-28 lg:self-start">
          <SectionHeading
            id="faq"
            eyebrow="سوالات متداول"
            title="هر چیزی که پیش از ثبت‌نام می‌پرسید"
            description="اگر پاسخ سوالتان اینجا نبود، کافی است پیام بدهید؛ مشاوران آموزشی در ساعات کاری پاسخ می‌دهند."
            align="start"
          />

          <Reveal delay={0.1} className="mt-7">
            <Button href="#contact" variant="secondary" size="md">
              <Icon name="chat" className="h-4 w-4" />
              پرسیدن سوال دیگر
            </Button>
          </Reveal>
        </div>

        <Reveal>
          <Accordion items={faqs} />
        </Reveal>
      </div>
    </Section>
  );
}
