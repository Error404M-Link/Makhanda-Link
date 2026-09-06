// Initialize map focused on Grahamstown/Makhanda coordinates
const map = L.map('map').setView([-33.3106, 26.5256], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

let currentMarker = null;

// Capture the explicit coordinates from the map globally as well
map.on('click', function(e) {
    const lat = e.latlng.lat;
    const lng = e.latlng.lng;

    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;

    if (currentMarker) {
        currentMarker.setLatLng(e.latlng);
    } else {
        currentMarker = L.marker(e.latlng).addTo(map);
    }
});

// ##### Disable Map & UI Controls #####
document.addEventListener("DOMContentLoaded", () => {
    const useMapBtn = document.getElementById('useMapBtn'); // <-- Grabbed Use Map button
    const streetSelect = document.getElementById('streetName');
    const mapWrapper = document.getElementById('mapWrapper');
    const latInput = document.getElementById("latitude");
    const lngInput = document.getElementById("longitude");
    const clearMapBtn = document.getElementById("clearMapBtn");
    const clearStreetBtn = document.getElementById("clearStreetButton");

    useMapBtn.addEventListener("click", () => {
        // Check if the map is currently hidden
        if (mapWrapper.style.display === "none" || mapWrapper.style.display === "") {
            mapWrapper.style.display = "block"; // Show the map wrapper
            useMapBtn.textContent = "Hide Map"; // Optional: change button text dynamically
            
            // Critical Leaflet fix: recalculate dimensions now that it's visible
            setTimeout(() => {
                map.invalidateSize();
            }, 10);
        } else {
            mapWrapper.style.display = "none";  // Hide the map wrapper if clicked again
            useMapBtn.textContent = "Use Map";
        }
    });

    // Handle mutual exclusion states based on inputs
    function toggleMutualExclusion(){
        const hasStreet = streetSelect.value !== "";
        const hasCoordinates = latInput.value !== "" && lngInput.value !== "";

        if(hasStreet){
            // If street is selected, disable map interactions visually
            mapWrapper.classList.add("disabledMap");
            streetSelect.removeAttribute("disabled");
            streetSelect.required = true;

            clearStreetBtn.style.display = "inline-block";
            clearMapBtn.style.display = "none";
        } else if(hasCoordinates){
            // If map has coordinates, disable street dropdown
            streetSelect.setAttribute("disabled", "true");
            streetSelect.required = false;
            mapWrapper.classList.remove("disabledMap");
            clearMapBtn.style.display = "inline-block"; 

            clearStreetBtn.style.display = "none";
        } else {
            // If both empty, enable both systems
            streetSelect.removeAttribute("disabled");
            streetSelect.required = true;
            mapWrapper.classList.remove("disabledMap");
            clearMapBtn.style.display = "none";
            clearStreetBtn.style.display = "none";
        }
    }

    // Listen to the street Dropdown change
    streetSelect.addEventListener("change", toggleMutualExclusion);

    clearStreetBtn.addEventListener("click", () => {
        streetSelect.value = "";
        toggleMutualExclusion();
    });

    // Reset map button functionality
    clearMapBtn.addEventListener("click", () => {
        latInput.value = "";
        lngInput.value = "";

        if (currentMarker !== null) {
            map.removeLayer(currentMarker);
            currentMarker = null; 
        }

        toggleMutualExclusion();
    });

    map.on("click", function(e) {
        document.getElementById("latitude").value = e.latlng.lat;
        document.getElementById("longitude").value = e.latlng.lng;

        if(currentMarker !== null){
            map.removeLayer(currentMarker);
        }
        currentMarker = L.marker([e.latlng.lat, e.latlng.lng]).addTo(map);

        toggleMutualExclusion();
    });
});

// DISPLAY THE WARD IN THE READ ONLY SPACE

document.addEventListener('DOMContentLoaded', ()=>{
    const streetSelect = document.getElementById('streetName');
    const wardDisplay = document.getElementById('wardDisplay');
    const wardIdInput = document.getElementById('wardId');

    if(streetSelect && wardDisplay && wardIdInput){
        streetSelect.addEventListener('change', function(){
            const selectedOption = this.options[this.selectedIndex];

            const linkedWardId = selectedOption.getAttribute('data-ward-id');
            const linkedWardInfo = selectedOption.getAttribute('data-ward-info');

            if(linkedWardId && linkedWardInfo){
                wardDisplay.value = linkedWardInfo;
                wardIdInput.value = linkedWardId;
            }else{
                wardDisplay.value = '';
                wardIdInput.value = '';
            }
        });
    }
});