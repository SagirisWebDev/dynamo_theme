(function () {
    var btn = document.querySelector('.dynamo-scroll-top');
    if (!btn) return;

    var threshold = 300;

    function update() {
        if ((window.scrollY || window.pageYOffset) > threshold) {
            btn.classList.add('is-visible');
        } else {
            btn.classList.remove('is-visible');
        }
    }

    btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    window.addEventListener('scroll', update, { passive: true });
    update();
})();
