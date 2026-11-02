// #################### SMALL SHARED HELPERS ####################

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ###################### SEARCH AND FILTERING ###############################################
// Works on any page that has #faultResultsContainer + any combination of the filter
// inputs below. If the results container is missing (a page with no search UI) this
// whole block quietly does nothing.

document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById('faultSeachInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const wardFilter = document.getElementById('wardFilter');
    const resultsContainer = document.getElementById('faultResultsContainer');

    if (!resultsContainer) return;

    // allFaultTickets.php marks its container so results/map stay limited to open faults
    const unresolvedOnly = resultsContainer.dataset.unresolvedOnly === '1';
    // Only the main board sets this (30), keeping the map from getting cluttered.
    // allFaultTickets.php omits it so a filtered search still shows every match.
    const resultLimit = resultsContainer.dataset.limit || '';

    let debounceTimer = null;

    function triggerFilterSearch() {
        const queryParam = new URLSearchParams({
            search: searchInput ? searchInput.value : '',
            category: categoryFilter ? categoryFilter.value : '',
            status: statusFilter ? statusFilter.value : '',
            ward: wardFilter ? wardFilter.value : '',
            unresolvedOnly: unresolvedOnly ? '1' : '0',
            limit: resultLimit
        });

        fetch(`faultFilterAjax.php?${queryParam.toString()}`)
            .then(response => response.json())
            .then(data => {
                resultsContainer.innerHTML = data.html;
                attachViewDetailsHandlers(resultsContainer);
                colorizeFaultCards(resultsContainer);

                // Keep the map in sync with whatever the filter just matched
                if (typeof updateMapMarkers === 'function' && document.getElementById('grahamstownMap')) {
                    updateMapMarkers(data.tickets || []);
                }
            })
            .catch(error => console.error("Error executing active filter update: ", error));
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(triggerFilterSearch, 350);
        });
    }
    if (categoryFilter) categoryFilter.addEventListener('change', triggerFilterSearch);
    if (statusFilter) statusFilter.addEventListener('change', triggerFilterSearch);
    if (wardFilter) wardFilter.addEventListener('change', triggerFilterSearch);
});

// ###################### INTERACTIVE GRAHAMSTOWN/MAKHANDA MAP ###############################

let ghtMap = null;
let ghtMarkersLayer = null;

                                        // Ensure that the map runs after window data layer match up
function initGrahamstownMap(ticketData) {

                                        // define the bounds locking down view exclusively to Grahamstown/Makhanda
    const GHTBounds = L.latLngBounds(
        L.latLng(-33.3400, 26.4700),     // South West corner
        L.latLng(-33.2800, 26.5800)      // North East corner
    );

    // initialise map frame
    ghtMap = L.map('grahamstownMap', {
        center: [-33.3106, 26.5256],    // Centered directly on Grahamstown CBD
        zoom: 15,                       // Close street range scale
        minZoom: 10,                    // Outer zoom lock limit
        maxZoom: 18,                    // Inner zoom limit
        maxBounds: GHTBounds,           // Apply spatial box constraint
        maxBoundsViscosity: 1.0,        // Hard bounce containment behavior

                                        // LOCK INTERACTION INITIALLY UNTIL WHEN CLICKED
        dragging: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        boxZoom: false,
        touchZoom: false,
        keyboard: false
    });

                                        // Display OpenStreetMap graphics surface tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(ghtMap);

                                        // Layer group so filtered results can replace pins without rebuilding the map
    ghtMarkersLayer = L.layerGroup().addTo(ghtMap);

                                        // INTERACTIVITY ENGINE
    const mapContainer = document.getElementById('grahamstownMap');
    mapContainer.style.cursor = 'pointer';

    function activateMap() {
        ghtMap.dragging.enable();
        ghtMap.scrollWheelZoom.enable();
        ghtMap.doubleClickZoom.enable();
        ghtMap.boxZoom.enable();
        ghtMap.touchZoom.enable();
        ghtMap.keyboard.enable();
        mapContainer.style.cursor = 'default';
    }
                                        // Listen to the activation click
    mapContainer.addEventListener('click', function () {
        activateMap();
    }, { once: true });

    updateMapMarkers(ticketData);
}

                                        // Clears and re-plots pins - reused for both the initial load and every filter
