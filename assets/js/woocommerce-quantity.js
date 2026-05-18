(function () {
    'use strict';

    function adjacentInput(button) {
        var parent = button.closest('.quantity') || button.parentNode;
        if (!parent) {
            return null;
        }
        return parent.querySelector('input.qty, input[type="number"]');
    }

    function readNumeric(value, fallback) {
        var parsed = parseFloat(value);
        return isNaN(parsed) ? fallback : parsed;
    }

    function step(input) {
        return readNumeric(input.getAttribute('step'), 1) || 1;
    }

    function minValue(input) {
        return readNumeric(input.getAttribute('min'), 1);
    }

    function maxValue(input) {
        var max = input.getAttribute('max');
        if (max === null || max === '') {
            return Infinity;
        }
        return readNumeric(max, Infinity);
    }

    function update(input, delta) {
        var current = readNumeric(input.value, minValue(input));
        var next = current + delta;
        var min = minValue(input);
        var max = maxValue(input);
        if (next < min) { next = min; }
        if (next > max) { next = max; }
        if (next === current) {
            return;
        }
        input.value = String(next);
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    document.addEventListener('click', function (event) {
        var target = event.target.closest('.dynamo-quantity-minus, .dynamo-quantity-plus');
        if (!target) {
            return;
        }
        var input = adjacentInput(target);
        if (!input) {
            return;
        }
        event.preventDefault();
        var delta = target.classList.contains('dynamo-quantity-plus') ? step(input) : -step(input);
        update(input, delta);
    });
}());
