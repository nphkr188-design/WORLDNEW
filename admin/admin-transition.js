window.addEventListener("pageshow", function () {
    document.body.classList.remove("admin-page-transition-leaving");
});

document.addEventListener("DOMContentLoaded", function () {
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

    if (reducedMotion.matches) {
        return;
    }

    document.addEventListener("click", function (event) {
        const link = event.target.closest("a[href]");

        if (
            event.defaultPrevented
            || event.button !== 0
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey
            || !link
            || link.target
            || link.hasAttribute("download")
            || link.hasAttribute("data-no-page-transition")
        ) {
            return;
        }

        const destination = new URL(link.href, window.location.href);

        if (
            !["http:", "https:"].includes(destination.protocol)
            || destination.origin !== window.location.origin
            || destination.href === window.location.href
            || (
                destination.pathname === window.location.pathname
                && destination.search === window.location.search
                && destination.hash
            )
        ) {
            return;
        }

        event.preventDefault();
        document.body.classList.add("admin-page-transition-leaving");

        window.setTimeout(function () {
            window.location.assign(destination.href);
        }, 180);
    });
});
