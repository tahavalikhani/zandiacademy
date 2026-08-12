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

			/*
			 * Two behaviours, one script. The FAQ is single-open: an answer you
			 * are not reading is noise between you and the next question. The
			 * syllabus is `data-accordion="multi"`, because comparing two
			 * sections is the point of a syllabus, and closing one to open
			 * another makes that impossible.
			 */
			var multi = accordion.getAttribute('data-accordion') === 'multi';

			triggers.forEach(function (trigger) {
				trigger.addEventListener('click', function () {
					var item = trigger.closest('.accordion__item');
					var isOpen = item.classList.contains('is-open');

					if (!multi) {
						accordion.querySelectorAll('.accordion__item').forEach(function (other) {
							other.classList.remove('is-open');
							other.querySelector('.accordion__trigger').setAttribute('aria-expanded', 'false');
						});
					}

					item.classList.toggle('is-open', !isOpen);
					trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
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
	 * The OTP plugin's runtime chrome
	 *
	 * WHY THIS IS JAVASCRIPT AND NOT MORE CSS
	 *
	 * Digits injects its notices — «شماره موبایل وارد شده قبلاً ثبت‌نام کرده
	 * است», «ارسال کد تایید از محدودیت فراتر رفته است» — with JavaScript, after
	 * an AJAX call. They are not in the markup zandi_clean_provider_form() gets
	 * to read in PHP, so that pass cannot reach them.
	 *
	 * assets/css/panel.css has now been through four rounds of trying to catch
	 * them with class-name substrings: 'error', 'msg', 'notice', then 'alert',
	 * 'warn', 'toast'. Every round shipped, and the notice came back cyan with
	 * pale pink text — a box the owner had to circle in a screenshot to point
	 * at, because at a glance it reads as empty.
	 *
	 * Guessing the class again is not a plan. So this does what the PHP cleaner
	 * does and matches on **what the element is**, not what it is called: a box
	 * that appeared after load, on an auth page, carrying text, painted in a
	 * colour the theme does not own. That is a notice, whatever Digits calls it
	 * this release. The theme tags it and its own stylesheet takes over.
	 *
	 * Nothing here is load-bearing. With this file absent the notice still
	 * appears and still says what it says — it just keeps the plugin's colours,
	 * which is exactly today's behaviour. It cannot fail shut: it only ever adds
	 * a class to something that already exists.
	 * -------------------------------------------------------------------- */

	var NOTICE_CLASS = 'zandi-provider-notice';
	var BUSY_CLASS = 'is-busy';

	/* Surfaces the theme itself paints. Anything else is the plugin's. */
	var THEME_SURFACES = [
		'rgba(0, 0, 0, 0)',
		'transparent',
		'rgb(255, 255, 255)', // #fff — the card
		'rgb(244, 246, 248)', // --mist
		'rgb(253, 242, 244)', // --rouge-50, which is what we repaint notices to
		'rgb(242, 245, 250)'  // --navy-50
	];

	function providerRoot() {
		return document.querySelector('.auth__provider, .digits_ui, #digits_protected');
	}

	/*
	 * Is this newly-inserted element one of the plugin's notices?
	 *
	 * Four signals, all cheap, and deliberately conservative — a false negative
	 * leaves one box looking like it does today, a false positive repaints
	 * something that was fine.
	 */
	function looksLikeNotice(node) {
		if (!node || 1 !== node.nodeType || node.classList.contains(NOTICE_CLASS)) {
			return false;
		}

		/* The theme's own elements are all named; none of them need this. */
		if (node.closest('.auth-aside, .auth__errors, .site-header, .site-footer')) {
			return false;
		}

		/* Nested boxes: tag the outermost one, or the padding stacks. */
		if (node.parentElement && node.parentElement.closest('.' + NOTICE_CLASS)) {
			return false;
		}

		if (!(node.textContent || '').trim()) {
			return false;
		}

		var background = window.getComputedStyle(node).backgroundColor;

		return -1 === THEME_SURFACES.indexOf(background);
	}

	function tagNotices(nodes) {
		Array.prototype.forEach.call(nodes, function (node) {
			if (looksLikeNotice(node)) {
				node.classList.add(NOTICE_CLASS);
			}
		});
	}

	function initProviderNotices() {
		if (!document.body.classList.contains('panel-page') || !window.MutationObserver) {
			return;
		}

		/*
		 * Digits appends some of its furniture to <body> and some inside the
		 * card, and which one a notice lands in has changed between releases.
		 * Watching the document covers both without having to know.
		 */
		new MutationObserver(function (records) {
			records.forEach(function (record) {
				tagNotices(record.addedNodes);
			});
		}).observe(document.body, { childList: true, subtree: true });
	}

	/* ----------------------------------------------------------------------
	 * Busy state on the plugin's buttons
	 *
	 * Sending a code and checking a code are both network round trips of a few
	 * seconds, and Digits gives no feedback for either — the button sits there
	 * looking untouched, which reads as «my tap did not register» and invites a
	 * second one.
	 *
	 * The button is NOT disabled. Digits binds its own handler to these and
	 * reads their state; disabling one inside its own click could cancel the
	 * request this is meant to be reporting on. This is presentation only.
	 * -------------------------------------------------------------------- */

	function clearBusy(button, observer, timer) {
		button.classList.remove(BUSY_CLASS);
		button.removeAttribute('aria-busy');

		if (observer) {
			observer.disconnect();
		}

		window.clearTimeout(timer);
	}

	/*
	 * Does this mutation mean the request finished?
	 *
	 * Watching the provider's own container was the obvious choice and it was
	 * wrong: Digits drops its notices into the card, outside the form, so the
	 * one signal that always arrives — «شماره قبلاً ثبت‌نام کرده است» — was the
	 * one the observer could not see, and the spinner ran until the timeout.
	 *
	 * So the whole document is watched and the noise is filtered instead. The
	 * header toggles a class on scroll, and a student on a phone will scroll
	 * while waiting; without this that would stop the spinner mid-request.
	 */
	function isProgress(record, button) {
		var node = record.target;

		if (!node || node === button || (node.contains && node.contains(button) && 'attributes' === record.type)) {
			return false;
		}

		return !(node.closest && node.closest('.site-header, .site-footer'));
	}

	function markBusy(button) {
		if (button.classList.contains(BUSY_CLASS)) {
			return;
		}

		button.classList.add(BUSY_CLASS);
		button.setAttribute('aria-busy', 'true');

		var observer = null;
		var timer = 0;
		var started = Date.now();

		/*
		 * Cleared by whatever happens next, because every outcome changes the
		 * DOM: the step advances, a notice appears, or the button is replaced.
		 * Watching for that is more reliable than guessing how long Digits
		 * takes.
		 *
		 * The 400ms floor is not cosmetic. Attribute changes count as «what
		 * happens next», and Digits touches attributes inside its own form for
		 * reasons that have nothing to do with the request — a focus ring, a
		 * validation class. Without the floor the spinner appeared and vanished
		 * inside one frame, which is indistinguishable from it never appearing,
		 * and that is the bug this whole block exists to fix.
		 */
		if (window.MutationObserver) {
			observer = new MutationObserver(function (records) {
				var meaningful = Array.prototype.some.call(records, function (record) {
					return isProgress(record, button);
				});

				if (!meaningful) {
					return;
				}

				var elapsed = Date.now() - started;

				if (elapsed >= 400) {
					clearBusy(button, observer, timer);

					return;
				}

				window.clearTimeout(timer);
				timer = window.setTimeout(function () {
					clearBusy(button, observer, timer);
				}, 400 - elapsed);
			});

			observer.observe(document.body, { childList: true, subtree: true, attributes: true });
		}

		/*
		 * The backstop, for the case where the request fails silently and
		 * nothing in the DOM ever moves. A spinner that never stops is worse
		 * than no spinner: it says the site is still trying when it is not.
		 */
		timer = window.setTimeout(function () {
			clearBusy(button, observer, timer);
		}, 20000);
	}

	function initProviderBusy() {
		if (!document.body.classList.contains('panel-page')) {
			return;
		}

		/*
		 * Delegated from the document, because the button a student taps at
		 * step two does not exist when this runs — Digits builds each step as
		 * it needs it.
		 */
		document.addEventListener('click', function (event) {
			var target = event.target;

			if (!target || !target.closest) {
				return;
			}

			var button = target.closest('button, input[type="submit"]');

			if (!button || button.disabled) {
				return;
			}

			/*
			 * Only the button that submits. Digits' secondary controls —
			 * «ارسال دوباره کد», «تغییر شماره» — are type="button" and either
			 * do nothing over the network or swap the step instantly.
			 */
			if ('button' === button.getAttribute('type')) {
				return;
			}

			var root = providerRoot();

			if (root && root.contains(button)) {
				markBusy(button);
			}
		});
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
		initProviderNotices();
		initProviderBusy();
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
