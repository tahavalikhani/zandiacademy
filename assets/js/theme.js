/**
 * Zandi Academy — progressive enhancement.
 *
 * Everything here is an upgrade to markup that already works: the menu is a
 * list of links, the accordion panels are visible without JS, the carousel is a
 * native scroll container, and the sign-in and sign-up forms are plain POSTs
 * handled server-side. Nothing on the page depends on this file loading.
 *
 * No framework, no build step. ~5 KB unminified.
 */
(function () {
	'use strict';

	var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* ----------------------------------------------------------------------
	 * Scroll reveal
	 *
	 * One observer for the whole page. Elements start at opacity 0 in CSS and
	 * are revealed on entry; if IntersectionObserver is missing, everything is
	 * shown immediately rather than left invisible.
	 * -------------------------------------------------------------------- */

	function initReveal() {
		var items = document.querySelectorAll('.reveal');

		if (!items.length) {
			return;
		}

		if (prefersReducedMotion || !('IntersectionObserver' in window)) {
			items.forEach(function (item) {
				item.classList.add('is-visible');
			});
			return;
		}

		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-visible');
						observer.unobserve(entry.target);
					}
				});
			},
			{ threshold: 0.15, rootMargin: '0px 0px -80px 0px' }
		);

		items.forEach(function (item) {
			observer.observe(item);
		});
	}

	/* ----------------------------------------------------------------------
	 * Sticky header: border and blur only once it overlaps content
	 * -------------------------------------------------------------------- */

	function initHeader() {
		var header = document.getElementById('site-header');

		if (!header) {
			return;
		}

		function sync() {
			header.classList.toggle('is-scrolled', window.scrollY > 12);
		}

		sync();
		window.addEventListener('scroll', sync, { passive: true });
	}

	/* ----------------------------------------------------------------------
	 * Mobile menu
	 * -------------------------------------------------------------------- */

	function initMenu() {
		var header = document.getElementById('site-header');
		var toggle = document.querySelector('.menu-toggle');
		var panel = document.getElementById('menu-mobile');

		if (!header || !toggle || !panel) {
			return;
		}

		function setOpen(open) {
			header.classList.toggle('is-menu-open', open);
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			toggle.setAttribute(
				'aria-label',
				open ? toggle.dataset.labelClose : toggle.dataset.labelOpen
			);
			// Stop the page scrolling behind the panel.
			document.body.style.overflow = open ? 'hidden' : '';
		}

		toggle.addEventListener('click', function () {
			setOpen(toggle.getAttribute('aria-expanded') !== 'true');
		});

		panel.addEventListener('click', function (event) {
			if (event.target.closest('a')) {
				setOpen(false);
			}
		});

		document.addEventListener('keydown', function (event) {
			if ('Escape' === event.key && header.classList.contains('is-menu-open')) {
				setOpen(false);
				toggle.focus();
			}
		});
	}

	/* ----------------------------------------------------------------------
	 * Course-page header
	 *
	 * Its own markup and palette, so it gets its own small handler rather than
	 * bending the site header's. Both degrade to plain links without JS.
	 * -------------------------------------------------------------------- */

	function initCourseHeader() {
		var header = document.getElementById('course-header');

		if (!header) {
			return;
		}

		function sync() {
			header.classList.toggle('is-scrolled', window.scrollY > 8);
		}

		sync();
		window.addEventListener('scroll', sync, { passive: true });

		var toggle = header.querySelector('[data-course-menu]');
		var panel = document.getElementById('course-mobile-nav');

		if (!toggle || !panel) {
			return;
		}

		function setOpen(open) {
			header.classList.toggle('is-open', open);
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			toggle.setAttribute(
				'aria-label',
				open ? toggle.dataset.labelClose : toggle.dataset.labelOpen
			);
		}

		toggle.addEventListener('click', function () {
			setOpen(toggle.getAttribute('aria-expanded') !== 'true');
		});

		panel.addEventListener('click', function (event) {
			if (event.target.closest('a')) {
				setOpen(false);
			}
		});

		document.addEventListener('keydown', function (event) {
			if ('Escape' === event.key && header.classList.contains('is-open')) {
				setOpen(false);
				toggle.focus();
			}
		});
	}

	/* ----------------------------------------------------------------------
	 * Scroll spy
	 *
	 * Marks the nav item whose section occupies the middle of the viewport.
	 * A band across the centre, so a section is "active" while it sits under
	 * the reader's eye rather than merely when its edge appears.
	 * -------------------------------------------------------------------- */

	function initScrollSpy() {
		if (!document.body.classList.contains('has-anchor-nav') || !('IntersectionObserver' in window)) {
			return;
		}

		var items = document.querySelectorAll('.menu-desktop [data-target]');

		if (!items.length) {
			return;
		}

		var map = {};
		var sections = [];

		items.forEach(function (item) {
			var section = document.getElementById(item.dataset.target);

			if (section) {
				map[item.dataset.target] = item;
				sections.push(section);
			}
		});

		if (!sections.length) {
			return;
		}

		var observer = new IntersectionObserver(
			function (entries) {
				var visible = entries
					.filter(function (entry) {
						return entry.isIntersecting;
					})
					.sort(function (a, b) {
						return b.intersectionRatio - a.intersectionRatio;
					});

				if (!visible.length) {
					return;
				}

				var id = visible[0].target.id;

				Object.keys(map).forEach(function (key) {
					map[key].classList.toggle('is-active', key === id);
				});
			},
			{ rootMargin: '-45% 0px -45% 0px', threshold: [0, 0.25, 0.5, 1] }
		);

		sections.forEach(function (section) {
			observer.observe(section);
		});
	}

	/* ----------------------------------------------------------------------
	 * Stat count-up
	 * -------------------------------------------------------------------- */

	var PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

	function toPersianDigits(value) {
		return String(value).replace(/\d/g, function (digit) {
			return PERSIAN_DIGITS[Number(digit)];
		});
	}

	function initCounters() {
		var stats = document.querySelectorAll('[data-count-to]');

		// The final figure is already rendered server-side, so with reduced
		// motion or no observer there is simply nothing to do.
		if (!stats.length || prefersReducedMotion || !('IntersectionObserver' in window)) {
			return;
		}

		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting) {
						return;
					}

					countUp(entry.target);
					observer.unobserve(entry.target);
				});
			},
			{ threshold: 0.4 }
		);

		stats.forEach(function (stat) {
			observer.observe(stat);
		});
	}

	function countUp(stat) {
		var output = stat.querySelector('.stat__number');
		var target = parseInt(stat.dataset.countTo, 10);

		if (!output || isNaN(target)) {
			return;
		}

		var duration = 1400;
		var start = null;

		function frame(timestamp) {
			if (null === start) {
				start = timestamp;
			}

			var progress = Math.min((timestamp - start) / duration, 1);
			// The shared premium easing curve, as an easeOutQuint.
			var eased = 1 - Math.pow(1 - progress, 5);

			output.textContent = toPersianDigits(Math.round(target * eased).toLocaleString('en-US'));

			if (progress < 1) {
				window.requestAnimationFrame(frame);
			}
		}

		window.requestAnimationFrame(frame);
	}

	/* ----------------------------------------------------------------------
	 * Accordion
	 * -------------------------------------------------------------------- */

	function initAccordions() {
		document.querySelectorAll('[data-accordion]').forEach(function (accordion) {
			var triggers = accordion.querySelectorAll('.accordion__trigger');

			triggers.forEach(function (trigger) {
				trigger.addEventListener('click', function () {
					var item = trigger.closest('.accordion__item');
					var isOpen = item.classList.contains('is-open');

					// Single-open: close the others first.
					accordion.querySelectorAll('.accordion__item').forEach(function (other) {
						other.classList.remove('is-open');
						other.querySelector('.accordion__trigger').setAttribute('aria-expanded', 'false');
					});

					if (!isOpen) {
						item.classList.add('is-open');
						trigger.setAttribute('aria-expanded', 'true');
					}
				});
			});
		});
	}

	/* ----------------------------------------------------------------------
	 * Carousel
	 *
	 * `scrollLeft` runs 0 → max in LTR and −max → 0 in RTL, so every bound
	 * check goes through Math.abs.
	 * -------------------------------------------------------------------- */

	function initCarousels() {
		document.querySelectorAll('[data-carousel]').forEach(function (carousel) {
			var track = carousel.querySelector('.carousel__track');
			var prev = carousel.querySelector('[data-carousel-prev]');
			var next = carousel.querySelector('[data-carousel-next]');

			if (!track || !prev || !next) {
				return;
			}

			function syncBounds() {
				var max = track.scrollWidth - track.clientWidth;
				var offset = Math.abs(track.scrollLeft);

				prev.disabled = offset <= 4;
				next.disabled = max <= 4 || offset >= max - 4;
			}

			function scrollByPage(direction) {
				var first = track.firstElementChild;
				var step = first ? first.getBoundingClientRect().width + 24 : track.clientWidth * 0.8;
				var isRtl = 'rtl' === window.getComputedStyle(track).direction;

				// In RTL the "next" item lives at a smaller (more negative) scrollLeft.
				var sign = ('next' === direction ? 1 : -1) * (isRtl ? -1 : 1);

				track.scrollBy({ left: step * sign, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
			}

			prev.addEventListener('click', function () {
				scrollByPage('prev');
			});

			next.addEventListener('click', function () {
				scrollByPage('next');
			});

			track.addEventListener('scroll', syncBounds, { passive: true });

			if ('ResizeObserver' in window) {
				new ResizeObserver(syncBounds).observe(track);
			} else {
				window.addEventListener('resize', syncBounds);
			}

			syncBounds();
		});
	}

	/* ------------------------------------------------------------------ */

	function init() {
		initReveal();
		initHeader();
		initMenu();
		initCourseHeader();
		initScrollSpy();
		initCounters();
		initAccordions();
		initCarousels();
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
