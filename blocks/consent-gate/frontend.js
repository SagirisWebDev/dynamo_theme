(function () {
    'use strict';

    function revealForCategory(category) {
        document.querySelectorAll('.dynamo-consent-gate[data-consent-category="' + category + '"]')
            .forEach(function (gate) {
                gate.style.display = '';
            });
    }

    function checkInitialConsent() {
        document.querySelectorAll('.dynamo-consent-gate[data-consent-category]')
            .forEach(function (el) {
                var category = el.getAttribute('data-consent-category');
                if (!category) { return; }
                var complianzGranted = typeof window.cmplz_has_consent === 'function' &&
                    window.cmplz_has_consent(category);
                var borlabsGranted = window.BorlabsCookie &&
                    typeof window.BorlabsCookie.checkCookieConsent === 'function' &&
                    window.BorlabsCookie.checkCookieConsent(category);
                if (complianzGranted || borlabsGranted) {
                    revealForCategory(category);
                }
            });
    }

    document.addEventListener('cmplz_status_change', function (e) {
        if (e.detail && e.detail.category && e.detail.value === 'allow') {
            revealForCategory(e.detail.category);
        }
    });

    document.addEventListener('borlabs-cookie-consent-saved', function () {
        if (!window.BorlabsCookie || typeof window.BorlabsCookie.checkCookieConsent !== 'function') {
            return;
        }
        document.querySelectorAll('.dynamo-consent-gate[data-consent-category]')
            .forEach(function (el) {
                var category = el.getAttribute('data-consent-category');
                if (category && window.BorlabsCookie.checkCookieConsent(category)) {
                    revealForCategory(category);
                }
            });
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkInitialConsent);
    } else {
        checkInitialConsent();
    }
}());
