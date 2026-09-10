<?php

session_start();

include "Error404Connection.php";

/* =========================================================
   ADMIN INFORMATION
========================================================= */

/*
   If your login stores the admin username in the session,
   this will use it.

   Otherwise it falls back to the first/latest admin account.
*/

$adminUsername = $_SESSION["username"] ?? "";

$adminName = "Qhamani Somhlahlo";
$adminRole = "System Admin";


if (!empty($adminUsername)) {

    $stmt = $conn->prepare("
        SELECT username
        FROM admin
        WHERE username = ?
        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param("s", $adminUsername);
        $stmt->execute();

        $adminResult = $stmt->get_result();

        if ($adminResult && $adminResult->num_rows > 0) {

            $admin = $adminResult->fetch_assoc();

            $adminName = $admin["username"];

        }

        $stmt->close();
    }

} else {

    /*
       Fallback if the login session does not yet contain
       the admin username.
    */

    $adminQuery = "
        SELECT username
        FROM admin
        ORDER BY last_login_date DESC
        LIMIT 1
    ";

    $adminResult = $conn->query($adminQuery);

    if ($adminResult && $adminResult->num_rows > 0) {

        $admin = $adminResult->fetch_assoc();

        $adminName = $admin["username"];
    }
}


/* =========================================================
   TIME-BASED GREETING
========================================================= */

$currentHour = date("H");

if ($currentHour < 12) {

    $greeting = "Good Morning";

} elseif ($currentHour < 18) {

    $greeting = "Good Afternoon";

} else {

    $greeting = "Good Evening";
}


/* =========================================================
   TOTAL ADMINS
========================================================= */

$totalAdmins = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM admin
");

if ($result) {

    $row = $result->fetch_assoc();

    $totalAdmins = (int)$row["total"];
}


/* =========================================================
   HELPER FUNCTION
========================================================= */

function tableExists($conn, $tableName)
{
    $tableName = $conn->real_escape_string($tableName);

    $result = $conn->query("
        SHOW TABLES LIKE '$tableName'
    ");

    return ($result && $result->num_rows > 0);
}


function getTableCount($conn, $tableName)
{
    if (!tableExists($conn, $tableName)) {
        return 0;
    }

    $safeTable = "`" . str_replace("`", "``", $tableName) . "`";

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM $safeTable
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        return (int)$row["total"];
    }

    return 0;
}


/* =========================================================
   MUNICIPAL TICKETS
========================================================= */

/*
   Change/add the actual table name here if your database
   uses a different name.
*/

$ticketTables = [
    "tickets",
    "municipal_tickets",
    "incident_tickets",
    "ward_tickets"
];

$totalTickets = 0;

foreach ($ticketTables as $table) {

    if (tableExists($conn, $table)) {

        $totalTickets = getTableCount($conn, $table);

        break;
    }
}


/* =========================================================
   USERS
========================================================= */

/*
   This checks common M-Link account tables.

   Add your actual table name if necessary.
*/

$userTables = [
    "users",
    "residents",
    "organisations",
    "organisation"
];

$totalUsers = 0;

foreach ($userTables as $table) {

    if (tableExists($conn, $table)) {

        $totalUsers += getTableCount($conn, $table);
    }
}


/*
   Include administrators in the overall user count.
*/

$totalUsers += $totalAdmins;


/* =========================================================
   ACTIVE NOTICES
========================================================= */

$noticeTables = [
    "notices",
    "ward_notices",
    "announcements"
];

$totalNotices = 0;

foreach ($noticeTables as $table) {

    if (tableExists($conn, $table)) {

        $totalNotices = getTableCount($conn, $table);

        break;
    }
}


/* =========================================================
   PATCH MANAGEMENT
========================================================= */

$patchTables = [
    "patches",
    "patch_management",
    "patches_management"
];

$totalPatches = 0;

