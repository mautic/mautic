'use strict';

Mautic.MarketplaceRating = {
    init: function () {
        const container = document.getElementById('marketplace-review-container');
        if (!container) {
            return;
        }

        const AUTH0_DOMAIN = container.getAttribute('data-auth0-domain');
        const AUTH0_CLIENT_ID = container.getAttribute('data-auth0-client-id');
        const API_URL = container.getAttribute('data-api-url');
        const PACKAGE_NAME = container.getAttribute('data-package-name');
        const SUBMIT_TEXT = container.getAttribute('data-submit-text');
        const AUTH0_SDK_URL = 'https://cdn.auth0.com/js/auth0-spa-js/2.0/auth0-spa-js.production.js';

        let auth0Client = null;

        const loadingEl = document.getElementById('auth-loading');
        const loginEl = document.getElementById('auth-login');
        const formEl = document.getElementById('auth-form');
        const userNameEl = document.getElementById('auth-user-name');
        const errorEl = document.getElementById('review-error');
        const successEl = document.getElementById('review-success');
        const submitBtn = document.getElementById('submit-btn');

        function loadAuth0SDK() {
            return new Promise(function (resolve, reject) {
                if (typeof auth0 !== 'undefined') {
                    resolve();
                    return;
                }

                const script = document.createElement('script');
                script.src = AUTH0_SDK_URL;
                script.onload = resolve;
                script.onerror = function () {
                    reject(new Error('Failed to load Auth0 SDK'));
                };
                document.head.appendChild(script);
            });
        }

        async function initAuth0() {
            try {
                await loadAuth0SDK();

                auth0Client = await auth0.createAuth0Client({
                    domain: AUTH0_DOMAIN,
                    clientId: AUTH0_CLIENT_ID,
                    cacheLocation: 'localstorage'
                });

                if (window.location.search.includes('code=')) {
                    await auth0Client.handleRedirectCallback();
                    window.history.replaceState({}, document.title, window.location.pathname);
                }

                await updateUI();
            } catch (e) {
                console.error('Auth0 init error:', e);
                showError('Failed to initialize authentication: ' + e.message);
                loadingEl.style.display = 'none';
                loginEl.style.display = 'block';
            }
        }

        async function updateUI() {
            loadingEl.style.display = 'none';

            const isAuthenticated = await auth0Client.isAuthenticated();

            if (isAuthenticated) {
                const user = await auth0Client.getUser();
                userNameEl.textContent = user.name || user.email;
                loginEl.style.display = 'none';
                formEl.style.display = 'block';
            } else {
                loginEl.style.display = 'block';
                formEl.style.display = 'none';
            }
        }

        document.getElementById('auth0-login-btn').addEventListener('click', async function () {
            try {
                await auth0Client.loginWithPopup();
                await updateUI();
            } catch (e) {
                console.error('Login error:', e);
                showError('Login failed: ' + e.message);
            }
        });

        document.getElementById('auth0-logout-btn').addEventListener('click', async function (e) {
            e.preventDefault();
            await auth0Client.logout({localOnly: true});
            await updateUI();
        });

        const stars = document.querySelectorAll('#rating-stars .star-icon');
        const ratingInput = document.getElementById('rating');

        stars.forEach(function (star) {
            star.addEventListener('click', function () {
                const rating = this.getAttribute('data-rating');
                ratingInput.value = rating;
                updateStars(rating);
            });

            star.addEventListener('mouseenter', function () {
                const rating = this.getAttribute('data-rating');
                highlightStars(rating);
            });

            star.addEventListener('mouseleave', function () {
                highlightStars(ratingInput.value);
            });
        });

        function updateStars(rating) {
            stars.forEach(function (s, index) {
                if (index < rating) {
                    s.classList.remove('ri-star-line', 'text-muted');
                    s.classList.add('ri-star-fill', 'text-warning');
                } else {
                    s.classList.remove('ri-star-fill', 'text-warning');
                    s.classList.add('ri-star-line', 'text-muted');
                }
            });
        }

        function highlightStars(rating) {
            stars.forEach(function (s, index) {
                if (index < rating) {
                    s.classList.add('text-warning');
                } else {
                    s.classList.remove('text-warning');
                }
            });
        }

        document.getElementById('review-form').addEventListener('submit', async function (e) {
            e.preventDefault();

            const rating = parseInt(ratingInput.value);
            const review = document.getElementById('review').value;

            if (rating < 1 || rating > 5) {
                showError('Please select a rating between 1 and 5 stars.');
                return;
            }

            try {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Submitting...';
                hideMessages();

                const user = await auth0Client.getUser();
                const token = await auth0Client.getTokenSilently();

                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify({
                        package: PACKAGE_NAME,
                        rating: rating,
                        review: review,
                        name: user.name || user.email,
                        user_id: user.sub,
                        picture: user.picture || null
                    })
                });

                if (!response.ok) {
                    throw new Error('Failed to submit review');
                }

                showSuccess('Review submitted successfully!');
                ratingInput.value = 0;
                updateStars(0);
                document.getElementById('review').value = '';

            } catch (err) {
                console.error('Submit error:', err);
                showError('Failed to submit review. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = SUBMIT_TEXT;
            }
        });

        function showError(msg) {
            errorEl.textContent = msg;
            errorEl.style.display = 'block';
            successEl.style.display = 'none';
        }

        function showSuccess(msg) {
            successEl.textContent = msg;
            successEl.style.display = 'block';
            errorEl.style.display = 'none';
        }

        function hideMessages() {
            errorEl.style.display = 'none';
            successEl.style.display = 'none';
        }

        initAuth0();
    }
};
