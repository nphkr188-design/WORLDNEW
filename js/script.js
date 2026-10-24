window.addEventListener("pageshow", function () {
    document.body.classList.remove("page-transition-leaving");
});

document.addEventListener("DOMContentLoaded", function () {

    const reducedPageMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

    if (!reducedPageMotion.matches) {
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
            ) {
                return;
            }

            const destination = new URL(link.href, window.location.href);

            if (
                destination.origin !== window.location.origin
                || destination.href === window.location.href
                || destination.hash && destination.pathname === window.location.pathname
            ) {
                return;
            }
            event.preventDefault();
            event.preventDefault();
            document.body.classList.add("page-transition-leaving");

            window.setTimeout(function () {
                window.location.assign(destination.href);
            }, 180);
        });
    }

    const imageInput =
        document.querySelector('input[type="file"][name="image"]');

    const preview =
        document.querySelector("#imagePreview");


    if (imageInput && preview) {

        imageInput.addEventListener(
            "change",
            function () {

                const file = this.files[0];

                if (!file) {
                    preview.style.display = "none";
                    return;
                }


                if (!file.type.startsWith("image/")) {

                    alert("กรุณาเลือกไฟล์รูปภาพ");

                    this.value = "";

                    preview.style.display = "none";

                    return;
                }


                const reader =
                    new FileReader();


                reader.onload =
                    function (event) {

                        preview.src =
                            event.target.result;

                        preview.style.display =
                            "block";
                    };


                reader.readAsDataURL(file);

            }
        );

    }

    const sidebar = document.querySelector("#newsSidebar");

    if (sidebar) {
        const sidebarToggle = sidebar.querySelector(".news-sidebar-toggle");
        const sidebarBackdrop = document.querySelector(".news-sidebar-backdrop");
        const headlineToggle = sidebar.querySelector(".news-sidebar-headline-heading");
        const headlinePanel = sidebar.querySelector("#newsSidebarHeadlines");
        const mobileSidebar = window.matchMedia("(max-width: 760px)");

        function setSidebarCollapsed(collapsed) {
            document.body.classList.toggle("news-sidebar-collapsed", collapsed);
            sidebarToggle.setAttribute("aria-expanded", String(!collapsed));
            sidebarToggle.setAttribute(
                "aria-label",
                collapsed ? "เปิดเมนูข่าว" : "ย่อเมนูข่าว"
            );

            if (collapsed) {
                headlineToggle.setAttribute("aria-expanded", "false");
                headlinePanel.hidden = true;
            }
        }

        setSidebarCollapsed(
            mobileSidebar.matches
            || document.body.classList.contains("news-sidebar-collapsed")
        );

        sidebarToggle.addEventListener("click", function () {
            setSidebarCollapsed(
                !document.body.classList.contains("news-sidebar-collapsed")
            );
        });

        headlineToggle.addEventListener("click", function () {
            const expanded = headlineToggle.getAttribute("aria-expanded") !== "true";
            headlineToggle.setAttribute("aria-expanded", String(expanded));
            headlinePanel.hidden = !expanded;
        });

        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener("click", function () {
                setSidebarCollapsed(true);
            });
        }

        document.addEventListener("keydown", function (event) {
            if (
                event.key === "Escape"
                && mobileSidebar.matches
                && !document.body.classList.contains("news-sidebar-collapsed")
            ) {
                setSidebarCollapsed(true);
                sidebarToggle.focus();
            }
        });

        mobileSidebar.addEventListener("change", function (event) {
            setSidebarCollapsed(event.matches);
        });
    }

    const slider = document.querySelector("[data-hero-slider]");

    if (!slider) {
        return;
    }

    const slides = Array.from(slider.querySelectorAll(".hero-slide"));
    const dots = Array.from(slider.querySelectorAll("[data-hero-dot]"));
    const previousButton = slider.querySelector("[data-hero-previous]");
    const nextButton = slider.querySelector("[data-hero-next]");

    if (slides.length < 2) {
        return;
    }

    let activeIndex = 0;
    let rotationTimer;
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

    function showSlide(index) {
        activeIndex = (index + slides.length) % slides.length;

        slides.forEach(function (slide, slideIndex) {
            const isActive = slideIndex === activeIndex;
            slide.classList.toggle("is-active", isActive);
            slide.setAttribute("aria-hidden", String(!isActive));
        });

        dots.forEach(function (dot, dotIndex) {
            const isActive = dotIndex === activeIndex;
            dot.classList.toggle("is-active", isActive);
            dot.setAttribute("aria-current", String(isActive));
        });
    }

    function stopRotation() {
        window.clearInterval(rotationTimer);
    }

    function startRotation() {
        stopRotation();

        if (reducedMotion.matches || document.hidden) {
            return;
        }

        rotationTimer = window.setInterval(function () {
            showSlide(activeIndex + 1);
        }, 6000);
    }

    if (previousButton) {
        previousButton.addEventListener("click", function () {
            showSlide(activeIndex - 1);
            startRotation();
        });
    }

    if (nextButton) {
        nextButton.addEventListener("click", function () {
            showSlide(activeIndex + 1);
            startRotation();
        });
    }

    dots.forEach(function (dot) {
        dot.addEventListener("click", function () {
            showSlide(Number(dot.dataset.heroDot));
            startRotation();
        });
    });

    slider.addEventListener("mouseenter", stopRotation);
    slider.addEventListener("mouseleave", startRotation);
    slider.addEventListener("focusin", stopRotation);
    slider.addEventListener("focusout", function (event) {
        if (!slider.contains(event.relatedTarget)) {
            startRotation();
        }
    });

    document.addEventListener("visibilitychange", function () {
        if (document.hidden) {
            stopRotation();
        } else {
            startRotation();
        }
    });

    if (typeof reducedMotion.addEventListener === "function") {
        reducedMotion.addEventListener("change", startRotation);
    }

    startRotation();
});