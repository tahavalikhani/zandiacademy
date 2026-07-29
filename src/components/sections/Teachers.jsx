import { teachers } from '../../data/content';
import Avatar from '../ui/Avatar';
import Badge from '../ui/Badge';
import Card from '../ui/Card';
import Icon from '../ui/Icon';
import Section, { SectionHeading } from '../ui/Section';
import { RevealGroup, RevealItem } from '../ui/Reveal';

export default function Teachers() {
  return (
    <Section id="teachers">
      <SectionHeading
        id="teachers"
        eyebrow="اساتید"
        title="کسانی که کنار شما هستند"
        description="هر استاد آکادمی مدرک تخصصی تدریس فرانسه دارد و پیش از ورود به کلاس، دوره آموزش روش تدریس آکادمی را گذرانده است."
        className="mb-12 sm:mb-14"
      />

      <RevealGroup step={0.09} className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {teachers.map((teacher, index) => (
          <RevealItem key={teacher.name} preset="fadeScale" className="h-full">
            <Card interactive className="flex h-full flex-col gap-5">
              <div className="flex items-center gap-4">
                <Avatar name={teacher.name} src={teacher.image} size="lg" index={index} />
                <span className="flex flex-col gap-0.5">
                  <span className="text-base font-bold text-navy-700">{teacher.name}</span>
                  <span className="text-xs text-navy-500">{teacher.role}</span>
                </span>
              </div>

              <p className="flex items-center gap-2 text-xs font-medium text-navy-600">
                <Icon name="graduation" className="h-4 w-4 text-navy-400" />
                <span dir="auto">{teacher.credential}</span>
              </p>

              <p className="text-pretty text-[0.9rem] leading-loose text-navy-600/85">{teacher.bio}</p>

              <Badge variant="neutral" className="mt-auto self-start">
                {teacher.focus}
              </Badge>
            </Card>
          </RevealItem>
        ))}
      </RevealGroup>
    </Section>
  );
}
