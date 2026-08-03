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

	/* ----------------------------------------------------------------------
	 * Sign-in and sign-up forms
	 *
	 * Three small upgrades to the theme's own auth forms. All of them are
	 * additive: with this file absent the forms are exactly what the server
	 * rendered, and they still post and still validate.
	 *
	 * Scoped to [data-auth-form] on purpose. When the Digits plugin is active it
	 * renders its own form inside .auth__provider and does its own validation
	 * and its own submit handling — reaching into that would be a good way to
	 * break sign-in for everybody.
	 * -------------------------------------------------------------------- */

	function initAuthForms() {
		var forms = document.querySelectorAll('[data-auth-form]');

		if (!forms.length) {
			return;
		}

		Array.prototype.forEach.call(forms, function (form) {
			enhancePasswordToggles(form);
			enhancePhoneField(form);
			guardAgainstDoubleSubmit(form);
		});
	}

	/*
	 * Show/hide the password. `aria-pressed` carries the state, so a screen
	 * reader announces it; the label swaps for everyone else.
	 */
	function enhancePasswordToggles(form) {
		var toggles = form.querySelectorAll('[data-password-toggle]');

		Array.prototype.forEach.call(toggles, function (toggle) {
			var input = document.getElementById(toggle.getAttribute('data-password-toggle'));

			if (!input) {
				return;
			}

			toggle.addEventListener('click', function () {
				var shown = 'text' === input.type;

				input.type = shown ? 'password' : 'text';
				toggle.setAttribute('aria-pressed', shown ? 'false' : 'true');
				toggle.textContent = shown ? 'نمایش' : 'پنهان';

				/*
				 * Changing `type` drops the caret. Putting it back at the end
				 * means "show me what I typed" does not also mean "start over".
				 */
				input.focus();

				if (input.setSelectionRange) {
					try {
						input.setSelectionRange(input.value.length, input.value.length);
					} catch (e) {
						/* Some browsers refuse this on certain input types. */
					}
				}
			});
		});
	}

	/*
	 * A Persian keyboard types ۰۹۱۲, not 0912. The server already folds those
	 * back via zandi_normalize_phone(), so this changes nothing about what is
	 * accepted — it is only so the field shows the number in the shape the
	 * placeholder promised while it is being typed.
	 */
	function enhancePhoneField(form) {
		var fields = form.querySelectorAll('input[type="tel"]');

		Array.prototype.forEach.call(fields, function (field) {
			field.addEventListener('input', function () {
				var latin = field.value.replace(/[۰-۹٠-٩]/g, function (digit) {
					var persian = '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit);

					return String(persian > -1 ? persian : '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
				});

				if (latin === field.value) {
					return;
				}

				/* Preserve the caret; replacing .value alone sends it to the end. */
				var caret = field.selectionStart;

				field.value = latin;

				if (field.setSelectionRange) {
					try {
						field.setSelectionRange(caret, caret);
					} catch (e) {
						/* Not supported here; the value is still correct. */
					}
				}
			});
		});
	}

	/*
	 * A slow connection invites a second tap, and a second tap on the register
	 * form is a second account. The button is disabled only once the browser is
	 * satisfied with the fields, so a form that fails validation is not left
	 * with a dead button.
	 */
	function guardAgainstDoubleSubmit(form) {
		form.addEventListener('submit', function () {
			if (form.checkValidity && !form.checkValidity()) {
				return;
			}

			var button = form.querySelector('[type="submit"]');

			if (!button || button.disabled) {
				return;
			}

			form.classList.add('is-busy');

			/*
			 * Deferred by a tick: disabling a submit button inside its own
			 * submit handler cancels the submission in some browsers.
			 */
			window.setTimeout(function () {
				button.disabled = true;
			}, 0);
		});
	}

	/* ----------------------------------------------------------------------
	 * Preview videos
	 *
	 * The server renders a poster and a play badge wrapped in a link to the
	 * video at its host — no iframe, no player bundle, no third-party request.
	 * This turns that link into a player at the moment it is clicked, and not
	 * one moment earlier.
	 *
	 * Without this file the link still works; it opens the video at the host in
	 * a new tab. That is the fallback, not a failure.
	 * -------------------------------------------------------------------- */

	function initVideos() {
		var facades = document.querySelectorAll('[data-video-embed]');

		Array.prototype.forEach.call(facades, function (facade) {
			facade.addEventListener('click', function (event) {
				/* Let ctrl/cmd/middle-click open the host page, as any link would. */
				if (event.metaKey || event.ctrlKey || event.shiftKey || 1 === event.button) {
					return;
				}

				event.preventDefault();
				playInPlace(facade);
			});
		});
	}

	function playInPlace(facade) {
		var iframe = document.createElement('iframe');

		iframe.src = withAutoplay(
			facade.getAttribute('data-video-embed'),
			facade.hasAttribute('data-video-autoplay')
		);
		iframe.title = facade.getAttribute('data-video-title') || 'ویدیو';
		iframe.setAttribute('allow', 'autoplay; fullscreen; encrypted-media; picture-in-picture');
		iframe.setAttribute('allowfullscreen', '');
		iframe.setAttribute('frameborder', '0');

		facade.parentNode.replaceChild(iframe, facade);

		/*
		 * The click landed on an element that no longer exists, so focus is
		 * back on <body> and a keyboard user has lost their place. Move it to
		 * the player they just asked for.
		 */
		iframe.setAttribute('tabindex', '-1');
		iframe.focus({ preventScroll: true });
	}

	/*
	 * Best-effort: most hosts read `autoplay` off the embed URL, and one that
	 * does not simply ignores an unknown parameter — the visitor presses play
	 * once more and nothing is broken. Never guess at a hash or a path segment,
	 * which a host *would* choke on.
	 */
	function withAutoplay(src, wanted) {
		if (!wanted || !src) {
			return src;
		}

		return src + (src.indexOf('?') > -1 ? '&' : '?') + 'autoplay=true';
	}

	/* ------------------------------------------------------------------ */

	function init() {
		initReveal();
		initHeader();
		initMenu();
		initCounters();
		initAccordions();
		initCarousels();
		initAuthForms();
		initVideos();
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
