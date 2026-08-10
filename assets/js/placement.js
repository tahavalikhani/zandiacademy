/**
 * Zandi Academy — placement test.
 *
 * An upgrade to a form that already works. The server renders every question
 * inside one <form> with one submit button; without this file that is a long
 * page, thirty answers and a result — which is the whole test, just less
 * comfortable. This turns it into one question per screen with a progress bar.
 *
 * NOTHING HERE SCORES ANYTHING. The answer key never reaches the browser: the
 * options were shuffled server-side and each radio carries its position on
 * screen, not the option's own index. All this file does is decide which step is
 * visible and how long the sitting took.
 *
 * No framework, no build step, no storage. State lives in the two variables
 * below and dies with the page, which is deliberate — a half-finished test
 * resurrected from localStorage a week later is not a measurement of anything.
 */
(function () {
	'use strict';

	var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* ----------------------------------------------------------------------
	 * The stepper
	 * -------------------------------------------------------------------- */

	function initTest() {
		var form = document.querySelector('[data-pt-form]');

		if (!form) {
			return;
		}

		var steps = Array.prototype.slice.call(form.querySelectorAll('[data-pt-step]'));

		if (!steps.length) {
			return;
		}

		var bar = form.querySelector('[data-pt-bar]');
		var fill = form.querySelector('[data-pt-fill]');
		var label = form.querySelector('[data-pt-label]');
		var duration = form.querySelector('[data-pt-duration]');
		var submit = form.querySelector('[data-pt-submit]');

		var current = 0;
		var startedAt = Date.now();

		/*
		 * The questions are `required` so that with scripts off the browser
		 * refuses a half-finished form. With scripts on they must not be: a
		 * required control inside a hidden step cannot be focused, and Chrome
		 * responds to that by refusing to submit at all with nothing on screen
		 * to explain why. The flow is enforced below instead.
		 */
		form.noValidate = true;

		Array.prototype.forEach.call(form.querySelectorAll('input[required]'), function (input) {
			input.removeAttribute('required');
		});

		// Only now: if this file had failed to load, the class is absent and
		// every question stays visible.
		form.classList.add('is-stepped');

		var questionSteps = steps.filter(function (step) {
			return step.hasAttribute('data-pt-question');
		});
		var totalQuestions = questionSteps.length;

		if (bar) {
			bar.hidden = false;
		}

		function stepIsAnswered(step) {
			var options = step.querySelectorAll('input[type="radio"]');

			if (!options.length) {
				return true; // The language-switch card has nothing to answer.
			}

			return Array.prototype.some.call(options, function (option) {
				return option.checked;
			});
		}

		function syncNext(step) {
			var next = step.querySelector('[data-pt-next]');

			if (!next) {
				return;
			}

			next.hidden = false;
			next.disabled = !stepIsAnswered(step);
		}

		function progressLabel(step) {
			if (!label) {
				return;
			}

			var number = step.getAttribute('data-pt-question');

			/*
			 * On the switch card the counter holds the number of the question
			 * that is coming, rather than blanking or jumping back to zero.
			 */
			if (!number) {
				var upcoming = steps.slice(steps.indexOf(step)).filter(function (item) {
					return item.hasAttribute('data-pt-question');
				})[0];

				number = upcoming ? upcoming.getAttribute('data-pt-question') : String(totalQuestions);
			}

			var template = label.getAttribute('data-pt-template') || '';

			label.textContent = template
				.replace('%1$s', toPersianDigits(number))
				.replace('%2$s', toPersianDigits(totalQuestions));

			if (fill) {
				fill.style.inlineSize = (Number(number) / totalQuestions) * 100 + '%';
			}
		}

		function show(index, moveFocus) {
			steps.forEach(function (step, position) {
				step.classList.toggle('is-current', position === index);
			});

			current = index;

			var step = steps[index];

			syncNext(step);
			progressLabel(step);

			if (submit) {
				submit.classList.toggle('is-visible', index === steps.length - 1);
			}

			if (moveFocus) {
				/*
				 * Focus moves to the step itself, not to its first radio.
				 * Landing on an option would have a screen reader announce
				 * «گزینهٔ ۱ از ۴» before the question it belongs to; landing on
				 * the step reads the question, then the options.
				 */
				step.focus({ preventScroll: true });

				var top = form.getBoundingClientRect().top + window.pageYOffset - 100;

				window.scrollTo({
					top: top < 0 ? 0 : top,
					behavior: prefersReducedMotion ? 'auto' : 'smooth'
				});
			}
		}

		form.addEventListener('change', function (event) {
			if (event.target.matches('input[type="radio"]')) {
				syncNext(steps[current]);
			}
		});

		form.addEventListener('click', function (event) {
			var next = event.target.closest('[data-pt-next]');

			if (!next || next.disabled) {
				return;
			}

			event.preventDefault();

			if (current < steps.length - 1) {
				show(current + 1, true);
			}
		});

		/*
		 * Enter on a radio would otherwise submit the form from question one.
		 * It advances instead, which is what the key looks like it should do.
		 */
		form.addEventListener('keydown', function (event) {
			if ('Enter' !== event.key || current === steps.length - 1) {
				return;
			}

			if (!event.target.matches('input[type="radio"]')) {
				return;
			}

			event.preventDefault();

			if (stepIsAnswered(steps[current])) {
				show(current + 1, true);
			}
		});

		form.addEventListener('submit', function () {
			if (duration) {
				duration.value = String(Math.round((Date.now() - startedAt) / 1000));
			}
		});

		show(0, false);
	}

	/* ----------------------------------------------------------------------
	 * Persian digits
	 *
	 * The same table as theme.js. Duplicated rather than shared because this
	 * file loads on two pages and theme.js on all of them — a shared module
	 * would mean either a third request or a global, for eleven lines.
	 * -------------------------------------------------------------------- */

	var PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

	function toPersianDigits(value) {
		return String(value).replace(/\d/g, function (digit) {
			return PERSIAN_DIGITS[Number(digit)];
		});
	}

	/* ----------------------------------------------------------------------
	 * Sharing a result
	 *
	 * The button is `hidden` in the markup and revealed only when there is
	 * something to reveal it for, so a browser with neither a share sheet nor a
	 * clipboard never shows a button that does nothing.
	 *
	 * What travels is a sentence and the test's public address — never the URL
	 * of this page, which is a private token that expires in a day.
	 * -------------------------------------------------------------------- */

	function initShare() {
		var button = document.querySelector('[data-pt-share]');

		if (!button) {
			return;
		}

		var text = button.getAttribute('data-pt-share');
		var canShare = 'share' in navigator;
		var canCopy = navigator.clipboard && navigator.clipboard.writeText;

		if (!canShare && !canCopy) {
			return;
		}

		button.hidden = false;

		button.addEventListener('click', function () {
			if (canShare) {
				navigator.share({ text: text }).catch(function () {
					/* Dismissing the share sheet is not an error. */
				});
				return;
			}

			navigator.clipboard.writeText(text).then(function () {
				var done = button.getAttribute('data-pt-shared');

				if (!done) {
					return;
				}

				// innerHTML, not textContent: the button holds an inline SVG
				// from the icon registry, and restoring plain text would drop it.
				var original = button.innerHTML;

				button.textContent = done;
				button.disabled = true;

				window.setTimeout(function () {
					button.innerHTML = original;
					button.disabled = false;
				}, 2400);
			}).catch(function () {
				/* Clipboard refused — leave the button as it was. */
			});
		});
	}

	/* ------------------------------------------------------------------ */

	function init() {
		initTest();
		initShare();
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
