/* CounterSlot: register for an event from [counterslot_events]. */
(function () {
	'use strict';

	const cfg = window.cslotEvents;
	const root = document.querySelector('[data-sb-events]');
	if (!cfg || !root) {
		return;
	}

	root.addEventListener('submit', async (e) => {
		const form = e.target.closest('.sb-event-form');
		if (!form) {
			return;
		}
		e.preventDefault();
		if (!form.reportValidity()) {
			return;
		}
		const card = form.closest('.sb-event-card');
		const message = card.querySelector('.sb-message');
		const button = form.querySelector('[type="submit"]');
		const show = (text, type) => {
			message.textContent = text;
			message.className = 'sb-message' + (type ? ' sb-message-' + type : '');
			message.hidden = !text;
		};

		button.disabled = true;
		show(cfg.i18n.sending);
		const data = new FormData(form);
		data.append('action', 'cslot_event_register');
		data.append('_wpnonce', cfg.nonce);
		let json = null;
		try {
			const res = await fetch(cfg.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' });
			json = await res.json();
		} catch (err) {
			// Network failure or a non-JSON response.
		}
		if (json && json.success) {
			form.closest('details').hidden = true;
			show(json.data.message, 'success');
			message.focus();
		} else {
			show((json && json.data && json.data.message) || cfg.i18n.error, 'error');
			button.disabled = false;
		}
	});
})();
