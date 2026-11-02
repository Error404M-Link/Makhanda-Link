<?php 
    require "reportFaultTicket.php"; 
    require_once "detailsOfTheLoggedInUser.php";

    /* 
       CHECK RESIDENT LOGIN
    */

    $username = $_SESSION["username"] ?? "";

    if ($username === "") {
        header("Location: ../PUBLIC%20PAGES/login.php");
        exit;
    }

    /* ########## SELECT THE DISTICT SERVICES AND STREETS FOT THE DROPDOWN ############## */

    $streetsResults = null; // Store the results of the streets from the database
    $categoryResults = null; // Store the results of the Categoris (Services) from the database
    $userWard = null; // Ward linked to the logged-in user's session, if any
    
/* 
   RESIDENT INFORMATION
*/

    $wardId = (int)(
        $userData["ward_id"]
        ?? $_SESSION["ward_id"]
        ?? 0
    );

    $firstName = $userData["first_Name"] ?? "Resident";
    $surname = $userData["surname"] ?? "";
    $profileImage = $userData["image_path"] ?? "";


/*
   UNREAD NOTIFICATIONS
*/

    $unreadNotificationCount = 0;

    if (isset($conn) && $conn) {

        $sqlNotif = "
            SELECT COUNT(*) AS unread_count
            FROM notification
            WHERE username = ?
            AND notification_type = 'ticket_update'
            AND is_read = 0
        ";

        $stmtNotif = $conn->prepare($sqlNotif);

        if ($stmtNotif) {

            $stmtNotif->bind_param("s", $username);
            $stmtNotif->execute();

            $resultNotif = $stmtNotif->get_result();

            if ($resultNotif && $rowNotif = $resultNotif->fetch_assoc()) {
                $unreadNotificationCount = (int)$rowNotif["unread_count"];
            }

            $stmtNotif->close();
        }
    }


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
        href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Ubuntu:wght@300;400;500;700&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/icon?family=Material+Icons"
        rel="stylesheet"
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
    --sidebar-collapsed-width: 85px;

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
   DASHBOARD CONTAINER
========================================= */

.dashboard-container {
    width: 100%;
    min-height: 100vh;
}


/* =========================================
   SIDEBAR
========================================= */

.sidebar {
    position: fixed;

    top: 0;
    left: 0;
    bottom: 0;

    width: var(--sidebar-width);
    min-height: 100vh;

    padding: 30px 18px;

    background: var(--green);
    color: var(--white);

    z-index: 1000;

    transition: width 0.3s ease;

    overflow-y: auto;
}

.sidebar::-webkit-scrollbar {
    display: none;
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

    overflow: hidden;
}


.sidebar-avatar {
    width: 48px;
    height: 48px;
    min-width: 48px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: rgba(255, 255, 255, 0.15);

    overflow: hidden;
}


.sidebar-avatar img {
    width: 100%;
    height: 100%;

    object-fit: cover;
}


.sidebar-avatar .material-icons {
    font-size: 42px;

    color: var(--white);
}


.sidebar-profile-info {
    display: flex;
    flex-direction: column;

    min-width: 0;
}


.sidebar-profile-info strong {
    font-size: 14px;
    font-weight: 600;

    color: var(--white);

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}


.sidebar-profile-info span {
    margin-top: 3px;

    font-size: 11px;

    color: rgba(255, 255, 255, 0.75);
}


/* =========================================
   SIDEBAR MENU
========================================= */

.sidebar-menu {
    display: flex;
    flex-direction: column;

    gap: 8px;
}


.sidebar-link {
    min-height: 48px;

    display: flex;
    align-items: center;

    gap: 15px;

    padding: 11px 14px;

    border-radius: 8px;

    color: rgba(255, 255, 255, 0.85);
    text-decoration: none;

    font-size: 14px;
    font-weight: 500;

    white-space: nowrap;

    transition:
        background 0.2s ease,
        color 0.2s ease;
}


.sidebar-link:hover {
    background: rgba(255, 255, 255, 0.12);

    color: var(--white);
}


.sidebar-link.active {
    background: rgba(255, 255, 255, 0.18);

    color: var(--white);
}


/* =========================================
   SIDEBAR ICONS
========================================= */

.sidebar-icon {
    width: 24px;
    height: 24px;
    min-width: 24px;

    object-fit: contain;

    filter: brightness(0) invert(1);
}


.sidebar-material-icon {
    width: 24px;
    min-width: 24px;

    font-size: 24px;

    text-align: center;

    color: var(--white);
}


/* =========================================
   COLLAPSED SIDEBAR (desktop)
========================================= */

.sidebar.collapsed {
    width: var(--sidebar-collapsed-width);
}


.sidebar.collapsed .sidebar-profile {
    justify-content: center;

    padding-left: 0;
    padding-right: 0;
}


.sidebar.collapsed .sidebar-profile-info {
    display: none;
}


.sidebar.collapsed .sidebar-link {
    justify-content: center;

    padding-left: 0;
    padding-right: 0;
}


.sidebar.collapsed .sidebar-link > span:not(.material-icons) {
    display: none;
}


/* =========================================
   MAIN WRAPPER
========================================= */

.main-wrapper {
    width: calc(100% - var(--sidebar-width));

    min-height: 100vh;

    margin-left: var(--sidebar-width);

    transition:
        margin-left 0.3s ease,
        width 0.3s ease;
}


.main-wrapper.sidebar-collapsed {
    width: calc(100% - var(--sidebar-collapsed-width));

    margin-left: var(--sidebar-collapsed-width);
}


