<?php 

session_start();
//check if user is logged in
if(!isset($_SESSION["username"])){
    header("location: ../PUBLIC PAGES/login.php");
    exit;
}
require "../reportFaultTicket.php"; 

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
?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Report a Fault</title>


    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >


    <link
        rel="stylesheet"
        href="reportFaultTicket.css"
    >


    <link
        rel="stylesheet"
        href="dashboardSideBar.css"
    >


    <style>
     /* =========================================
   RESET
========================================= */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}


/* =========================================
   M-LINK COLOURS
========================================= */

:root {
    --sidebar-width: 270px;

    --green: #4b561b;
    --cream: #f4f0e7;
    --teal: #244f4b;
    --brown: #955b24;
    --text: #263238;
    --white: #ffffff;
    --border: #ded9ce;
    --light-green: #eef0e5;
}


/* =========================================
   FONTS
========================================= */

.lora-sidebar {
    font-family: "Lora", serif;
    font-optical-sizing: auto;
    font-weight: 500;
    font-style: normal;
}


/* =========================================
   PAGE
========================================= */

html,
body {
    width: 100%;
    min-height: 100%;
}


body {
    min-height: 100vh;

    display: block;

    background: var(--cream);

    color: var(--text);

    font-family: "Ubuntu", Arial, sans-serif;
}


body::-webkit-scrollbar {
    display: none;
}


/* =========================================
   SIDEBAR
========================================= */

.sidebar {
    display: block !important;

    position: fixed !important;

    top: 0 !important;
    left: 0 !important;
    bottom: 0 !important;

    width: var(--sidebar-width) !important;
    height: 100vh !important;

    padding: 30px 18px !important;

    background: var(--green) !important;

    color: var(--white) !important;

    z-index: 99999 !important;

    transform: none !important;

    overflow-y: auto;
}


/* =========================================
   SIDEBAR PROFILE
========================================= */

.sidebar-profile {
    display: flex;

    align-items: center;

    gap: 13px;

    padding: 5px 7px 25px;

    margin-bottom: 20px;

    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}


.sidebar-avatar {
    width: 48px;
    height: 48px;
    min-width: 48px;

    display: flex;

    align-items: center;
    justify-content: center;

    border: 3px solid rgba(255, 255, 255, 0.65);

    border-radius: 50%;

    background: var(--cream);

    overflow: hidden;
}


.sidebar-avatar img {
    width: 100%;
    height: 100%;

    object-fit: cover;
}


.sidebar-avatar-placeholder {
    width: 100%;
    height: 100%;

    display: flex;

    align-items: center;
    justify-content: center;

    color: var(--green);

    font-size: 20px;
    font-weight: 700;
}


.sidebar-profile-info {
    display: flex;

    flex-direction: column;

    min-width: 0;
}


.sidebar-profile-info strong {
    color: var(--white);

    font-size: 14px;
    font-weight: 600;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


.sidebar-profile-info span {
    margin-top: 3px;

    color: rgba(255, 255, 255, 0.8);

    font-size: 11px;
}


/* =========================================
   SIDEBAR MENU
========================================= */

.sidebar-menu {
    width: 100%;

    display: flex !important;

    flex-direction: column;

    gap: 8px;
}


.sidebar-link {
    width: 100%;
    min-height: 48px;

    display: flex !important;

    align-items: center;

    gap: 15px;

    padding: 11px 14px;

    border-radius: 8px;

    color: var(--white) !important;

    font-size: 14px;
    font-weight: 500;

    text-decoration: none;

    white-space: nowrap;

    transition:
        background 0.2s ease,
        color 0.2s ease;
}


.sidebar-link:hover {
    background: rgba(255, 255, 255, 0.12);

    color: var(--white) !important;
}


.sidebar-link.active {
    background: rgba(255, 255, 255, 0.18);

    color: var(--white) !important;
}


/* =========================================
   SIDEBAR ICONS
========================================= */

.sidebar-icon {
    width: 23px;
    height: 23px;
    min-width: 23px;

    display: block;

    object-fit: contain;

    filter: brightness(0) invert(1);
}


/* =========================================
   REQUIRED ASTERISK
========================================= */

.requiredAsterisk {
    color: #b33a3a;

    margin-left: 2px;

    font-size: 1.2rem;
}


/* =========================================
   FORM CONTAINER
========================================= */

.formContainer {
    width: calc(100% - 340px);
    max-width: 1000px;

    margin-top: 35px;
    margin-right: 35px;
    margin-bottom: 35px;
    margin-left: 305px;

    padding: 35px 45px;

    border: 1px solid var(--border);

    border-radius: 12px;

    background: var(--white);

    box-shadow: 0 4px 18px rgba(38, 50, 56, 0.08);
}


/* =========================================
   PAGE HEADER
========================================= */

.faultsHeader {
    margin: 0 50px 20px;

    color: var(--teal);

    font-size: 0.9rem;
    font-weight: 600;
}


.faultsHeader h4 {
    max-width: 600px;

    margin-bottom: 7px;

    color: var(--teal);

    font-family: "Lora", serif;

    font-size: 2.2rem;
    font-weight: 700;
}


.faultsHeader p {
    color: #6f7474;

    font-size: 0.9rem;

    line-height: 1.6;
}


/* =========================================
   REPORT FAULT HEADER
========================================= */

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


/* =========================================
   SELECT PLACEHOLDER
========================================= */

.placeHolderSelect:invalid {
    color: #888888;
}


.placeHolderSelect option {
    color: var(--text);
}


/* =========================================
   FORM GROUP HEADER
========================================= */

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


/* =========================================
   FORM GROUP
========================================= */

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


/* =========================================
   INPUTS
========================================= */

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

    transition:
        border-color 0.2s ease,
        background 0.2s ease,
        box-shadow 0.2s ease;
}


