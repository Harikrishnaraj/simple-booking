/* CounterSlot frontend: 3-step form with a day strip and time-slot grid. */
(function () {
	'use strict';

	const cfg = window.sbBooking;
	if (!cfg) {
		return;
	}

	async function post(action, data) {
		data.append('action', action);
		let json = null;
		try {
			const res = await fetch(cfg.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' });
			json = await res.json();
		} catch (e) {
			// Network failure or a non-JSON response; fall through to the generic error.
		}
		if (!json || !json.success) {
			const err = new Error((json && json.data && json.data.message) || cfg.i18n.error);
			err.code = json && json.data && json.data.code;
			throw err;
		}
		return json.data;
	}

	function choose(group, button) {
		group.querySelectorAll('[aria-pressed]').forEach((b) => {
			const on = b === button;
			b.classList.toggle('selected', on);
			b.setAttribute('aria-pressed', on ? 'true' : 'false');
		});
	}

	function makeButton(className, label) {
		const b = document.createElement('button');
		b.type = 'button';
		b.className = className;
		b.setAttribute('aria-pressed', 'false');
		if (label) {
			b.textContent = label;
		}
		return b;
	}

	function init(root) {
		const form = root.querySelector('form');
		const steps = Array.from(root.querySelectorAll('.sb-step'));
		const indicators = Array.from(root.querySelectorAll('.sb-step-item'));
		const message = root.querySelector('.sb-message');
		const daysEl = root.querySelector('.sb-days');
		const slotsEl = root.querySelector('.sb-slots-grid');
		const summary = root.querySelector('.sb-summary');
		const toDetails = root.querySelector('[data-sb-go="3"]');
		const { service_id: service, staff_id: staff, location_id: location, booking_date: dateInput, booking_time: timeInput } = form.elements;
		let request = 0;

		function slotsNote(text) {
			const p = document.createElement("p");
			p.className = "sb-hint";
			p.textContent = text;
			slotsEl.replaceChildren(p);
		}

		function setMessage(text, type) {
			message.textContent = text || '';
			message.hidden = !text;
			message.className = 'sb-message' + (type ? ' sb-message-' + type : '');
		}

		function show(n) {
			steps.forEach((s, i) => { s.hidden = i !== n - 1; });
			indicators.forEach((s, i) => {
				s.classList.toggle('active', i === n - 1);
				if (i === n - 1) {
					s.setAttribute('aria-current', 'step');
				} else {
					s.removeAttribute('aria-current');
				}
			});
			setMessage('');
			const first = steps[n - 1].querySelector('select, input:not([type="hidden"]), .selected, button:not(:disabled)');
			if (first) {
				first.focus();
			}
		}

		// Only offer staff who perform the chosen service (empty list = all services).
		function filterStaff() {
			if (!staff) {
				return;
			}
			Array.from(staff.options).forEach((o) => {
				if (!o.value) {
					return;
				}
				const ids = o.dataset.services ? o.dataset.services.split(',') : [];
				const elsewhere = location && o.dataset.location !== location.value;
				o.hidden = o.disabled = elsewhere || (ids.length > 0 && !ids.includes(service.value));
			});
			if (staff.selectedOptions[0] && staff.selectedOptions[0].disabled) {
				staff.value = '';
			}
			showStaffPhoto();
		}

		// Photo of the chosen staff member beside the dropdown (decorative: the name is in the select).
		function showStaffPhoto() {
			const img = staff && form.querySelector('.sb-staff-photo');
			if (!img) {
				return;
			}
			const url = staff.selectedOptions[0] ? staff.selectedOptions[0].dataset.photo : '';
			img.hidden = !url;
			if (url) {
				img.src = url;
			}
		}

		async function loadSlots() {
			const mine = ++request;
			timeInput.value = '';
			toDetails.disabled = true;
			slotsEl.setAttribute('aria-busy', 'true');
			slotsNote(cfg.i18n.loading);

			const data = new FormData();
			data.append('service_id', service.value);
			data.append('staff_id', staff ? staff.value : '');
			data.append('location_id', location ? location.value : '');
			data.append('date', dateInput.value);

			try {
				const { slots } = await post('sb_get_available_slots', data);
				if (mine !== request) {
					return; // A newer day was picked while this was loading.
				}
				if (slots.length) {
					slotsEl.replaceChildren();
				} else {
					slotsNote(cfg.i18n.noSlots);
				}
				slots.forEach((time) => {
					const b = makeButton('sb-slot-btn', time);
					b.dataset.time = time;
					slotsEl.append(b);
				});
			} catch (err) {
				if (mine === request) {
					slotsNote(err.message);
				}
			} finally {
				if (mine === request) {
					slotsEl.removeAttribute('aria-busy');
				}
			}
		}

		cfg.days.forEach((d) => {
			const b = makeButton('sb-day-btn');
			b.dataset.date = d.date;
			b.disabled = !d.open;
			b.setAttribute('aria-label', d.label);
			[['sb-day-week', d.week], ['sb-day-num', d.day], ['sb-day-month', d.month]].forEach(([cls, text]) => {
				const span = document.createElement('span');
				span.className = cls;
				span.textContent = text;
				span.setAttribute('aria-hidden', 'true');
				b.append(span);
			});
			daysEl.append(b);
		});

		daysEl.addEventListener('click', (e) => {
			const b = e.target.closest('.sb-day-btn');
			if (!b || b.disabled) {
				return;
			}
			choose(daysEl, b);
			dateInput.value = b.dataset.date;
			loadSlots();
		});

		slotsEl.addEventListener('click', (e) => {
			const b = e.target.closest('.sb-slot-btn');
			if (!b) {
				return;
			}
			choose(slotsEl, b);
			timeInput.value = b.dataset.time;
			toDetails.disabled = false;
		});

		root.addEventListener('click', (e) => {
			const go = e.target.closest('[data-sb-go]');
			if (!go) {
				return;
			}
			const n = Number(go.dataset.sbGo);
			if (n === 2 && go.closest('.sb-step') === steps[0] && dateInput.value) {
				loadSlots(); // Service or staff may have changed; refresh times for the chosen day.
			}
			if (n === 3) {
				refreshPrice();
				const day = daysEl.querySelector('.selected');
				summary.textContent = service.selectedOptions[0].textContent.trim() + ' · ' + day.getAttribute('aria-label') + ' · ' + timeInput.value;
			}
			show(n);
		});

		// Custom fields: only the questions for the chosen service; hidden ones are disabled so
		// the browser doesn't require them and they aren't sent.
		function showFields() {
			form.querySelectorAll('[data-sb-field]').forEach((field) => {
				const ids = field.dataset.services ? field.dataset.services.split(',') : [];
				const show = !ids.length || ids.includes(service.value);
				field.hidden = !show;
				field.querySelectorAll('input, select, textarea').forEach((input) => { input.disabled = !show; });
			});
			// No "Extras" heading when none of the extras apply to this service.
			const extras = form.querySelector('.sb-extras');
			if (extras) {
				extras.hidden = !extras.querySelector('[data-sb-field]:not([hidden])');
			}
		}
		service.addEventListener('change', showFields);
		showFields();

		// Price preview (the server works out the real price on submit).
		const priceTable = form.querySelector('[data-sb-price]');
		const couponError = form.querySelector('.sb-coupon-error');
		async function refreshPrice() {
			if (!priceTable) {
				return;
			}
			const data = new FormData();
			data.append('service_id', service.value);
			data.append('date', dateInput.value);
			data.append('coupon', form.elements.coupon ? form.elements.coupon.value : '');
			form.querySelectorAll('input[name="extras[]"]:checked:not(:disabled)').forEach((x) => data.append('extras[]', x.value));
			try {
				const res = await post('sb_quote', data);
				priceTable.tBodies[0].replaceChildren(...res.lines.map(([label, amount], i) => {
					const tr = document.createElement('tr');
					if (i === res.lines.length - 1) {
						tr.className = 'sb-price-total';
					}
					const th = document.createElement('th');
					th.scope = 'row';
					th.textContent = label;
					const td = document.createElement('td');
					td.textContent = amount;
					tr.append(th, td);
					return tr;
				}));
				// Nothing to show for an unpriced service with no extras or discount.
				priceTable.hidden = !Number(res.total) && res.lines.length <= 2;
				couponError.textContent = res.couponError;
				couponError.hidden = !res.couponError;
			} catch (err) {
				// Leave the last preview in place; the booking itself is priced on the server.
			}
		}
		form.addEventListener('click', (e) => {
			if (e.target.closest('[data-sb-apply-coupon]')) {
				refreshPrice();
			}
		});
		if (form.elements.coupon) {
			form.elements.coupon.addEventListener('keydown', (e) => {
				if (e.key === 'Enter') {
					e.preventDefault();
					refreshPrice();
				}
			});
		}

		service.addEventListener('change', filterStaff);
		if (location) {
			location.addEventListener('change', filterStaff);
		}
		if (staff) {
			staff.addEventListener('change', showStaffPhoto);
		}
		filterStaff();

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			if (!form.reportValidity()) {
				return;
			}
			const button = form.querySelector('[type="submit"]');
			button.disabled = true;
			setMessage(cfg.i18n.sending);

			const data = new FormData(form);
			data.append('_wpnonce', cfg.nonce);

			try {
				const res = await post('sb_submit_booking', data);
				form.hidden = true;
				root.querySelector('.sb-step-indicator').hidden = true;
				setMessage(res.message, 'success');
				message.focus();
			} catch (err) {
				if (err.code === 'slot_unavailable') {
					show(2);
					loadSlots();
				}
				setMessage(err.message, 'error');
			} finally {
				button.disabled = false;
			}
		});
	}

	document.querySelectorAll('[data-sb-booking]').forEach(init);
})();
