/**
 * واژه‌نامه — the dictionary's own script, loaded on /dictionary/ pages only.
 *
 * Every feature here is an addition to a page that already works without it:
 * the search form submits and the server answers. So each control this file
 * drives ships `hidden` in the markup and is revealed here, only once it is
 * known to work (CLAUDE.md, «A control that only works with JavaScript ships
 * hidden»). And it holds no user-facing words: every label is in the markup.
 *
 *   1. Suggestions while typing — from static JSON files under
 *      assets/dictionary/, fetched straight from the web server. Typing never
 *      runs PHP and never leaves the site.
 *   2. A row of French letters for people without a French keyboard.
 *   3. «شنیدن تلفظ» — the browser's own French voice (speechSynthesis), shown
 *      only when the device actually has one. No audio files, no third party.
 *   4. Recent searches — kept in this browser's localStorage, nowhere else.
 *
 * No dependencies, passive listeners, nothing on scroll.
 */
(function () {
	'use strict';

	var form = document.querySelector('.dict-search');

	if (!form) {
		return;
	}

	var input = form.querySelector('.dict-search__input');
	var list = form.querySelector('.dict-suggest');
	var base = form.getAttribute('data-suggest-base') || '';
	var version = form.getAttribute('data-suggest-ver') || '';
	var wordUrl = form.getAttribute('data-word-url') || '';

	/* ------------------------------------------------------------------
	 * Keys — the same filing rules as zandi_dict_norm_fr() / _fa() in PHP.
	 * ---------------------------------------------------------------- */

	function isPersian(text) {
		return /[؀-ۿ]/.test(text);
	}

	function normFr(text) {
		return text
			.trim()
			.toLowerCase()
			.replace(/œ/g, 'oe')
			.replace(/æ/g, 'ae')
			.normalize('NFD')
			.replace(/[̀-ͯ]/g, '')
			.replace(/[’‘ʼ]/g, "'")
			.replace(/\s+/g, ' ');
	}

	function normFa(text) {
		return text
			.replace(/[ً-ٰٟـ]/g, '')
			.replace(/[يى]/g, 'ی')
			.replace(/ك/g, 'ک')
			.replace(/[أإ]/g, 'ا')
			.replace(/[ةۀ]/g, 'ه')
			.replace(/[‌\s]/g, '');
	}

	function shardFor(key, persian) {
		if (persian) {
			var code = key.codePointAt(0).toString(16);
			return 'fa-' + ('0000' + code).slice(-4);
		}

		var two = (key + '__').slice(0, 2);
		var out = '';

		for (var i = 0; i < 2; i++) {
			var c = two.charAt(i);
			out += c >= 'a' && c <= 'z' ? c : '_';
		}

		return 'fr-' + out;
	}

	function urlFor(word, from) {
		var url = wordUrl.replace('__WORD__', encodeURIComponent(word));

		if (from) {
			url += (url.indexOf('?') === -1 ? '?' : '&') + 'from=' + encodeURIComponent(from);
		}

		return url;
	}

	/* ------------------------------------------------------------------
	 * 1. Suggestions
	 * ---------------------------------------------------------------- */

	var shards = {};
	var active = -1;
	var timer = 0;
	var latest = '';

	function load(name) {
		if (!shards[name]) {
			shards[name] = fetch(base + name + '.json?ver=' + encodeURIComponent(version), { credentials: 'omit' })
				.then(function (response) {
					return response.ok ? response.json() : [];
				})
				.catch(function () {
					delete shards[name];
					return [];
				});
		}

		return shards[name];
	}

	function close() {
		list.hidden = true;
		list.textContent = '';
		input.setAttribute('aria-expanded', 'false');
		input.removeAttribute('aria-activedescendant');
		active = -1;
	}

	function render(rows) {
		list.textContent = '';
		active = -1;

		if (!rows.length) {
			close();
			return;
		}

		rows.forEach(function (row, index) {
			var item = document.createElement('li');
			var link = document.createElement('a');
			var word = document.createElement('span');
			var gloss = document.createElement('span');
			var isForm = row[2] !== '';

			item.id = 'dict-suggest-' + index;
			item.setAttribute('role', 'option');
			item.className = 'dict-suggest__item' + (isForm ? ' is-form' : '');

			link.href = isForm ? urlFor(row[2], row[0]) : urlFor(row[0]);
			link.tabIndex = -1;

			word.className = 'dict-suggest__word';
			word.dir = 'auto';
			word.textContent = row[0];

			gloss.className = 'dict-suggest__gloss';
			gloss.dir = 'auto';
			gloss.textContent = isForm ? row[2] : row[1];

			link.appendChild(word);
			link.appendChild(gloss);
			item.appendChild(link);
			list.appendChild(item);
		});

		list.hidden = false;
		input.setAttribute('aria-expanded', 'true');
	}

	function suggest() {
		var text = input.value;
		var persian = isPersian(text);
		var key = persian ? normFa(text) : normFr(text);

		latest = key;

		if (key.length < 2) {
			close();
			return;
		}

		load(shardFor(key, persian)).then(function (rows) {
			if (key !== latest) {
				return; // The reader has typed on since this was asked.
			}

			var out = [];
			var seen = {};

			for (var i = 0; i < rows.length && out.length < 8; i++) {
				var rowKey = persian ? normFa(rows[i][0]) : normFr(rows[i][0]);
				var target = rows[i][2] || rows[i][0];

				if (rowKey.indexOf(key) === 0 && !seen[target + '|' + rows[i][0]]) {
					seen[target + '|' + rows[i][0]] = true;
					out.push(rows[i]);
				}
			}

			render(out);
		});
	}

	function move(step) {
		var items = list.querySelectorAll('.dict-suggest__item');

		if (!items.length) {
			return;
		}

		if (active >= 0) {
			items[active].classList.remove('is-active');
		}

		active = (active + step + items.length) % items.length;
		items[active].classList.add('is-active');
		input.setAttribute('aria-activedescendant', items[active].id);
	}

	if (input && list && base && wordUrl && window.fetch) {
		input.addEventListener('input', function () {
			window.clearTimeout(timer);
			timer = window.setTimeout(suggest, 60);
		});

		input.addEventListener('keydown', function (event) {
			if (list.hidden) {
				return;
			}

			if (event.key === 'ArrowDown') {
				event.preventDefault();
				move(1);
			} else if (event.key === 'ArrowUp') {
				event.preventDefault();
				move(-1);
			} else if (event.key === 'Escape') {
				close();
			} else if (event.key === 'Enter' && active >= 0) {
				event.preventDefault();
				window.location.href = list.querySelectorAll('.dict-suggest__item a')[active].href;
			}
		});

		// Close when focus leaves the search, after a click on a suggestion lands.
		form.addEventListener('focusout', function () {
			window.setTimeout(function () {
				if (!form.contains(document.activeElement)) {
					close();
				}
			}, 150);
		});
	}

	/* ------------------------------------------------------------------
	 * 2. French letters
	 * ---------------------------------------------------------------- */

	var accents = form.querySelector('.dict-accents');

	if (accents && input && typeof input.setRangeText === 'function') {
		accents.hidden = false;

		accents.addEventListener('mousedown', function (event) {
			// Keep the caret in the field: a button would otherwise take focus.
			if (event.target.closest('.dict-accents__key')) {
				event.preventDefault();
			}
		});

		accents.addEventListener('click', function (event) {
			var key = event.target.closest('.dict-accents__key');

			if (!key) {
				return;
			}

			var start = input.selectionStart === null ? input.value.length : input.selectionStart;
			var end = input.selectionEnd === null ? input.value.length : input.selectionEnd;

			input.setRangeText(key.getAttribute('data-letter'), start, end, 'end');
			input.focus();
			input.dispatchEvent(new Event('input', { bubbles: true }));
		});
	}

	/* ------------------------------------------------------------------
	 * 3. Pronunciation
	 * ---------------------------------------------------------------- */

	var buttons = document.querySelectorAll('.dict-listen');

	function frenchVoice() {
		var voices = window.speechSynthesis.getVoices();

		for (var i = 0; i < voices.length; i++) {
			if (/^fr(-|_|$)/i.test(voices[i].lang)) {
				return voices[i];
			}
		}

		return null;
	}

	function enableSpeech() {
		var voice = frenchVoice();

		if (!voice) {
			return false;
		}

		buttons.forEach(function (button) {
			if (!button.hidden) {
				return;
			}

			button.hidden = false;
			button.addEventListener('click', function () {
				var utterance = new window.SpeechSynthesisUtterance(button.getAttribute('data-say') || '');

				utterance.lang = voice.lang;
				utterance.voice = voice;
				utterance.rate = 0.9;
				window.speechSynthesis.cancel();
				window.speechSynthesis.speak(utterance);
			});
		});

		return true;
	}

	if (buttons.length && 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window) {
		// Voices arrive asynchronously in most browsers.
		if (!enableSpeech() && 'onvoiceschanged' in window.speechSynthesis) {
			window.speechSynthesis.addEventListener('voiceschanged', function once() {
				if (enableSpeech()) {
					window.speechSynthesis.removeEventListener('voiceschanged', once);
				}
			});
		}
	}

	/* ------------------------------------------------------------------
	 * 4. Recent searches
	 * ---------------------------------------------------------------- */

	var STORE = 'zandiDictRecent';
	var page = document.querySelector('.dict-page');
	var recent = [];

	try {
		recent = JSON.parse(window.localStorage.getItem(STORE) || '[]');
		if (!Array.isArray(recent)) {
			recent = [];
		}
	} catch (e) {
		recent = [];
	}

	var current = page ? page.getAttribute('data-dict-word') || page.getAttribute('data-dict-word-fa') || '' : '';

	if (current) {
		recent = [current].concat(recent.filter(function (w) {
			return w !== current;
		})).slice(0, 10);

		try {
			window.localStorage.setItem(STORE, JSON.stringify(recent));
		} catch (e) {
			// Private mode or storage blocked: the list simply is not kept.
		}
	}

	var box = document.querySelector('.dict-recent');
	var shown = recent.filter(function (w) {
		return typeof w === 'string' && w && w !== current;
	});

	if (box && shown.length) {
		var ul = box.querySelector('.dict-recent__list');

		shown.forEach(function (word) {
			var li = document.createElement('li');
			var a = document.createElement('a');

			a.className = 'dict-chip dict-chip--small';
			a.href = urlFor(word);
			a.dir = 'auto';
			a.textContent = word;
			li.appendChild(a);
			ul.appendChild(li);
		});

		box.hidden = false;
	}
})();
