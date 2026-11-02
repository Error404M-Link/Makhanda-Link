<?php 
    require "Error404Connection.php"; 

    /* ########## SELECT THE DISTINCT SERVICES AND STREETS FOR THE DROPDOWN ############## */

    $streetsResults = null; // Store the results of the streets from the database
    $categoryResults = null; // Store the results of the Categories (Services) from the database
    $userWard = null; // Ward linked to the logged-in user's session, if any

    if(isset($conn) && $conn){

        // Pull ward info for display purposes from the session's own ward_id
        if (!empty($_SESSION['ward_id'])) {

            $stmtUserWard = $conn->prepare("
                SELECT
                    ward_id,
                    ward_number,
                    ward_name
                FROM ward
                WHERE ward_id = ?
                LIMIT 1
            ");

            $stmtUserWard->bind_param(
                "i",
                $_SESSION['ward_id']
            );

            $stmtUserWard->execute();

            $userWard =
                $stmtUserWard
                ->get_result()
                ->fetch_assoc();

            $stmtUserWard->close();
        }


        // Streets for the dropdown
        $stmtStreets = $conn->prepare("
            SELECT DISTINCT
                street_name
            FROM address_directory
            ORDER BY street_name ASC
        ");

        if($stmtStreets){

            $stmtStreets->execute();

            $streetsResult =
                $stmtStreets->get_result();

            $stmtStreets->close();
        }


        // Fetching the Categories/Services
        $stmtCategory = $conn->prepare("
            SELECT DISTINCT
                service_type
            FROM municipal_service
            ORDER BY service_type ASC
        ");

        if($stmtCategory){

            $stmtCategory->execute();

            $categoryResults =
                $stmtCategory->get_result();

            $stmtCategory->close();
        }
    }

    function e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    $sessionUserId   = $_SESSION['user_id']   ?? $_SESSION['id']       ?? $_SESSION['staff_id'] ?? null;
    $sessionUsername = $_SESSION['user_name'] ?? $_SESSION['username'] ?? $_SESSION['email']    ?? $_SESSION['name'] ?? null;

    $loggedInUser = '';
    $userProfilePic = $_SESSION['profile_pic'] ?? '';

    if ($sessionUserId && isset($conn)) {
        $stmt = $conn->prepare("SELECT full_name, first_name, username, profile_pic FROM users WHERE user_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $sessionUserId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($userRow = $res->fetch_assoc()) {
                $loggedInUser = !empty($userRow['full_name']) ? $userRow['full_name'] : (!empty($userRow['first_name']) ? $userRow['first_name'] : $userRow['username']);
                if (!empty($userRow['profile_pic'])) {
                    $userProfilePic = $userRow['profile_pic'];
                }
            }
            $stmt->close();
        }
    } 

    if (empty($loggedInUser) && $sessionUsername && isset($conn)) {
        $stmt = $conn->prepare("SELECT username, first_Name, surname, profile_id FROM user WHERE username = ? OR email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $sessionUsername, $sessionUsername);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($userRow = $res->fetch_assoc()) {
                $fullName = trim(($userRow['first_Name'] ?? '') . ' ' . ($userRow['surname'] ?? ''));
                $loggedInUser = !empty($fullName) ? $fullName : $userRow['username'];
                if (!empty($userRow['profile_id'])) {
                    $userProfilePic = $userRow['profile_id'];
                }
            }
            $stmt->close();
        }
    }

    if (empty($loggedInUser)) {
        $loggedInUser = $sessionUsername ?? 'Communication Officer';
    }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report an Incident | Communication Officer</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="reportFaultTicket.css">
    <link rel="stylesheet" href="communication_officer_dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,700;1,700&family=Ubuntu:wght@400;700&display=swap" rel="stylesheet">

    <style>
/* =========================================
   RESET & BASE STYLES
========================================= */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

:root {
    --green: #4b561b;
    --cream: #f4f0e7;
    --teal: #244f4b;
    --brown: #955b24;
    --text: #263238;
    --white: #ffffff;
    --border: #ded9ce;
    --light-green: #eef0e5;
}

body {
    min-height: 100vh;
    background: var(--cream);
    color: var(--text);
    font-family: "Ubuntu", Arial, sans-serif;
    padding: 20px;
}

/* =========================================
   TOPBAR / HEADER
========================================= */
.topbar-container {
    max-width: 1000px;
    margin: 0 auto 20px auto;
    background: #ffffff;
    border-radius: 12px;
    padding: 15px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid var(--border);
    box-shadow: 0 4px 18px rgba(38, 50, 56, 0.04);
}

.topbar-left .eyebrow {
    font-size: 0.75rem;
    letter-spacing: 1px;
    color: var(--brown);
    font-weight: 700;
    text-transform: uppercase;
}

.topbar-left h1 {
    font-family: "Lora", serif;
    font-size: 1.5rem;
    color: var(--teal);
}

.topbar-user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-info-text {
    text-align: right;
}

.user-name {
    display: block;
    font-weight: 700;
    color: var(--teal);
    font-size: 0.95rem;
}

.user-subtext-top {
    font-size: 0.75rem;
    color: #6f7474;
}

.topbar-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--green);
}

