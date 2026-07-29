import Badge from '../ui/Badge';
import Button from '../ui/Button';
import Card from '../ui/Card';
import Icon from '../ui/Icon';
import Thumbnail from '../ui/Thumbnail';

/**
 * One course in the grid.
 *
 * The whole card lifts on hover but only the button is a link target, so the
 * accessible name of the action stays specific ("مشاهده دوره — فرانسه از صفر").
 */
export default function CourseCard({ course }) {
  const { title, level, duration, sessions, description, badge, tone } = course;

  return (
    <Card interactive padded={false} className="flex h-full flex-col overflow-hidden">
      <div className="relative p-3 pb-0">
        <Thumbnail level={level} tone={tone} />
        {badge && (
          <Badge variant="rouge" className="absolute top-5 end-5 shadow-soft">
            {badge}
          </Badge>
        )}
      </div>

      <div className="flex flex-1 flex-col gap-4 p-6">
        <div className="flex items-center gap-2">
          <Badge variant="navy">سطح {level}</Badge>
          <span className="flex items-center gap-1.5 text-xs text-navy-500">
            <Icon name="clock" className="h-4 w-4" />
            {duration}
          </span>
          <span className="flex items-center gap-1.5 text-xs text-navy-500">
            <Icon name="layers" className="h-4 w-4" />
            {sessions}
          </span>
        </div>

        <h3 className="text-lg font-bold text-navy-700">{title}</h3>

        <p className="text-pretty text-[0.9rem] leading-loose text-navy-600/85">{description}</p>

        <Button
          href="#register"
          variant="secondary"
          size="sm"
          className="mt-auto self-start group-hover:border-navy-300"
        >
          <span aria-hidden="true">مشاهده دوره</span>
          <span className="sr-only">{`مشاهده دوره ${title}`}</span>
          <Icon name="arrowLeft" className="h-4 w-4 transition-transform duration-300 ease-premium group-hover:-translate-x-1" />
        </Button>
      </div>
    </Card>
  );
}
