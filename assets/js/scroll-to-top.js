(function () {
    var btn = document.querySelector('.dynamo-scroll-top');
    if (!btn) return;

    var threshold = 300;

    function update() {
        var visible = (window.scrollY || window.pageYOffset) > threshold;
        btn.classList.toggle('is-visible', visible);
        if (visible) {
            btn.removeAttribute('aria-hidden');
            btn.removeAttribute('tabindex');
        } else {
            btn.setAttribute('aria-hidden', 'true');
            btn.setAttribute('tabindex', '-1');
        }
    }

    btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    window.addEventListener('scroll', update, { passive: true });
    update();
})();
