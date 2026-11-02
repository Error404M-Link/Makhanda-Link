document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       SIDEBAR
    ===================================================== */

    const menuBtn = document.getElementById("menuBtn");
    const sidebar = document.getElementById("sidebar");

    if (menuBtn && sidebar) {

        // Sidebar is OPEN when the page loads
        sidebar.classList.add("open");

        // Hamburger opens/closes sidebar
        menuBtn.addEventListener("click", function () {
            sidebar.classList.toggle("open");
        });
    }


    /* =====================================================
       SEARCH SUMMARIES
    ===================================================== */

    const searchInput =
        document.getElementById("summarySearch");

    const summaryGrid =
        document.getElementById("summaryGrid");

    const noResults =
        document.getElementById("noResults");


    if (
        searchInput &&
        summaryGrid &&
        noResults
    ) {

        searchInput.addEventListener("input", function () {

            const searchTerm =
                this.value.toLowerCase().trim();

            const cards =
                summaryGrid.querySelectorAll(".summary-card");

            let visibleCards = 0;


            cards.forEach(function (card) {

                const title =
                    card.dataset.title
                        ? card.dataset.title.toLowerCase()
                        : "";

                const message =
                    card.dataset.message
                        ? card.dataset.message.toLowerCase()
                        : "";


                if (
                    title.includes(searchTerm) ||
                    message.includes(searchTerm)
                ) {

                    card.style.display = "";
                    visibleCards++;

                } else {

                    card.style.display = "none";

                }

            });


            if (
                cards.length > 0 &&
                visibleCards === 0
            ) {

                noResults.style.display = "block";

            } else {

                noResults.style.display = "none";

            }

        });

    }

});


menuBtn.addEventListener("click", function () {

    sidebar.classList.toggle("open");

    if (sidebar.classList.contains("open")) {
        document.body.classList.add("sidebar-open");
    } else {
        document.body.classList.remove("sidebar-open");
    }

});