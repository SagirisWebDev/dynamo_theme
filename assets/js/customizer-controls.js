(function () {
    if (!window.wp || !wp.customize) {
        return;
    }

    var styleInjected = false;
    function injectStyles() {
        if (styleInjected) return;
        styleInjected = true;
        var s = document.createElement('style');
        s.textContent =
            '.dynamo-stepper { display: flex; align-items: stretch; gap: 4px; }' +
            '.dynamo-stepper > input { flex: 1; min-width: 0; }' +
            '.dynamo-stepper-btn {' +
            '  width: 28px; padding: 0; line-height: 1; cursor: pointer;' +
            '  background: #f0f0f1; border: 1px solid #8c8f94; border-radius: 3px;' +
            '  font-size: 16px; color: #1d2327;' +
            '}' +
            '.dynamo-stepper-btn:hover { background: #e0e0e1; }' +
            '.dynamo-stepper-btn:focus { outline: 2px solid #2271b1; outline-offset: -2px; }';
        document.head.appendChild(s);
    }

    function decimalsOf(n) {
        var s = String(n);
        var i = s.indexOf('.');
        return i < 0 ? 0 : s.length - i - 1;
    }

    function bump(value, step, direction) {
        var match = String(value || '').match(/^\s*(-?\d*\.?\d+)\s*(\D*)\s*$/);
        var unit = '';
        var num  = 0;
        if (match) {
            num  = parseFloat(match[1]);
            unit = match[2] || '';
        }
        var next = num + step * direction;
        var decimals = decimalsOf(step);
        if (decimals > 0) {
            next = parseFloat(next.toFixed(decimals));
        } else {
            next = Math.round(next);
        }
        return next + unit;
    }

    function wireInput(input) {
        if (input.dataset.dynamoStepperWired === '1') return;
        if (input.parentNode && input.parentNode.classList && input.parentNode.classList.contains('dynamo-stepper')) return;
        input.dataset.dynamoStepperWired = '1';

        var step = parseFloat(input.getAttribute('data-dynamo-step'));
        if (!isFinite(step) || step <= 0) step = 1;

        var settingId = input.getAttribute('data-customize-setting-link');

        var wrapper = document.createElement('div');
        wrapper.className = 'dynamo-stepper';
        input.parentNode.insertBefore(wrapper, input);

        var minus = document.createElement('button');
        minus.type = 'button';
        minus.className = 'dynamo-stepper-btn dynamo-stepper-btn--minus';
        minus.setAttribute('aria-label', 'Decrease');
        minus.textContent = '−';

        var plus = document.createElement('button');
        plus.type = 'button';
        plus.className = 'dynamo-stepper-btn dynamo-stepper-btn--plus';
        plus.setAttribute('aria-label', 'Increase');
        plus.textContent = '+';

        wrapper.appendChild(minus);
        wrapper.appendChild(input);
        wrapper.appendChild(plus);

        function apply(direction) {
            var next = bump(input.value, step, direction);
            input.value = next;
            if (settingId && wp.customize(settingId)) {
                wp.customize(settingId).set(next);
            } else {
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        minus.addEventListener('click', function () { apply(-1); });
        plus.addEventListener('click', function () { apply(1); });
    }

    function scan() {
        document.querySelectorAll('input[data-dynamo-step]').forEach(wireInput);
    }

    wp.customize.bind('ready', function () {
        injectStyles();
        scan();
        wp.customize.control.bind('add', function () {
            setTimeout(scan, 0);
        });
    });
})();
