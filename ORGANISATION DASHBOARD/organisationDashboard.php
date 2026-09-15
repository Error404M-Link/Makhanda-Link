<?php

// ============================================================
// START SESSION
// ============================================================

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


// ============================================================
// DATABASE CONNECTION
// ============================================================

require_once "Error404Connection.php";


// ============================================================
// LOAD LOGGED-IN ORGANISATION
// ============================================================

require_once "detailsOfTheLoggedInOrganisation.php";


// ============================================================
// NO CACHE
// ============================================================

require_once "noCache.php";


// ============================================================
// SORTING
// ============================================================

$sortOrder = 'DESC';


// ============================================================
// TICKET FILTERS
// ============================================================

$whereClauses = [];
$bindTypes = "";
$bindParams = [];


// ------------------------------------------------------------
// SERVICE FILTER
// ------------------------------------------------------------

if (!empty($_GET['service_id'])) {

    $whereClauses[] = "i.service_id = ?";

    $bindTypes .= "i";

    $bindParams[] = (int)$_GET['service_id'];
}


// ------------------------------------------------------------
// STATUS FILTER
// ------------------------------------------------------------

if (!empty($_GET['status'])) {

    if ($_GET['status'] === 'resolved') {

        $whereClauses[] = "ts.resolved IS NOT NULL";

    } elseif ($_GET['status'] === 'progress') {

        $whereClauses[] =
            "ts.InProgress IS NOT NULL
             AND ts.resolved IS NULL";

    } elseif ($_GET['status'] === 'open') {

        $whereClauses[] =
            "ts.pending IS NOT NULL
             AND ts.InProgress IS NULL
             AND ts.resolved IS NULL";
    }
}


// ============================================================
// WHERE CLAUSE
// ============================================================

if (empty($whereClauses)) {

    $whereString = "1=1";

} else {

    $whereString = implode(" AND ", $whereClauses);
}


// ============================================================
// TICKET STATISTICS
// ============================================================
//
// IMPORTANT:
// The organisation table shown in your screenshot does NOT
// contain ward_id.
//
// Therefore, this organisation dashboard does NOT use
// $wardId to filter tickets.
// ============================================================

$totalTickets = 0;
$inProgressTickets = 0;
$resolvedTickets = 0;


// ------------------------------------------------------------
// GET TICKET STATISTICS
// ------------------------------------------------------------

$statsSql = "
    SELECT
        COUNT(*) AS total_tickets,

        SUM(
            CASE
                WHEN ts.InProgress IS NOT NULL
                AND ts.resolved IS NULL
                THEN 1
                ELSE 0
            END
        ) AS in_progress_tickets,

        SUM(
            CASE
                WHEN ts.resolved IS NOT NULL
                THEN 1
                ELSE 0
            END
        ) AS resolved_tickets

    FROM incident_ticket i

    INNER JOIN ticket_status ts
        ON i.status_id = ts.status_id

    WHERE 1=1
";


$statsStmt = $conn->prepare($statsSql);

if (!$statsStmt) {

    die("Database Error: " . $conn->error);
}


$statsStmt->execute();

$statsResult = $statsStmt->get_result();


if ($row = $statsResult->fetch_assoc()) {

    $totalTickets =
        (int)($row['total_tickets'] ?? 0);

    $inProgressTickets =
        (int)($row['in_progress_tickets'] ?? 0);

    $resolvedTickets =
        (int)($row['resolved_tickets'] ?? 0);
}


$statsStmt->close();


// ============================================================
// RETRIEVE TICKETS
// ============================================================

$sql = "
    SELECT

        i.incident_id,

        ms.service_type,

        ad.street_name,

        w.ward_name,

        i.date_reported,

        i.incident_description,

        i.location_description,

        i.latitude,

        i.longitude,

        ts.pending,

        ts.InProgress,

        ts.resolved

    FROM incident_ticket i

    LEFT JOIN municipal_service ms
        ON i.service_id = ms.service_id

    LEFT JOIN address_directory ad
        ON i.address_id = ad.address_id

    LEFT JOIN ward w
        ON i.ward_id = w.ward_id

    INNER JOIN ticket_status ts
        ON i.status_id = ts.status_id

    WHERE " . $whereString . "

    ORDER BY i.date_reported " . $sortOrder;


