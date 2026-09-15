<?php 
    require "reportFaultTicket.php"; 

    /* ########## SELECT THE DISTICT SERVICES AND STREETS FOT THE DROPDOWN ############## */

    $streetsResults = null; // Store the results of the streets from the database
    $categoryResults = null; // Store the results of the Categoris (Services) from the database

    if(isset($conn) && $conn){
        // Select the street along with the ward for Auto filling the ward
        $stmtStreets = $conn->prepare(" SELECT DISTINCT 
                                            ad.address_id, 
                                            ad.street_name, 
                                            ad.ward_id, 
                                            w.ward_number, 
                                            w.ward_name
                                        FROM address_directory ad
                                        INNER JOIN ward w ON ad.ward_id = w.ward_id
                                        ORDER BY ad.street_name ASC ");

        if($stmtStreets){
            $stmtStreets->execute();
            $streetsResult = $stmtStreets->get_result();
            $stmtStreets->close();
        }

        // Fetching the Categories/Services
        $stmtCategory = $conn->prepare("SELECT DISTINCT service_type 
                                        FROM municipal_service
                                        ORDER BY service_type ASC");
        if($stmtCategory){
            $stmtCategory->execute();
            $categoryResults = $stmtCategory->get_result();
            $stmtCategory->close();
        }
    }
?>

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
                <!-- ###### READ ONLY THE WARD #######--> 
                 
                <label for="wardDisplay">Ward:</label>
                <input type="text" id="wardDisplay" class="placeHolderSelect" placeholder="Select a street first..." readonly required>
                
                <!-- Hidden input to submit ward_id to POST -->
                <input type="hidden" name="wardId" id="wardId" required>
            </div>

            <div class="formGroup">
                <label for="category">Category: <span class="requiredAsterisk">*</span></label>

                <!-- ###################  PULL CATEGORY/SERVICE FROM DATABASE #################-->
                <select name="category" id="category" class="placeHolderSelect" required>
                    <option value="" disabled selected hidden>select a fault category</option>
                    <?php 
                        if($categoryResults && $categoryResults->num_rows > 0){
                            while($row_category = $categoryResults->fetch_assoc()){
                                $category = htmlspecialchars($row_category['service_type']);
                                echo "<option value=\"{$category}\">{$category}</option>";
                            }
                        }
                    ?>
                </select>
            </div>

            <div class="formGroup">
                <p class="formGroupHeader"> Select a street or click the button to use the map </p>
                <label for="streetName">Street Name: <span class="requiredAsterisk">*</span></label>

                    <!-- ########## PULL THE STREETS FROM THE DATABASE ############# -->
                <select name="streetName" id="streetName" class="placeHolderSelect" required>
                    <option value="" disabled selected hidden>select a street</option>
                    <?php 
                        if ($streetsResult && $streetsResult->num_rows > 0) {
                            while ($row_street = $streetsResult->fetch_assoc()) {
                                $street = htmlspecialchars($row_street['street_name']);
                                $wardId = htmlspecialchars($row_street['ward_id']);
                                $wardNum = htmlspecialchars($row_street['ward_number']);
                                $wardName = htmlspecialchars($row_street['ward_name']);

                                echo "<option value=\"{$street}\" data-ward-id=\"{$wardId}\" data-ward-info=\"Ward {$wardNum} - {$wardName}\">{$street}</option>";
                            }
                        }
                    ?>
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
                    <a href="residence.php"><button type="button" class="btn cancelBtn">Cancel</button></a>
                </div>
                <button type="submit" class="btn submitBtn">Submit</button>
            </div>
        </form>
    </section>

    <!-- ################# CUSTOM WINDOW POPUP ################# -->
    <div id="customModal" class="modal-overlay">
        <div class="modal-box">
            <h3 id="modalTitle">Notification</h3>
            <p id="modalMessage"></p>
            <div class="modal-actions" style="display: flex; gap: 10px; justify-content: center; margin-top: 15px;">
                <button type="button" class="modal-btn" id="modalCloseBtn">OK</button>
                <a href="residence.php">
                    <button type="button" class="modal-btn secondary-btn" id="modalDashboardBtn" style="background-color: #4b5563;">
                        Back to Dashboard
                    </button>
                <a>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="reportFaultTicket.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const streetSelect = document.getElementById('streetName');
            const wardDisplay = document.getElementById('wardDisplay');
            const wardIdInput = document.getElementById('wardId');
            const cancelBtn = document.getElementById('cancelBtn');
            const customModal = document.getElementById('customModal');
            const modalMessage = document.getElementById('modalMessage');
            const modalCloseBtn = document.getElementById('modalCloseBtn');

            // Display the ward based on the street selected 
            if (streetSelect && wardDisplay && wardIdInput) {
                streetSelect.addEventListener('change', function () {
                    const selectedOption = this.options[this.selectedIndex];

                    const linkedWardId = selectedOption.getAttribute('data-ward-id');
                    const linkedWardInfo = selectedOption.getAttribute('data-ward-info');

                    if (linkedWardId && linkedWardInfo) {
                        wardDisplay.value = linkedWardInfo;
                        wardIdInput.value = linkedWardId;
                    } else {
                        wardDisplay.value = '';
                        wardIdInput.value = '';
                    }
                });
            }

            // Window POPUP logic
            function showModal(msg) {
                if (modalMessage && customModal) {
                    modalMessage.innerHTML = msg;
                    customModal.style.display = 'flex';
                }
            }

            if (modalCloseBtn) {
                modalCloseBtn.addEventListener('click', () => {
                    customModal.style.display = 'none';
                });
            }

            // Trigger popup if PHP has a message.
            <?php if (!empty($message)): ?>
                showModal(<?php echo json_encode($message); ?>);
            <?php endif; ?>
        });
    </script>
</body>
</html>