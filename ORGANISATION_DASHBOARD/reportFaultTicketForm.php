<?php 
    require "reportFaultTicket.php"; 

    /* ########## SELECT THE DISTICT SERVICES AND STREETS FOT THE DROPDOWN ############## */

    $streetsResults = null; // Store the results of the streets from the database
    $categoryResults = null; // Store the results of the Categoris (Services) from the database
    $userWard = null; // Ward linked to the logged-in user's session, if any

    if(isset($conn) && $conn){
        // Pull ward info for display purposes from the session's own ward_id
        // (already set at login by login_process.php). Ward is now ALWAYS this
        // value, regardless of street or map selection - it no longer changes
        // based on what street is picked.
        if (!empty($_SESSION['ward_id'])) {
            $stmtUserWard = $conn->prepare("SELECT ward_id, ward_number, ward_name
                                            FROM ward
                                            WHERE ward_id = ? LIMIT 1");
            $stmtUserWard->bind_param("i", $_SESSION['ward_id']);
            $stmtUserWard->execute();
            $userWard = $stmtUserWard->get_result()->fetch_assoc(); // null if ward_id doesn't match a real ward
            $stmtUserWard->close();
        }
        // Streets for the dropdown (used only to determine address_id on submit -
        // ward no longer comes from the street, see $userWard above)
        $stmtStreets = $conn->prepare("SELECT DISTINCT street_name 
                                       FROM address_directory 
                                       ORDER BY street_name ASC");

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
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="reportFaultTicket.css"/>
    <link rel="stylesheet" href="organisation.css"/>
    <style>
     /* =========================================
   TOPBAR
========================================= */

.topbar {
    position: sticky;

    top: 0;

    width: 100%;
    height: 95px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 35px;

    background: var(--white);

    border-bottom: 1px solid var(--border);

    z-index: 900;
}


.topbar-left,
.topbar-right {
    display: flex;
    align-items: center;
}


.topbar-left {
    gap: 20px;
}


.topbar-right {
    gap: 28px;
}


/* =========================================
   HAMBURGER MENU
========================================= */

.menu-btn {
    width: 42px;
    height: 42px;

    padding: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border: none;
    border-radius: 0;

    background: transparent;

    color: var(--text);

    cursor: pointer;
}


.menu-btn:hover {
    background: transparent;

    color: var(--green);
}


.menu-btn .material-icons {
    font-size: 29px;
}


/* =========================================
   M-LINK LOGO
========================================= */

.logo-link {
    display: flex;
    align-items: center;
}


.topbar-logo {
    width: 64px;
    height: 64px;

    display: block;

    object-fit: contain;
}


/* =========================================
   TOPBAR BUTTONS
========================================= */

.topbar-icon-btn {
    display: flex;
    align-items: center;

    gap: 8px;

    color: var(--text);
    text-decoration: none;

    font-size: 12px;
    font-weight: 500;

    white-space: nowrap;

    transition: color 0.2s ease;
}


.topbar-icon-btn:hover {
    color: var(--brown);
}


.topbar-icon-img {
    width: 26px;
    height: 26px;

    display: block;

    object-fit: contain;
}


/* =========================================
   NOTIFICATION
========================================= */

.notification-link {
    position: relative;

    display: flex;
    align-items: center;

    gap: 8px;

    color: var(--text);
}


.notification-icon-wrapper {
    position: relative;

    width: 28px;
    height: 28px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;
}


.notification-icon-wrapper img {
    width: 26px;
    height: 26px;

    display: block;

    object-fit: contain;
}


.notification-badge {
    position: absolute;

    top: -8px;
    right: -9px;

    min-width: 19px;
    height: 19px;

    padding: 0 5px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 2px solid var(--white);
    border-radius: 20px;

    background: var(--brown);
    color: var(--white);

    font-size: 9px;
    font-weight: 700;

    line-height: 1;
}


.notification-text {
    font-size: 12px;
    font-weight: 500;

    color: var(--text);
}


.notification-link:hover .notification-text {
    color: var(--brown);
}


/* =========================================
   TOPBAR PROFILE
========================================= */

.topbar-profile {
    display: flex;
    align-items: center;

    gap: 10px;

    padding-left: 20px;

    border-left: 1px solid var(--border);

    color: var(--text);
    text-decoration: none;
}


.topbar-avatar {
    width: 42px;
    height: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: #ece8df;

    overflow: hidden;
}


.topbar-avatar img {
    width: 100%;
    height: 100%;

    object-fit: cover;
}


.topbar-avatar .material-icons {
    font-size: 40px;

    color: var(--green);
}


.topbar-profile-info {
    display: flex;
    flex-direction: column;
}


.topbar-profile-info strong {
    font-size: 13px;
    font-weight: 600;

    color: var(--text);
}


.topbar-profile-info span {
    margin-top: 2px;

    font-size: 10px;

    color: #777777;
}


/* =========================================
   SIDEBAR / TOPBAR MOBILE
========================================= */

@media (max-width: 750px) {

    .sidebar {
        width: var(--sidebar-width);

        transform: translateX(-100%);

        transition: transform 0.3s ease;
    }


    .sidebar.mobile-open {
        transform: translateX(0);
    }


    .main-wrapper,
    .main-wrapper.sidebar-collapsed {
        width: 100%;

        margin-left: 0;
    }


    .topbar {
        height: 80px;

        padding: 0 18px;
    }


    .topbar-left {
        gap: 12px;
    }


    .topbar-right {
        gap: 14px;
    }


    .topbar-logo {
        width: 52px;
        height: 52px;
    }


    .topbar-profile-info {
        display: none;
    }


    .topbar-profile {
        padding-left: 12px;
    }


    .topbar-avatar {
        width: 37px;
        height: 37px;
    }


    .topbar-icon-btn > span,
    .notification-text {
        display: none;
    }
}

    </style>
</head>
<body>

    <input type="checkbox" id="sidebarToggle">
    <label for="sidebarToggle" class="hamburger">
        <span></span><span></span><span></span>
    </label>

          <aside
        class="sidebar"
        id="sidebar"
    >


        <div class="sidebar-profile">

            <div class="sidebar-avatar">

                <?php if (!empty($userData["image_path"])): ?>

                    <img
                        src="<?php echo htmlspecialchars($userData["image_path"]); ?>"
                        alt="Profile"
                    >

                <?php else: ?>

                    <span class="material-icons">
                        account_circle
                    </span>

                <?php endif; ?>

            </div>


            <div class="sidebar-profile-info">

                <strong>
                    <?php
                    echo htmlspecialchars(
                        ($userData["username"] ?? "") 
                    );
                    ?>
                </strong>

                <span>
                    Organisation
                </span>

            </div>

        </div>


        <nav class="sidebar-menu">


            <a
                href="organisationdb.php"
                class="sidebar-link active"
            >

                <img
                    src="../icons/home.svg"
                    alt=""
                    class="sidebar-icon"
                >

                <span>
                    Dashboard
                </span>

            </a>


            <a
                href="reportFaultTicketForm.php"
                class="sidebar-link"
            >

                <span class="material-icons sidebar-material-icon">
                    report_problem
                </span>

                <span>
                    Report Fault
                </span>

            </a>


            <a
                href="ticketHistory.php"
                class="sidebar-link"
            >

                <span class="material-icons sidebar-material-icon">
                    confirmation_number
                </span>

                <span>
                    My Tickets
                </span>

            </a>


            <a
                href="settings.php"
                class="sidebar-link"
            >

                <span class="material-icons sidebar-material-icon">
                    settings
                </span>

                <span>
                    Profile
                </span>

            </a>


            <a href="logout.php" class="sidebar-link">

                <span class="material-icons sidebar-material-icon">
                    logout
                </span>

                <span>
                    Logout
                </span>

            </a>


        </nav>


    </aside>
      

    <section class="formContainer">

        <div class="faultsHeader reportFaultHeader">
            <h2>Report A Fault.</h2>
        </div>
        
        <?php echo $message; ?>

        <form action="" method="POST" enctype="multipart/form-data" id="faultForm">
            
            <div class="formGroup">
                <label for="wardDisplay">Ward: <span class="requiredAsterisk">*</span></label>

                <!-- Always the logged-in user's own ward - pre-filled on page load,
                     doesn't change based on street or map selection. Read-only preview
                     only; the server re-derives this from the session on submit anyway. -->
                <input type="text" id="wardDisplay" class="placeHolderSelect"
                       value="Ward <?php echo $userWard ? htmlspecialchars($userWard['ward_number']) . ' - ' . htmlspecialchars($userWard['ward_name']) : ''; ?>"
                       readonly>

                <small class="fieldHint">This is the ward linked to your account.</small>
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
                                echo "<option value=\"{$street}\">{$street}</option>";
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
                <a href="organisationdb.php">
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
            const cancelBtn = document.getElementById('cancelBtn');
            const customModal = document.getElementById('customModal');
            const modalMessage = document.getElementById('modalMessage');
            const modalCloseBtn = document.getElementById('modalCloseBtn');

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