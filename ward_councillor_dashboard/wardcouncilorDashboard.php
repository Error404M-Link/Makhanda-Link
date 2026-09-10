<?php

session_start();

require_once "../LOGIN_FOLDER/db.php";


/* ==================================================
   CHECK IF USER IS LOGGED IN
================================================== */

if (
    !isset($_SESSION["username"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "ward_councillor"
) {
    header("Location: ../LOGIN_FOLDER/login.php");
    exit;
}


/* ==================================================
   GET LOGGED-IN COUNCILLOR DETAILS
================================================== */

$councillor_name = $_SESSION["councillor_name"] ?? "Ward Councillor";

$ward_id = isset($_SESSION["ward_id"])
    ? (int) $_SESSION["ward_id"]
    : 0;

$username = $_SESSION["username"];


/* ==================================================
   VALIDATE WARD
================================================== */

if ($ward_id < 1 || $ward_id > 14) {
    die("No valid ward has been assigned to this councillor.");
}


/* ==================================================
   STATUS IDs
================================================== */

$PENDING_STATUS = 1;
$INPROGRESS_STATUS = 2;
$RESOLVED_STATUS = 3;


/* ==================================================
   TOTAL OPEN FAULTS
   Pending + In Progress
================================================== */

$total_open = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM incident_ticket
    WHERE ward_id = ?
    AND status_id IN (?, ?)
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "iii",
        $ward_id,
        $PENDING_STATUS,
        $INPROGRESS_STATUS
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $total_open = (int)$row["total"];
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   REPORTED TODAY
================================================== */

$reported_today = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM incident_ticket
    WHERE ward_id = ?
    AND DATE(created_at) = CURDATE()
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $ward_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $reported_today = (int)$row["total"];
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   RESOLVED THIS MONTH
================================================== */

$resolved_this_month = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM incident_ticket
    WHERE ward_id = ?
    AND status_id = ?
    AND MONTH(created_at) = MONTH(CURDATE())
    AND YEAR(created_at) = YEAR(CURDATE())
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $ward_id,
        $RESOLVED_STATUS
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $resolved_this_month = (int)$row["total"];
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   TOTAL TICKETS
================================================== */

$total_tickets = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM incident_ticket
    WHERE ward_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $ward_id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $total_tickets = (int)$row["total"];
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   PENDING TICKETS
================================================== */

$pending_tickets = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM incident_ticket
    WHERE ward_id = ?
    AND status_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $ward_id,
        $PENDING_STATUS
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $pending_tickets = (int)$row["total"];
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   IN PROGRESS TICKETS
================================================== */

$in_progress_tickets = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM incident_ticket
    WHERE ward_id = ?
    AND status_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $ward_id,
        $INPROGRESS_STATUS
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $in_progress_tickets = (int)$row["total"];
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   RESOLVED TICKETS
================================================== */

$resolved_tickets = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM incident_ticket
    WHERE ward_id = ?
    AND status_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $ward_id,
        $RESOLVED_STATUS
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $resolved_tickets = (int)$row["total"];
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   RESOLUTION RATE
================================================== */

$resolution_rate = 0;

if ($total_tickets > 0) {
    $resolution_rate =
        ($resolved_tickets / $total_tickets) * 100;
}


/* ==================================================
   RECENT TICKETS
   ONLY FOR CURRENT COUNCILLOR'S WARD
================================================== */

$recent_tickets = [];

$sql = "
    SELECT
        incident_id,
        ward_id,
        priority,
        location_description,
        status_id,
        created_at
    FROM incident_ticket
    WHERE ward_id = ?
    ORDER BY created_at DESC
    LIMIT 5
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $ward_id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $recent_tickets[] = $row;
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   TOP PROBLEM STREETS
================================================== */

$street_data = [];

$sql = "
    SELECT
        location_description,
        COUNT(*) AS total
    FROM incident_ticket
    WHERE ward_id = ?
    GROUP BY location_description
    ORDER BY total DESC
    LIMIT 4
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $ward_id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $street_data[] = $row;
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   FIND HIGHEST STREET COUNT
================================================== */

$max_street_count = 1;

foreach ($street_data as $street) {

    if ($street["total"] > $max_street_count) {
        $max_street_count = $street["total"];
    }
}


/* ==================================================
   STATUS NAME FUNCTION
================================================== */

function getStatusName($status_id)
{
    switch ((int)$status_id) {

        case 1:
            return "Pending";

        case 2:
            return "In Progress";

        case 3:
            return "Resolved";

        default:
            return "Unknown";
    }
}


/* ==================================================
   STATUS CSS CLASS
================================================== */

function getStatusClass($status_id)
{
    switch ((int)$status_id) {

        case 1:
            return "pending-status";

        case 2:
            return "progress-status";

        case 3:
            return "resolved-status";

        default:
            return "pending-status";
    }
}


/* ==================================================
   CURRENT DATE
================================================== */

$current_date = date("d F Y");

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>M-LINK Ward Councillor Dashboard</title>

    <!-- CSS -->
    <link rel="stylesheet" href="Wardcouncillo.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<div class="container">

    <!-- ================= TOP NAVIGATION ================= -->

    <header class="topbar">

        <!-- Hamburger Button -->
        <button class="menu-btn" id="menuBtn">
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- Logo -->
        <div class="top-logo">
            <h2>M-LINK</h2>
            <p>Makhanda Connect</p>
        </div>

        <!-- Right Side -->
        <div class="top-right">

            <!-- Notifications -->
            <div class="icon">
                <i class="fa-regular fa-bell"></i>
                <span>3</span>
            </div>

            <!-- Messages -->
            <div class="icon">
                <i class="fa-regular fa-envelope"></i>
                <span>2</span>
            </div>

            <!-- User -->
            <div class="user">

                <img src="profile.jpg" alt="Profile">

                <div>
                    <h4><?php echo htmlspecialchars($councillor_name); ?></h4>
                    <small>Ward Councillor</small>
                </div>

            </div>

        </div>

    </header>


    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar" id="sidebar">

        <!-- Profile -->
        <div class="profile">

            <img src="profile.jpg" alt="Profile">

            <h3><?php echo htmlspecialchars($councillor_name); ?></h3>

            <p>Ward Councillor</p>

            <span>Ward <?php echo $ward_id; ?></span>

        </div>


        <!-- Navigation -->
        <ul class="menu">

            <li class="active">
                <i class="fa-solid fa-house"></i>
                <span>Dashboard</span>
            </li>


            <!-- COUNCILLOR -->
            <li>
                <i class="fa-solid fa-table-columns"></i>
                <span>Councillor Overview</span>
            </li>


            <!-- TICKETS -->
            <li class="menu-title">
                TICKETS MANAGEMENT
            </li>

            <li>
                <a href="WardTicketManagement.php">
                    <i class="fa-solid fa-ticket"></i>
                    <span>Ward Tickets Management</span>
                </a>
            </li>


            <!-- REPORTS -->
            <li class="menu-title">
                COMMUNICATION
            </li>

            <li>
                <a href="generateSummaryForm.php">
                    <i class="fa-solid fa-file-lines"></i>
                    <span>Generate Reports / Ward Summaries</span>
                </a>
            </li>


            <!-- ANALYTICS -->
            <li class="menu-title">
                ANALYTICS
            </li>

            <li>
                <a href="Analytics.php">
                    <i class="fa-solid fa-chart-column"></i>
                    <span>Analytics & Reports</span>
                </a>
            </li>


            <!-- OTHER -->
            <li class="menu-title">
                ACCOUNT
            </li>

            <li>
                <i class="fa-regular fa-bell"></i>
                <span>Notifications</span>
                <b class="badge">3</b>
            </li>

            <li>
                <i class="fa-regular fa-envelope"></i>
                <span>Messages</span>
                <b class="badge">2</b>
            </li>

            <li>
                <a href="profile_settings.js">
                    <i class="fa-solid fa-user"></i>
                    <span>Profile</span>
                </a>
            </li>

            <li>
                <a href="settings.html">
                    <i class="fa-solid fa-gear"></i>
                </a>
                <span>Settings</span>
            </li>

            <li>
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </li>

        </ul>

    </aside>


    <!-- ================= MAIN CONTENT ================= -->

    <main class="main-content" id="mainContent">


        <!-- WELCOME -->

        <section class="page-heading">

            <div>

                <h1>
                    Welcome back,
                    <?php echo htmlspecialchars($councillor_name); ?> 👋
                </h1>

                <p>
                    Here's what's happening in Ward
                    <?php echo $ward_id; ?> today.
                </p>

            </div>

            <div class="date">
                <i class="fa-regular fa-calendar"></i>
                <?php echo date("d F Y"); ?>
            </div>

        </section>


        <!-- ================= SUMMARY CARDS ================= -->

        <section class="cards">


            <!-- Open Faults -->

            <div class="card">

                <i class="fa-solid fa-triangle-exclamation"></i>

                <h4>Total Open Faults</h4>

                <h2><?php echo $total_open; ?></h2>

                <small>
                    Pending and In Progress tickets
                </small>

            </div>


            <!-- Reported -->

            <div class="card">

                <i class="fa-solid fa-file-circle-plus"></i>

                <h4>Reported Today</h4>

                <h2><?php echo $reported_today; ?></h2>

                <small>
                    New reports today
                </small>

            </div>


            <!-- Resolved -->

            <div class="card">

                <i class="fa-solid fa-circle-check"></i>

                <h4>Resolved This Month</h4>

                <h2><?php echo $resolved_this_month; ?></h2>

                <small>
                    Resolved Ward tickets
                </small>

            </div>


            <!-- Notices -->

            <div class="card">

                <i class="fa-solid fa-bullhorn"></i>

                <h4>Active Notices</h4>

                <h2>12</h2>

                <a href="#">
                    View all notices →
                </a>

            </div>


            <!-- Events -->

            <div class="card">

                <i class="fa-solid fa-calendar-days"></i>

                <h4>Upcoming Events</h4>

                <h2>8</h2>

                <a href="#">
                    View calendar →
                </a>

            </div>

        </section>


        <!-- ================= DASHBOARD INFORMATION ================= -->

        <section class="dashboard-grid">


            <!-- Tickets Status -->

            <div class="panel">

                <div class="panel-header">

                    <h3>Ward Tickets Overview</h3>

                    <a href="WardTicketManagement.php">
                        View all tickets →
                    </a>

                </div>

                <div class="ticket-status">

                    <div>
                        <span class="dot open"></span>
                        Open
                        <strong><?php echo $total_open; ?></strong>
                    </div>

                    <div>
                        <span class="dot progress"></span>
                        In Progress
                        <strong><?php echo $in_progress_tickets; ?></strong>
                    </div>

                    <div>
                        <span class="dot pending"></span>
                        Pending
                        <strong><?php echo $pending_tickets; ?></strong>
                    </div>

                    <div>
                        <span class="dot resolved"></span>
                        Resolved
                        <strong><?php echo $resolved_tickets; ?></strong>
                    </div>

                </div>

            </div>


            <!-- Problem Streets -->

            <div class="panel">

                <div class="panel-header">

                    <h3>Top Problem Streets</h3>

                    <a href="#">
                        Full report →
                    </a>

                </div>


                <?php if (!empty($street_data)): ?>

                    <?php foreach ($street_data as $street): ?>

                        <?php
                        $bar_width =
                            ($street["total"] / $max_street_count) * 100;
                        ?>

                        <div class="street">

                            <span>
                                <?php
                                echo htmlspecialchars(
                                    $street["location_description"]
                                );
                                ?>
                            </span>

                            <div class="bar">
                                <div style="width: <?php echo $bar_width; ?>%;"></div>
                            </div>

                            <strong>
                                <?php echo $street["total"]; ?>
                            </strong>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p>No location data available for this ward.</p>

                <?php endif; ?>


            </div>


            <!-- Resolution -->

            <div class="panel resolution">

                <h3>Resolution Rate</h3>

                <h2>
                    <?php echo number_format($resolution_rate, 1); ?>
                    <span>%</span>
                </h2>

                <p>
                    Current resolution rate for Ward
                    <?php echo $ward_id; ?>.
                </p>

                <div class="fake-chart">

                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>

                </div>

            </div>


        </section>


        <!-- ================= RECENT TICKETS ================= -->

        <section class="recent-tickets">

            <div class="panel-header">

                <h3>Recent Ward Tickets</h3>

                <a href="WardTicketManagement.php">
                    View all →
                </a>

            </div>


            <table>

                <thead>

                    <tr>

                        <th>Ticket ID</th>
                        <th>Priority</th>
                        <th>Street</th>
                        <th>Status</th>
                        <th>Reported On</th>

                    </tr>

                </thead>


                <tbody>

                <?php if (!empty($recent_tickets)): ?>

                    <?php foreach ($recent_tickets as $ticket): ?>

                        <tr>

                            <td>
                                #<?php echo htmlspecialchars($ticket["incident_id"]); ?>
                            </td>


                            <td>

                                <span class="priority <?php echo htmlspecialchars(strtolower($ticket["priority"])); ?>">

                                    <?php
                                    echo htmlspecialchars(
                                        $ticket["priority"]
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $ticket["location_description"]
                                );
                                ?>

                            </td>


                            <td>

                                <span class="status <?php echo getStatusClass($ticket["status_id"]); ?>">

                                    <?php
                                    echo getStatusName(
                                        $ticket["status_id"]
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php
                                echo date(
                                    "d M Y",
                                    strtotime($ticket["created_at"])
                                );
                                ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                <?php else: ?>

                    <tr>

                        <td colspan="5" style="text-align:center;">

                            No tickets have been reported
                            for Ward <?php echo $ward_id; ?>.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </section>


        <!-- ================= QUICK ACTIONS ================= -->

        <section class="quick-actions">

            <h3>Quick Actions</h3>

            <div class="actions">

                <!-- View Ward Tickets -->
                <button type="button"
                    onclick="window.location.href='WardTicketManagement.php'">

                    <i class="fa-solid fa-ticket"></i>
                    View Ward Tickets

                </button>


                <!-- Generate Ward Summary -->
                <button type="button"
                    onclick="window.location.href='wardSummary.html'">

                    <i class="fa-solid fa-file-lines"></i>
                    Generate Ward Summary

                </button>


                <button type="button"
                    onclick="window.location.href='../ManageContent - Notices/manageContent.html'">

                    <i class="fa-solid fa-ticket"></i>
                    Create Notices

                </button>


                <!-- View Analytics -->
                <button type="button"
                    onclick="window.location.href='Analytics.php'">

                    <i class="fa-solid fa-chart-column"></i>
                    View Analytics

                </button>

            </div>

        </section>


        <!-- FOOTER -->

        <footer>

            <p>
                © 2026 M-LINK | Empowering Communities
            </p>

        </footer>


    </main>

</div>


<!-- ================= JAVASCRIPT ================= -->

<script src="wardcouncillor.js"></script>

</body>
</html>