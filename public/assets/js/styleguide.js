/*
 * Development-only checks for /_stilet: reads the colour tokens straight from
 * tokens.css, computes WCAG contrast ratios, and verifies the web fonts.
 */
(function () {
    'use strict';

    const styles = getComputedStyle(document.documentElement);

    function parse(value) {
        value = value.trim();
        if (value.charAt(0) === '#') {
            let hex = value.slice(1);
            if (hex.length === 3) hex = hex.split('').map(function (c) { return c + c; }).join('');
            return [parseInt(hex.slice(0, 2), 16), parseInt(hex.slice(2, 4), 16), parseInt(hex.slice(4, 6), 16), 1];
        }
        const match = value.match(/rgba?\(([^)]+)\)/);
        if (match) {
            const parts = match[1].split(',').map(parseFloat);
            return [parts[0], parts[1], parts[2], parts.length > 3 ? parts[3] : 1];
        }
        return null;
    }

    function token(name) {
        return parse(styles.getPropertyValue(name));
    }

    function blend(fg, bg) {
        const a = fg[3];
        return [fg[0] * a + bg[0] * (1 - a), fg[1] * a + bg[1] * (1 - a), fg[2] * a + bg[2] * (1 - a), 1];
    }

    function luminance(c) {
        const channel = function (v) {
            v /= 255;
            return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
        };
        return 0.2126 * channel(c[0]) + 0.7152 * channel(c[1]) + 0.0722 * channel(c[2]);
    }

    function ratio(a, b) {
        const la = luminance(a);
        const lb = luminance(b);
        return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
    }

    function hex(c) {
        return '#' + c.slice(0, 3).map(function (v) {
            return Math.round(v).toString(16).padStart(2, '0');
        }).join('').toUpperCase();
    }

    // Swatches
    document.querySelectorAll('[data-swatch]').forEach(function (swatch) {
        const name = swatch.getAttribute('data-swatch');
        const colour = token(name);
        swatch.querySelector('[data-chip]').style.backgroundColor = 'var(' + name + ')';
        swatch.querySelector('[data-hex]').textContent = colour ? hex(colour) : '?';
    });

    // Contrast table
    let failures = 0;
    document.querySelectorAll('[data-contrast]').forEach(function (row) {
        const bg = token(row.dataset.bg);
        let fg = token(row.dataset.fg);
        if (!bg || !fg) return;
        if (fg[3] < 1) fg = blend(fg, bg);

        const value = ratio(fg, bg);
        const pass = value >= parseFloat(row.dataset.min);
        if (!pass) failures++;

        row.querySelector('[data-ratio]').textContent = value.toFixed(2).replace('.', ',') + ':1';
        const result = row.querySelector('[data-result]');
        result.textContent = pass ? 'Kalon' : 'Dështon';
        result.className = 'badge badge--' + (pass ? 'success' : 'danger');

        const sample = row.querySelector('[data-sample]');
        sample.style.color = 'var(' + row.dataset.fg + ')';
        sample.style.backgroundColor = 'var(' + row.dataset.bg + ')';
    });

    const summary = document.querySelector('[data-contrast-summary]');
    if (summary) {
        summary.textContent = failures === 0
            ? 'Të gjitha çiftet kalojnë WCAG AA.'
            : failures + ' çift(e) nuk kalojnë WCAG AA.';
    }

    // Web fonts: loaded from our own server, and tabular figures available
    document.fonts.ready.then(function () {
        const status = document.querySelector('[data-font-status]');
        if (!status) return;

        const loaded = {};
        document.fonts.forEach(function (face) {
            const family = face.family.replace(/["']/g, '');
            const key = family + (face.style === 'italic' ? ' italic' : '');
            if (face.status === 'loaded') loaded[key] = true;
        });

        const ones = document.querySelector('[data-tnum="1"]');
        const zeros = document.querySelector('[data-tnum="0"]');
        const tabular = ones && zeros
            && Math.abs(ones.getBoundingClientRect().width - zeros.getBoundingClientRect().width) < 0.5;

        const parts = ['Manrope', 'Newsreader', 'Newsreader italic'].map(function (name) {
            return name + (loaded[name] ? ' ✓' : ' ✗');
        });
        parts.push('shifra tabelare ' + (tabular ? '✓' : '✗'));
        status.textContent = parts.join(' · ');
        status.dataset.fontsOk = String(Boolean(loaded.Manrope && loaded.Newsreader && tabular));
    });
})();