function updateMapMarkers(ticketData) {
    if (!ghtMarkersLayer) return;

    ghtMarkersLayer.clearLayers();

    ticketData.forEach(ticket => {
        if (ticket.latitude && ticket.longitude) {

            const popupBody = `
                <div style="font-family: sans-serif; min-width: 160px;">
                    <h4 style="margin: 0 0 5px 0; color: #111;">Ticket #${escapeHtml(ticket.ticketId)}</h4>
                    <p style="margin: 0 0 4px 0; font-size: 13px;"><strong>Category:</strong> ${escapeHtml(ticket.category)}</p>
                    <p style="margin: 0 0 4px 0; font-size: 13px;"><strong>Street:</strong> ${escapeHtml(ticket.streetName)}</p>
                    <p style="margin: 0 0 10px 0; font-size: 13px;"><strong>Status:</strong> <span style="color:#fd7e14; font-weight:bold;">${escapeHtml(ticket.status)}</span></p>
                    <button type="button" class="viewDetailsBtn" data-id="${escapeHtml(ticket.ticketId)}" style="display: block; width: 100%; font-size: 12px; color: #007bff; background: none; border: none; text-decoration: none; font-weight: bold; cursor: pointer; padding: 0;">Track Lifecycle →</button>
                </div>
            `;

            const marker = L.marker([parseFloat(ticket.latitude), parseFloat(ticket.longitude)])
                .addTo(ghtMarkersLayer)
                .bindPopup(popupBody);

                                        // Wire up the popup's own "View Details" button once it's opened
            marker.on('popupopen', () => attachViewDetailsHandlers(marker.getPopup().getElement()));
        }
    });
}

// ################################ STATS ANIMATION ############################################
                                                    // making the number count every time they appear on screen
document.addEventListener("DOMContentLoaded", () => {
    const reels = document.querySelectorAll(".statReel");

    // setup the numbers inside the reels immediately on load
    reels.forEach((reel) => {
        const target = parseInt(reel.getAttribute('data-target'), 10);

        if (target == 0) {
            return;
        }

                                            // populate or start counting
        for (let i = 1; i <= target; i++) {
            const numSpan = document.createElement('span');
            numSpan.textContent = i;
            reel.appendChild(numSpan);
        }
    });

                            // Create the intersection observer engine
    const observerOptions = {
        root: null,         // use the browser viewport
        threshold: 0.2      // Trigger count when 20% of the item is visible on screen
    };

    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const reel = entry.target;
            const target = parseInt(reel.getAttribute('data-target'), 10);
            const height = reel.parentElement.clientHeight;

                            // when entering viewport
            if (entry.isIntersecting && target > 0) {
                reel.style.transform = `translateY(-${height * target}px)`;
            } else {
                            // When scrolling away
                reel.style.transition = "none";
                reel.style.transform = "translateY(0px)";

                reel.offsetHeight;
                reel.style.transition = "transform 1.2s cubic-bezier(0.1, 1, 0.1, 1)";
            }
        });
    }, observerOptions);

                            // attach every observer to each individual stats reel element frame
    reels.forEach(reel => statsObserver.observe(reel));

});

// ######################### Fault cards colour treatment #########################################

function colorizeFaultCards(container) {
    const scope = container || document;

    // colour palette from m-link colour palette
    const cardColors = [
        '#A38C7A', // Medium taupe / earth brown
        '#BC9E8B', // Lighter warm clay brown
        '#F7E9D4', // Creamy sand / light beige
        '#B5C29E'  // Soft sage green
    ];

    const faultCards = scope.querySelectorAll('.faultCard');

    faultCards.forEach(card => {
        const randomIndex = Math.floor(Math.random() * cardColors.length);
        const randomColor = cardColors[randomIndex];

        card.style.backgroundColor = randomColor;

        const title = card.querySelector('h3');
        const locationText = card.querySelector('.location');
        const viewBtn = card.querySelector('.viewBtn');

        if (randomColor === '#F7E9D4') {
            // Dark styling for the light cream card
            if (title) title.style.color = '#2B2520';
            if (locationText) locationText.style.color = '#544941';

            if (viewBtn) {
                viewBtn.style.color = '#2B2520';
                viewBtn.style.borderColor = 'rgba(43, 37, 32, 0.4)';
                viewBtn.style.backgroundColor = 'rgba(43, 37, 32, 0.05)';

                viewBtn.onmouseenter = () => viewBtn.style.backgroundColor = 'rgba(43, 37, 32, 0.15)';
                viewBtn.onmouseleave = () => viewBtn.style.backgroundColor = 'rgba(43, 37, 32, 0.05)';
            }
        } else {
            // Light styling for the medium/darker cards
            if (title) title.style.color = '#FFFFFF';
            if (locationText) locationText.style.color = '#F0F0F0';

            if (viewBtn) {
                viewBtn.style.color = '#FFFFFF';
                viewBtn.style.borderColor = 'rgba(255, 255, 255, 0.4)';
                viewBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.08)';

                viewBtn.onmouseenter = () => viewBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
                viewBtn.onmouseleave = () => viewBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.08)';
            }
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    colorizeFaultCards(document);
});

