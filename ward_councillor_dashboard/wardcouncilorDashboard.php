<?php


require_once "../Error404Connection.php";
require_once "detailsOfTheLoggedInUser.php";

if (empty($_SESSION['username']) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "ward_councillor") {
    $msg = urlencode("Unauthorized access");
    header("Location: ../PUBLIC PAGES/login.php?error={$msg}");
    exit();
}

$ward_id = isset($_SESSION["ward_id"])
    ? (int)$_SESSION["ward_id"]
    : 0;

if ($ward_id < 1 || $ward_id > 14) {
    die("No valid ward has been assigned to this councillor.");
}


// ---- Resolve the councillor's ward_id ----
$stmt = $conn->prepare("SELECT ward_id FROM ward_councillor WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();
 
$ward_id = $row['ward_id'] ?? null;
 
$total_open = 0;
$in_progress_tickets = 0;
$pending_tickets = 0;
$resolved_tickets = 0;

$reported_today = 0;
$resolved_this_month = 0;

$street_data = [];
$max_street_count = 0;
$resolution_rate = 0.0;

$recent_activities = [];
 
if (!empty($ward_id)) {
 
    // ---- Ticket status counts ----
    $statusCountsQuery = "
        SELECT
            SUM(CASE WHEN it.status_id IS NULL THEN 1 ELSE 0 END) AS total_open,
            SUM(CASE WHEN ts.resolved IS NOT NULL THEN 1 ELSE 0 END) AS resolved_tickets,
            SUM(CASE WHEN ts.resolved IS NULL AND ts.InProgress IS NOT NULL THEN 1 ELSE 0 END) AS in_progress_tickets,
            SUM(CASE WHEN ts.resolved IS NULL AND ts.InProgress IS NULL AND ts.pending IS NOT NULL THEN 1 ELSE 0 END) AS pending_tickets
        FROM incident_ticket it
        LEFT JOIN ticket_status ts ON it.status_id = ts.status_id
        WHERE it.ward_id = ?
    ";
    $stmt = $conn->prepare($statusCountsQuery);
    $stmt->bind_param("i", $ward_id);
    $stmt->execute();
    $statusResult = $stmt->get_result();
    $statusRow = $statusResult->fetch_assoc();
    $stmt->close();
 
    $total_open          = (int) ($statusRow['total_open'] ?? 0);
    $resolved_tickets     = (int) ($statusRow['resolved_tickets'] ?? 0);
    $in_progress_tickets  = (int) ($statusRow['in_progress_tickets'] ?? 0);
    $pending_tickets      = (int) ($statusRow['pending_tickets'] ?? 0);
 
    // ---- Top problem streets (top 5 by ticket count) ----
    $streetQuery = "
        SELECT location_description, COUNT(*) AS total
        FROM incident_ticket
        WHERE ward_id = ?
        GROUP BY location_description
        ORDER BY total DESC
        LIMIT 5
    ";
    $stmt = $conn->prepare($streetQuery);
    $stmt->bind_param("i", $ward_id);
    $stmt->execute();
    $streetResult = $stmt->get_result();
 
    while ($streetRow = $streetResult->fetch_assoc()) {
        $street_data[] = $streetRow;
    }
    $stmt->close();
 
    if (!empty($street_data)) {
        $max_street_count = max(array_column($street_data, 'total'));
    }
        // ---- Reports Today ----
    $reportedTodayQuery = "
        SELECT COUNT(*) AS reported_today
        FROM incident_ticket
        WHERE ward_id = ?
        AND DATE(created_at) = CURDATE()
    ";

    $stmt = $conn->prepare($reportedTodayQuery);
    $stmt->bind_param("i", $ward_id);
    $stmt->execute();

    $reportedTodayResult = $stmt->get_result();
    $reportedTodayRow = $reportedTodayResult->fetch_assoc();

    $reported_today = (int) ($reportedTodayRow['reported_today'] ?? 0);

    $stmt->close();


    // ---- Resolved This Month ----
    $resolvedThisMonthQuery = "
        SELECT COUNT(*) AS resolved_this_month
        FROM incident_ticket it
        LEFT JOIN ticket_status ts
            ON it.status_id = ts.status_id
        WHERE it.ward_id = ?
        AND ts.resolved IS NOT NULL
        AND MONTH(ts.resolved) = MONTH(CURDATE())
        AND YEAR(ts.resolved) = YEAR(CURDATE())
    ";

    $stmt = $conn->prepare($resolvedThisMonthQuery);
    $stmt->bind_param("i", $ward_id);
    $stmt->execute();

    $resolvedThisMonthResult = $stmt->get_result();
    $resolvedThisMonthRow = $resolvedThisMonthResult->fetch_assoc();

    $resolved_this_month = (int) ($resolvedThisMonthRow['resolved_this_month'] ?? 0);

    $stmt->close();
 
    // ---- Resolution rate ----
    $total_tickets = $total_open + $in_progress_tickets + $pending_tickets + $resolved_tickets;
 
    $resolution_rate = $total_tickets > 0
        ? ($resolved_tickets / $total_tickets) * 100
        : 0.0;


    // ---- Recent activity: newest 6 events (reported / in progress / resolved) ----
    $activityQuery = "
        (SELECT 'reported' AS activity_type,
                it.location_description AS location_description,
                it.created_at AS activity_time
         FROM incident_ticket it
         WHERE it.ward_id = ?)

        UNION ALL

        (SELECT 'in_progress',
                it.location_description,
                ts.InProgress
         FROM incident_ticket it
         INNER JOIN ticket_status ts ON it.status_id = ts.status_id
         WHERE it.ward_id = ?
           AND ts.InProgress IS NOT NULL)

        UNION ALL

        (SELECT 'resolved',
                it.location_description,
                ts.resolved
         FROM incident_ticket it
         INNER JOIN ticket_status ts ON it.status_id = ts.status_id
         WHERE it.ward_id = ?
           AND ts.resolved IS NOT NULL)

        ORDER BY activity_time DESC
        LIMIT 3
    ";
    $stmt = $conn->prepare($activityQuery);
    $stmt->bind_param("iii", $ward_id, $ward_id, $ward_id);
    $stmt->execute();
    $activityResult = $stmt->get_result();

    while ($activityRow = $activityResult->fetch_assoc()) {
        $recent_activities[] = $activityRow;
    }
    $stmt->close();
}


// ---- Helpers for the Recent Activity card ----

// "5 min ago", "2 hours ago", "Yesterday", or a date for anything older than a week
function timeAgo(?string $datetime): string
{
    if (empty($datetime)) {
        return '';
    }

    $time = strtotime($datetime);
    if ($time === false) {
        return '';
    }

    // Date-only values (no time of day) can't be shown as "x hours ago"
    if (strlen($datetime) === 10) {
        $days = (int) floor((strtotime(date('Y-m-d')) - $time) / 86400);
        if ($days <= 0) return 'Today';
        if ($days === 1) return 'Yesterday';
        if ($days < 7) return $days . ' days ago';
        return date('d M Y', $time);
    }

    $diff = time() - $time;

    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return intdiv($diff, 60) . ' min ago';
    if ($diff < 86400)  { $h = intdiv($diff, 3600);  return $h . ($h === 1 ? ' hour ago' : ' hours ago'); }
    if ($diff < 604800) { $d = intdiv($diff, 86400); return $d . ($d === 1 ? ' day ago' : ' days ago'); }

    return date('d M Y', $time);
}

$activity_meta = [
    'reported'    => ['icon' => 'ticket.svg', 'label' => 'New ticket reported', 'class' => 'is-reported'],
    'in_progress' => ['icon' => 'inprogress.svg', 'label' => 'Ticket in progress',  'class' => 'is-progress'],
    'resolved'    => ['icon' => 'resolved.svg',   'label' => 'Ticket resolved',     'class' => 'is-resolved'],
];


$current_date = date("d F Y");
/* 
UNREAD NOTIFICATIONS */

$unreadNotificationCount = 0;

$notification_stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count
    FROM notification
    WHERE username = ?
    AND notification_type = 'new_ticket'
    AND is_read = 0
");

if ($notification_stmt) {

    $notification_stmt->bind_param("s", $username);
    $notification_stmt->execute();

    $notification_result = $notification_stmt->get_result();

    if ($notification_result && $notification_row = $notification_result->fetch_assoc()) {
        $unreadNotificationCount =
            (int)($notification_row['unread_count'] ?? 0);
    }

    $notification_stmt->close();
}

/* UNREAD MESSAGES (for the sidebar badge)*/

$unread_count = 0;

$unread_stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count
    FROM internal_messages
    WHERE receiver_username = ?
    AND is_read = 0
");

if ($unread_stmt) {
    $unread_stmt->bind_param("s", $username);
    $unread_stmt->execute();
    $unread_data  = $unread_stmt->get_result()->fetch_assoc();
    $unread_count = (int) ($unread_data['unread_count'] ?? 0);
    $unread_stmt->close();
}


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>M-LINK Ward Councillor Dashboard</title>

    <!-- Restores the sidebar state before the page draws (no flash) -->
    <script>
    try { if (localStorage.getItem("sidebarState") === "closed") document.documentElement.classList.add("sidebar-collapsed"); } catch (e) {}
    </script>

   <link rel="stylesheet" href="Wardcouncillo.css?v=<?php echo filemtime(__DIR__ . '/Wardcouncillo.css'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="container"> 
     <header class="topbar"> <!-- HAMBURGER -->
         <button class="menu-btn" id="menuBtn" type="button" aria-label="Toggle menu" aria-controls="sidebar" aria-expanded="true" > <i class="fa-solid fa-bars"></i> </button> 
         <!-- M-LINK LOGO --> <a href="logout.php" class="logo-link" title="M-LINK Homepage" >
            <img src="makanaLogo.svg" alt="M-Link logo" class="topbar-logo" > </a>
           <div class="top-right"> 
            
           <!-- NOTIFICATIONS -->
             <a href="notifications.php" class="topbar-icon-btn" title="Notifications" >
                <span class="notification-icon-wrapper"> 
                <img src="../icons/add_alert.svg" alt="" class="topbar-icon-img" > 
                <?php if ($unreadNotificationCount > 0): ?> <span class="notification-badge"> <?php echo $unreadNotificationCount > 99 ? "99+" : $unreadNotificationCount; ?> </span> <?php endif; ?> </span> <span class="topbar-icon-label"> Notifications </span> </a> <!-- MESSAGES --> <a href="messages.php" class="topbar-icon-btn" title="Messages" > <img src="../icons/message.svg" alt="" class="topbar-icon-img" > <span class="topbar-icon-label"> Messages </span> </a> 
                     <div class="user"> <img src="<?php echo htmlspecialchars( $userData['image_path'] ?? 'profile.jpg' ); ?>" alt="Profile Picture" id="profileImage" > <div class="user-details"> <h4> <?php echo htmlspecialchars($fullName); ?> </h4> <small> Ward Councillor </small> </div> </div> </div> </header> <!-- ===================================================== SIDEBAR ====================================================== --> <?php $activePage = 'dashboard'; include 'ward_sidebar.php'; ?>



    <!--  MAIN CONTENT  -->

    <main class="main-content" id="mainContent">


        <!-- WELCOME -->

        <section class="page-heading">

            <div>

                <h1>
                    Welcome back,
                    <?php echo htmlspecialchars($fullName); ?>
                </h1>

                <p>
                    Here's what's happening in Ward
                    <?php echo $ward_id; ?> today.
                </p>

            </div>

            <div class="date">
                <i class="fa-regular fa-calendar"></i>
                <?php echo $current_date; ?>
            </div>

        </section>


        <!--  SUMMARY CARDS  -->

        <section class="cards">


            <!-- Open Faults -->

            <div class="card">

                <span class="card-icon" style="--icon: url('../icons/problem.svg')"></span>

                <h4>Total Open Faults</h4>

                <h2><?php echo $total_open; ?></h2>

                <small>
                    Pending and In Progress tickets
                </small>

            </div>


            <!-- Reported -->

            <div class="card">

                <span class="card-icon" style="--icon: url('../icons/inbox_text.svg.svg')"></span>

                <h4>Reported Today</h4>

                <h2><?php echo $reported_today; ?></h2>

                <small>
                    New reports today
                </small>

            </div>


            <!-- Resolved -->

            <div class="card">

                <span class="card-icon" style="--icon: url('../icons/resolved.svg')"></span>

                <h4>Resolved This Month</h4>

                <h2><?php echo $resolved_this_month; ?></h2>

                <small>
                    Resolved Ward tickets
                </small>

            </div>


            <!-- Notices -->

            <div class="card">

                <span class="card-icon" style="--icon: url('../icons/info.svg')"></span>

                <h4>Active Notices</h4>

                <h2>12</h2>

                <a href='../ManageContent - Notices/manageContent.php'>
                    View all notices →
                </a>

            </div>
        </section>


        <!-- QUICK ACTIONS + RECENT ACTIVITY  -->

        <section class="home-grid">


            <!-- Quick Actions -->
<div class="panel">

    <div class="panel-header">
        <h3>Quick Actions</h3>
    </div>

    <div class="quick-actions">

        <a href="../USER_DASHBOARD/reportFaultTicketForm.php" class="quick-action">
            <span class="quick-action-icon" style="--icon: url('../icons/problem.svg')"></span>
            <span class="quick-action-text">
                <strong>Report a Fault</strong>
                <small>Log a new fault in your ward</small>
            </span>
        </a>

        <a href="WardTicketManagement.php" class="quick-action">
            <span class="quick-action-icon" style="--icon: url('../icons/inprogress.svg')"></span>
            <span class="quick-action-text">
                <strong>View tickets</strong>
                <small>Track and update ward tickets</small>
            </span>
        </a>

        <a href="../ManageContent - Notices/manageContent.php" class="quick-action">
            <span class="quick-action-icon" style="--icon: url('../icons/manage_accounts.svg')"></span>
            <span class="quick-action-text">
                <strong>View notices</strong>
                <small>Manage active notices</small>
            </span>
        </a>

    </div>
</div>


            <!-- Recent Activity -->
            <div class="panel">

                <div class="panel-header">
                    <h3>Recent Activity</h3>
                    <a href="WardTicketManagement.php">View all tickets</a>
                </div>

                <?php if (empty($recent_activities)): ?>

                    <p class="activity-empty">
                        No activity yet. New reports and status changes in Ward
                        <?php echo $ward_id; ?> will appear here.
                    </p>

                <?php else: ?>

                    <ul class="activity-list">

                        <?php foreach ($recent_activities as $activity): ?>

                            <?php
                            $meta = $activity_meta[$activity['activity_type']]
                                ?? $activity_meta['reported'];

                            $location = trim(
                                (string)($activity['location_description'] ?? '')
                            );

                            if ($location === '') {
                                $location = 'No location given';
                            }
                            ?>

                            <li class="activity-item <?php echo htmlspecialchars($meta['class']); ?>">

    <span class="activity-icon"
          style="--icon: url('../icons/<?php echo htmlspecialchars($meta['icon']); ?>')"></span>

    <div class="activity-text">
        <strong><?php echo htmlspecialchars($meta['label']); ?></strong>
        <span><?php echo htmlspecialchars($location); ?></span>
    </div>

    <span class="activity-time">
        <?php echo htmlspecialchars(timeAgo($activity['activity_time'])); ?>
    </span>

</li>

                        <?php endforeach; ?>

                    </ul>

                <?php endif; ?>

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


<!-- Sidebar open/close button (shared by all ward councillor pages) -->
<script src="ward_sidebar.js"></script>

</body>
</html>