.formGroup textarea {
    min-height: 120px;

    resize: vertical;
}


.formGroup select {
    cursor: pointer;
}


.formGroup select option {
    background: var(--white);

    color: var(--text);
}


.formGroup input:focus,
.formGroup select:focus,
.formGroup textarea:focus {
    border-color: var(--green);

    background: var(--white);

    outline: none;

    box-shadow: 0 0 0 3px rgba(75, 86, 27, 0.12);
}


.formGroup ::placeholder {
    color: #999c9c;

    font-size: 13px;

    opacity: 1;
}


/* =========================================
   FIELD HINT
========================================= */

.fieldHint {
    margin-top: 4px;

    color: #6f7474;

    font-size: 12px;
}


/* =========================================
   INLINE BUTTON ROW
========================================= */

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


/* =========================================
   MAP
========================================= */

#map {
    width: 100%;
    height: 350px;

    margin-top: 8px;

    border: 1px solid var(--border);

    border-radius: 8px;

    overflow: hidden;

    z-index: 1;
}


/* =========================================
   BUTTON GROUP
========================================= */

.buttonGroup {
    display: flex;

    gap: 10px;

    margin-top: 15px;
}


/* =========================================
   GENERAL BUTTON
========================================= */

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

    transition:
        background 0.2s ease,
        color 0.2s ease,
        border-color 0.2s ease;
}


.btn:hover {
    background: var(--light-green);

    border-color: var(--green);

    color: var(--green);
}


/* =========================================
   USE MAP BUTTON
========================================= */

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


/* =========================================
   CLEAR BUTTONS
========================================= */

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


/* =========================================
   FORM ACTIONS
========================================= */

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


/* =========================================
   SUBMIT BUTTON
========================================= */

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


/* =========================================
   RESET + CANCEL
========================================= */

.resetBtn,
.cancelBtn {
    border: 1px solid var(--border);

    background: var(--white);

    color: #656a6a;
}


.resetBtn:hover,
.cancelBtn:hover {
    border-color: var(--brown);

    background: #f8f2ec;

    color: var(--brown);
}


/* =========================================
   FILE INPUT
========================================= */

.formGroup input[type="file"] {
    padding: 8px 12px;

    cursor: pointer;
}


.formGroup input[type="file"]::file-selector-button {
    margin-right: 15px;

    padding: 7px 13px;

    border: 1px solid var(--green);

    border-radius: 5px;

    background: var(--green);

    color: var(--white);

    font-family: "Ubuntu", Arial, sans-serif;

    font-size: 12px;
    font-weight: 600;

    cursor: pointer;
}


.formGroup input[type="file"]::file-selector-button:hover {
    border-color: var(--teal);

    background: var(--teal);
}


/* =========================================
   DISABLED MAP
========================================= */

.disabledMap {
    opacity: 0.5;

    pointer-events: none;

    cursor: not-allowed;
}


.disabledMap #map {
    border: 2px dashed #b8b9b2;

    background: #eeece5;
}


/* =========================================
   MODAL
========================================= */

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


/* =========================================
   TABLET
========================================= */

@media (max-width: 900px) {

    .sidebar {
        width: 220px !important;

        padding: 25px 12px !important;
    }


    .formContainer {
        width: calc(100% - 260px);

        margin-top: 25px;
        margin-right: 20px;
        margin-bottom: 25px;
        margin-left: 240px;

        padding: 30px 25px;
    }
}


