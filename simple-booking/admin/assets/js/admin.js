/* Simple Booking admin: AJAX for forms, delete buttons and status selects, plus the theme toggle and dialogs. */
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
			select.classList.replace('sb-status--' + select.dataset.previous, 'sb-status--' + select.value);
			select.dataset.previous = select.value;
		} catch (err) {
			select.value = select.dataset.previous;
			window.alert(err.message);
		} finally {
			select.disabled = false;
		}
	});

	// Dark/light theme: switch immediately, remember it for this user.
	document.addEventListener('click', (e) => {
		const button = e.target.closest('[data-sb-theme-toggle]');
		if (!button) {
			return;
		}
		const dark = !document.body.classList.contains('sb-theme-dark');
		document.body.classList.toggle('sb-theme-dark', dark);
		document.body.classList.toggle('sb-theme-light', !dark);
		button.setAttribute('aria-pressed', String(dark));
		const icon = button.querySelector('.dashicons');
		icon.classList.toggle('dashicons-lightbulb', dark);
		icon.classList.toggle('dashicons-admin-appearance', !dark);
		const data = new FormData();
		data.append('theme', dark ? 'dark' : 'light');
		post('sb_save_theme', data).catch(() => {});
	});

	// Dialogs: [data-sb-open="id"] opens; data-sb-fill (JSON) fills the form fields for editing.
	document.addEventListener('click', (e) => {
		const opener = e.target.closest('[data-sb-open]');
		if (opener) {
			const dialog = document.getElementById(opener.dataset.sbOpen);
			const form = dialog.querySelector('form');
			const values = opener.dataset.sbFill ? JSON.parse(opener.dataset.sbFill) : null;
			form.reset();
			form.elements.id.value = values ? values.id : 0;
			if (values) {
				Object.keys(values).forEach((name) => {
					if (form.elements[name]) {
						form.elements[name].value = values[name] ?? '';
					}
				});
			}
			const title = dialog.querySelector('[data-new]');
			title.textContent = values ? title.dataset.edit : title.dataset.new;
			dialog.showModal();
			return;
		}
		if (e.target.closest('[data-sb-close]')) {
			e.target.closest('dialog').close();
		}
	});

	document.addEventListener('change', (e) => {
		if (e.target.matches('select[data-sb-autosubmit]')) {
			e.target.form.submit();
		}
	});
})();
