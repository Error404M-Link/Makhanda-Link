/* INCIDENTS BY WARD*/
new Chart(
    document.getElementById("wardChart"),
    {type: "bar",
        data: {
            labels: wardLabels,
            datasets: [
                {label: "Number of Incidents",
                    data: wardData
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    }
);

/*FAULTS BY SERVICE*/
new Chart(
    document.getElementById("serviceChart"),
    {
        type: "bar",
        data: {
            labels: serviceLabels,
            datasets: [
                {label: "Number of Incidents",
                    data: serviceData
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    }
);

/* INCIDENT STATUS*/
new Chart(
    document.getElementById("statusChart"),
    {
        type: "doughnut",
        data: {
            labels: statusLabels,
            datasets: [
                {
                    data: statusData
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    }
);
/*PRIORITY DISTRIBUTION*/
new Chart(
    document.getElementById("priorityChart"),
    {
        type: "pie",
        data: {
            labels: priorityLabels,
            datasets: [
                {
                    data: priorityData
                }
            ]

        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    }
);
/*INCIDENTS OVER TIME*/
new Chart(
    document.getElementById("dateChart"),
    {
        type: "line",
        data: {
            labels: dateLabels,
            datasets: [
                {
                    label: "Incidents Reported",
                    data: dateData,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    }
);