/* =========================================
   SMALL SCREEN

   IMPORTANT:
   SIDEBAR IS NOT HIDDEN
========================================= */

@media (max-width: 750px) {

    .sidebar {
        display: block !important;

        width: 85px !important;

        padding: 25px 10px !important;

        transform: none !important;
    }


    .sidebar-profile {
        justify-content: center;

        padding-left: 0;
        padding-right: 0;
    }


    .sidebar-profile-info {
        display: none;
    }


    .sidebar-link {
        justify-content: center;

        padding-left: 0;
        padding-right: 0;
    }


    .sidebar-link span {
        display: none;
    }


    .formContainer {
        width: calc(100% - 115px);

        margin-top: 20px;
        margin-right: 15px;
        margin-bottom: 20px;
        margin-left: 100px;

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


    #map {
        height: 300px;
    }
}


/* =========================================
   MOBILE
========================================= */

@media (max-width: 500px) {

    .formContainer {
        width: calc(100% - 105px);

        margin-top: 15px;
        margin-right: 10px;
        margin-bottom: 15px;
        margin-left: 95px;

        padding: 22px 15px;
    }


    .reportFaultHeader h2 {
        font-size: 23px;
    }


    .inlineButtonActionRow {
        flex-direction: column;

        align-items: stretch;
    }


    .inlineButtonActionRow.rightOnly {
        align-items: stretch;
    }


    .buttonGroup {
        flex-direction: column;
    }


    .leftActions {
        flex-direction: column;
    }


    .leftActions .btn,
    .formActions .btn {
        width: 100%;
    }


    .clearStreetButton,
    #clearMapBtn,
    .useMapBtn {
        width: 100% !important;
    }


    #map {
        height: 260px;
    }
}
    </style>

</head>


