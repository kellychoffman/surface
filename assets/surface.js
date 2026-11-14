/* Surface: one quiet screen. UI is composed only when an action needs it. */
(function () {
	'use strict';

	var C = window.SURFACE;
	var root = document.getElementById('surface');
	var state = { actions: [], busy: {}, view: 'stack' };
	try { if (localStorage.getItem('surfaceView') === 'list') state.view = 'list'; } catch (e) {}

	function setView(v) {
		state.view = v;
		try { localStorage.setItem('surfaceView', v); } catch (e) {}
		document.getElementById('list').className = 'list ' + v;
		advance();
		setSub();
	}

	/* Stack view shows one card: the first that is not dismissed. */
	function advance() {
		var list = document.getElementById('list');
		var items = [].slice.call(list.children).filter(function (l) { return !l.classList.contains('gone'); });
		[].slice.call(list.children).forEach(function (l) { l.classList.remove('current'); });
		if (items[0]) items[0].classList.add('current');
		list.setAttribute('data-left', Math.min(items.length, 3));
	}

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
			C.canMode ? h('a', { href: '#', onclick: function (e) {
				e.preventDefault();
				api('/mode', 'POST', { demo: !C.demo }).then(function () { location.reload(); });
			} }, C.demo ? 'Demo mode: on' : 'Demo mode: off') : null,
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
		sub.appendChild(document.createTextNode(n === 1 ? 'One thing needs you. ' : n + ' things need you. '));
		if (n > 1) {
			sub.appendChild(state.view === 'stack'
				? h('button', { class: 'link sublink', onclick: function () { setView('list'); } }, 'Show all')
				: h('button', { class: 'link sublink', onclick: function () { setView('stack'); } }, 'One at a time'));
		}
		if (bulk > 1) {
			sub.appendChild(h('button', { class: 'link sublink', onclick: doAll }, 'Do everything for me'));
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
		list.className = 'list ' + state.view;
		advance();
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

		buttons.appendChild(h('button', { class: 'link skip', onclick: function () {
			li.parentNode.appendChild(li);
			advance();
		} }, 'Skip for now'));

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
		li.appendChild(h('button', { class: 'btn next', onclick: function () {
			li.classList.add('gone');
			advance();
		} }, 'Next'));
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
		if (state.view === 'stack') setView('list'); // Show the whole run.
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

	function suggestBtn(a, fill) {
		var btn = h('button', { class: 'link' }, 'Suggest a draft');
		btn.addEventListener('click', function () {
			btn.textContent = 'Drafting\u2026';
			api('/draft', 'POST', { id: a.id }).then(function (r) {
				fill(r);
				btn.textContent = 'Suggest another';
			}).catch(function (e) { btn.textContent = e.message; });
		});
		return btn;
	}

	function compose(li, a) {
		if (li.querySelector('.tray')) return;
		var tray = h('div', { class: 'tray' });
		var meta = a.meta || {};
		var submit = function (data, label) {
			working(li, label || 'Saving\u2026');
			api('/submit', 'POST', { id: a.id, data: data }).then(function (r) { done(li, r); }).catch(function (e) { fail(li, a, e); });
		};
		var cancel = h('button', { class: 'link', onclick: function () { tray.remove(); } }, 'Cancel');
		var focus;

		if (a.kind === 'comment') {
			var ta = h('textarea', { rows: 3, placeholder: 'Write your reply', 'aria-label': 'Your reply' });
			tray.appendChild(ta);
			tray.appendChild(h('div', { class: 'row' },
				h('button', { class: 'btn primary', onclick: function () { submit({ content: ta.value }, 'Sending\u2026'); } }, 'Send reply'),
				suggestBtn(a, function (r) { ta.value = r.content; ta.focus(); }),
				cancel));
			focus = ta;
		}

		if (a.kind === 'write' || a.kind === 'draft') {
			var t = h('input', { type: 'text', placeholder: 'Title', 'aria-label': 'Title', value: meta.title || '' });
			var b = h('textarea', { rows: 9, placeholder: 'Start writing. Leave a blank line between paragraphs.', 'aria-label': 'Post' });
			b.value = meta.content || '';
			tray.appendChild(t);
			tray.appendChild(b);
			var go = function (publish) { return function () { submit({ title: t.value, content: b.value, publish: publish }, publish ? 'Publishing\u2026' : 'Saving\u2026'); }; };
			tray.appendChild(h('div', { class: 'row' },
				h('button', { class: 'btn primary', onclick: go(true) }, 'Publish'),
				h('button', { class: 'btn', onclick: go(false) }, 'Save draft'),
				a.kind === 'write' ? suggestBtn(a, function (r) { t.value = r.title || t.value; b.value = r.content; }) : null,
				cancel));
			focus = t;
		}

		if (a.kind === 'plugins') {
			var left = (meta.plugins || []).length;
			(meta.plugins || []).forEach(function (p) {
				var status = h('span', { class: 'ver' }, p.from + ' \u2192 ' + p.to);
				var btn = h('button', { class: 'btn' }, 'Update');
				var row = h('div', { class: 'plugin' }, h('span', { class: 'pname' }, p.name), status, btn);
				btn.addEventListener('click', function () {
					btn.disabled = true;
					btn.textContent = 'Updating\u2026';
					api('/submit', 'POST', { id: 'plugins', data: { plugin: p.file } }).then(function () {
						row.classList.add('ok');
						btn.remove();
						status.textContent = 'Updated';
						if (--left === 0) done(li, { message: 'All plugins updated.' });
					}).catch(function (e) {
						btn.disabled = false;
						btn.textContent = 'Update';
						status.textContent = e.message;
					});
				});
				tray.appendChild(row);
			});
			tray.appendChild(h('div', { class: 'row' }, cancel));
		}

		if (a.kind === 'core') {
			tray.appendChild(h('p', { class: 'detail' }, 'This updates WordPress itself to ' + meta.version + '. Back up first if the site matters.'));
			tray.appendChild(h('div', { class: 'row' },
				h('button', { class: 'btn primary', onclick: function () { submit({}, 'Updating WordPress\u2026'); } }, 'Update to ' + meta.version),
				cancel));
		}

		if (a.kind === 'connect') {
			var k = h('input', { type: 'password', placeholder: 'sk-ant-\u2026', 'aria-label': 'Anthropic API key', autocomplete: 'off' });
			tray.appendChild(k);
			tray.appendChild(h('div', { class: 'row' },
				h('button', { class: 'btn primary', onclick: function () { submit({ key: k.value }, 'Connecting\u2026'); } }, 'Connect'), cancel));
			focus = k;
		}

		li.appendChild(tray);
		if (focus) setTimeout(function () { focus.focus(); }, 0);
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