/* =========================================
   FORM CONTAINER
========================================= */
.formContainer {
    width: 100%;
    max-width: 1000px;
    margin: 0 auto;
    padding: 35px 45px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--white);
    box-shadow: 0 4px 18px rgba(38, 50, 56, 0.08);
}

.requiredAsterisk {
    color: #b33a3a;
    margin-left: 2px;
    font-size: 1.2rem;
}

.reportFaultHeader {
    margin-bottom: 30px;
    text-align: center;
}

.reportFaultHeader strong {
    color: var(--teal);
    font-size: 14px;
    font-weight: 700;
}

.reportFaultHeader h2 {
    margin: 5px 0 7px;
    color: var(--teal);
    font-family: "Lora", serif;
    font-size: 28px;
    font-weight: 600;
}

.placeHolderSelect:invalid {
    color: #888888;
}

.placeHolderSelect option {
    color: var(--text);
}

.formGroupHeader {
    margin-top: 10px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border);
    color: var(--green);
    font-family: "Lora", serif;
    font-size: 18px;
    font-weight: 600;
}

.formGroup {
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
}

.formGroup label {
    margin-bottom: 8px;
    color: var(--text);
    font-size: 13px;
    font-weight: 600;
    text-align: left;
}

.formGroup input[type="text"],
.formGroup input[type="file"],
.formGroup select,
.formGroup textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid var(--border);
    border-radius: 7px;
    background: #faf9f6;
    color: var(--text);
    font-family: "Ubuntu", Arial, sans-serif;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
}

.formGroup textarea {
    min-height: 120px;
    resize: vertical;
}

.formGroup select {
    cursor: pointer;
}

.formGroup input:focus,
.formGroup select:focus,
.formGroup textarea:focus {
    border-color: var(--green);
    background: var(--white);
    box-shadow: 0 0 0 3px rgba(75, 86, 27, 0.12);
}

.fieldHint {
    margin-top: 4px;
    color: #6f7474;
    font-size: 12px;
}

.inlineButtonActionRow {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: 8px;
}

.inlineButtonActionRow.rightOnly {
    justify-content: flex-end;
}

#map {
    width: 100%;
    height: 350px;
    margin-top: 8px;
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
    z-index: 1;
}

.btn {
    width: 100%;
    margin-top: 10px;
    padding: 11px 18px;
    border: 1px solid var(--green);
    border-radius: 7px;
    background: var(--white);
    color: var(--green);
    cursor: pointer;
    font-family: "Ubuntu", Arial, sans-serif;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}

.btn:hover {
    background: var(--light-green);
    border-color: var(--green);
    color: var(--green);
}

.useMapBtn {
    width: auto !important;
    margin-top: 0 !important;
    padding: 10px 20px !important;
    border: 1px solid var(--green) !important;
    background: var(--green) !important;
    color: var(--white) !important;
    font-size: 13px !important;
}

.useMapBtn:hover {
    border-color: var(--teal) !important;
    background: var(--teal) !important;
}