<body>


    <!-- =========================================
         RESIDENT SIDEBAR
    ========================================== -->

    <aside class="sidebar">


        <div class="sidebar-profile">


            <div class="sidebar-avatar">

                <?php if (!empty($userData["image_path"])): ?>

                    <img
                        src="<?php echo htmlspecialchars($userData["image_path"]); ?>"
                        alt="Profile"
                    >

                <?php else: ?>

                    <span class="sidebar-avatar-placeholder">
                        <?php
                            echo strtoupper(
                                substr(
                                    $userData["first_Name"]
                                    ?? $_SESSION["username"]
                                    ?? "R",
                                    0,
                                    1
                                )
                            );
                        ?>
                    </span>

                <?php endif; ?>

            </div>


            <div class="sidebar-profile-info">

                <strong>

                    <?php
                        echo htmlspecialchars(
                            $userData["first_Name"]
                            ?? $_SESSION["username"]
                            ?? "Resident"
                        );
                    ?>

                </strong>

                <span>Resident</span>

            </div>

        </div>



        <nav class="sidebar-menu">


            <!-- DASHBOARD -->

            <a
                href="residence.php"
                class="sidebar-link"
            >

                <img
                    src="../icons/home.svg"
                    alt=""
                    class="sidebar-icon"
                >

                <span>Dashboard</span>

            </a>



            <!-- REPORT FAULT -->

            <a
                href="reportFault.php"
                class="sidebar-link active"
            >

                <img
                    src="../icons/report.svg"
                    alt=""
                    class="sidebar-icon"
                >

                <span>Report Fault</span>

            </a>



            <!-- MY TICKETS -->

            <a
                href="myTickets.php"
                class="sidebar-link"
            >

                <img
                    src="../icons/ticket.svg"
                    alt=""
                    class="sidebar-icon"
                >

                <span>My Tickets</span>

            </a>



            <!-- PROFILE -->

            <a
                href="settings.php"
                class="sidebar-link"
            >

                <img
                    src="../icons/settings.svg"
                    alt=""
                    class="sidebar-icon"
                >

                <span>Profile</span>

            </a>



            <!-- LOGOUT -->

            <a
                href="../logout.php"
                class="sidebar-link"
            >

                <img
                    src="../icons/logout.svg"
                    alt=""
                    class="sidebar-icon"
                >

                <span>Logout</span>

            </a>


        </nav>


    </aside>



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

                    <span class="requiredAsterisk">
                        *
                    </span>

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

                    <span class="requiredAsterisk">
                        *
                    </span>

                </label>


                <select
                    name="category"
                    id="category"
                    class="placeHolderSelect"
                    required
                >

                    <option
                        value=""
                        disabled
                        selected
                        hidden
                    >
                        select a fault category
                    </option>


                    <?php 

                        if(
                            $categoryResults &&
                            $categoryResults->num_rows > 0
                        ){

                            while(
                                $row_category =
                                $categoryResults->fetch_assoc()
                            ){

                                $category =
                                    htmlspecialchars(
                                        $row_category['service_type']
                                    );

                                echo "
                                    <option value=\"{$category}\">
                                        {$category}
                                    </option>
                                ";
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

                    <span class="requiredAsterisk">
                        *
                    </span>

                </label>


                <select
                    name="streetName"
                    id="streetName"
                    class="placeHolderSelect"
                    required
                >

                    <option
                        value=""
                        disabled
                        selected
                        hidden
                    >
                        select a street
                    </option>


                    <?php 

                        if (
                            $streetsResult &&
                            $streetsResult->num_rows > 0
                        ) {

                            while (
                                $row_street =
                                $streetsResult->fetch_assoc()
                            ) {

                                $street =
                                    htmlspecialchars(
                                        $row_street['street_name']
                                    );

                                echo "
                                    <option value=\"{$street}\">
                                        {$street}
                                    </option>
                                ";
                            }
                        }

                    ?>

                </select>



                <div class="inlineButtonActionRow">


                    <button
                        type="button"
                        class="btn useMapBtn"
                        id="useMapBtn"
                    >
                        Use Map
                    </button>


                    <button
                        type="button"
                        class="btn clearStreetButton"
                        id="clearStreetButton"
                        style="display: none;"
                    >
                        Clear street
                    </button>


                </div>

            </div>



            <!-- MAP VALUES -->

            <input
                type="hidden"
                name="latitude"
                id="latitude"
            >


            <input
                type="hidden"
                name="longitude"
                id="longitude"
            >



            <!-- MAP -->

            <div
                class="formGroup"
                id="mapWrapper"
                style="display: none;"
            >


                <label>
                    Tap map for the exact location
                </label>


                <div id="map"></div>


                <div class="inlineButtonActionRow rightOnly">


                    <button
                        type="button"
                        class="btn"
                        id="clearMapBtn"
                        style="display: none;"
                    >
                        Clear Map
                    </button>


                </div>

            </div>



            <!-- FAULT DESCRIPTION -->

            <div class="formGroup">


                <label for="description">

                    Fault Description:

                    <span class="requiredAsterisk">
                        *
                    </span>

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


                    <button
                        type="reset"
                        class="btn resetBtn"
                    >
                        Reset
                    </button>


                    <a href="residence.php">

                        <button
                            type="button"
                            class="btn cancelBtn"
                        >
                            Cancel
                        </button>

                    </a>


                </div>


                <button
                    type="submit"
                    class="btn submitBtn"
                >
                    Submit
                </button>


            </div>


        </form>


    </section>



    <!-- =========================================
         CUSTOM WINDOW POPUP
    ========================================== -->

    <div
        id="customModal"
        class="modal-overlay"
    >


        <div class="modal-box">


            <h3 id="modalTitle">
                Notification
            </h3>


            <p id="modalMessage"></p>


            <div
                class="modal-actions"
                style="
                    display: flex;
                    gap: 10px;
                    justify-content: center;
                    margin-top: 15px;
                "
            >


                <button
                    type="button"
                    class="modal-btn"
                    id="modalCloseBtn"
                >
                    OK
                </button>


                <a href="residence.php">

                    <button
                        type="button"
                        class="modal-btn secondary-btn"
                        id="modalDashboardBtn"
                        style="background-color: #4b5563;"
                    >
                        Back to Dashboard
                    </button>

                </a>


            </div>


        </div>


    </div>



    <!-- =========================================
         LEAFLET
    ========================================== -->

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    ></script>


    <script src="reportFaultTicket.js"></script>



    <script>

        document.addEventListener(
            'DOMContentLoaded',
            () => {

                const cancelBtn =
                    document.getElementById(
                        'cancelBtn'
                    );

                const customModal =
                    document.getElementById(
                        'customModal'
                    );

                const modalMessage =
                    document.getElementById(
                        'modalMessage'
                    );

                const modalCloseBtn =
                    document.getElementById(
                        'modalCloseBtn'
                    );


                // Window POPUP logic

                function showModal(msg) {

                    if (
                        modalMessage &&
                        customModal
                    ) {

                        modalMessage.innerHTML =
                            msg;

                        customModal.style.display =
                            'flex';
                    }
                }


                if (modalCloseBtn) {

                    modalCloseBtn.addEventListener(
                        'click',
                        () => {

                            customModal.style.display =
                                'none';

                        }
                    );
                }


                // Trigger popup if PHP has a message.

                <?php if (!empty($message)): ?>

                    showModal(
                        <?php
                            echo json_encode(
                                $message
                            );
                        ?>
                    );

                <?php endif; ?>

            }
        );

    </script>


</body>

</html>