// ######################### VIEW DETAILS POPUP #########################################

function ensureTicketModal() {
    let modal = document.getElementById('ticketDetailModal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'ticketDetailModal';
    modal.className = 'ticketModalOverlay';
    modal.innerHTML = `
        <div class="ticketModalBox">
            <span class="closeTicketModal">&times;</span>
            <div class="ticketModalBody"><p>Loading ticket details...</p></div>
        </div>
    `;
    document.body.appendChild(modal);

    modal.querySelector('.closeTicketModal').addEventListener('click', closeTicketModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeTicketModal();
        }
    });

    return modal;
}

function closeTicketModal() {
    const modal = document.getElementById('ticketDetailModal');
    if (modal) {
        modal.classList.remove('show-ticket-modal');
        document.body.classList.remove('modal-open');
    }
}

function buildTicketModalContent(t) {
    const wardText = t.ward_name ? `${escapeHtml(t.ward_name)} (Ward ${escapeHtml(t.ward_number)})` : 'N/A';
    const streetText = t.street_name || t.location_description || 'N/A';
    const statusClass = String(t.ticket_status || 'unknown').toLowerCase().replace(/\s+/g, '-');

    let imagesHtml = '';
    if (t.images && t.images.length > 0) {
        imagesHtml = `<div class="ticketModalImages">` +
            t.images.map(src => `<img src="${escapeHtml(src)}" alt="Fault image">`).join('') +
            `</div>`;
    }

    return `
        <h3>Ticket #${escapeHtml(t.incident_id)} &mdash; ${escapeHtml(t.category || 'General Incident')}</h3>
        <span class="statusBadge ${statusClass}">${escapeHtml(t.ticket_status || 'Unknown')}</span>
        ${imagesHtml}
        <p class="ticketModalDescription">${escapeHtml(t.incident_description)}</p>
        <ul class="ticketModalMeta">
            <li><strong>Location:</strong> ${escapeHtml(streetText)}</li>
            <li><strong>Ward:</strong> ${wardText}</li>
            <li><strong>Priority:</strong> ${escapeHtml(t.priority || 'Normal')}</li>
            <li><strong>Reported:</strong> ${escapeHtml(t.date_reported || 'N/A')}</li>
            ${t.date_resolved ? `<li><strong>Resolved:</strong> ${escapeHtml(t.date_resolved)}</li>` : ''}
        </ul>
    `;
}

function openTicketModal(ticketId) {
    const modal = ensureTicketModal();
    const body = modal.querySelector('.ticketModalBody');
    body.innerHTML = '<p>Loading ticket details...</p>';
    modal.classList.add('show-ticket-modal');
    document.body.classList.add('modal-open');

    fetch(`viewTicket.php?id=${encodeURIComponent(ticketId)}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                body.innerHTML = `<p>${escapeHtml(data.error)}</p>`;
                return;
            }
            body.innerHTML = buildTicketModalContent(data);
        })
        .catch(() => {
            body.innerHTML = '<p>Something went wrong loading this ticket. Please try again.</p>';
        });
}

function attachViewDetailsHandlers(container) {
    const scope = container || document;
    if (!scope || !scope.querySelectorAll) return;

    scope.querySelectorAll('.viewDetailsBtn').forEach(btn => {
        // avoid double-binding the same button if this runs more than once on it
        if (btn.dataset.boundView === '1') return;
        btn.dataset.boundView = '1';

        btn.addEventListener('click', (event) => {
            event.preventDefault();
            openTicketModal(btn.dataset.id);
        });
    });
}

document.addEventListener("DOMContentLoaded", () => {
    attachViewDetailsHandlers(document);
});

// ######################### How it works section ################################

document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("howItWorksWrapper");
    const openBtn = document.getElementById("openhowItWorksBtn");
    const closeBtn = document.querySelector(".closeHowItWorks");

    if (openBtn && modal) {
        openBtn.addEventListener("click", function () {
            modal.classList.add("show-modal");
            document.body.classList.add("modal-open"); // lock page background scroll
        });
    }

    if (closeBtn && modal) {
        closeBtn.addEventListener("click", function () {
            modal.classList.remove("show-modal");
            document.body.classList.remove("modal-open"); // restore background scroll
        });
    }

    window.addEventListener("click", function (event) {
        if (event.target == modal) {
            modal.classList.remove("show-modal");
            document.body.classList.remove("modal-open"); // remove background scroll
        }
    });
});