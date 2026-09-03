/**
 * Zandi Academy — progressive enhancement.
 *
 * Everything here is an upgrade to markup that already works: the menu is a
 * list of links, the accordion panels are visible without JS, the carousel is a
 * native scroll container, and the sign-in and sign-up forms are plain POSTs
 * handled server-side. Nothing on the page depends on this file loading.
 *
 * No framework, no build step. 25 KB unminified, 8.6 KB over the wire.
 * Re-measure with `stat -c '%s %n'` when you edit it rather than trusting this
 * line: it said «~5 KB» through five features that each added to it.
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
	 *
	 * Three additive layers, in this order: the arrows (always), the «ادامه
	 * مطلب» expanders (only where a review is actually clipped), and autoplay
	 * (only where there is somewhere to advance to). With this file absent the
	 * track is still a native scroll-snap strip and every review is readable in
	 * full — style.css only clamps under the `.js` root class.
	 * -------------------------------------------------------------------- */

	var AUTOPLAY_MS = 6000;

	function initCarousels() {
		document.querySelectorAll('[data-carousel]').forEach(function (carousel) {
			var track = carousel.querySelector('.carousel__track');
			var prev = carousel.querySelector('[data-carousel-prev]');
			var next = carousel.querySelector('[data-carousel-next]');

			if (!track || !prev || !next) {
				return;
			}

			function overflows() {
				return track.scrollWidth - track.clientWidth > 4;
			}

			function syncBounds() {
				var max = track.scrollWidth - track.clientWidth;
				var offset = Math.abs(track.scrollLeft);

				prev.disabled = offset <= 4;
				next.disabled = max <= 4 || offset >= max - 4;

				// Two arrows that can never do anything read as broken. They
				// come back on their own once there are enough cards to scroll.
				carousel.classList.toggle('is-static', max <= 4);
			}

			function atEnd() {
				var max = track.scrollWidth - track.clientWidth;
				return max <= 4 || Math.abs(track.scrollLeft) >= max - 4;
			}

			function scrollByPage(direction) {
				var first = track.firstElementChild;
				var step = first ? first.getBoundingClientRect().width + 24 : track.clientWidth * 0.8;
				var isRtl = 'rtl' === window.getComputedStyle(track).direction;

				// In RTL the "next" item lives at a smaller (more negative) scrollLeft.
				var sign = ('next' === direction ? 1 : -1) * (isRtl ? -1 : 1);

				track.scrollBy({ left: step * sign, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
			}

			/* --------------------------------------------------------------
			 * Expanders
			 *
			 * The button is revealed only when the quote is really clipped —
			 * scrollHeight past clientHeight — so a short review gets no dead
			 * control. Both labels are already in the markup; this only toggles
			 * aria-expanded and a class, so no user-facing string lives here.
			 * ------------------------------------------------------------ */

			var quotes = [];

			/*
			 * MEASURE AFTER THE FONTS LAND. Vazirmatn is self-hosted and arrives
			 * after first paint, and the fallback stack sets different line
			 * counts — measured at load, a 278-character review reported three
			 * hidden lines and offered an «ادامه مطلب» that expanded nothing.
			 * document.fonts.ready is the only honest moment to ask. Re-run on
			 * resize too: a narrower card wraps to more lines.
			 */
			function measureExpanders() {
				quotes.forEach(function (pair) {
					var clipped = pair.quote.scrollHeight - pair.quote.clientHeight > 2;

					if ('true' === pair.button.getAttribute('aria-expanded')) {
						return;
					}

					pair.button.hidden = !clipped;
					pair.quote.classList.toggle('is-clipped', clipped);
				});
			}

			function initExpanders() {
				carousel.querySelectorAll('[data-testimonial-more]').forEach(function (button) {
					var quote = button.closest('.testimonial__body');
					quote = quote && quote.querySelector('[data-testimonial-quote]');

					if (!quote) {
						return;
					}

					quotes.push({ button: button, quote: quote });

					button.addEventListener('click', function () {
						var expanded = 'true' === button.getAttribute('aria-expanded');

						button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
						quote.classList.toggle('is-expanded', !expanded);
						quote.classList.toggle('is-clipped', expanded);

						// Reading is the opposite of wanting the thing to move.
						stopAutoplay();
						syncBounds();
					});
				});

				measureExpanders();

				if (document.fonts && document.fonts.ready) {
					document.fonts.ready.then(measureExpanders);
				}
			}

			/* --------------------------------------------------------------
			 * Autoplay
			 *
			 * Opt-in per carousel, never under reduced motion, and never when
			 * the track does not overflow — a carousel that advances by a few
			 * pixels and springs back reads as broken. It pauses for hover,
			 * focus, a hidden tab and being scrolled off screen, and it stops
			 * for good the moment somebody takes control, because resuming
			 * under a reader is worse than not moving at all.
			 * ------------------------------------------------------------ */

			var timer = null;
			var stopped = false;
			var paused = false;
			var onScreen = true;
			var programmatic = false;

			function wanted() {
				return carousel.hasAttribute('data-carousel-autoplay') &&
					!prefersReducedMotion &&
					!stopped;
			}

			function tick() {
				if (!wanted() || paused || !onScreen || !overflows()) {
					return;
				}

				programmatic = true;

				if (atEnd()) {
					track.scrollTo({ left: 0, behavior: 'smooth' });
				} else {
					scrollByPage('next');
				}

				// Let the smooth scroll settle before a manual scroll counts.
				window.setTimeout(function () {
					programmatic = false;
				}, 900);
			}

			function startAutoplay() {
				if (timer || !wanted() || !overflows()) {
					return;
				}

				timer = window.setInterval(tick, AUTOPLAY_MS);
			}

			function pauseAutoplay() {
				paused = true;
			}

			function resumeAutoplay() {
				paused = false;
			}

			function stopAutoplay() {
				stopped = true;

				if (timer) {
					window.clearInterval(timer);
					timer = null;
				}
			}

			prev.addEventListener('click', function () {
				stopAutoplay();
				scrollByPage('prev');
			});

			next.addEventListener('click', function () {
				stopAutoplay();
				scrollByPage('next');
			});

			track.addEventListener('scroll', function () {
				syncBounds();

				// A scroll this script did not start means somebody took over.
				if (!programmatic) {
					stopAutoplay();
				}
			}, { passive: true });

			carousel.addEventListener('pointerenter', pauseAutoplay);
			carousel.addEventListener('pointerleave', resumeAutoplay);
			carousel.addEventListener('focusin', pauseAutoplay);
			carousel.addEventListener('focusout', resumeAutoplay);

			document.addEventListener('visibilitychange', function () {
				if (document.hidden) {
					pauseAutoplay();
				} else {
					resumeAutoplay();
				}
			});

			if ('IntersectionObserver' in window) {
				new IntersectionObserver(
					function (entries) {
						onScreen = entries[0].isIntersecting;
					},
					{ threshold: 0.25 }
				).observe(carousel);
			}

			function onResize() {
				syncBounds();
				measureExpanders();
			}

			if ('ResizeObserver' in window) {
				new ResizeObserver(onResize).observe(track);
			} else {
				window.addEventListener('resize', onResize);
			}

			initExpanders();
			syncBounds();
			startAutoplay();
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

	/* ----------------------------------------------------------------------
	 * Copying a SpotPlayer licence
	 *
	 * A licence is 128 hexadecimal characters, set in a monospace block that
	 * wraps over four lines. Selecting that by hand on a phone — inside a
	 * scrolling right-to-left page, with no word boundaries to double-tap —
	 * is the kind of task people give up on halfway and paste wrong.
	 *
	 * THE BUTTON IS HIDDEN IN THE MARKUP AND REVEALED HERE, and only once this
	 * has established that the browser can actually write to the clipboard. A
	 * button that does nothing when pressed is worse than no button, and the
	 * key underneath stays ordinary selectable text either way — which is what
	 * makes leaving the button out a complete answer rather than a degraded
	 * one.
	 *
	 * Neither label is written here. Both «کپی کن» and «کپی شد» are in the
	 * markup, one shown at a time by CSS, because every word on this site sits
	 * behind a filter in PHP and a string hard-coded into a script is a string
	 * nothing can reach.
	 * -------------------------------------------------------------------- */

	var COPY_FEEDBACK_MS = 2400;

	function canCopy() {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return true;
		}

		try {
			return !!(document.queryCommandSupported && document.queryCommandSupported('copy'));
		} catch (error) {
			return false;
		}
	}

	function copyText(text) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(text);
		}

		/*
		 * The fallback, for a browser without the async clipboard — and for the
		 * case nobody thinks of until it happens: the API is missing outside a
		 * secure context, so this is also what runs if the site is ever reached
		 * over plain http.
		 *
		 * The field is moved off-screen rather than hidden. `display: none` and
		 * `visibility: hidden` both make a field unselectable, and a field that
		 * cannot be selected cannot be copied from.
		 */
		return new Promise(function (resolve, reject) {
			var field = document.createElement('textarea');

			field.value = text;
			field.setAttribute('readonly', '');
			field.style.position = 'fixed';
			field.style.insetBlockStart = '-1000px';
			field.style.opacity = '0';

			document.body.appendChild(field);
			field.select();

			if (field.setSelectionRange) {
				field.setSelectionRange(0, text.length);
			}

			var copied = false;

			try {
				copied = document.execCommand('copy');
			} catch (error) {
				copied = false;
			}

			document.body.removeChild(field);

			if (copied) {
				resolve();
			} else {
				reject(new Error('copy failed'));
			}
		});
	}

	function selectText(element) {
		if (!window.getSelection || !document.createRange) {
			return;
		}

		var range = document.createRange();

		range.selectNodeContents(element);

		var selection = window.getSelection();

		selection.removeAllRanges();
		selection.addRange(range);
	}

	function initLicenceCopy() {
		var keys = document.querySelectorAll('.panel-licence__key');

		if (!keys.length) {
			return;
		}

		Array.prototype.forEach.call(keys, function (key) {
			// Tapping the key selects the whole thing, for the student who
			// would rather copy it themselves — and for every browser below.
			key.addEventListener('click', function () {
				selectText(key);
			});
		});

		if (!canCopy()) {
			return;
		}

		var buttons = document.querySelectorAll('.panel-licence__copy');

		Array.prototype.forEach.call(buttons, function (button) {
			var block = button.parentNode && button.parentNode.parentNode;
			var key = block && block.querySelector ? block.querySelector('.panel-licence__key') : null;

			if (!key) {
				return;
			}

			button.hidden = false;

			var timer = null;

			button.addEventListener('click', function () {
				copyText(key.textContent.trim()).then(
					function () {
						button.classList.add('is-copied');

						window.clearTimeout(timer);

						timer = window.setTimeout(function () {
							button.classList.remove('is-copied');
						}, COPY_FEEDBACK_MS);
					},
					function () {
						/*
						 * The key is on screen and the copy did not happen, so
						 * the honest recovery is to hand the student the
						 * selection and let them press copy themselves. No
						 * alert: nothing has been lost and nothing is broken.
						 */
						selectText(key);
					}
				);
			});
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
		initLicenceCopy();
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