.clearStreetButton,
#clearMapBtn {
    width: auto !important;
    margin-top: 0 !important;
    padding: 7px 13px !important;
    border: 1px solid var(--brown) !important;
    background: transparent !important;
    color: var(--brown) !important;
    font-size: 12px !important;
}

.clearStreetButton:hover,
#clearMapBtn:hover {
    background: #f6eee7 !important;
}

.formActions {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-top: 30px;
    padding-top: 22px;
    border-top: 1px solid var(--border);
}

.leftActions {
    display: flex;
    gap: 10px;
}

.formActions .btn {
    width: auto;
    margin-top: 0;
    padding: 11px 24px;
}

.submitBtn {
    border-color: var(--green) !important;
    background: var(--green) !important;
    color: var(--white) !important;
}

.submitBtn:hover {
    border-color: var(--teal) !important;
    background: var(--teal) !important;
    color: var(--white) !important;
}

.resetBtn,
.cancelBtn {
    border: 1px solid var(--border);
    background: var(--white);
    color: #656a6a;
    text-decoration: none;
    display: inline-block;
}

.resetBtn:hover,
.cancelBtn:hover {
    border-color: var(--brown);
    background: #f8f2ec;
    color: var(--brown);
}

.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100vw;
    height: 100vh;
    justify-content: center;
    align-items: center;
    padding: 20px;
    background: rgba(38, 50, 56, 0.65);
    z-index: 999999;
}

.modal-box {
    position: relative;
    width: 90%;
    max-width: 420px;
    padding: 28px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--white);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.25);
    text-align: center;
    z-index: 1000000;
}

.modal-box h3 {
    margin-top: 0;
    margin-bottom: 12px;
    color: var(--teal);
    font-family: "Lora", serif;
    font-size: 1.3rem;
    font-weight: 600;
}

.modal-box p {
    margin-bottom: 22px;
    color: #666d6d;
    font-size: 0.9rem;
    line-height: 1.5;
}

.modal-btn {
    padding: 10px 26px;
    border: none;
    border-radius: 6px;
    background: var(--green);
    color: var(--white);
    cursor: pointer;
    font-family: "Ubuntu", Arial, sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
}

.modal-btn:hover {
    background: var(--teal);
}

@media (max-width: 750px) {
    .formContainer {
        padding: 25px 20px;
    }

    .formActions {
        flex-direction: column;
        align-items: stretch;
    }

    .leftActions {
        width: 100%;
    }

    .leftActions .btn {
        flex: 1;
    }

    .formActions .submitBtn {
        width: 100%;
    }
}
    </style>
</head>

