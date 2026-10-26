/**
 * forms/utils.js
 * Shared utilities for all nurse form preview scripts.
 * Loaded before every other nurse-form-*.js file.
 */
'use strict';

/**
 * HTML-escape a value for safe insertion into innerHTML.
 * @param {*} v
 * @returns {string}
 */
function escHtml(v) {
    return String(v == null ? '' : v)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Format an ISO date string (YYYY-MM-DD) to a readable locale string.
 * @param {string} val
 * @returns {string}
 */
function fmtDate(val) {
    if (!val) { return ''; }
    try {
        return new Date(val + 'T00:00').toLocaleDateString('en-PH', {
            year: 'numeric', month: 'long', day: 'numeric',
        });
    } catch (e) { return val; }
}

/**
 * Inject a CSS string into <head> exactly once.
 * @param {string} id    - Unique id for the <style> element.
 * @param {string} css   - CSS text to inject.
 */
function injectStyle(id, css) {
    var existing = document.getElementById(id);
    if (existing) { existing.remove(); }
    var s = document.createElement('style');
    s.id = id;
    s.textContent = css;
    document.head.appendChild(s);
}

/**
 * Open a print URL in a new window and trigger window.print() after load.
 * @param {string} url
 */
function openPrint(url) {
    var win = window.open(url, '_blank', 'width=900,height=1200');
    if (win) {
        win.addEventListener('load', function () {
            setTimeout(function () { win.focus(); win.print(); }, 200);
        });
    }
}

/**
 * Read the value of a DOM input/select/textarea by id.
 * @param {string} id
 * @returns {string}
 */
function fieldVal(id) {
    var el = document.getElementById(id);
    return el ? (el.value || '') : '';
}

/**
 * Check whether a checkbox is checked.
 * @param {string} id
 * @returns {boolean}
 */
function fieldChecked(id) {
    var el = document.getElementById(id);
    return !!(el && el.checked);
}

/**
 * Get the value of the selected radio button in a named group.
 * @param {string} name
 * @returns {string}
 */
function radioVal(name) {
    var el = document.querySelector('input[name="' + name + '"]:checked');
    return el ? el.value : '';
}

/**
 * Build the standard SUNN clinic header HTML block.
 * @param {string} prefix  - CSS class prefix, e.g. 'mc' or 'rx'.
 * @returns {string}
 */
function buildSunnHeader(prefix) {
    var p = prefix + '-';
    return '<div class="' + p + 'hdr">' +
        '<img src="/clinic/images/sunn-logo.png" alt="SUNN Logo"/>' +
        '<div class="' + p + 'ht">' +
            '<div class="' + p + 'republic">Republic of the Philippines</div>' +
            '<div class="' + p + 'uni">STATE UNIVERSITY OF NORTHERN NEGROS</div>' +
            '<div class="' + p + 'office">MEDICAL AND DENTAL SERVICES OFFICE</div>' +
            '<div class="' + p + 'addr">' +
                'SUNN Main Campus<br/>Sagay City, Negros Occidental<br/>Philippines 6122' +
            '</div>' +
        '</div>' +
    '</div>';
}
