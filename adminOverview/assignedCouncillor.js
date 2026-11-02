document.addEventListener("DOMContentLoaded", function () {

    const searchInput = document.getElementById("searchInput");
    const tableBody = document.getElementById("noticesTableBody");

    if (!searchInput || !tableBody) {
        console.log("Search input or table body not found.");
        return;
    }

    searchInput.addEventListener("keyup", function () {

        const searchValue = searchInput.value.toLowerCase().trim();

        const rows = tableBody.getElementsByTagName("tr");

        for (let i = 0; i < rows.length; i++) {

            const row = rows[i];

            // Ignore the "No councillors found" row
            if (row.cells.length < 5) {
                continue;
            }

            const username = row.cells[0].textContent.toLowerCase();
            const wardId = row.cells[1].textContent.toLowerCase();
            const dateAppointed = row.cells[2].textContent.toLowerCase();
            const contact = row.cells[3].textContent.toLowerCase();

            const rowText =
                username + " " +
                wardId + " " +
                dateAppointed + " " +
                contact;

            if (rowText.includes(searchValue)) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        }

    });

});