/* =========================================================
   FAQ ACCORDION
   ========================================================= */

const faqItems = document.querySelectorAll(".faq-item");

faqItems.forEach(item => {

    const question = item.querySelector(".faq-question");

    if (!question) return;

    question.addEventListener("click", () => {

        faqItems.forEach(other => {

            if (other !== item) {
                other.classList.remove("active");
            }

        });

        item.classList.toggle("active");

    });

});


/* =========================================================
   TECHNOLOGICAL CHALLENGE ACCORDION
   ========================================================= */

const items = document.querySelectorAll(".accordion-item");

items.forEach(item => {

    const accordionHeader = item.querySelector(".accordion-header");

    if (!accordionHeader) return;

    accordionHeader.addEventListener("click", () => {

        items.forEach(other => {

            if (other !== item) {
                other.classList.remove("active");
            }

        });

        item.classList.toggle("active");

    });

});


/* =========================================================
   HEADER SCROLL EFFECT
   ========================================================= */

const header = document.querySelector("header");

if (header) {

    window.addEventListener("scroll", () => {

        if (window.scrollY > 80) {
            header.classList.add("scrolled");
        } else {
            header.classList.remove("scrolled");
        }

    });

}


/* =========================================================
   MOBILE NAVIGATION
   ========================================================= */

const mainNav = document.querySelector(".main-nav");
const menuToggle = document.querySelector(".menu-toggle");

if (mainNav && menuToggle) {

    /* Open / close menu */

    menuToggle.addEventListener("click", () => {

        const isOpen = mainNav.classList.toggle("menu-open");

        menuToggle.setAttribute(
            "aria-expanded",
            isOpen ? "true" : "false"
        );

        menuToggle.setAttribute(
            "aria-label",
            isOpen ? "Close menu" : "Open menu"
        );

    });


    /* Close menu after clicking a link */

    const menuLinks = mainNav.querySelectorAll("a");

    menuLinks.forEach(link => {

        link.addEventListener("click", () => {

            mainNav.classList.remove("menu-open");

            menuToggle.setAttribute(
                "aria-expanded",
                "false"
            );

            menuToggle.setAttribute(
                "aria-label",
                "Open menu"
            );

        });

    });


    /* Close menu when clicking outside */

    document.addEventListener("click", event => {

        if (!mainNav.contains(event.target)) {

            mainNav.classList.remove("menu-open");

            menuToggle.setAttribute(
                "aria-expanded",
                "false"
            );

            menuToggle.setAttribute(
                "aria-label",
                "Open menu"
            );

        }

    });


    /* Reset menu when returning to desktop */

    window.addEventListener("resize", () => {

        if (window.innerWidth > 900) {

            mainNav.classList.remove("menu-open");

            menuToggle.setAttribute(
                "aria-expanded",
                "false"
            );

            menuToggle.setAttribute(
                "aria-label",
                "Open menu"
            );

        }

    });

}