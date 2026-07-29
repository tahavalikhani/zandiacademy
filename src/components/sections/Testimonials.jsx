import { testimonials } from '../../data/content';
import Avatar from '../ui/Avatar';
import Card from '../ui/Card';
import Carousel from '../ui/Carousel';
import Icon from '../ui/Icon';
import Rating from '../ui/Rating';
import Reveal from '../ui/Reveal';
import Section, { SectionHeading } from '../ui/Section';

export default function Testimonials() {
  return (
    <Section id="testimonials">
      <SectionHeading
        id="testimonials"
        eyebrow="نظرات زبان‌آموزان"
        title="کسانی که مسیر را تمام کرده‌اند"
        description="بیش از دو هزار نفر با آکادمی زندی به فرانسه رسیده‌اند. چند نفرشان تجربه‌شان را نوشته‌اند."
        className="mb-12 sm:mb-14"
      />

      <Reveal>
        <Carousel label="نظرات زبان‌آموزان آکادمی زندی">
          {testimonials.map((testimonial, index) => (
            <TestimonialCard key={testimonial.name} testimonial={testimonial} index={index} />
          ))}
        </Carousel>
      </Reveal>
    </Section>
  );
}

function TestimonialCard({ testimonial, index }) {
  const { name, role, quote, rating } = testimonial;

  return (
    <Card as="figure" interactive className="flex h-full flex-col gap-5">
      <div className="flex items-center justify-between gap-3">
        <Icon name="quote" className="h-8 w-8 text-navy-100" strokeWidth={0} fill="currentColor" />
        <Rating value={rating} />
      </div>

      <blockquote className="flex-1 text-pretty text-[0.92rem] leading-loose text-navy-600/90">
        {quote}
      </blockquote>

      <figcaption className="flex items-center gap-3 border-t border-navy-100/70 pt-5">
        <Avatar name={name} index={index} />
        <span className="flex flex-col">
          <span className="text-sm font-bold text-navy-700">{name}</span>
          <span className="text-xs text-navy-500">{role}</span>
        </span>
      </figcaption>
    </Card>
  );
}
