/**
 * SearchableSelect — native <select>-lər üçün axtarışlı açılan siyahı (asılılıqsız).
 *
 * Native select DOM-da öz yerində qalır və həqiqət mənbəyidir: mövcud kod `sel.value = ...`,
 * `innerHTML` ilə option doldurma və `change` hadisələri ilə işləməyə davam edir.
 * Komponent yalnız görünən düymə + axtarışlı panel əlavə edir.
 *
 * İstisnalar: multiple, data-no-search, .hidden-storage içində, şablonlar (#...-template, <template>).
 */
(function () {
    'use strict';

    var instances = new WeakMap();
    var openInstance = null;
    var uid = 0;

    var STYLE_CLASS = /^(form-input-compact|form-input-admin|search-input|w-|min-w-|max-w-|flex-1|flex-none|mt-|mb-|ml-|mr-|rounded|text-(xs|sm|base))/;
    var AZ_MAP = { 'ə': 'e', 'ı': 'i', 'ş': 's', 'ç': 'c', 'ğ': 'g', 'ö': 'o', 'ü': 'u', 'İ': 'i' };

    function normalize(s) {
        return String(s || '')
            .replace(/[əışçğöüİ]/gi, function (ch) { return AZ_MAP[ch] || AZ_MAP[ch.toLowerCase()] || ch; })
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .toLowerCase().replace(/\s+/g, ' ').trim();
    }

    var proto = HTMLSelectElement.prototype;
    var valueDesc = Object.getOwnPropertyDescriptor(proto, 'value');
    var indexDesc = Object.getOwnPropertyDescriptor(proto, 'selectedIndex');

    function shouldEnhance(select) {
        if (!(select instanceof HTMLSelectElement) || select.multiple) return false;
        if (select.hasAttribute('data-no-search') || select.classList.contains('multi-select-native')) return false;
        if (select.closest('template, .hidden-storage, [id$="-template"], [data-no-search]')) return false;
        return true;
    }

    function Instance(select) {
        this.select = select;
        this.id = 'ss-' + (++uid);
        this.activeIndex = -1;
        this.items = [];
        this.build();
    }

    Instance.prototype.build = function () {
        var self = this;
        var select = this.select;

        // Klonlanmış gün sətirlərindən qalan köhnə düyməni sil
        var next = select.nextElementSibling;
        if (next && next.classList.contains('ss-trigger')) next.remove();

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'ss-trigger';
        Array.prototype.forEach.call(select.classList, function (c) {
            if (STYLE_CLASS.test(c)) trigger.classList.add(c);
        });
        if (select.style.width) trigger.style.width = select.style.width;
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        if (select.getAttribute('aria-label')) trigger.setAttribute('aria-label', select.getAttribute('aria-label'));
        trigger.innerHTML = '<span class="ss-trigger-text"></span>';
        select.insertAdjacentElement('afterend', trigger);
        this.trigger = trigger;
        this.textEl = trigger.firstChild;

        select.classList.add('ss-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        // Proqramla dəyər dəyişəndə (sel.value = ...) düymə yenilənsin
        Object.defineProperty(select, 'value', {
            configurable: true,
            get: function () { return valueDesc.get.call(this); },
            set: function (v) { valueDesc.set.call(this, v); self.refresh(); }
        });
        Object.defineProperty(select, 'selectedIndex', {
            configurable: true,
            get: function () { return indexDesc.get.call(this); },
            set: function (v) { indexDesc.set.call(this, v); self.refresh(); }
        });

        this.observer = new MutationObserver(function () { self.refresh(); });
        this.observer.observe(select, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ['disabled', 'label'] });

        select.addEventListener('change', function () { self.refresh(); });
        // <label> kliki gizli select-ə fokus verir — düyməyə ötür
        select.addEventListener('focus', function () { trigger.focus(); });

        trigger.addEventListener('click', function () {
            if (openInstance === self) self.close(); else self.open('');
        });
        trigger.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                self.open('');
            } else if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                self.open(e.key);
            }
        });

        this.refresh();
    };

    Instance.prototype.refresh = function () {
        var select = this.select;
        var opt = select.options[indexDesc.get.call(select)];
        var text = opt ? (opt.textContent || opt.value) : '';
        this.textEl.textContent = text || ' ';
        this.trigger.classList.toggle('ss-placeholder', !opt || opt.value === '');
        this.trigger.disabled = select.disabled;
        this.trigger.title = text && text.length > 28 ? text : '';
        if (openInstance === this) this.renderList();
    };

    Instance.prototype.open = function (initialQuery) {
        if (this.select.disabled) return;
        if (openInstance && openInstance !== this) openInstance.close();
        openInstance = this;
        var self = this;

        var panel = document.createElement('div');
        panel.className = 'ss-panel';
        panel.innerHTML =
            '<div class="ss-search-wrap">' +
            '<svg class="ss-search-icon" viewBox="0 0 20 20" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M9 15.5a6.5 6.5 0 1 0 0-13 6.5 6.5 0 0 0 0 13ZM14 14l4 4"/></svg>' +
            '<input type="text" class="ss-search" placeholder="Search..." autocomplete="off" spellcheck="false" role="combobox" aria-autocomplete="list" aria-expanded="true">' +
            '</div>' +
            '<ul class="ss-list" role="listbox" id="' + this.id + '-list"></ul>';
        document.body.appendChild(panel);
        this.panel = panel;
        this.search = panel.querySelector('.ss-search');
        this.list = panel.querySelector('.ss-list');
        this.search.setAttribute('aria-controls', this.id + '-list');
        this.search.value = initialQuery || '';

        this.search.addEventListener('input', function () {
            self.activeIndex = -1;
            self.renderList();
        });
        this.search.addEventListener('keydown', function (e) { self.onKey(e); });
        this.list.addEventListener('mousedown', function (e) { e.preventDefault(); });
        this.list.addEventListener('click', function (e) {
            var li = e.target.closest('.ss-option');
            if (li && !li.classList.contains('is-disabled')) self.choose(+li.dataset.index);
        });

        this.onOutside = function (e) {
            if (!panel.contains(e.target) && e.target !== self.trigger && !self.trigger.contains(e.target)) self.close();
        };
        this.onReposition = function (e) {
            if (e && e.type === 'scroll' && panel.contains(e.target)) return;
            self.position();
        };
        document.addEventListener('mousedown', this.onOutside, true);
        document.addEventListener('touchstart', this.onOutside, true);
        window.addEventListener('resize', this.onReposition);
        window.addEventListener('scroll', this.onReposition, true);

        this.trigger.setAttribute('aria-expanded', 'true');
        this.trigger.classList.add('is-open');
        this.renderList(true);
        this.position();
        this.search.focus();
        if (initialQuery) this.search.setSelectionRange(initialQuery.length, initialQuery.length);
    };

    Instance.prototype.close = function (restoreFocus) {
        if (!this.panel) return;
        document.removeEventListener('mousedown', this.onOutside, true);
        document.removeEventListener('touchstart', this.onOutside, true);
        window.removeEventListener('resize', this.onReposition);
        window.removeEventListener('scroll', this.onReposition, true);
        this.panel.remove();
        this.panel = null;
        this.trigger.setAttribute('aria-expanded', 'false');
        this.trigger.classList.remove('is-open');
        if (openInstance === this) openInstance = null;
        if (restoreFocus) this.trigger.focus();
    };

    Instance.prototype.position = function () {
        if (!this.panel) return;
        var r = this.trigger.getBoundingClientRect();
        if (r.width === 0 && r.height === 0) { this.close(); return; }
        var vw = document.documentElement.clientWidth;
        var vh = window.innerHeight;
        var width = Math.min(Math.max(r.width, 240), vw - 16);
        var left = Math.min(Math.max(8, r.left), vw - width - 8);
        var spaceBelow = vh - r.bottom - 8;
        var spaceAbove = r.top - 8;
        var maxH = 340;
        var panel = this.panel;
        panel.style.width = width + 'px';
        panel.style.left = left + 'px';
        if (spaceBelow < 220 && spaceAbove > spaceBelow) {
            var h = Math.min(maxH, spaceAbove - 4);
            panel.style.maxHeight = h + 'px';
            panel.style.top = '';
            panel.style.bottom = (vh - r.top + 4) + 'px';
        } else {
            panel.style.maxHeight = Math.min(maxH, Math.max(160, spaceBelow - 4)) + 'px';
            panel.style.bottom = '';
            panel.style.top = (r.bottom + 4) + 'px';
        }
    };

    Instance.prototype.renderList = function (scrollToSelected) {
        if (!this.panel) return;
        var select = this.select;
        var q = normalize(this.search.value);
        var selectedIndex = indexDesc.get.call(select);
        var list = this.list;
        var html = [];
        var items = [];
        var lastGroup = null;

        Array.prototype.forEach.call(select.options, function (opt, i) {
            var label = opt.textContent || opt.value;
            // data-keywords: əlavə axtarış sözləri (məs: şəhərin Azərbaycan adı, köhnə yazılışlar)
            if (q && normalize(label + ' ' + (opt.getAttribute('data-keywords') || '')).indexOf(q) === -1) return;
            var group = opt.parentElement && opt.parentElement.tagName === 'OPTGROUP' ? opt.parentElement.label : null;
            if (group && group !== lastGroup) {
                html.push('<li class="ss-group" role="presentation">' + escapeHtml(group) + '</li>');
            }
            lastGroup = group;
            items.push(i);
            var cls = 'ss-option' + (i === selectedIndex ? ' is-selected' : '') + (opt.disabled ? ' is-disabled' : '') + (opt.value === '' ? ' is-placeholder' : '');
            html.push('<li class="' + cls + '" role="option" id="' + this.id + '-o' + i + '" data-index="' + i + '" aria-selected="' + (i === selectedIndex) + '">' + highlight(label, q) + '</li>');
        }, this);

        if (!items.length) html.push('<li class="ss-empty" role="presentation">No results</li>');
        list.innerHTML = html.join('');
        this.items = items;

        if (this.activeIndex === -1 || items.indexOf(this.activeIndex) === -1) {
            this.activeIndex = q ? (items.find(function (i) { return !select.options[i].disabled && select.options[i].value !== ''; }) ?? items[0] ?? -1)
                : (items.indexOf(selectedIndex) !== -1 ? selectedIndex : (items[0] ?? -1));
        }
        this.highlightActive(scrollToSelected || !!q);
        this.position();
    };

    Instance.prototype.highlightActive = function (scroll) {
        var prev = this.list.querySelector('.is-active');
        if (prev) prev.classList.remove('is-active');
        var el = this.activeIndex >= 0 ? this.list.querySelector('[data-index="' + this.activeIndex + '"]') : null;
        if (el) {
            el.classList.add('is-active');
            this.search.setAttribute('aria-activedescendant', el.id);
            if (scroll) el.scrollIntoView({ block: 'nearest' });
        } else {
            this.search.removeAttribute('aria-activedescendant');
        }
    };

    Instance.prototype.onKey = function (e) {
        var items = this.items;
        var pos = items.indexOf(this.activeIndex);
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!items.length) return;
            pos = e.key === 'ArrowDown' ? Math.min(items.length - 1, pos + 1) : Math.max(0, pos - 1);
            this.activeIndex = items[pos];
            this.highlightActive(true);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (this.activeIndex >= 0 && !this.select.options[this.activeIndex].disabled) this.choose(this.activeIndex);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            this.close(true);
        } else if (e.key === 'Tab') {
            this.close(false);
        }
    };

    Instance.prototype.choose = function (index) {
        var select = this.select;
        var changed = indexDesc.get.call(select) !== index;
        indexDesc.set.call(select, index);
        this.refresh();
        this.close(true);
        if (changed) {
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    /** Uyğun hissəni qalın göstər (Azərbaycan hərfləri normallaşdırılmış müqayisə ilə). */
    function highlight(label, q) {
        if (!q) return escapeHtml(label);
        var norm = '';
        var map = [];
        for (var i = 0; i < label.length; i++) {
            var n = normalize(label[i]) || (label[i] === ' ' ? ' ' : '');
            for (var k = 0; k < n.length; k++) { norm += n[k]; map.push(i); }
        }
        var at = norm.indexOf(q);
        if (at === -1) return escapeHtml(label);
        var start = map[at];
        var end = map[at + q.length - 1] + 1;
        return escapeHtml(label.slice(0, start)) + '<mark>' + escapeHtml(label.slice(start, end)) + '</mark>' + escapeHtml(label.slice(end));
    }

    function enhance(select) {
        if (!shouldEnhance(select)) return;
        if (instances.has(select)) return;
        instances.set(select, new Instance(select));
    }

    function enhanceAll(root) {
        (root || document).querySelectorAll('select').forEach(enhance);
    }

    function start() {
        enhanceAll(document);
        // Sonradan əlavə olunan select-lər (yeni gün, modal sətirləri, admin redaktə formaları)
        new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) return;
                    if (node.tagName === 'SELECT') enhance(node);
                    else if (node.querySelectorAll) node.querySelectorAll('select').forEach(enhance);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }

    window.SearchableSelect = { enhance: enhance, enhanceAll: enhanceAll, normalize: normalize };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
