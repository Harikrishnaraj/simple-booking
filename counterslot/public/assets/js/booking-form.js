/* CounterSlot frontend: 3-step form with a day strip and time-slot grid. */
(function () {
	'use strict';

	const cfg = window.cslotBooking;
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

	// Same rules as CSlot_Validator on the server.
	const NAME_RE = /^[\p{L}\p{M}][\p{L}\p{M} .'’-]*$/u;
	const EMAIL_RE = /^[^\s@]+@[^\s@.]+(\.[^\s@.]+)+$/;
	const PHONE_RE = /^\+?[0-9 ().-]+$/;
	const rules = {
		name(v) {
			if (!v) {
				return cfg.i18n.nameRequired;
			}
			if (v.length < 2 || v.length > 100) {
				return cfg.i18n.nameLength;
			}
			return NAME_RE.test(v) ? '' : cfg.i18n.nameChars;
		},
		email(v) {
			if (!v) {
				return cfg.i18n.emailRequired;
			}
			return EMAIL_RE.test(v) ? '' : cfg.i18n.emailInvalid;
		},
		phone(v) {
			const digits = v.replace(/\D/g, '').length;
			return !v || (PHONE_RE.test(v) && digits >= 7 && digits <= 15) ? '' : cfg.i18n.phoneInvalid;
		},
	};

	// The message for a field, or '' when it's fine. Browser checks (required, custom fields) included.
	function fieldError(input) {
		const rule = rules[input.dataset.sbRule];
		input.setCustomValidity(rule ? rule(input.value.trim()) : '');
		if (input.validity.valid) {
			return '';
		}
		if (input.validity.customError) {
			return input.validationMessage;
		}
		if (input.validity.valueMissing) {
			return input.tagName === 'SELECT' ? cfg.i18n.choose : cfg.i18n.required;
		}
		return input.validationMessage;
	}

	// Message under the field, linked for screen readers.
	function showError(input, text) {
		const group = input.closest('.sb-form-group') || input.parentElement;
		let el = group.querySelector(':scope > .sb-field-error');
		if (!el && text) {
			el = document.createElement('p');
			el.className = 'sb-field-error';
			el.id = (input.id || input.name.replace(/\W/g, '')) + '-error';
			group.append(el);
		}
		if (!el) {
			return;
		}
		el.textContent = text;
		el.hidden = !text;
		const ids = (input.getAttribute('aria-describedby') || '').split(' ').filter((id) => id && id !== el.id);
		if (text) {
			input.setAttribute('aria-invalid', 'true');
			ids.push(el.id);
		} else {
			input.removeAttribute('aria-invalid');
		}
		if (ids.length) {
			input.setAttribute('aria-describedby', ids.join(' '));
		} else {
			input.removeAttribute('aria-describedby');
		}
	}

	// Check every visible field in a step; focus the first problem.
	function validateStep(step) {
		let first = null;
		step.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach((input) => {
			if (input.disabled || input.closest('[hidden], .sb-hp')) {
				return;
			}
			const text = fieldError(input);
			showError(input, text);
			if (text && !first) {
				first = input;
			}
		});
		if (first) {
			first.focus();
		}
		return !first;
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

		// Location → service → staff: only services someone at the chosen location performs, and
		// only the staff who perform the chosen service there (no services set = all services).
		// Options are rebuilt, not hidden: iOS Safari ignores hidden <option>s.
		const serviceNodes = Array.from(service.children).map((n) => n.cloneNode(true));
		const staffOptions = staff ? Array.from(staff.options).filter((o) => o.value && o.value !== 'any') : [];
		const staffFixed = staff ? Array.from(staff.options).filter((o) => !o.value || o.value === 'any') : [];

		function offers(option, serviceId) {
			const ids = option.dataset.services ? option.dataset.services.split(',') : [];
			const own = option.dataset.location;
			const here = !location || !own || own === '0' || own === location.value; // no location = every location
			return here && (!ids.length || ids.includes(serviceId));
		}

		function filterServices() {
			if (!staffOptions.length) {
				return;
			}
			const keep = service.value;
			const ok = (o) => !o.value || staffOptions.some((m) => offers(m, o.value));
			const nodes = serviceNodes.map((n) => {
				if (n.tagName !== 'OPTGROUP') {
					return ok(n) ? n.cloneNode(true) : null;
				}
				const group = n.cloneNode(false);
				group.append(...Array.from(n.children).filter(ok).map((o) => o.cloneNode(true)));
				return group.children.length ? group : null;
			}).filter(Boolean);
			service.replaceChildren(...nodes);
			service.value = Array.from(service.options).some((o) => o.value === keep) ? keep : '';
			if (service.options.length === 1) {
				service.options[0].textContent = cfg.i18n.noServices;
			}
		}

		function filterStaff() {
			if (!staff) {
				return;
			}
			const keep = staff.value;
			const list = service.value ? staffOptions.filter((o) => offers(o, service.value)) : [];
			// "Any available" only when there is more than one to choose from.
			staff.replaceChildren(...staffFixed.filter((o) => !o.value || list.length > 1), ...list);
			if (list.length === 1) {
				staff.value = list[0].value;
			} else {
				staff.value = Array.from(staff.options).some((o) => o.value === keep) ? keep : '';
			}
			if (staff.value) {
				showError(staff, '');
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
				const { slots } = await post('cslot_get_available_slots', data);
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
			const from = steps.indexOf(go.closest('.sb-step'));
			if (n > from + 1 && !validateStep(steps[from])) {
				return; // Going forward: this step must be complete first.
			}
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
				const res = await post('cslot_quote', data);
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
			location.addEventListener('change', () => {
				filterServices();
				showFields();
				filterStaff();
			});
		}
		if (staff) {
			staff.addEventListener('change', showStaffPhoto);
		}
		filterServices();
		showFields();
		filterStaff();

		// Re-check a field as it's typed in once it has shown a message, and the name straight
		// away so digits and symbols are flagged as they're typed.
		form.addEventListener('input', (e) => {
			const input = e.target;
			if (input.dataset.sbRule === 'name' || input.getAttribute('aria-invalid')) {
				showError(input, fieldError(input));
			}
		});
		form.addEventListener('change', (e) => {
			if (e.target.getAttribute('aria-invalid')) {
				showError(e.target, fieldError(e.target));
			}
		});
		form.addEventListener('focusout', (e) => {
			const input = e.target;
			if (input.dataset.sbRule && input.value.trim()) {
				showError(input, fieldError(input));
			}
		});

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			if (!validateStep(steps[2])) {
				return;
			}
			const button = form.querySelector('[type="submit"]');
			button.disabled = true;
			setMessage(cfg.i18n.sending);

			const data = new FormData(form);
			data.append('_wpnonce', cfg.nonce);

			try {
				const res = await post('cslot_submit_booking', data);
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
