/* Surface: one quiet screen. UI is composed only when an action needs it. */
(function () {
	'use strict';

	var C = window.SURFACE;
	var root = document.getElementById('surface');
	var state = { actions: [], busy: {} };

	/* ---------- helpers ---------- */

	function h(tag, attrs) {
		var el = document.createElement(tag);
		Object.keys(attrs || {}).forEach(function (k) {
			if (k === 'class') el.className = attrs[k];
			else if (k.slice(0, 2) === 'on') el.addEventListener(k.slice(2), attrs[k]);
			else if (attrs[k] !== false && attrs[k] != null) el.setAttribute(k, attrs[k]);
		});
		for (var i = 2; i < arguments.length; i++) {
			var c = arguments[i];
			if (c == null || c === false) continue;
			el.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);
		}
		return el;
	}

	function api(path, method, body) {
		return fetch(C.root + path, {
			method: method || 'GET',
			headers: { 'X-WP-Nonce': C.nonce, 'Content-Type': 'application/json' },
			body: body ? JSON.stringify(body) : undefined,
			credentials: 'same-origin'
		}).then(function (r) {
			return r.json().then(function (j) {
				if (!r.ok) throw new Error(j && j.message ? j.message : 'Something went wrong.');
				return j;
			});
		});
	}

	/* ---------- shell ---------- */

	function shell() {
		root.innerHTML = '';
		root.appendChild(h('header', { class: 'top' },
			h('p', { class: 'site' }, C.site),
			h('h1', {}, C.hello),
			h('p', { class: 'sub', id: 'sub' }, 'Looking around…')
		));
		root.appendChild(h('ol', { class: 'list', id: 'list' }));
		root.appendChild(h('footer', {},
			h('a', { href: C.classic }, 'Classic admin'),
			h('a', { href: C.logout }, 'Log out')
		));
	}

	function setSub() {
		var n = state.actions.length;
		var bulk = state.actions.filter(function (a) { return a.auto && a.auto.bulk; }).length;
		var sub = document.getElementById('sub');
		sub.innerHTML = '';
		if (!n) {
			sub.textContent = 'Nothing needs you. Go outside.';
			return;
		}
		sub.appendChild(document.createTextNode(n === 1 ? 'One thing needs you.' : n + ' things need you. '));
		if (bulk > 1) {
			sub.appendChild(h('button', { class: 'link', onclick: doAll }, 'Do everything for me'));
		}
	}

	function render() {
		var list = document.getElementById('list');
		list.innerHTML = '';
		state.actions.forEach(function (a, i) {
			var li = card(a);
			li.style.animationDelay = (i * 40) + 'ms';
			list.appendChild(li);
		});
		setSub();
	}

	/* ---------- cards ---------- */

	function card(a) {
		var li = h('li', { class: 'item', 'data-id': a.id });
		var body = h('div', { class: 'body' },
			h('h2', {}, a.title),
			a.detail ? h('p', { class: 'detail' }, a.detail) : null
		);
		var buttons = h('div', { class: 'buttons' });

		if (a.self) {
			if (a.self.type === 'link') {
				buttons.appendChild(h('a', { class: 'btn', href: a.self.url }, a.self.label));
			} else {
				buttons.appendChild(h('button', { class: 'btn', onclick: function () { compose(li, a); } }, a.self.label));
			}
		}
		if (a.auto) {
			buttons.appendChild(h('button', { class: 'btn primary', onclick: function () { auto(li, a); } }, a.auto.label));
		}

		li.appendChild(body);
		li.appendChild(buttons);
		return li;
	}

	function working(li, text) {
		li.classList.add('busy');
		li.querySelector('.buttons').style.display = 'none';
		var tray = li.querySelector('.tray');
		if (tray) tray.remove();
		li.appendChild(h('p', { class: 'status' }, text));
	}

	function done(li, res) {
		li.classList.remove('busy');
		li.classList.add('done');
		li.querySelectorAll('.status, .tray').forEach(function (n) { n.remove(); });
		var line = h('p', { class: 'status ok' }, res.message || 'Done.');
		if (res.link) line.appendChild(h('a', { href: res.link.url }, ' ' + res.link.label));
		if (res.undo) {
			line.appendChild(h('button', {
				class: 'link', onclick: function () {
					api('/undo', 'POST', { undo: res.undo }).then(function (r) {
						line.textContent = r.message;
						line.classList.remove('ok');
					}).catch(function (e) { line.textContent = e.message; });
				}
			}, ' Undo'));
		}
		li.appendChild(line);
		li.querySelector('.buttons').remove();
		state.actions = state.actions.filter(function (x) { return x.id !== li.getAttribute('data-id'); });
		setSub();
	}

	function fail(li, a, e) {
		li.classList.remove('busy');
		li.querySelectorAll('.status').forEach(function (n) { n.remove(); });
		li.querySelector('.buttons').style.display = '';
		li.appendChild(h('p', { class: 'status err' }, e.message));
	}

	function auto(li, a) {
		working(li, 'On it…');
		return api('/do', 'POST', { id: a.id }).then(function (r) { done(li, r); }).catch(function (e) { fail(li, a, e); });
	}

	function doAll() {
		var todo = state.actions.filter(function (a) { return a.auto && a.auto.bulk; });
		var chain = Promise.resolve();
		todo.forEach(function (a) {
			chain = chain.then(function () {
				var li = document.querySelector('[data-id="' + a.id + '"]');
				return li ? auto(li, a) : null;
			});
		});
	}

	/* ---------- composers: only built when asked for ---------- */

	function compose(li, a) {
		if (li.querySelector('.tray')) return;
		var tray = h('div', { class: 'tray' });
		var submit = function (data, label) {
			working(li, label || 'Saving…');
			api('/submit', 'POST', { id: a.id, data: data }).then(function (r) { done(li, r); }).catch(function (e) { fail(li, a, e); });
		};
		var cancel = h('button', { class: 'link', onclick: function () { tray.remove(); } }, 'Cancel');

		if (a.kind === 'comment') {
			var ta = h('textarea', { rows: 3, placeholder: 'Your reply', 'aria-label': 'Your reply' });
			tray.appendChild(ta);
			tray.appendChild(h('div', { class: 'row' },
				h('button', { class: 'btn primary', onclick: function () { submit({ content: ta.value }, 'Sending…'); } }, 'Send reply'), cancel));
			setTimeout(function () { ta.focus(); }, 0);
		}

		if (a.kind === 'write') {
			var t = h('input', { type: 'text', placeholder: 'Title', 'aria-label': 'Title' });
			var b = h('textarea', { rows: 8, placeholder: 'Start writing. Blank line between paragraphs.', 'aria-label': 'Post' });
			tray.appendChild(t);
			tray.appendChild(b);
			tray.appendChild(h('div', { class: 'row' },
				h('button', { class: 'btn primary', onclick: function () { submit({ title: t.value, content: b.value, publish: true }, 'Publishing…'); } }, 'Publish'),
				h('button', { class: 'btn', onclick: function () { submit({ title: t.value, content: b.value }); } }, 'Save draft'),
				cancel));
			setTimeout(function () { t.focus(); }, 0);
		}

		if (a.kind === 'connect') {
			var k = h('input', { type: 'password', placeholder: 'sk-ant-…', 'aria-label': 'Anthropic API key', autocomplete: 'off' });
			tray.appendChild(k);
			tray.appendChild(h('div', { class: 'row' },
				h('button', { class: 'btn primary', onclick: function () { submit({ key: k.value }, 'Connecting…'); } }, 'Connect'), cancel));
			setTimeout(function () { k.focus(); }, 0);
		}

		li.appendChild(tray);
	}

	/* ---------- boot ---------- */

	shell();
	api('/actions').then(function (r) {
		state.actions = r.actions;
		render();
	}).catch(function (e) {
		document.getElementById('sub').textContent = e.message;
	});
})();