/* =========================================
   DASHBOARD CONTENT
========================================= */

.dashboard-content {
    width: 100%;
}


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


    <div class="dashboard-container">


    <!-- =========================================
         RESIDENT SIDEBAR
    ========================================== -->

    <aside
        class="sidebar"
        id="sidebar"
    >


        <div class="sidebar-profile">


            <div class="sidebar-avatar">

                <?php if ($profileImage !== ""): ?>

                    <img
                        src="<?php echo htmlspecialchars($profileImage); ?>"
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
                        trim(
                            $firstName . " " . $surname
                        )
                    );
                    ?>

                </strong>

                <span>
                    Resident
                </span>

            </div>


        </div>



        <nav class="sidebar-menu">


            <!-- DASHBOARD -->

            <a
                href="residence.php"
                class="sidebar-link"
                title="Dashboard"
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



            <!-- REPORT FAULT -->

            <a
                href="reportFaultTicketForm.php"
                class="sidebar-link active"
                title="Report Fault"
            >

                <span class="material-icons sidebar-material-icon">
                    report_problem
                </span>

                <span>
                    Report Fault
                </span>

            </a>



            <!-- MY TICKETS -->

            <a
                href="ticketDetails.php"
                class="sidebar-link"
                title="My Tickets"
            >

                <span class="material-icons sidebar-material-icon">
                    confirmation_number
                </span>

                <span>
                    My Tickets
                </span>

            </a>



            <!-- SETTINGS -->

            <a
                href="settings.php"
                class="sidebar-link"
                title="Settings"
            >

                <span class="material-icons sidebar-material-icon">
                    settings
                </span>

                <span>
                    Profile
                </span>

            </a>



            <!-- LOGOUT -->

            <a
                href="logout.php"
                class="sidebar-link"
                title="Logout"
            >

                <span class="material-icons sidebar-material-icon">
                    logout
                </span>

                <span>
                    Logout
                </span>

            </a>


        </nav>


    </aside>



    <!-- =========================================
         MAIN WRAPPER
    ========================================== -->

    <div class="main-wrapper" id="mainWrapper">


        <header class="topbar">


            <div class="topbar-left">


                <!-- MENU -->

                <button
                    type="button"
                    class="menu-btn"
                    id="menuBtn"
                    title="Menu"
                >

                    <span class="material-icons">
                        menu
                    </span>

                </button>



                <!-- M-LINK LOGO -->

                
             <a
    href="../PUBLIC%20PAGES/homepage.php"
    class="logo-link"
    title="Go to M-LINK Homepage"

            >

                <img
                    src="mlinkLogo.png"
                    alt="M-LINK Logo"
                    class="topbar-logo"
                >

            </a>


            </div>



            <div class="topbar-right">


                <!-- NOTIFICATIONS -->

                <a
                    href="notifications.php"
                    class="topbar-icon-btn notification-link"
                    title="Notifications"
                >


                    <div class="notification-icon-wrapper">


                        <img
                            src="../icons/add_alert.svg"
                            alt="Notifications"
                            class="topbar-icon-img"
                        >


                        <?php if ($unreadNotificationCount > 0): ?>

                            <span class="notification-badge">

                                <?php
                                echo $unreadNotificationCount > 99
                                    ? "99+"
                                    : $unreadNotificationCount;
                                ?>

                            </span>

                        <?php endif; ?>


                    </div>


                    <span class="notification-text">
                        Notifications
                    </span>


                </a>



                <!-- MESSAGES -->

                <a
                    href="messages.php"
                    class="topbar-icon-btn"
                    title="Messages"
                >

                    <img
                        src="../icons/message.svg"
                        alt="Messages"
                        class="topbar-icon-img"
                    >

                    <span>
                        Messages
                    </span>

                </a>



                <!-- PROFILE -->

                <a
                    href="settings.php"
                    class="topbar-profile"
                    title="Profile"
                >


                    <div class="topbar-avatar">

                        <?php if ($profileImage !== ""): ?>

                            <img
                                src="<?php echo htmlspecialchars($profileImage); ?>"
                                alt="Profile"
                            >

                        <?php else: ?>

                            <span class="material-icons">
                                account_circle
                            </span>

                        <?php endif; ?>

                    </div>


                    <div class="topbar-profile-info">

                        <strong>
                            <?php
                            echo htmlspecialchars($firstName);
                            ?>
                        </strong>

                        <span>
                            Resident
                        </span>

                    </div>


                </a>


            </div>


        </header>


        <main class="dashboard-content">





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


                <?php if (!empty($newTicketId)): ?>

                    <button
                        type="button"
                        class="modal-btn"
                        id="modalViewTicketBtn"
                        data-href="ticketDetails.php?ticketId=<?php echo (int)$newTicketId; ?>"
                        style="background-color: var(--teal, #244f4b);"
                    >
                        View Ticket
                    </button>

                <?php endif; ?>


            </div>


        </div>


    </div>


    </main>


    </div>


    </div>


    <!-- =========================================
         LEAFLET
    ========================================== -->

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    ></script>


    <script src="residence.js"></script>


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


                // "View Ticket" -> open the ticket that was just lodged

                const modalViewTicketBtn =
                    document.getElementById(
                        'modalViewTicketBtn'
                    );

                if (modalViewTicketBtn) {

                    modalViewTicketBtn.addEventListener(
                        'click',
                        () => {

                            window.location.href =
                                modalViewTicketBtn.dataset.href;

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