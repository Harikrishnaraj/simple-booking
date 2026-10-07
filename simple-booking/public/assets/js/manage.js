/* Simple Booking: customer cancels or reschedules from the link in their email. */
(function () {
	'use strict';

	const cfg = window.sbManage;
	const root = document.querySelector('[data-sb-manage]');
	if (!cfg || !root) {
		return;
	}

	const form = root.querySelector('.sb-manage-reschedule');
	const daysEl = form.querySelector('.sb-days');
	const slotsEl = form.querySelector('.sb-slots-grid');
	const submit = form.querySelector('[type="submit"]');
	const message = root.querySelector('.sb-message');
	let chosenDate = '';
	let chosenTime = '';
	let request = 0;

	async function post(action, fields) {
		const data = new FormData();
		data.append('action', action);
		data.append('id', cfg.id);
		data.append('token', cfg.token);
		Object.entries(fields || {}).forEach(([k, v]) => data.append(k, v));
		let json = null;
		try {
			const res = await fetch(cfg.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' });
			json = await res.json();
		} catch (e) {
			// Network failure or a non-JSON response.
		}
		if (!json || !json.success) {
			const err = new Error((json && json.data && json.data.message) || cfg.i18n.error);
			err.code = json && json.data && json.data.code;
			throw err;
		}
		return json.data;
	}

	function setMessage(text, type) {
		message.textContent = text;
		message.hidden = !text;
		message.className = 'sb-message' + (type ? ' sb-message-' + type : '');
	}

	// After a change the page shows stale details, so replace the controls with the result.
	function done(text) {
		root.querySelectorAll('[data-sb-manage-actions], .sb-manage-reschedule, .sb-hint').forEach((el) => { el.hidden = true; });
		setMessage(text, 'success');
		message.focus();
	}

	function choose(group, button) {
		group.querySelectorAll('button').forEach((b) => {
			const on = b === button;
			b.classList.toggle('selected', on);
			b.setAttribute('aria-pressed', on ? 'true' : 'false');
		});
	}

	function note(text) {
		const p = document.createElement('p');
		p.className = 'sb-hint';
		p.textContent = text;
		slotsEl.replaceChildren(p);
	}

	cfg.days.forEach((d) => {
		const b = document.createElement('button');
		b.type = 'button';
		b.className = 'sb-day-btn';
		b.dataset.date = d.date;
		b.disabled = !d.open;
		b.setAttribute('aria-pressed', 'false');
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

	daysEl.addEventListener('click', async (e) => {
		const day = e.target.closest('.sb-day-btn');
		if (!day || day.disabled) {
			return;
		}
		choose(daysEl, day);
		chosenDate = day.dataset.date;
		chosenTime = '';
		submit.disabled = true;
		const mine = ++request;
		note(cfg.i18n.loading);
		try {
			const res = await post('sb_manage_slots', { date: chosenDate });
			if (mine !== request) {
				return;
			}
			if (!res.slots.length) {
				note(cfg.i18n.noSlots);
				return;
			}
			slotsEl.replaceChildren(...res.slots.map((time) => {
				const b = document.createElement('button');
				b.type = 'button';
				b.className = 'sb-slot-btn';
				b.dataset.time = time;
				b.textContent = time;
				b.setAttribute('aria-pressed', 'false');
				return b;
			}));
		} catch (err) {
			if (mine === request) {
				note(err.message);
			}
		}
	});

	slotsEl.addEventListener('click', (e) => {
		const slot = e.target.closest('.sb-slot-btn');
		if (slot) {
			choose(slotsEl, slot);
			chosenTime = slot.dataset.time;
			submit.disabled = false;
		}
	});

	root.querySelector('[data-sb-manage-open]').addEventListener('click', (e) => {
		form.hidden = !form.hidden;
		e.currentTarget.setAttribute('aria-expanded', String(!form.hidden));
		if (!form.hidden) {
			const first = daysEl.querySelector('button:not(:disabled)');
			if (first) {
				first.focus();
			}
		}
	});

	root.querySelector('[data-sb-manage-cancel]').addEventListener('click', async (e) => {
		if (!window.confirm(cfg.i18n.confirmCancel)) {
			return;
		}
		e.currentTarget.disabled = true;
		try {
			done((await post('sb_manage_cancel')).message);
		} catch (err) {
			setMessage(err.message, 'error');
			e.currentTarget.disabled = false;
		}
	});

	form.addEventListener('submit', async (e) => {
		e.preventDefault();
		submit.disabled = true;
		try {
			done((await post('sb_manage_reschedule', { booking_date: chosenDate, booking_time: chosenTime })).message);
		} catch (err) {
			setMessage(err.message, 'error');
			if (err.code === 'slot_unavailable') {
				daysEl.querySelector('.selected')?.click();
			} else {
				submit.disabled = false;
			}
		}
	});
})();
