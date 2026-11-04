/**
 * assets/js/telemetry.js
 * Educational In-Browser Telemetry & Diagnostics Engine
 * 
 * Captures and logs every UI event (clicks, form submits, inputs), JS function call,
 * and application CSS/DOM modification directly to the Browser DevTools Console,
 * while safely filtering out external extensions (DarkReader, Google Translate).
 */

(function () {
    'use strict';

    const STYLES = {
        backend: 'background: #0f172a; color: #38bdf8; padding: 2px 6px; border-radius: 3px; font-weight: 600;',
        ui: 'background: #1e3a8a; color: #93c5fd; padding: 2px 6px; border-radius: 3px; font-weight: 600;',
        js: 'background: #064e3b; color: #6ee7b7; padding: 2px 6px; border-radius: 3px; font-weight: 600;',
        dom: 'background: #4c1d95; color: #c4b5fd; padding: 2px 6px; border-radius: 3px; font-weight: 600;',
        sql: 'color: #fbbf24; font-weight: 500;',
        sub: 'color: #94a3b8; font-size: 11px;'
    };

    const AppTelemetry = {
        init() {
            this.logBackendPayload();
            this.setupUIEventListeners();
            this.setupExtensionSafeDOMObserver();
            console.log(
                '%c[Telemetry Ready]%c Amazon Mini Educational Telemetry is active. Open DevTools Console to trace app lifecycle.',
                'background: #10b981; color: white; padding: 2px 6px; border-radius: 3px; font-weight: bold;',
                'color: #64748b; font-style: italic;'
            );
        },

        logBackendPayload() {
            const dataEl = document.getElementById('php-telemetry-data');
            if (!dataEl) return;

            try {
                const telemetry = JSON.parse(dataEl.textContent);
                const req = telemetry.request || {};
                const db = telemetry.database || {};
                const events = telemetry.events || [];

                console.groupCollapsed(
                    `%c[Backend PHP]%c ${req.method} ?route=${req.route} (${req.duration_ms} ms | ${db.query_count || 0} SQL queries | ${req.memory_peak_kb} KB)`,
                    STYLES.backend,
                    'color: #0284c7; font-weight: 600;'
                );
                console.log('Action Handler:', req.action);
                console.log('Authenticated User:', req.user || 'Guest');

                if (db.queries && db.queries.length > 0) {
                    console.groupCollapsed(`%cSQL Queries (${db.queries.length})`, STYLES.sql);
                    console.table(db.queries);
                    console.groupEnd();
                }

                if (events.length > 0) {
                    console.groupCollapsed(`External Events (${events.length})`, 'color: #a855f7;');
                    events.forEach(e => console.log(`[${e.timestamp}] ${e.event}:`, e.details));
                    console.groupEnd();
                }

                console.groupEnd();
            } catch (err) {
                console.warn('[Telemetry] Could not parse backend PHP telemetry JSON:', err);
            }
        },

        setupUIEventListeners() {
            // Log all button and action link clicks
            document.addEventListener('click', (e) => {
                const target = e.target.closest('button, a, input[type="submit"], [data-action]');
                if (!target) return;

                // Ignore clicks on DarkReader or Google Translate controls
                if (this.isExternalExtensionElement(target)) return;

                const tag = target.tagName.toLowerCase();
                const text = (target.innerText || target.value || target.getAttribute('aria-label') || '').trim().slice(0, 35);
                const identifier = target.id ? `#${target.id}` : (target.className ? `.${target.className.split(' ').join('.')}` : '');
                const action = target.getAttribute('data-action') || target.getAttribute('href') || target.getAttribute('type') || 'action';

                console.log(
                    `%c[UI Event: Click]%c <${tag}${identifier}> "${text}" -> [${action}]`,
                    STYLES.ui,
                    'color: #2563eb; font-weight: 500;',
                    { element: target, event: e }
                );
            }, true);

            // Log form submissions
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (!form || this.isExternalExtensionElement(form)) return;

                const action = form.getAttribute('action') || window.location.search;
                const method = (form.getAttribute('method') || 'GET').toUpperCase();

                console.log(
                    `%c[UI Event: Form Submit]%c <form action="${action}" method="${method}">`,
                    STYLES.ui,
                    'color: #1d4ed8; font-weight: 600;',
                    { form }
                );
            }, true);

            // Log input/select changes
            document.addEventListener('change', (e) => {
                const input = e.target;
                if (!input || this.isExternalExtensionElement(input)) return;

                const name = input.getAttribute('name') || input.id || 'input';
                // Don't log full passwords in console
                const val = input.type === 'password' ? '••••••••' : input.value;

                console.log(
                    `%c[UI Event: Input Change]%c field "${name}" = "${val}"`,
                    STYLES.ui,
                    'color: #3b82f6;',
                    { input, value: val }
                );
            }, true);
        },

        setupExtensionSafeDOMObserver() {
            // MutationObserver watching class and style attribute changes on application nodes
            const observer = new MutationObserver((mutations) => {
                for (const mutation of mutations) {
                    if (mutation.type === 'attributes') {
                        const attrName = mutation.attributeName;
                        if (attrName !== 'class' && attrName !== 'style' && !attrName.startsWith('data-state')) {
                            continue;
                        }

                        const target = mutation.target;
                        if (this.isExternalExtensionElement(target)) continue;

                        const oldValue = mutation.oldValue || '(none)';
                        const newValue = target.getAttribute(attrName) || '(none)';

                        if (oldValue !== newValue) {
                            console.log(
                                `%c[CSS/DOM Change]%c <${target.tagName.toLowerCase()}${target.id ? '#' + target.id : ''}> attribute "${attrName}" changed: "${oldValue}" -> "${newValue}"`,
                                STYLES.dom,
                                'color: #7c3aed;',
                                { target, oldValue, newValue }
                            );
                        }
                    }
                }
            });

            // Start observing the app container once DOM is loaded
            const root = document.getElementById('app-root') || document.body;
            observer.observe(root, {
                attributes: true,
                attributeOldValue: true,
                subtree: true,
                attributeFilter: ['class', 'style', 'data-state']
            });
        },

        isExternalExtensionElement(el) {
            if (!el || !el.getAttribute) return false;

            // Detect DarkReader injected elements and attributes
            if (
                el.classList?.contains('darkreader') ||
                el.hasAttribute?.('data-darkreader-inline-stroke') ||
                el.hasAttribute?.('data-darkreader-inline-color') ||
                el.hasAttribute?.('data-darkreader-inline-bgcolor') ||
                el.tagName === 'DARKREADER-INLINE-OVERRIDE'
            ) {
                return true;
            }

            // Detect Google Translate injected tags
            if (
                el.tagName === 'FONT' ||
                el.id?.startsWith('goog-gt-') ||
                el.classList?.contains('skiptranslate') ||
                el.classList?.contains('goog-te-banner-frame') ||
                el.hasAttribute?.('data-google-lang')
            ) {
                return true;
            }

            return false;
        },

        // Wrapper to trace any JS function call in console
        wrapFunction(moduleName, funcName, fn) {
            return function (...args) {
                const start = performance.now();
                let result;
                let error = null;

                try {
                    result = fn.apply(this, args);
                    return result;
                } catch (err) {
                    error = err;
                    throw err;
                } finally {
                    const durationMs = (performance.now() - start).toFixed(2);
                    console.log(
                        `%c[JS Call]%c ${moduleName}.${funcName}() completed in ${durationMs}ms`,
                        STYLES.js,
                        'color: #059669; font-weight: 500;',
                        { args, result, error }
                    );
                }
            };
        }
    };

    window.AppTelemetry = AppTelemetry;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => AppTelemetry.init());
    } else {
        AppTelemetry.init();
    }
})();
