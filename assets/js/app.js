/**
 * assets/js/app.js
 * Storefront UI Interactions with Automatic Telemetry Instrumentation.
 */

(function () {
    'use strict';

    const App = {
        getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.content) return meta.content;
            const input = document.querySelector('input[name="csrf_token"]');
            if (input && input.value) return input.value;
            return '';
        },

        async addToCart(productId, quantity = 1, buttonEl = null) {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            const token = App.getCsrfToken();
            if (token) {
                formData.append('csrf_token', token);
            }

            if (buttonEl) {
                buttonEl.disabled = true;
                buttonEl.dataset.originalText = buttonEl.textContent;
                buttonEl.textContent = 'Adding...';
            }

            try {
                const response = await fetch('index.php?route=cart/add', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const contentType = response.headers.get('content-type') || '';
                let data = null;
                if (contentType.includes('application/json')) {
                    data = await response.json();
                }

                if (!response.ok) {
                    if (data && data.require_login && data.redirect) {
                        App.showToast('Please sign in to add items to your cart.', 'error');
                        setTimeout(() => { window.location.href = data.redirect; }, 1000);
                        return data;
                    }
                    const msg = (data && data.message) ? data.message : 'Failed to add item to cart.';
                    App.showToast(msg, 'error');
                    return data || { success: false, message: msg };
                }

                if (data && data.success) {
                    App.updateBadgeCount('cart-badge', data.cart_count);
                    App.showToast(data.message || 'Added to cart!', 'success');
                } else {
                    App.showToast((data && data.message) ? data.message : 'Failed to add item.', 'error');
                }
                return data;
            } catch (err) {
                App.showToast('Network error while adding to cart.', 'error');
                throw err;
            } finally {
                if (buttonEl) {
                    buttonEl.disabled = false;
                    buttonEl.textContent = buttonEl.dataset.originalText || 'Add to Cart';
                }
            }
        },

        async toggleWishlist(productId, buttonEl = null) {
            const formData = new FormData();
            formData.append('product_id', productId);

            const token = App.getCsrfToken();
            if (token) {
                formData.append('csrf_token', token);
            }

            try {
                const response = await fetch('index.php?route=wishlist/toggle', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const contentType = response.headers.get('content-type') || '';
                let data = null;
                if (contentType.includes('application/json')) {
                    data = await response.json();
                }

                if (!response.ok) {
                    if (data && data.require_login && data.redirect) {
                        App.showToast('Please sign in to save items to your wishlist.', 'error');
                        setTimeout(() => { window.location.href = data.redirect; }, 1000);
                        return data;
                    }
                    const msg = (data && data.message) ? data.message : 'Failed to update wishlist.';
                    App.showToast(msg, 'error');
                    return data || { success: false, message: msg };
                }

                if (data && data.success) {
                    App.updateBadgeCount('wishlist-badge', data.wishlist_count);
                    App.showToast(data.message, 'success');

                    if (buttonEl) {
                        buttonEl.classList.toggle('active', data.in_wishlist);
                        buttonEl.setAttribute('aria-pressed', data.in_wishlist ? 'true' : 'false');
                    }
                }
                return data;
            } catch (err) {
                App.showToast('Network error while toggling wishlist.', 'error');
                throw err;
            }
        },

        updateBadgeCount(elementId, count) {
            const badge = document.getElementById(elementId);
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'inline-flex' : 'none';
            }
        },

        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.setAttribute('translate', 'no');
            toast.textContent = message;

            const container = document.getElementById('toast-container') || document.body;
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        },

        openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            }
        },

        closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            }
        }
    };

    // Automatically wrap App methods with Telemetry profiling
    if (window.AppTelemetry && window.AppTelemetry.wrapFunction) {
        for (const [key, fn] of Object.entries(App)) {
            if (typeof fn === 'function') {
                App[key] = window.AppTelemetry.wrapFunction('App', key, fn);
            }
        }
    }

    window.App = App;

    // Attach data-action handlers on DOM load
    document.addEventListener('DOMContentLoaded', () => {
        // Quantity spinner controls
        document.querySelectorAll('.qty-spinner-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const spinner = btn.closest('.qty-spinner');
                if (!spinner) return;
                const input = spinner.querySelector('.qty-spinner-input');
                if (!input) return;

                const min = parseInt(input.min, 10) || 1;
                const max = parseInt(input.max, 10) || 10;
                let val = parseInt(input.value, 10) || 1;

                if (btn.dataset.dir === 'up' && val < max) {
                    val++;
                } else if (btn.dataset.dir === 'down' && val > min) {
                    val--;
                }

                input.value = val;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        // "Buy Now" button handler
        document.querySelectorAll('[data-action="buy-now"]').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const pid = btn.dataset.productId;
                if (!pid) return;

                const form = document.getElementById('product-purchase-form');
                const qtyInput = form ? form.querySelector('input[name="quantity"]') : null;
                const qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;

                btn.disabled = true;
                const originalText = btn.innerHTML;
                btn.textContent = 'Processing...';

                try {
                    const result = await App.addToCart(pid, qty);
                    if (result && result.success) {
                        window.location.href = 'index.php?route=checkout';
                    } else if (result && result.redirect) {
                        window.location.href = result.redirect;
                    }
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            });
        });

        // Copy Tracking Number button
        document.querySelectorAll('[data-action="copy-tracking"]').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const code = btn.dataset.code;
                if (!code) return;

                try {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        await navigator.clipboard.writeText(code);
                    } else {
                        const tempInput = document.createElement('input');
                        tempInput.value = code;
                        document.body.appendChild(tempInput);
                        tempInput.select();
                        document.execCommand('copy');
                        tempInput.remove();
                    }

                    App.showToast(`Tracking code ${code} copied!`, 'success');
                    const origHtml = btn.innerHTML;
                    btn.innerHTML = '<span>✓ Copied!</span>';
                    setTimeout(() => { btn.innerHTML = origHtml; }, 2000);
                } catch (err) {
                    App.showToast('Could not copy to clipboard.', 'error');
                }
            });
        });

        // Quick add to cart buttons
        document.querySelectorAll('[data-action="add-to-cart"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const pid = btn.dataset.productId;
                if (pid) App.addToCart(pid, 1, btn);
            });
        });

        // Wishlist toggle buttons
        document.querySelectorAll('[data-action="toggle-wishlist"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const pid = btn.dataset.productId;
                if (pid) App.toggleWishlist(pid, btn);
            });
        });

        // Modal triggers
        document.querySelectorAll('[data-modal-open]').forEach(btn => {
            btn.addEventListener('click', () => App.openModal(btn.dataset.modalOpen));
        });
        document.querySelectorAll('[data-modal-close]').forEach(btn => {
            btn.addEventListener('click', () => App.closeModal(btn.dataset.modalClose));
        });

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-backdrop.is-open').forEach(modal => {
                    App.closeModal(modal.id);
                });
            }
        });
    });
})();
