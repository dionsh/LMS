/*
 * Small progressive enhancements. Every page works without JavaScript;
 * this file only adds comfort. No dependencies, no inline scripts (CSP).
 */
(function () {
    'use strict';

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ------------------------------------------------------------------
     * Menus and drawers
     *   <button data-toggle="site-menu" aria-controls="site-menu" aria-expanded="false">
     *   <div id="site-menu" class="site-menu" data-overlay> … <button data-close="site-menu">
     * An element with [data-scrim="id"] is shown alongside the target.
     * ------------------------------------------------------------------ */
    function initToggles() {
        let openId = null;
        let lastTrigger = null;

        function setOpen(id, open, trigger) {
            const target = document.getElementById(id);
            if (!target) return;

            target.classList.toggle('is-open', open);
            document.querySelectorAll('[data-toggle="' + id + '"]').forEach(function (btn) {
                btn.setAttribute('aria-expanded', String(open));
            });
            document.querySelectorAll('[data-scrim="' + id + '"]').forEach(function (scrim) {
                scrim.classList.toggle('is-visible', open);
            });
            document.documentElement.classList.toggle('has-overlay', open);

            if (open) {
                openId = id;
                lastTrigger = trigger || null;
                const focusable = target.querySelector('a[href], button:not([disabled]), input, select, textarea');
                if (focusable) focusable.focus();
            } else {
                openId = null;
                if (lastTrigger) lastTrigger.focus();
            }
        }

        document.addEventListener('click', function (event) {
            const toggle = event.target.closest('[data-toggle]');
            if (toggle) {
                const id = toggle.getAttribute('data-toggle');
                setOpen(id, toggle.getAttribute('aria-expanded') !== 'true', toggle);
                return;
            }

            const close = event.target.closest('[data-close], [data-scrim]');
            if (close) {
                setOpen(close.getAttribute('data-close') || close.getAttribute('data-scrim'), false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && openId) setOpen(openId, false);
        });

        // Closing the drawer when the layout switches to desktop
        window.matchMedia('(min-width: 64rem)').addEventListener('change', function (mq) {
            if (mq.matches && openId) setOpen(openId, false);
        });
    }

    /* ------------------------------------------------------------------
     * <details class="menu"> dropdowns: close on outside click and on Esc
     * ------------------------------------------------------------------ */
    function initDropdowns() {
        document.addEventListener('click', function (event) {
            document.querySelectorAll('details.menu[open]').forEach(function (menu) {
                if (!menu.contains(event.target)) menu.removeAttribute('open');
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            document.querySelectorAll('details.menu[open]').forEach(function (menu) {
                menu.removeAttribute('open');
                menu.querySelector('summary').focus();
            });
        });
    }

    /* ------------------------------------------------------------------
     * Tabs (WAI-ARIA pattern): [role=tablist] > [role=tab][aria-controls]
     * ------------------------------------------------------------------ */
    function initTabs() {
        document.querySelectorAll('[role="tablist"]').forEach(function (list) {
            const tabs = Array.from(list.querySelectorAll('[role="tab"]'));

            function select(tab, focus) {
                tabs.forEach(function (other) {
                    const selected = other === tab;
                    other.setAttribute('aria-selected', String(selected));
                    other.tabIndex = selected ? 0 : -1;
                    const panel = document.getElementById(other.getAttribute('aria-controls'));
                    if (panel) panel.hidden = !selected;
                });
                if (focus) tab.focus();
            }

            tabs.forEach(function (tab, index) {
                tab.addEventListener('click', function () { select(tab, false); });
                tab.addEventListener('keydown', function (event) {
                    let next = null;
                    if (event.key === 'ArrowRight') next = tabs[(index + 1) % tabs.length];
                    if (event.key === 'ArrowLeft') next = tabs[(index - 1 + tabs.length) % tabs.length];
                    if (event.key === 'Home') next = tabs[0];
                    if (event.key === 'End') next = tabs[tabs.length - 1];
                    if (next) {
                        event.preventDefault();
                        select(next, true);
                    }
                });
            });
        });
    }

    /* ------------------------------------------------------------------
     * Dismissable messages: <button data-dismiss> inside .alert
     * ------------------------------------------------------------------ */
    function initDismiss() {
        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-dismiss]');
            if (!button) return;
            const alert = button.closest('.alert');
            if (alert) alert.remove();
        });
    }

    /* ------------------------------------------------------------------
     * Confirmation before destructive actions:
     *   <form method="post" data-confirm="Fshij këtë detyrë?" data-confirm-text="…" data-confirm-button="Fshij">
     * Uses <dialog id="confirm-dialog"> rendered by the layout.
     * ------------------------------------------------------------------ */
    function initConfirm() {
        const dialog = document.getElementById('confirm-dialog');
        if (!dialog || typeof dialog.showModal !== 'function') return;

        let pendingForm = null;

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!form.hasAttribute('data-confirm') || form.dataset.confirmed === 'yes') return;

            event.preventDefault();
            pendingForm = form;
            dialog.querySelector('[data-confirm-title]').textContent = form.getAttribute('data-confirm');
            dialog.querySelector('[data-confirm-text]').textContent = form.getAttribute('data-confirm-text') || '';
            dialog.querySelector('[data-confirm-accept]').textContent = form.getAttribute('data-confirm-button') || 'Po, vazhdo';
            dialog.showModal();
        });

        dialog.addEventListener('close', function () {
            if (dialog.returnValue === 'accept' && pendingForm) {
                pendingForm.dataset.confirmed = 'yes';
                pendingForm.requestSubmit();
            }
            pendingForm = null;
            dialog.returnValue = '';
        });
    }

    /* ------------------------------------------------------------------
     * Submit buttons show a busy state (prevents double submissions)
     * ------------------------------------------------------------------ */
    function initBusyButtons() {
        document.addEventListener('submit', function (event) {
            if (event.defaultPrevented || event.target.method === 'dialog') return;
            const button = event.submitter;
            if (button && button.classList.contains('btn')) {
                window.setTimeout(function () { button.setAttribute('aria-busy', 'true'); }, 0);
            }
        });

        // Coming back with the browser's back button (page restored from cache)
        // must not leave buttons stuck in the busy state
        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) return;
            document.querySelectorAll('.btn[aria-busy="true"]').forEach(function (btn) {
                btn.removeAttribute('aria-busy');
            });
        });
    }

    /* ------------------------------------------------------------------
     * Show/hide password: <button data-password-toggle aria-controls="password">
     * ------------------------------------------------------------------ */
    function initPasswordToggles() {
        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-password-toggle]');
            if (!button) return;
            const input = document.getElementById(button.getAttribute('aria-controls'));
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(show));
            button.setAttribute('aria-label', show ? 'Fshih fjalëkalimin' : 'Shfaq fjalëkalimin');
            const use = button.querySelector('use');
            if (use) {
                use.setAttribute('href', use.getAttribute('href').replace(/#.*$/, show ? '#eye-off' : '#eye'));
            }
        });
    }

    /* ------------------------------------------------------------------
     * Gentle reveal on scroll for [data-reveal] (below the fold only)
     * ------------------------------------------------------------------ */
    function initReveal() {
        const items = document.querySelectorAll('[data-reveal]');
        if (reduceMotion || !('IntersectionObserver' in window) || items.length === 0) return;

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.remove('is-pending');
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -8% 0px' });

        items.forEach(function (item) {
            if (item.getBoundingClientRect().top > window.innerHeight) {
                item.classList.add('is-pending');
                observer.observe(item);
            }
        });
    }

    /* ------------------------------------------------------------------
     * Masthead shadow once the page is scrolled
     * ------------------------------------------------------------------ */
    function initMasthead() {
        const masthead = document.querySelector('.masthead');
        if (!masthead) return;
        let ticking = false;
        window.addEventListener('scroll', function () {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(function () {
                masthead.classList.toggle('is-scrolled', window.scrollY > 8);
                ticking = false;
            });
        }, { passive: true });
    }

    initToggles();
    initDropdowns();
    initTabs();
    initDismiss();
    initConfirm();
    initBusyButtons();
    initPasswordToggles();
    initReveal();
    initMasthead();
})();
