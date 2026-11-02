document.addEventListener("DOMContentLoaded", function () {

    const data = window.dashboardData || {};
    const colors = {
        primary: "rgba(37, 99, 235, 0.85)",     // Primary Blue
        primaryBorder: "rgba(37, 99, 235, 1)",
        secondary: "rgba(79, 70, 229, 0.85)",   // Indigo
        secondaryBorder: "rgba(79, 70, 229, 1)",
        lineFill: "rgba(37, 99, 235, 0.15)",
        // Priority Doughnut Colors: Low (Green), Medium (Amber), High (Red)
        priority: [
            "rgba(34, 197, 94, 0.85)",  
            "rgba(245, 158, 11, 0.85)",  
            "rgba(239, 68, 68, 0.85)"   
        ],
        priorityBorders: [
            "rgba(34, 197, 94, 1)",
            "rgba(245, 158, 11, 1)",
            "rgba(239, 68, 68, 1)"
        ]
    };
    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.getElementById("sidebar");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    function toggleSidebar() {
        if (sidebar && sidebarOverlay) {
            sidebar.classList.toggle("open");
            sidebarOverlay.classList.toggle("active");
        }
    }
    if (menuToggle) {
        menuToggle.addEventListener("click", toggleSidebar);
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener("click", toggleSidebar);
    }

    const searchInput = document.getElementById("incidentSearch");
    const incidentRows = document.querySelectorAll(".incident-row");
    if (searchInput) {
        searchInput.addEventListener("input", function () {
            const searchValue = this.value.toLowerCase().trim();
            incidentRows.forEach(function (row) {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(searchValue) ? "" : "none";
            });
        });
    }
    const wardCanvas = document.getElementById("wardChart");
    if (wardCanvas && typeof Chart !== "undefined") {
        new Chart(wardCanvas, {
            type: "bar",
            data: {
                labels: data.wardLabels || [],
                datasets: [{
                    label: "Incident Tickets",
                    data: data.wardData || [],
                    backgroundColor: colors.primary,
                    borderColor: colors.primaryBorder,
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        text: "Number of Fault Incidents",
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
    const dateCanvas = document.getElementById("dateChart");
    if (dateCanvas && typeof Chart !== "undefined") {
        new Chart(dateCanvas, {
            type: "line",
            data: {
                labels: data.dateLabels || [],
                datasets: [{
                    label: "Incidents Reported",
                    data: data.dateData || [],
                    backgroundColor: colors.lineFill,
                    borderColor: colors.primaryBorder,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: colors.primaryBorder,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: "index"
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: "Date"
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Number of Incidents"
                        },
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
    const serviceCanvas = document.getElementById("serviceChart");
    if (serviceCanvas && typeof Chart !== "undefined") {
        new Chart(serviceCanvas, {
            type: "bar",
            data: {
                labels: data.serviceLabels || [],
                datasets: [{
                    label: "Incident Tickets",
                    data: data.serviceData || [],
                    backgroundColor: colors.secondary,
                    borderColor: colors.secondaryBorder,
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        text: "Number of Fault Incidents",
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
        4. PRIORITY DISTRIBUTION (Doughnut Chart)
    ===================================================== */
    const priorityCanvas = document.getElementById("priorityChart");

    if (priorityCanvas && typeof Chart !== "undefined") {
        new Chart(priorityCanvas, {
            type: "doughnut",
            data: {
                labels: data.priorityLabels || [],
                datasets: [{
                    label: "Incident Tickets",
                    data: data.priorityData || [],
                    backgroundColor: colors.priority,
                    borderColor: colors.priorityBorders,
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