foreach ($patchTables as $table) {

    if (tableExists($conn, $table)) {

        $totalPatches = getTableCount($conn, $table);

        break;
    }
}


/* =========================================================
   RECENT ADMIN ACTIVITY
========================================================= */

$recentAdmins = $conn->query("
    SELECT
        username,
        last_login_date,
        registered_date
    FROM admin
    ORDER BY last_login_date DESC
    LIMIT 5
");


/* =========================================================
   DATE
========================================================= */

$today = date("l, d F Y");

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>M-Link | Admin Dashboard</title>


    <!-- =====================================================
         GOOGLE MATERIAL SYMBOLS
    ====================================================== -->

    <!-- GOOGLE MATERIAL SYMBOLS -->
<link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0"
>

<!-- DASHBOARD CSS -->
<link
    rel="stylesheet"
    href="admindashboard.css"
>

</head>


<body>


<div class="container">


    <!-- =====================================================
         SIDEBAR TOGGLE
    ====================================================== -->

    <input
        type="checkbox"
        id="sidebarToggle"
    >

    <label
        for="sidebarToggle"
        class="hamburger"
    >

        <span></span>
        <span></span>
        <span></span>

    </label>


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">


        <!-- LOGO -->

        <div class="logo">

            <img
                src="MakanaLogo.svg"
                alt="M-Link logo"
                class="sidebar-logo"
            >

            <h2>M-LINK</h2>

            <p>Makhanda Connect</p>

        </div>


        <!-- ADMIN PROFILE -->

        <div class="profile">

            <img
                src="profile.jpg"
                alt="Admin Profile"
            >

            <h3>
                <?= htmlspecialchars($adminName) ?>
            </h3>

            <p>
                <?= htmlspecialchars($adminRole) ?>
            </p>

        </div>


        <!-- =================================================
             NAVIGATION
        ================================================== -->

        <ul class="menu">


            <!-- DASHBOARD -->

            <li class="active">

                <a href="admindashboard.php">

                    <span class="material-symbols-outlined">
                        dashboard
                    </span>

                    <span>Dashboard</span>

                </a>

            </li>


            <!-- MUNICIPAL TICKETS -->

            <li>

                <a href="WardTicketManagement.php">

                    <span class="material-symbols-outlined">
                        location_on
                    </span>

                    <span>Municipal Tickets</span>

                </a>

            </li>


            <!-- WARDS -->

            <li>

                <a href="generateSummaryForm.php">

                    <span class="material-symbols-outlined">
                        map
                    </span>

                    <span>Wards</span>

                </a>

            </li>


            <!-- USER MANAGEMENT -->

            <li>

                <a href="UserM.php">

                    <span class="material-symbols-outlined">
                        group
                    </span>

                    <span>User Management</span>

                </a>

            </li>


            <!-- WARD COUNCILLORS -->

            <li>

                <a href="userDashboard.html">

                    <span class="material-symbols-outlined">
                        groups
                    </span>

                    <span>Ward Councillors</span>

                </a>

            </li>


            <!-- MANAGE CONTENT -->

            <li>

                <a href="manageContent.php">

                    <span class="material-symbols-outlined">
                        edit_note
                    </span>

                    <span>Manage Content</span>

                </a>

            </li>


            <!-- PROFILE -->

            <li>

                <a href="profile_settings.js">

                    <span class="material-symbols-outlined">
                        person
                    </span>

                    <span>Profile</span>

                </a>

            </li>


            <!-- SETTINGS -->

            <li>

                <a href="settings.html">

                    <span class="material-symbols-outlined">
                        settings
                    </span>

                    <span>Settings</span>

                </a>

            </li>


            <!-- LOGOUT -->

            <li>

                <a
                    href="homepage.php"
                    class="logout-link"
                >

                    <span class="material-symbols-outlined">
                        logout
                    </span>

                    <span>Logout</span>

                </a>

            </li>


        </ul>

    </aside>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <!-- =================================================
             TOP BAR
        ================================================== -->

        <header class="topbar">


            <!-- PAGE INFORMATION -->

            <div class="topbar-title">

                <h2>Admin Dashboard</h2>

                <p>
                    <?= htmlspecialchars($today) ?>
                </p>

            </div>


            <!-- TOP RIGHT -->

            <div class="topbar-right">


                <!-- NOTIFICATIONS -->

                <a
                    href="#"
                    class="top-icon"
                >

                    <span class="material-symbols-outlined">
                        notifications
                    </span>

                    <span class="notification-count">
                        3
                    </span>

                </a>


                <!-- MESSAGES -->

                <a
                    href="#"
                    class="top-icon"
                >

                    <span class="material-symbols-outlined">
                        mail
                    </span>

                    <span class="notification-count">
                        2
                    </span>

                </a>


                <!-- ADMIN -->

                <div class="user">

                    <img
                        src="profile.jpg"
                        alt="Admin"
                    >

                    <div>

                        <h4>
                            <?= htmlspecialchars($adminName) ?>
                        </h4>

                        <small>
                            <?= htmlspecialchars($adminRole) ?>
                        </small>

                    </div>

                </div>


            </div>

        </header>



        <!-- =================================================
             DASHBOARD CONTENT
        ================================================== -->

        <div class="dashboard-content">


            <!-- =================================================
                 WELCOME
            ================================================== -->

            <section class="dashboard-welcome">

                <div>

                    <p class="welcome-label">
                        M-LINK ADMINISTRATION
                    </p>

                    <h1>
                        <?= $greeting ?>,
                        <?= htmlspecialchars($adminName) ?>!
                    </h1>

                    <p>
                        Here's what's happening in Makhanda today.
                    </p>

                </div>

                <div class="welcome-icon">

                    <span class="material-symbols-outlined">
                        account_balance
                    </span>

                </div>

            </section>



            <!-- =================================================
                 STATISTICS
            ================================================== -->

            <section class="cards">


                <!-- TICKETS -->

                <div class="card">

                    <div class="card-icon">

                        <span class="material-symbols-outlined">
                            confirmation_number
                        </span>

                    </div>

                    <div class="card-information">

                        <p>
                            Municipal Tickets
                        </p>

                        <h2>
                            <?= number_format($totalTickets) ?>
                        </h2>

                        <span>
                            Total submitted tickets
                        </span>

                    </div>

                </div>



                <!-- USERS -->

                <div class="card">

                    <div class="card-icon">

                        <span class="material-symbols-outlined">
                            group
                        </span>

                    </div>

                    <div class="card-information">

                        <p>
                            User Management
                        </p>

                        <h2>
                            <?= number_format($totalUsers) ?>
                        </h2>

                        <span>
                            Registered accounts
                        </span>

                    </div>

                </div>



                <!-- ADMINS -->

                <div class="card">

                    <div class="card-icon">

                        <span class="material-symbols-outlined">
                            admin_panel_settings
                        </span>

                    </div>

                    <div class="card-information">

                        <p>
                            System Admins
                        </p>

                        <h2>
                            <?= number_format($totalAdmins) ?>
                        </h2>

                        <span>
                            Administrator accounts
                        </span>

                    </div>

                </div>



                <!-- NOTICES -->

                <div class="card">

                    <div class="card-icon">

                        <span class="material-symbols-outlined">
                            campaign
                        </span>

                    </div>

                    <div class="card-information">

                        <p>
                            Active Notices
                        </p>

                        <h2>
                            <?= number_format($totalNotices) ?>
                        </h2>

                        <span>
                            Community notices
                        </span>

                    </div>

                </div>



                <!-- PATCHES -->

                <div class="card">

                    <div class="card-icon">

                        <span class="material-symbols-outlined">
                            calendar_month
                        </span>

                    </div>

                    <div class="card-information">

                        <p>
                            Patch Management
                        </p>

                        <h2>
                            <?= number_format($totalPatches) ?>
                        </h2>

                        <span>
                            Recorded patches
                        </span>

                    </div>

                </div>


            </section>



            <!-- =================================================
                 LOWER DASHBOARD
            ================================================== -->

            <section class="dashboard-grid">


                <!-- =================================================
                     RECENT ADMIN ACTIVITY
                ================================================== -->

                <div class="dashboard-panel">

                    <div class="panel-header">

                        <div>

                            <h3>
                                Recent Admin Activity
                            </h3>

                            <p>
                                Latest administrator activity
                            </p>

                        </div>

                        <span class="material-symbols-outlined">
                            history
                        </span>

                    </div>


                    <div class="activity-list">


                        <?php if ($recentAdmins && $recentAdmins->num_rows > 0): ?>


                            <?php while ($activity = $recentAdmins->fetch_assoc()): ?>


                                <div class="activity-item">


                                    <div class="activity-icon">

                                        <span class="material-symbols-outlined">
                                            person
                                        </span>

                                    </div>


                                    <div class="activity-details">

                                        <strong>
                                            <?= htmlspecialchars($activity["username"]) ?>
                                        </strong>

                                        <span>
                                            Administrator account
                                        </span>

                                    </div>


                                    <div class="activity-date">

                                        <?php

                                        if (!empty($activity["last_login_date"])) {

                                            echo htmlspecialchars(
                                                date(
                                                    "d M Y, H:i",
                                                    strtotime(
                                                        $activity["last_login_date"]
                                                    )
                                                )
                                            );

                                        } else {

                                            echo "No login recorded";

                                        }

                                        ?>

                                    </div>


                                </div>


                            <?php endwhile; ?>


                        <?php else: ?>


                            <div class="empty-state">

                                <span class="material-symbols-outlined">
                                    info
                                </span>

                                <p>
                                    No recent administrator activity.
                                </p>

                            </div>


                        <?php endif; ?>


                    </div>

                </div>



                <!-- =================================================
                     QUICK ACTIONS
                ================================================== -->

                <div class="dashboard-panel">

                    <div class="panel-header">

                        <div>

                            <h3>
                                Quick Actions
                            </h3>

                            <p>
                                Frequently used administration tools
                            </p>

                        </div>

                        <span class="material-symbols-outlined">
                            bolt
                        </span>

                    </div>


                    <div class="quick-actions">


                        <a href="UserM.php">

                            <span class="material-symbols-outlined">
                                group
                            </span>

                            <div>

                                <strong>
                                    Manage Users
                                </strong>

                                <small>
                                    View and manage accounts
                                </small>

                            </div>

                        </a>


                        <a href="WardTicketManagement.php">

                            <span class="material-symbols-outlined">
                                confirmation_number
                            </span>

                            <div>

                                <strong>
                                    Municipal Tickets
                                </strong>

                                <small>
                                    Review submitted tickets
                                </small>

                            </div>

                        </a>


                        <a href="manageContent.php">

                            <span class="material-symbols-outlined">
                                edit_note
                            </span>

                            <div>

                                <strong>
                                    Manage Content
                                </strong>

                                <small>
                                    Update M-Link information
                                </small>

                            </div>

                        </a>


                        <a href="generateSummaryForm.php">

                            <span class="material-symbols-outlined">
                                map
                            </span>

                            <div>

                                <strong>
                                    Ward Management
                                </strong>

                                <small>
                                    View ward information
                                </small>

                            </div>

                        </a>


                    </div>

                </div>


            </section>


        </div>



        <!-- =================================================
             FOOTER
        ================================================== -->

        <footer>

            <p>
                © <?= date("Y") ?> M-LINK | Empowering Communities
            </p>

        </footer>


    </main>

</div>



<script src="dashboard.js"></script>


</body>

</html>