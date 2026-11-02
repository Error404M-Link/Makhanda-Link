document.addEventListener("DOMContentLoaded", () => {
    // Sequentially pulse active status step icons
    const activeSteps = document.querySelectorAll(".step.active .step-icon");
    activeSteps.forEach((icon, index) => {
        setTimeout(() => {
            icon.style.transform = "scale(1.2)";
            setTimeout(() => {
                icon.style.transform = "scale(1)";
            }, 250);
        }, index * 400);
    });

    // Toggle hidden comments button
    const toggleBtn = document.getElementById("toggleCommentsBtn");
    if (toggleBtn) {
        const hiddenComments = document.querySelectorAll(".timeline-item.hidden-comment");
        const totalCount = document.querySelectorAll(".timeline-item").length;

        toggleBtn.addEventListener("click", () => {
            const isExpanded = toggleBtn.getAttribute("data-expanded") === "true";

            if (!isExpanded) {
                // Expand: reveal hidden comments
                hiddenComments.forEach((item, index) => {
                    item.style.display = "block";
                    item.style.animation = `fadeInUp 0.4s ease forwards ${index * 0.1}s`;
                });
                toggleBtn.textContent = "Show Less Comments";
                toggleBtn.setAttribute("data-expanded", "true");
            } else {
                // Collapse: re-hide comments beyond the first 3
                hiddenComments.forEach((item) => {
                    item.style.display = "none";
                });
                toggleBtn.textContent = `Show All Comments (${totalCount})`;
                toggleBtn.setAttribute("data-expanded", "false");
            }
        });
    }

    // Search/filter the ticket list panel
    const ticketSearchInput = document.getElementById("ticketSearchInput");
    const ticketListContainer = document.getElementById("ticketListContainer");
    const noSearchResults = document.getElementById("noSearchResults");

    if (ticketSearchInput && ticketListContainer) {
        const ticketItems = ticketListContainer.querySelectorAll(".ticket-list-item");

        ticketSearchInput.addEventListener("input", () => {
            const query = ticketSearchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            ticketItems.forEach((item) => {
                const matches = item.dataset.search.includes(query);
                item.style.display = matches ? "" : "none";
                if (matches) {
                    visibleCount++;
                }
            });

            if (noSearchResults) {
                noSearchResults.style.display =
                    visibleCount === 0 && query.length > 0 ? "block" : "none";
            }
        });
    }
});