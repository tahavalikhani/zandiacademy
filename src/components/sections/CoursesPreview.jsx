import { courses } from '../../data/content';
import Button from '../ui/Button';
import Icon from '../ui/Icon';
import Reveal, { RevealGroup, RevealItem } from '../ui/Reveal';
import Section, { SectionHeading } from '../ui/Section';
import CourseCard from './CourseCard';

export default function CoursesPreview() {
  return (
    <Section id="courses">
      <SectionHeading
        id="courses"
        eyebrow="دوره‌ها"
        title="از اولین کلمه تا مدرک بین‌المللی"
        description="هر دوره بر اساس چارچوب اروپایی CEFR طراحی شده و در پایان آن دقیقاً می‌دانید در چه سطحی هستید و قدم بعدی چیست."
        className="mb-12 sm:mb-14"
      />

      <RevealGroup step={0.07} className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {courses.map((course) => (
          <RevealItem key={course.title} preset="fadeScale" className="h-full">
            <CourseCard course={course} />
          </RevealItem>
        ))}
      </RevealGroup>

      <Reveal className="mt-12 flex flex-col items-center gap-4 text-center">
        <p className="text-sm text-navy-600/85">
          مطمئن نیستید کدام دوره مناسب شماست؟ جلسه تعیین سطح رایگان پاسخ می‌دهد.
        </p>
        <Button href="#register" variant="secondary" size="md">
          رزرو تعیین سطح رایگان
          <Icon name="arrowLeft" className="h-4 w-4" />
        </Button>
      </Reveal>
    </Section>
  );
}
