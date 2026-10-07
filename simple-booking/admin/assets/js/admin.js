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
		if (!button || !window.confirm(button.dataset.sbConfirm || cfg.i18n.confirmDelete)) {
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
			if (button.dataset.sbRedirect) {
				window.location.href = button.dataset.sbRedirect;
			} else {
				window.location.reload();
			}
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
			form.dispatchEvent(new Event('sb:filled'));
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

	// Staff photo: pick an image from the media library into the hidden photo_id field.
	let mediaFrame = null;
	document.addEventListener('click', (e) => {
		const field = e.target.closest('[data-sb-photo]');
		if (!field) {
			return;
		}
		const input = field.querySelector('input[name="photo_id"]');
		const preview = field.querySelector('.sb-photo-preview');
		const show = (url) => {
			preview.src = url || '';
			preview.hidden = !url;
			field.querySelector('.sb-photo-placeholder').hidden = !!url;
			field.querySelector('[data-sb-photo-remove]').hidden = !url;
		};

		if (e.target.closest('[data-sb-photo-remove]')) {
			input.value = '0';
			show('');
			return;
		}
		if (!e.target.closest('[data-sb-photo-choose]') || !window.wp || !window.wp.media) {
			return;
		}
		if (!mediaFrame) {
			mediaFrame = window.wp.media({
				title: cfg.i18n.choosePhoto,
				button: { text: cfg.i18n.usePhoto },
				library: { type: 'image' },
				multiple: false,
			});
		}
		mediaFrame.off('select').on('select', () => {
			const image = mediaFrame.state().get('selection').first().toJSON();
			input.value = image.id;
			show((image.sizes && image.sizes.thumbnail && image.sizes.thumbnail.url) || image.url);
		});
		mediaFrame.open();
	});

	// Notifications: placeholder chips insert at the cursor of the last focused subject/message field.
	let placeholderTarget = null;
	document.addEventListener('focusin', (e) => {
		if (e.target.matches('[data-sb-placeholder-target]')) {
			placeholderTarget = e.target;
		}
	});
	document.addEventListener('click', async (e) => {
		const chip = e.target.closest('[data-sb-insert]');
		if (chip) {
			const field = placeholderTarget || chip.form.elements.body;
			const start = field.selectionStart ?? field.value.length;
			const end = field.selectionEnd ?? start;
			field.setRangeText(chip.dataset.sbInsert, start, end, 'end');
			field.focus();
			return;
		}

		// Save the template, then email the saved version to the current admin.
		const test = e.target.closest('[data-sb-test-template]');
		if (!test) {
			return;
		}
		const form = test.form;
		if (!form.reportValidity()) {
			return;
		}
		test.disabled = true;
		try {
			await post('sb_save_template', new FormData(form));
			const data = new FormData();
			data.append('key', form.elements.key.value);
			const res = await post('sb_test_template', data);
			window.alert(cfg.i18n.testSent.replace('%s', res.to));
		} catch (err) {
			window.alert(err.message);
		} finally {
			test.disabled = false;
		}
	});

	// Booking dialogs: staff filtered by service, and free times loaded for the chosen service/staff/date.
	// A rescheduled booking (id > 0) doesn't block its own slot; its current time is preselected.
	let slotRequest = 0;
	async function refreshSlots(form) {
		const { service_id: service, staff_id: staff, booking_date: date, booking_time: time } = form.elements;
		Array.from(staff.options).forEach((o) => {
			const ids = o.dataset.services ? o.dataset.services.split(',') : [];
			o.hidden = o.disabled = !!o.value && ids.length > 0 && !ids.includes(service.value);
		});
		if (staff.selectedOptions[0] && staff.selectedOptions[0].disabled) {
			staff.value = '';
		}

		const setOptions = (labels, values = []) => {
			time.replaceChildren(...labels.map((label, i) => new Option(label, values[i] ?? '')));
		};
		if (!service.value || !date.value) {
			setOptions([cfg.i18n.pickDate]);
			return;
		}
		const mine = ++slotRequest;
		setOptions([cfg.i18n.loadingTimes]);
		const data = new FormData();
		data.append('service_id', service.value);
		data.append('staff_id', staff.value);
		data.append('date', date.value);
		data.append('exclude', form.elements.id.value);
		try {
			const res = await post('sb_admin_slots', data);
			if (mine !== slotRequest) {
				return;
			}
			if (!res.slots.length) {
				setOptions([cfg.i18n.noTimes]);
				return;
			}
			setOptions(res.slots, res.slots);
			const current = form.elements.current_time ? form.elements.current_time.value : '';
			if (current && res.slots.includes(current)) {
				time.value = current;
			}
		} catch (err) {
			if (mine === slotRequest) {
				setOptions([err.message]);
			}
		}
	}
	document.addEventListener('change', (e) => {
		const form = e.target.closest('form[data-sb-slots]');
		if (form && ['service_id', 'staff_id', 'booking_date'].includes(e.target.name)) {
			refreshSlots(form);
		}
	});
	document.addEventListener('sb:filled', (e) => {
		if (e.target.matches('form[data-sb-slots]')) {
			refreshSlots(e.target);
		}
	}, true);

	// Picking an existing customer's email fills in their name and phone.
	document.addEventListener('change', (e) => {
		const input = e.target.closest('[data-sb-customer-email]');
		const match = input && input.list && Array.from(input.list.options).find((o) => o.value === input.value);
		if (match) {
			input.form.elements.name.value = match.dataset.name;
			input.form.elements.phone.value = match.dataset.phone;
		}
	});

	// Staff schedule: show the weekly table only for custom hours; add/remove day-off rows.
	document.addEventListener('change', (e) => {
		const box = e.target.closest('[data-sb-schedule]');
		if (box && e.target.name === 'schedule_custom') {
			box.querySelector('.sb-schedule').hidden = e.target.value !== '1';
		}
	});
	document.addEventListener('click', (e) => {
		const add = e.target.closest('[data-sb-add-row]');
		if (add) {
			const list = add.parentElement.querySelector('.sb-days-off');
			const row = list.querySelector('.sb-days-off__row').cloneNode(true);
			row.querySelectorAll('input').forEach((i) => { i.value = ''; });
			list.append(row);
			row.querySelector('input').focus();
			return;
		}
		const remove = e.target.closest('[data-sb-remove-row]');
		if (remove) {
			const row = remove.closest('.sb-days-off__row');
			if (row.parentElement.children.length > 1) {
				row.remove();
			} else {
				row.querySelectorAll('input').forEach((i) => { i.value = ''; });
			}
		}
	});
})();
