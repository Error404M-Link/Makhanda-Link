// =========================================================
// M-LINK ADMIN DASHBOARD JAVASCRIPT
// =========================================================

document.addEventListener("DOMContentLoaded", function () {


    // =====================================================
    // SIDEBAR / HAMBURGER
    // =====================================================

    const sidebarToggle = document.getElementById("sidebarToggle");
    const hamburger = document.querySelector(".hamburger");
    const sidebar = document.querySelector(".sidebar");

    if (sidebarToggle && hamburger && sidebar) {

        // Toggle sidebar
        hamburger.addEventListener("click", function () {

            sidebarToggle.checked = !sidebarToggle.checked;

        });


        // Close sidebar when ESC is pressed
        document.addEventListener("keydown", function (event) {

            if (event.key === "Escape") {

                sidebarToggle.checked = false;

            }

        });


        // Close sidebar when clicking outside it
        document.addEventListener("click", function (event) {

            if (
                sidebarToggle.checked &&
                !sidebar.contains(event.target) &&
                !hamburger.contains(event.target)
            ) {

                sidebarToggle.checked = false;

            }

        });

    }


    // =====================================================
    // SIDEBAR ACTIVE MENU
    // =====================================================

    const menuItems = document.querySelectorAll(".menu li");

    menuItems.forEach(function (item) {

        item.addEventListener("click", function () {

            menuItems.forEach(function (menu) {

                menu.classList.remove("active");

            });

            item.classList.add("active");

        });

    });


    // =====================================================
    // NOTIFICATIONS
    // =====================================================

    const notificationIcon =
        document.querySelector(".top-icon:nth-child(1)");

    if (notificationIcon) {

        notificationIcon.addEventListener("click", function (event) {

            event.preventDefault();

            alert("You have 3 new notifications.");

        });

    }


    // =====================================================
    // MESSAGES
    // =====================================================

    const messageIcon =
        document.querySelector(".top-icon:nth-child(2)");

    if (messageIcon) {

        messageIcon.addEventListener("click", function (event) {

            event.preventDefault();

            alert("You have 2 unread messages.");

        });

    }


    // =====================================================
    // LOGOUT CONFIRMATION
    // =====================================================

    const logoutButton =
        document.querySelector(".logout-link");

    if (logoutButton) {

        logoutButton.addEventListener("click", function (event) {

            const confirmLogout =
                confirm("Are you sure you want to logout?");

            if (!confirmLogout) {

                event.preventDefault();

            }

        });

    }


    // =====================================================
    // CLOSE SIDEBAR AFTER CLICKING MENU ITEM
    // ON SMALLER SCREENS
    // =====================================================

    menuItems.forEach(function (item) {

        const link = item.querySelector("a");

        if (!link) {
            return;
        }

        link.addEventListener("click", function () {

            if (
                window.innerWidth <= 900 &&
                sidebarToggle
            ) {

                sidebarToggle.checked = false;

            }

        });

    });


});