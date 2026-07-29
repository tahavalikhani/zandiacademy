import { contact, footerLinks, site, socials } from '../../data/content';
import Container from '../ui/Container';
import Icon from '../ui/Icon';
import Logo from './Logo';

/** Current Jalali year in Persian digits, so the copyright line never goes stale. */
const currentYear = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { year: 'numeric' }).format(
  new Date(),
);

export default function Footer() {
  return (
    <footer id="contact" className="scroll-mt-28 bg-navy-800 text-navy-100">
      <Container className="py-14 sm:py-16">
        <div className="grid gap-12 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,2fr)] lg:gap-16">
          <div className="flex flex-col gap-6">
            <Logo onDark />

            <p className="max-w-sm text-pretty text-sm leading-loose text-navy-100/70">
              {site.description}
            </p>

            <ul className="flex flex-wrap items-center gap-2">
              {socials.map((social) => (
                <li key={social.label}>
                  <a
                    href={social.href}
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label={social.label}
                    className="flex h-11 w-11 items-center justify-center rounded-soft border border-white/10 bg-white/5 text-navy-100 transition-all duration-300 ease-premium hover:-translate-y-0.5 hover:border-white/25 hover:bg-white/10 hover:text-white"
                  >
                    <Icon name={social.icon} className="h-5 w-5" />
                  </a>
                </li>
              ))}
            </ul>
          </div>

          {/* The contact column carries an address and opening hours, so it gets
              extra width instead of an equal quarter. */}
          <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-[repeat(3,minmax(0,1fr))_1.5fr]">
            {footerLinks.map((group) => (
              <nav key={group.title} aria-label={group.title} className="flex flex-col gap-4">
                <h2 className="text-sm font-bold text-white">{group.title}</h2>
                <ul className="flex flex-col gap-3">
                  {group.links.map((link) => (
                    <li key={link.label}>
                      <a
                        href={link.href}
                        className="text-sm text-navy-100/70 transition-colors duration-200 hover:text-white"
                      >
                        {link.label}
                      </a>
                    </li>
                  ))}
                </ul>
              </nav>
            ))}

            <div className="flex flex-col gap-4">
              <h2 className="text-sm font-bold text-white">تماس با ما</h2>
              <ul className="flex flex-col gap-4 text-sm text-navy-100/70">
                <li>
                  <a
                    href={contact.phoneHref}
                    className="flex items-start gap-2.5 transition-colors duration-200 hover:text-white"
                  >
                    <Icon name="phone" className="mt-0.5 h-4 w-4 shrink-0 text-navy-300" />
                    <span dir="ltr" className="text-start">
                      {contact.phone}
                    </span>
                  </a>
                </li>
                <li>
                  <a
                    href={`mailto:${contact.email}`}
                    className="flex items-start gap-2.5 transition-colors duration-200 hover:text-white"
                  >
                    <Icon name="mail" className="mt-0.5 h-4 w-4 shrink-0 text-navy-300" />
                    <span dir="ltr" className="text-start">
                      {contact.email}
                    </span>
                  </a>
                </li>
                <li className="flex items-start gap-2.5">
                  <Icon name="pin" className="mt-0.5 h-4 w-4 shrink-0 text-navy-300" />
                  <span className="leading-relaxed">{contact.address}</span>
                </li>
                <li className="flex items-start gap-2.5">
                  <Icon name="clock" className="mt-0.5 h-4 w-4 shrink-0 text-navy-300" />
                  <span className="leading-relaxed">{contact.hours}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div className="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-8 sm:flex-row">
          <p className="text-xs text-navy-100/60">
            © {currentYear} {site.name} — تمامی حقوق محفوظ است.
          </p>

          <p className="flex items-center gap-2 text-xs text-navy-100/60">
            <Icon name="globe" className="h-4 w-4 text-navy-300" />
            {site.tagline}
          </p>
        </div>
      </Container>
    </footer>
  );
}
