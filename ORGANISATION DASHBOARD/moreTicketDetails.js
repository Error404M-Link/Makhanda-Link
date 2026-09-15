let map = null;
let marker = null;

const modal = document.getElementById('ticketModal');
const closeModalBtn = document.getElementById('closeModalBtn');
const modalMapContainer = document.getElementById('modalMap');

// Attach click listeners to each ticket row
document.querySelectorAll('.clickable-ticket').forEach(ticket => {
    ticket.addEventListener('click', function() {
        const data = this.dataset;

        // Populate Modal Fields
        document.getElementById('modalTicketId').textContent = '#ID-' + data.id;
        document.getElementById('modalServiceType').textContent = data.service;
        document.getElementById('modalDescription').textContent = data.description;
        document.getElementById('modalAddress').textContent = data.street + ' (Ward: ' + data.ward + ')';
        document.getElementById('modalLocationDesc').textContent = data.locationDesc;
        document.getElementById('modalDate').textContent = data.date;

        // Populate Badge
        const badge = document.getElementById('modalStatusBadge');
        badge.className = 'status ' + data.statusClass;
        badge.textContent = data.statusLabel;

        const lat = parseFloat(data.lat);
        const lng = parseFloat(data.lng);

        // Check if non-empty valid coordinates exist
        if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
            modalMapContainer.style.display = 'block';

            // Initialize Map once or update view
            if (!map) {
                map = L.map('modalMap');
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
            }

            // Center map and drop marker at the ticket coordinates
            map.setView([lat, lng], 16);

            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng]).addTo(map);
            }

            // Force map renderer calculation after modal visible transition
            setTimeout(() => { map.invalidateSize(); }, 200);
        } else {
            // If coordinates are missing, hide top map element
            modalMapContainer.style.display = 'none';
        }

        // Display Modal Overlay
        modal.style.display = 'flex';
    });
});

// Close Modal on Close Button Click
closeModalBtn.addEventListener('click', () => {
    modal.style.display = 'none';
});

// Close Modal when clicking outside the card
window.addEventListener('click', (event) => {
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});