$userTickets = [];


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die("Database Error: " . $conn->error);
}


// ------------------------------------------------------------
// BIND FILTER PARAMETERS ONLY IF FILTERS EXIST
// ------------------------------------------------------------

if (!empty($bindParams)) {

    $stmt->bind_param(
        $bindTypes,
        ...$bindParams
    );
}


$stmt->execute();

$res = $stmt->get_result();


while ($row = $res->fetch_assoc()) {


    // --------------------------------------------------------
    // DETERMINE STATUS
    // --------------------------------------------------------

    if (!empty($row['resolved'])) {

        $statusLabel = 'Resolved';

        $statusClass = 'resolved-status';

    } elseif (!empty($row['InProgress'])) {

        $statusLabel = 'In Progress';

        $statusClass = 'progress-status';

    } else {

        $statusLabel = 'Open';

        $statusClass = 'open-status';
    }


    // --------------------------------------------------------
    // FORMAT DATE
    // --------------------------------------------------------

    $formattedDate =
        date(
            "d M Y, H:i",
            strtotime($row['date_reported'])
        );


    // --------------------------------------------------------
    // STORE TICKET
    // --------------------------------------------------------

    $userTickets[] = [

        'id' =>
            $row['incident_id'],

        'service' =>
            $row['service_type']
            ?? 'General Fault',

        'street' =>
            $row['street_name']
            ?? 'Location Unspecified',

        'ward' =>
            $row['ward_name']
            ?? 'N/A',

        'description' =>
            $row['incident_description']
            ?? 'No additional description provided.',

        'location_desc' =>
            $row['location_description']
            ?? 'N/A',

        'latitude' =>
            $row['latitude'],

        'longitude' =>
            $row['longitude'],

        'date' =>
            $formattedDate,

        'statusLabel' =>
            $statusLabel,

        'statusClass' =>
            $statusClass
    ];
}


$stmt->close();


// ============================================================
// ORGANISATION DISPLAY VALUES
// ============================================================
//
// These values come from
// detailsOfTheLoggedInOrganisation.php
// ============================================================

$profileImg = 'profile.jpg';

$displayName =
    !empty($organisationName)
        ? $organisationName
        : $firstName . ' ' . $surname;

