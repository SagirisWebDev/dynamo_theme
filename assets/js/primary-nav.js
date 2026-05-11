(function () {
    const container = document.querySelector('.menu-primary-container');
    if (!container) return;
    const btn = container.querySelector('.dynamo-menu-toggle');
    if (!btn) return;

    const BREAKPOINT = 921;

    function setOpen(open) {
        container.classList.toggle('is-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        setOpen(!container.classList.contains('is-open'));
    });

    document.addEventListener('click', function (e) {
        if (container.classList.contains('is-open') && !container.contains(e.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && container.classList.contains('is-open')) {
            setOpen(false);
            btn.focus();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > BREAKPOINT && container.classList.contains('is-open')) {
            setOpen(false);
        }
    }, { passive: true });
})();