<body>

    <!-- =========================================
         HEADER / TOPBAR
    ========================================== -->
    <header class="topbar-container">
        <div class="topbar-left">
            <p class="eyebrow">MAKANA MUNICIPALITY</p>
            <h1>Report an Incident</h1>
        </div>

        <div class="topbar-user-profile">
            <div class="user-info-text">
                <span class="user-name"><?= e($loggedInUser) ?></span>
                <small class="user-subtext-top">Communication Officer</small>
            </div>
            <div class="avatar-wrapper">
                <img src="<?= !empty($userProfilePic) ? e($userProfilePic) : '../assets/images/default-avatar.png' ?>" 
                     alt="Profile Picture" 
                     class="topbar-avatar">
            </div>
        </div>
    </header>

    <!-- =========================================
         REPORT FAULT FORM
    ========================================== -->
    <section class="formContainer">

        <div class="faultsHeader reportFaultHeader">
            <strong>
                Report A Fault
            </strong>
            <h2>
                Report and track local issues.
            </h2>
        </div>

        <?php echo $message; ?>

        <form
            action=""
            method="POST"
            enctype="multipart/form-data"
            id="faultForm"
        >

            <!-- WARD -->
            <div class="formGroup">
                <label for="wardDisplay">
                    Ward:
                    <span class="requiredAsterisk">*</span>
                </label>

                <input
                    type="text"
                    id="wardDisplay"
                    class="placeHolderSelect"
                    value="Ward <?php
                        echo $userWard
                            ? htmlspecialchars($userWard['ward_number'])
                                . ' - '
                                . htmlspecialchars($userWard['ward_name'])
                            : '';
                    ?>"
                    readonly
                >

                <small class="fieldHint">
                    This is the ward linked to your account.
                </small>
            </div>

            <!-- CATEGORY -->
            <div class="formGroup">
                <label for="category">
                    Category:
                    <span class="requiredAsterisk">*</span>
                </label>

                <select
                    name="category"
                    id="category"
                    class="placeHolderSelect"
                    required
                >
                    <option value="" disabled selected hidden>
                        select a fault category
                    </option>

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

            <!-- STREET -->
            <div class="formGroup">
                <p class="formGroupHeader">
                    Select a street or click the button to use the map
                </p>

                <label for="streetName">
                    Street Name:
                    <span class="requiredAsterisk">*</span>
                </label>

                <select
                    name="streetName"
                    id="streetName"
                    class="placeHolderSelect"
                    required
                >
                    <option value="" disabled selected hidden>
                        select a street
                    </option>

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
                    <button type="button" class="btn useMapBtn" id="useMapBtn">
                        Use Map
                    </button>

                    <button type="button" class="btn clearStreetButton" id="clearStreetButton" style="display: none;">
                        Clear street
                    </button>
                </div>
            </div>

            <!-- MAP VALUES -->
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            <!-- MAP -->
            <div class="formGroup" id="mapWrapper" style="display: none;">
                <label>Tap map for the exact location</label>
                <div id="map"></div>
                <div class="inlineButtonActionRow rightOnly">
                    <button type="button" class="btn" id="clearMapBtn" style="display: none;">
                        Clear Map
                    </button>
                </div>
            </div>

            <!-- FAULT DESCRIPTION -->
            <div class="formGroup">
                <label for="description">
                    Fault Description:
                    <span class="requiredAsterisk">*</span>
                </label>

                <textarea
                    name="description"
                    class="description"
                    rows="4"
                    required
                    placeholder="Type issue description details here..."
                ></textarea>
            </div>

            <!-- LOCATION DESCRIPTION -->
            <div class="formGroup">
                <label for="locationDescription">
                    location Description: (Optional)
                </label>

                <textarea
                    name="locationDescription"
                    class="description"
                    rows="4"
                    placeholder="Provide description details about the location..."
                ></textarea>
            </div>

            <!-- EVIDENCE IMAGE -->
            <div class="formGroup">
                <label for="evidence_image">
                    Evidence Image (Optional):
                </label>

                <input
                    type="file"
                    name="evidence_image"
                    id="evidence_image"
                    accept="image/*"
                >
            </div>

            <!-- FORM BUTTONS -->
            <div class="formActions">
                <div class="leftActions">
                    <button type="reset" class="btn resetBtn">
                        Reset
                    </button>

                    <a href="communication_officer_dashboard.php" class="btn cancelBtn" style="text-align: center;">
                        Cancel
                    </a>
                </div>

                <button type="submit" class="btn submitBtn">
                    Submit
                </button>
            </div>

        </form>

    </section>

    <!-- =========================================
         CUSTOM WINDOW POPUP
    ========================================== -->
    <div id="customModal" class="modal-overlay">
        <div class="modal-box">
            <h3 id="modalTitle">Notification</h3>
            <p id="modalMessage"></p>
            <div class="modal-actions" style="display: flex; gap: 10px; justify-content: center; margin-top: 15px;">
                <button type="button" class="modal-btn" id="modalCloseBtn">
                    OK
                </button>

                <a href="communication_officer_dashboard.php">
                    <button type="button" class="modal-btn secondary-btn" id="modalDashboardBtn" style="background-color: #4b5563;">
                        Back to Dashboard
                    </button>
                </a>
            </div>
        </div>
    </div>

    <!-- =========================================
         LEAFLET & SCRIPTS
    ========================================== -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="reportFaultTicket.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const customModal = document.getElementById('customModal');
            const modalMessage = document.getElementById('modalMessage');
            const modalCloseBtn = document.getElementById('modalCloseBtn');

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

            <?php if (!empty($message)): ?>
                showModal(<?php echo json_encode($message); ?>);
            <?php endif; ?>
        });
    </script>
</body>
</html>