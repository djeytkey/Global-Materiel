(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var wrap = document.querySelector('.gm-cache-bust-wrap');
		if (!wrap || typeof gmCacheBust === 'undefined') {
			return;
		}

		var toggle = wrap.querySelector('#gm-cache-bust-toggle');
		var modeSelect = wrap.querySelector('#gm-cache-bust-mode');
		if (!toggle || !modeSelect) {
			return;
		}

		function setOnState(isOn) {
			wrap.classList.toggle('is-on', isOn);
			wrap.classList.toggle('is-off', !isOn);
			modeSelect.disabled = !isOn;
		}

		function saveSettings() {
			wrap.classList.add('is-saving');

			var body = new URLSearchParams({
				action: 'gm_save_cache_bust',
				nonce: gmCacheBust.nonce,
				enabled: toggle.checked ? '1' : '0',
				mode: modeSelect.value
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
					modeSelect.value = gmCacheBust.mode;
					setOnState(toggle.checked);
				})
				.finally(function () {
					wrap.classList.remove('is-saving');
					gmCacheBust.enabled = toggle.checked;
					gmCacheBust.mode = modeSelect.value;
				});
		}

		setOnState(toggle.checked);

		toggle.addEventListener('change', function () {
			setOnState(toggle.checked);
			saveSettings();
		});

		modeSelect.addEventListener('change', function () {
			saveSettings();
		});

		wrap.addEventListener('click', function (event) {
			event.stopPropagation();
		});
	});
})();
