document.addEventListener("DOMContentLoaded", function () {

    const data = window.dashboardData || {};


    /* =====================================================
       MOBILE MENU
    ===================================================== */

    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.querySelector(".sidebar");

    if (menuToggle && sidebar) {

        menuToggle.addEventListener("click", function () {

            sidebar.classList.toggle("open");

        });

    }


    /* =====================================================
       INCIDENT SEARCH
    ===================================================== */

    const searchInput =
        document.getElementById("incidentSearch");

    const incidentRows =
        document.querySelectorAll(".incident-row");

    if (searchInput) {

        searchInput.addEventListener("input", function () {

            const searchValue =
                this.value.toLowerCase().trim();

            incidentRows.forEach(function (row) {

                const rowText =
                    row.textContent.toLowerCase();

                row.style.display =
                    rowText.includes(searchValue)
                        ? ""
                        : "none";

            });

        });

    }


    /* =====================================================
       INCIDENTS BY WARD
    ===================================================== */

    const wardCanvas =
        document.getElementById("wardChart");

    if (
        wardCanvas &&
        typeof Chart !== "undefined"
    ) {

        new Chart(wardCanvas, {

            type: "bar",

            data: {

                labels: data.wardLabels || [],

                datasets: [{

                    label: "Incident Tickets",

                    data: data.wardData || [],

                    borderWidth: 1

                }]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                scales: {

                    y: {

                        beginAtZero: true,

                        ticks: {
                            precision: 0
                        }

                    }

                },

                plugins: {

                    legend: {
                        display: false
                    }

                }

            }

        });

    }


    /* =====================================================
       FAULTS BY MUNICIPAL SERVICE
    ===================================================== */

    const serviceCanvas =
        document.getElementById("serviceChart");

    if (
        serviceCanvas &&
        typeof Chart !== "undefined"
    ) {

        new Chart(serviceCanvas, {

            type: "bar",

            data: {

                labels: data.serviceLabels || [],

                datasets: [{

                    label: "Incident Tickets",

                    data: data.serviceData || [],

                    borderWidth: 1

                }]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                scales: {

                    y: {

                        beginAtZero: true,

                        ticks: {
                            precision: 0
                        }

                    }

                },

                plugins: {

                    legend: {
                        display: false
                    }

                }

            }

        });

    }


    /* =====================================================
       PRIORITY DISTRIBUTION
    ===================================================== */

    const priorityCanvas =
        document.getElementById("priorityChart");

    if (
        priorityCanvas &&
        typeof Chart !== "undefined"
    ) {

        new Chart(priorityCanvas, {

            type: "doughnut",

            data: {

                labels: data.priorityLabels || [],

                datasets: [{

                    label: "Incident Tickets",

                    data: data.priorityData || [],

                    borderWidth: 1

                }]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {

                        position: "bottom"

                    }

                }

            }

        });

    }


});