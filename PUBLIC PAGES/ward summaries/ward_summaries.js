document.addEventListener("DOMContentLoaded", function () {

    /* ==========================================
       WARD SELECTOR
    ========================================== */

    const wardSelect =
        document.getElementById("wardSelect");


    if (wardSelect) {

        wardSelect.addEventListener("change", function () {

            const selectedWard = this.value;

            window.location.href =
                "ward_summaries.php?ward=" +
                encodeURIComponent(selectedWard);

        });

    }



    /* ==========================================
       SEARCH
    ========================================== */

    const searchInput =
        document.getElementById("summarySearch");

    const cards =
        document.querySelectorAll(".summary-card");

    const noResults =
        document.getElementById("noResults");


    if (searchInput) {

        searchInput.addEventListener("input", function () {

            const search =
                this.value.toLowerCase().trim();

            let visibleCards = 0;


            cards.forEach(function (card) {

                const title =
                    card.dataset.title.toLowerCase();

                const message =
                    card.dataset.message.toLowerCase();


                if (
                    title.includes(search) ||
                    message.includes(search)
                ) {

                    card.style.display = "flex";

                    visibleCards++;

                } else {

                    card.style.display = "none";

                }

            });


            if (visibleCards === 0 && search !== "") {

                noResults.style.display = "block";

            } else {

                noResults.style.display = "none";

            }

        });

    }

});



/* ==========================================
   OPEN SUMMARY
========================================== */

function openSummary(button) {

    const modal =
        document.getElementById("summaryModal");

    const title =
        document.getElementById("modalTitle");

    const message =
        document.getElementById("modalMessage");

    const date =
        document.getElementById("modalDate");


    title.textContent =
        button.dataset.title;


    message.textContent =
        button.dataset.message;


    const rawDate =
        button.dataset.date;


    if (rawDate) {

        const formattedDate =
            new Date(rawDate).toLocaleDateString(
                "en-ZA",
                {
                    day: "2-digit",
                    month: "long",
                    year: "numeric"
                }
            );

        date.textContent =
            formattedDate;

    }


    modal.classList.add("show");

    document.body.style.overflow = "hidden";

}



/* ==========================================
   CLOSE SUMMARY
========================================== */

function closeSummary() {

    const modal =
        document.getElementById("summaryModal");


    modal.classList.remove("show");

    document.body.style.overflow = "";

}



/* ==========================================
   CLOSE WHEN CLICKING OUTSIDE
========================================== */

const summaryModal =
    document.getElementById("summaryModal");


if (summaryModal) {

    summaryModal.addEventListener("click", function (event) {

        if (event.target === summaryModal) {

            closeSummary();

        }

    });

}



/* ==========================================
   ESCAPE KEY
========================================== */

document.addEventListener("keydown", function (event) {

    if (event.key === "Escape") {

        closeSummary();

    }

});