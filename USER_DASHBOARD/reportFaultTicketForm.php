<?php include "reportFaultTicket.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report a Fault</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="reportFaultTicket.css"/>
    <link rel="stylesheet" href="dashboardSideBar.css"/>
    <style>
        
    </style>
</head>
<body>
    <!--###### Navigation Bar ##############-->

    <input type="checkbox" id="sidebarToggle">
    <label for="sidebarToggle" class="hamburger">
        <span></span><span></span><span></span>
    </label>

    <aside class="sidebar">
        <nav class="navigation">
            <img src="MakanaLogo.svg" height="150" width="150" alt="M-link logo">
            
            <ul>
                <li><a href="#home-section">Home</a></li>
                <li><a href="#appointments-section">Appointments</a></li>
                <li><a href="#patient-details-section">Patients</a></li>
                <li><a href="#schedule-section">Schedule</a></li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <button class="logout-btn"><a href="DoctorLoginPage.html">Logout</a></button>
        </div>
    </aside>

    <!-- ################################################# -->

    <section class="formContainer">

        <div class="faultsHeader reportFaultHeader">
            <strong>Report A Fault</strong>
            <h2>Report and track local issues.</h2>
        </div>
        
        <?php echo $message; ?>

        <form action="" method="POST" enctype="multipart/form-data" id="faultForm">
            
            <div class="formGroup">
                <label for="wardId">Ward: <span class="requiredAsterisk">*</span></label>
                <select name="wardId" id="wardId" class="placeHolderSelect" required>
                    <option value="" disabled selected hidden>select a ward</option>
                    <option value="1">Ward 1</option>
                    <option value="2">Ward 2</option>
                    <option value="3">Ward 3</option>
                </select>
            </div>

            <div class="formGroup">
                <label for="category">Category: <span class="requiredAsterisk">*</span></label>
                <select name="category" id="category" class="placeHolderSelect" required>
                    <option value="" disabled selected hidden>select a fault category</option>
                    <option value="Water Leak">Water Leak</option>
                    <option value="Electricity">Electricity Outage</option>
                    <option value="Pothole">Pothole</option>
                </select>
            </div>

            <div class="formGroup">
                <p class="formGroupHeader"> Select a street or click the button to use the map </p>
                <label for="streetName">Street Name: <span class="requiredAsterisk">*</span></label>
                <select name="streetName" id="streetName" class="placeHolderSelect" required>
                    <option value="" disabled selected hidden>select a street</option>
                    <option value="High Street">High Street</option>
                    <option value="African Street">African Street</option>
                    <option value="Raglan Road">Raglan Road</option>
                </select>

                <div class="inlineButtonActionRow">
                    <button type="button" class="btn useMapBtn" id="useMapBtn">Use Map</button>
                    <button type="button" class="btn clearStreetButton" id="clearStreetButton" style="display: none;">Clear street</button>
                </div>
            </div>

            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            <div class="formGroup" id="mapWrapper" style="display: none;">
                <label>Tap map for the exact location</label>
                <div id="map"></div>

                
                <div class="inlineButtonActionRow rightOnly">
                    <button type="button" class="btn" id="clearMapBtn" style="display: none;">Clear Map</button>
                </div>
            </div>

            <div class="formGroup">
                <label for="description">Fault Description: <span class="requiredAsterisk">*</span></label>
                <textarea name="description" class="description" rows="4" required placeholder="Type issue description details here..."></textarea>
            </div>

            <div class="formGroup">
                <label for="locationDescription">location Description: (Optional)</label>
                <textarea name="locationDescription" class="description" rows="4" placeholder="Provide description details about the location..."></textarea>
            </div>

            <div class="formGroup">
                <label for="evidence_image">Evidence Image (Optional):</label>
                <input type="file" name="evidence_image" id="evidence_image" accept="image/*">
            </div>

            <div class="formActions">
                <div class="leftActions">
                    <button type="reset" class="btn resetBtn">Reset</button>
                    <button type="button" class="btn cancelBtn">Cancel</button>
                </div>
                <button type="submit" class="btn submitBtn">Submit</button>
            </div>
        </form>
    </section>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script >
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

    // --- NEW: Toggle Map Visibility Functionality ---
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
    </script>
</body>
</html>