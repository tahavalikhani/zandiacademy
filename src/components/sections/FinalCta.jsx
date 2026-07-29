import { useState } from 'react';
import { finalCta } from '../../data/content';
import Button from '../ui/Button';
import Container from '../ui/Container';
import Icon from '../ui/Icon';
import Input from '../ui/Input';
import Reveal from '../ui/Reveal';

/**
 * Closing call to action.
 *
 * The form posts nowhere yet — `onSubmit` is the seam where the WordPress
 * endpoint (admin-ajax, WPCF7 or the REST route) gets wired in. Until then it
 * confirms locally so the interaction can be reviewed end to end.
 */
export default function FinalCta() {
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = (event) => {
    event.preventDefault();
    setSubmitted(true);
  };

  return (
    <section id="register" aria-labelledby="register-title" className="scroll-mt-28 py-16 sm:py-20 lg:py-24">
      <Container>
        <Reveal preset="fadeScale" className="relative overflow-hidden rounded-[2rem] bg-navy-700 shadow-lift">
          {/* Engraved arcs, echoing the hero panel. */}
          <svg
            viewBox="0 0 1200 480"
            preserveAspectRatio="xMidYMid slice"
            aria-hidden="true"
            className="absolute inset-0 h-full w-full"
          >
            <g fill="none" stroke="white" strokeOpacity="0.09">
              {[90, 150, 215, 285, 360, 440].map((r) => (
                <circle key={r} cx="1010" cy="60" r={r} />
              ))}
            </g>
            <g fill="none" stroke="white" strokeOpacity="0.06">
              {[70, 130, 195].map((r) => (
                <circle key={r} cx="120" cy="430" r={r} />
              ))}
            </g>
          </svg>

          {/* Tricolore mark, matching the hero panel. */}
          <span aria-hidden="true" className="absolute top-0 start-8 block h-1.5 w-24 overflow-hidden rounded-b-pill sm:start-12">
            <span dir="ltr" className="flex h-full w-full">
              <span className="flex-1 bg-navy-200" />
              <span className="flex-1 bg-white" />
              <span className="flex-1 bg-rouge-500" />
            </span>
          </span>

          <div className="relative grid gap-10 p-8 sm:p-12 lg:grid-cols-2 lg:items-center lg:gap-16 lg:p-16">
            <div className="flex flex-col items-start gap-6">
              <h2 id="register-title" className="text-display-sm font-bold text-white">
                {finalCta.title}
              </h2>

              <p className="max-w-prose text-pretty leading-[2.1] text-navy-100/85">
                {finalCta.description}
              </p>

              <div className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                <Button href={finalCta.primaryCta.href} variant="onDark" size="lg" className="w-full sm:w-auto">
                  {finalCta.primaryCta.label}
                  <Icon name="arrowLeft" className="h-5 w-5" />
                </Button>
                <Button
                  href={finalCta.secondaryCta.href}
                  variant="outlineOnDark"
                  size="lg"
                  className="w-full sm:w-auto"
                >
                  {finalCta.secondaryCta.label}
                </Button>
              </div>
            </div>

            <div className="rounded-card border border-white/15 bg-white/[0.07] p-6 backdrop-blur-sm sm:p-7">
              {submitted ? (
                <div role="status" className="flex flex-col items-center gap-3 py-8 text-center">
                  <span className="flex h-14 w-14 items-center justify-center rounded-full bg-success/15 text-success">
                    <Icon name="check" className="h-7 w-7" strokeWidth={2.2} />
                  </span>
                  <p className="text-base font-bold text-white">درخواست شما ثبت شد</p>
                  <p className="text-sm text-navy-100/80">
                    مشاور آموزشی در کمتر از یک روز کاری با شما تماس می‌گیرد.
                  </p>
                </div>
              ) : (
                <form onSubmit={handleSubmit} className="flex flex-col gap-4" noValidate={false}>
                  <p className="text-sm font-bold text-white">رزرو جلسه مشاوره رایگان</p>

                  <Input
                    label="نام و نام خانوادگی"
                    name="fullName"
                    autoComplete="name"
                    required
                    placeholder="مثلاً سارا مرادی"
                    className="[&>label]:text-navy-100"
                  />

                  <Input
                    label="شماره تماس"
                    name="phone"
                    type="tel"
                    inputMode="tel"
                    autoComplete="tel"
                    required
                    placeholder="۰۹۱۲۰۰۰۰۰۰۰"
                    hint="شماره شما فقط برای هماهنگی جلسه استفاده می‌شود."
                    className="[&>label]:text-navy-100 [&>p]:text-navy-200/70"
                  />

                  <Button type="submit" variant="onDark" size="md" className="mt-1 w-full">
                    رزرو جلسه رایگان
                  </Button>

                  <p className="text-center text-xs text-navy-200/70">{finalCta.reassurance}</p>
                </form>
              )}
            </div>
          </div>
        </Reveal>
      </Container>
    </section>
  );
}
