(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var wrap = document.querySelector('.gm-cache-bust-wrap');
		if (!wrap || typeof gmCacheBust === 'undefined') {
			return;
		}

		var toggle = wrap.querySelector('#gm-cache-bust-toggle');
		var dropdown = wrap.querySelector('#gm-cache-bust-mode');
		if (!toggle || !dropdown) {
			return;
		}

		var trigger = dropdown.querySelector('.gm-cache-bust-dropdown-toggle');
		var label = dropdown.querySelector('.gm-cache-bust-dropdown-label');
		var options = dropdown.querySelectorAll('.gm-cache-bust-dropdown-option');

		function getMode() {
			return dropdown.getAttribute('data-value') || 'load';
		}

		function setMode(mode) {
			dropdown.setAttribute('data-value', mode);
			Array.prototype.forEach.call(options, function (option) {
				var selected = option.getAttribute('data-value') === mode;
				option.classList.toggle('is-selected', selected);
				option.setAttribute('aria-selected', selected ? 'true' : 'false');
				if (selected) {
					label.textContent = option.textContent;
				}
			});
		}

		function closeDropdown() {
			dropdown.classList.remove('is-open');
			trigger.setAttribute('aria-expanded', 'false');
		}

		function openDropdown() {
			dropdown.classList.add('is-open');
			trigger.setAttribute('aria-expanded', 'true');
		}

		function setOnState(isOn) {
			wrap.classList.toggle('is-on', isOn);
			wrap.classList.toggle('is-off', !isOn);
			if (!isOn) {
				closeDropdown();
			}
		}

		function saveSettings() {
			wrap.classList.add('is-saving');

			var body = new URLSearchParams({
				action: 'gm_save_cache_bust',
				nonce: gmCacheBust.nonce,
				enabled: toggle.checked ? '1' : '0',
				mode: getMode()
			});

			fetch(gmCacheBust.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				body: body.toString()
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (data) {
					if (!data || !data.success) {
						throw new Error('save_failed');
					}
				})
				.catch(function () {
					toggle.checked = gmCacheBust.enabled;
					setMode(gmCacheBust.mode);
					setOnState(toggle.checked);
				})
				.finally(function () {
					wrap.classList.remove('is-saving');
					gmCacheBust.enabled = toggle.checked;
					gmCacheBust.mode = getMode();
				});
		}

		setOnState(toggle.checked);

		toggle.addEventListener('change', function () {
			setOnState(toggle.checked);
			saveSettings();
		});

		trigger.addEventListener('click', function (event) {
			event.preventDefault();
			event.stopPropagation();
			if (dropdown.classList.contains('is-open')) {
				closeDropdown();
			} else {
				openDropdown();
			}
		});

		Array.prototype.forEach.call(options, function (option) {
			option.addEventListener('click', function (event) {
				event.preventDefault();
				event.stopPropagation();
				var mode = option.getAttribute('data-value');
				if (mode && mode !== getMode()) {
					setMode(mode);
					saveSettings();
				}
				closeDropdown();
			});
		});

		document.addEventListener('click', function (event) {
			if (!dropdown.contains(event.target)) {
				closeDropdown();
			}
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				closeDropdown();
			}
		});

		wrap.addEventListener('click', function (event) {
			event.stopPropagation();
		});
	});
})();
