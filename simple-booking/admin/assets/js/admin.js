/* Simple Booking admin: AJAX for forms, delete buttons and status selects. */
(function () {
	'use strict';

	const cfg = window.sbAdmin;
	if (!cfg) {
		return;
	}

	async function post(action, data) {
		data.append('action', action);
		data.append('_wpnonce', cfg.nonce);
		let json = null;
		try {
			const res = await fetch(cfg.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' });
			json = await res.json();
		} catch (e) {
			// Network failure or a non-JSON response; fall through to the generic error.
		}
		if (!json || !json.success) {
			throw new Error((json && json.data && json.data.message) || cfg.i18n.error);
		}
		return json.data || {};
	}

	document.addEventListener('submit', async (e) => {
		const form = e.target.closest('form[data-sb-action]');
		if (!form) {
			return;
		}
		e.preventDefault();
		const button = form.querySelector('[type="submit"]');
		button.disabled = true;
		try {
			await post(form.dataset.sbAction, new FormData(form));
			window.location.href = form.dataset.sbRedirect || window.location.href;
		} catch (err) {
			window.alert(err.message);
			button.disabled = false;
		}
	});

	document.addEventListener('click', async (e) => {
		const button = e.target.closest('[data-sb-delete]');
		if (!button || !window.confirm(cfg.i18n.confirmDelete)) {
			return;
		}
		button.disabled = true;
		const data = new FormData();
		data.append('id', button.dataset.id);
		try {
			const res = await post(button.dataset.sbDelete, data);
			if (res.message) {
				window.alert(res.message);
			}
			window.location.reload();
		} catch (err) {
			window.alert(err.message);
			button.disabled = false;
		}
	});

	document.addEventListener('focusin', (e) => {
		const select = e.target.closest('select[data-sb-status]');
		if (select) {
			select.dataset.previous = select.value;
		}
	});

	document.addEventListener('change', async (e) => {
		const select = e.target.closest('select[data-sb-status]');
		if (!select) {
			return;
		}
		select.disabled = true;
		const data = new FormData();
		data.append('id', select.dataset.id);
		data.append('status', select.value);
		try {
			await post('sb_update_booking_status', data);
			select.dataset.previous = select.value;
		} catch (err) {
			select.value = select.dataset.previous;
			window.alert(err.message);
		} finally {
			select.disabled = false;
		}
	});
})();