$displayRole =
    !empty($userRole)
        ? $userRole
        : 'Organisation';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>M-LINK Organisation Dashboard</title>

    <link rel="stylesheet" href="residence.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
    <input type="checkbox" id="sidebarToggle">
    <div class="container">
        <!-- ================= TOP BAR ================= -->
        
        <header class="topbar">
            
            <!-- Hamburger -->
            <label for="sidebarToggle" class="menu-btn">
                <span></span>
                <span></span>
                <span></span>
            </label>

            <!-- Logo -->
            <div class="top-logo">
                <h2>M-LINK</h2>
                <p>Makhanda Connect</p>
            </div>

            <!-- Right side -->
            <!-- <div class="top-right">
                <div class="icon">
                    <span>2</span>
                </div>
                <div class="profile">
                    <img src="<?php echo $profileImg; ?>" alt="Resident Profile">
                    <h3><?php echo $fullName; ?></h3>
                    <p><?php echo $userRole; ?></p>
                    <span><?php echo $wardNum; ?></span>
                </div>
            </div> -->
        </header>

        <!-- ####### NAVIGATION/SIDEBAR ####### -->
        <aside class="sidebar" id="sidebar">
            <!-- Profile -->
            <div class="profile">

          <img src="<?php echo htmlspecialchars($profileImg); ?>" alt="Organisation Profile">
          <h3> <?php echo htmlspecialchars($organisationName); ?>
          </h3>
          <p><?php echo htmlspecialchars($displayRole); ?>
          </p>
          <span> <?php echo htmlspecialchars($organisationContact); ?>
          </span>
    </div>

            <!-- Navigation -->
            <ul class="menu">
                <!-- DASHBOARD -->
                <li class="active">
                    <span>
                        Organisation Dashboard
                    </span>
                </li>
                <!-- TICKETS -->
                <li class="menu-title">
                    TICKETS
                </li>
                <li>
                    <span>
                        <a href="reportFaultTicketForm.php" style="color: inherit; text-decoration: none;">
                            Create Ticket / Report a Fault
                        </a>
                    </span>
                </li>
                <li>
                    <span>
                        <a href="ticketHistory.php" style="color: inherit; text-decoration: none;"> Ticket History </a>
                    </span>
                </li>
                <!-- ACCOUNT -->

                <li class="menu-title">
                    ACCOUNT
                </li>
                <li>
                    <span>
                        <a href="settings.php" style="color: inherit; text-decoration: none;">Profile</a>
                    </span>
                </li>
                <!-- COMMUNITY -->
                <li class="menu-title">
                    COMMUNITY
                </li>
                <li>
                    <span>
                        Reviews & FAQ
                    </span>
                </li>
                <li>
                    <span>
                        Ward Information & Notices
                    </span>
                </li>
                <!-- LOGOUT -->
                <li class="logout">
                    <span>
                        <a href="logout.php">
                            Logout
                        </a>
                    </span>
                </li>
            </ul>
        </aside>

        <!-- ================= MAIN CONTENT ================= -->

        <main class="main-content">
            <!-- PAGE HEADING -->

            <section class="page-heading">
                <div>
                    <h1> Welcome back, <?php echo htmlspecialchars($organisationName); ?></h1>
                    <p>Here's what's happening in your community.</p>
                </div>
            </section>

            <!-- ================= SUMMARY ================= -->
            <section class="cards">
                <!-- Total Tickets in the ward -->
                <div class="card">
                    <h4>Ward Tickets</h4>
                    <h2><?php echo $totalTickets; ?></h2>
                    <p>
                        Total tickets in your ward
                    </p>
                </div>

                <!-- In Progress -->
                <div class="card">
                    <h4>In Progress</h4>
                    <h2><?php echo $inProgressTickets; ?></h2>
                    <p>
                        Currently being handled
                    </p>
                </div>

                <!-- Resolved -->
                <div class="card">
                    <h4>Resolved</h4>
                    <h2><?php echo $resolvedTickets; ?></h2>
                    <p>
                        Successfully resolved
                    </p>
                </div>

            </section>

            <!-- ########################## DASHBOARD CONTENT ##################################### -->

            <section class="dashboard-grid">

                        <!-- MY TICKETS -->
                <div class="panel tickets-panel">
                    <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3>Reported Incidents (<?php echo count($userTickets); ?>)</h3>
                        <a href="ticketHistory.php" class="view-all-btn">View All →</a>
                    </div>

                    <div class="ticket-list">
                        <?php if (!empty($userTickets)): ?>
                            <?php foreach ($userTickets as $ticket): ?>
                                <div class="ticket clickable-ticket" 
                                    data-id="<?php echo htmlspecialchars($ticket['id']); ?>"
                                    data-service="<?php echo htmlspecialchars($ticket['service']); ?>"
                                    data-description="<?php echo htmlspecialchars($ticket['description']); ?>"
                                    data-street="<?php echo htmlspecialchars($ticket['street']); ?>"
                                    data-ward="<?php echo htmlspecialchars($ticket['ward']); ?>"
                                    data-location-desc="<?php echo htmlspecialchars($ticket['location_desc']); ?>"
                                    data-lat="<?php echo htmlspecialchars($ticket['latitude'] ?? ''); ?>"
                                    data-lng="<?php echo htmlspecialchars($ticket['longitude'] ?? ''); ?>"
                                    data-date="<?php echo htmlspecialchars($ticket['date']); ?>"
                                    data-status-label="<?php echo htmlspecialchars($ticket['statusLabel']); ?>"
                                    data-status-class="<?php echo htmlspecialchars($ticket['statusClass']); ?>">
                                    
                                    <div class="ticket-info">
                                        <h4>#ID-<?php echo htmlspecialchars($ticket['id']); ?></h4>
                                        <p><strong><?php echo htmlspecialchars($ticket['service']); ?></strong> — <?php echo htmlspecialchars($ticket['description']); ?></p>
                                        <small>
                                            <?php echo htmlspecialchars($ticket['street']); ?> 
                                            (Ward: <?php echo htmlspecialchars($ticket['ward']); ?>) · 
                                            <?php echo htmlspecialchars($ticket['date']); ?>
                                        </small>
                                    </div>
                                    <span class="status <?php echo htmlspecialchars($ticket['statusClass']); ?>">
                                        <?php echo htmlspecialchars($ticket['statusLabel']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="ticket" style="padding: 20px; text-align: center;">
                                <p style="color: #888; margin: 0;">No tickets found matching your filter criteria.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!--#### FAULT DEFAULT MODAL #####-->
                <div class="modal-overlay" id="ticketModal">
                    <div class="modal-card">
                        <button class="modal-close-btn" id="closeModalBtn">&times;</button>
                        
                        <!-- Top Map Container (Hidden if coordinates do not exist) -->
                        <div id="modalMap"></div>

                        <div class="modal-body">
                            <div class="modal-header-row">
                                <div>
                                    <h3 id="modalTicketId">#ID-0000</h3>
                                    <span id="modalServiceType" style="font-weight: 600; color: #4a5568;"></span>
                                </div>
                                <span id="modalStatusBadge" class="status">--</span>
                            </div>

                            <div class="detail-group">
                                <label>Incident Description</label>
                                <p id="modalDescription">--</p>
                            </div>

                            <div class="detail-group">
                                <label>Exact Location / Address</label>
                                <p id="modalAddress">--</p>
                            </div>

                            <div class="detail-group" id="locationDescGroup">
                                <label>Location Landmark / Directions</label>
                                <p id="modalLocationDesc">--</p>
                            </div>

                            <div class="detail-group">
                                <label>Date Reported</label>
                                <p id="modalDate">--</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= LATEST NOTICE ================= -->
                <div class="panel notice-panel">
                    <div class="panel-header">
                        <h3>
                            Latest Notice
                        </h3>
                    </div>
                    <div class="notice-alert">
                        <div>
                            <h4>
                                Water Interruption
                            </h4>
                            <p>
                                Residents in High Street and
                                surrounding areas may experience
                                temporary water interruptions.
                            </p>
                        </div>
                    </div>

                    <div class="notice-date">
                        <span>
                            17 August 2026
                        </span>
                    </div>
                    <a href="#" class="view-link">
                        View all ward notices →
                    </a>
                </div>
            </section>

           <!-- ================= ORGANISATION INFORMATION ================= -->

<section class="ward-panel">

    <div class="ward-content">

        <h3>
            Organisation Information
        </h3>

        <p>
            <strong>
                <?php echo htmlspecialchars($organisationName); ?>
            </strong>
        </p>

        <p>
            Username:
            <?php echo htmlspecialchars($organisationUsername); ?>
        </p>

        <p>
            Address:
            <?php echo htmlspecialchars($organisationAddress); ?>
        </p>

        <p>
            Contact:
            <?php echo htmlspecialchars($organisationContact); ?>
        </p>

        <p>
            Account Type:
            <?php echo htmlspecialchars($displayRole); ?>
        </p>

    </div>

    <a
        href="settings.php"
        class="ward-button"
    >
        Manage Organisation
    </a>

</section>

            <!-- ================= QUICK REPORT ================= -->

            <section class="report-section">
                <div>
                    <h2>
                        Have you noticed a problem?
                    </h2>
                    <p>
                        Report a municipal fault and help
                        improve your community.
                    </p>
                </div>
                <a href="reportFaultTicketForm.php">
                    <button class="report-button">
                        Report a Fault
                    </button>
                </a>
            </section>

            <footer>
                <p>
                    © 2026 M-LINK | Empowering Communities
                </p>
            </footer>
        </main>
    </div>
    <script src="residence.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="moreTicketDetails.js"></script>
    <script src="forceReload.js"></script>
</body>
</html>