/* =========================================================
   WARD COUNCILLOR SIDEBAR TOGGLE
   Load this on every ward councillor page:
   <script src="ward_sidebar.js"></script>

   800px and wider:
     the sidebar stays open on every page, or shrinks to an icon
     strip when <html> has the class "sidebar-collapsed".
     The choice is remembered in localStorage, so it stays that
     way until the hamburger button is pressed again.

   Under 800px (phones):
     the sidebar slides in over the page when <body> has the
     class "sidebar-open" and starts closed on each page.
========================================================= */

(() => {
    const menuBtn  = document.getElementById("menuBtn");
    const backdrop = document.getElementById("sidebarBackdrop");
    if (!menuBtn) return;

    const icon    = menuBtn.querySelector("i");
    const root    = document.documentElement;
    const desktop = window.matchMedia("(min-width: 800px)");
    const KEY     = "sidebarState";

    const isOpen = () =>
        desktop.matches
            ? !root.classList.contains("sidebar-collapsed")
            : document.body.classList.contains("sidebar-open");

    function render() {
        const open = isOpen();
        menuBtn.setAttribute("aria-expanded", String(open));

        // Show a close (X) icon only when the sidebar covers the page on small screens
        if (icon) {
            const showClose = !desktop.matches && open;
            icon.classList.toggle("fa-xmark", showClose);
            icon.classList.toggle("fa-bars", !showClose);
        }
    }

    function setOpen(open) {
        if (desktop.matches) {
            root.classList.toggle("sidebar-collapsed", !open);
            try { localStorage.setItem(KEY, open ? "open" : "closed"); } catch (e) {}
        } else {
            document.body.classList.toggle("sidebar-open", open);
        }
        render();
    }

    menuBtn.addEventListener("click", () => setOpen(!isOpen()));

    if (backdrop) {
        backdrop.addEventListener("click", () => setOpen(false));
    }

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !desktop.matches) setOpen(false);
    });

    desktop.addEventListener("change", () => {
        document.body.classList.remove("sidebar-open");
        render();
    });

    render();
})();