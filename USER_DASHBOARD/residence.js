document.addEventListener(
    "DOMContentLoaded",
    function () {

        const menuBtn =
            document.getElementById("menuBtn");

        const sidebar =
            document.getElementById("sidebar");

        const mainWrapper =
            document.getElementById("mainWrapper");

        const notificationBtn =
            document.getElementById("notificationBtn");

        const messageBtn =
            document.getElementById("messageBtn");


        //sidebar menu
        if (
            menuBtn &&
            sidebar &&
            mainWrapper
        ) {

            menuBtn.addEventListener(
                "click",
                function () {

                    const mobile =
                        window.innerWidth <= 750;


                    //mobile sidebar
                    if (mobile) {

                        sidebar.classList.toggle(
                            "mobile-open"
                        );

                        return;
                    }


                    //desktop sidebar
                    sidebar.classList.toggle(
                        "collapsed"
                    );


                    mainWrapper.classList.toggle(
                        "sidebar-collapsed"
                    );


                    //keep hamburger icon the same
                    const icon =
                        menuBtn.querySelector(
                            ".material-symbols-outlined"
                        );


                    if (icon) {

                        icon.textContent =
                            "menu";

                    }

                }
            );

        }


        //notifications
        if (notificationBtn) {

            notificationBtn.addEventListener(
                "click",
                function () {

                    console.log(
                        "Notifications clicked"
                    );

                }
            );

        }


        //messages
        if (messageBtn) {

            messageBtn.addEventListener(
                "click",
                function () {

                    console.log(
                        "Messages clicked"
                    );

                }
            );

        }


        //close mobile sidebar when screen changes
        window.addEventListener(
            "resize",
            function () {

                if (
                    window.innerWidth > 750 &&
                    sidebar
                ) {

                    sidebar.classList.remove(
                        "mobile-open"
                    );

                }

            }
        );

    }
);