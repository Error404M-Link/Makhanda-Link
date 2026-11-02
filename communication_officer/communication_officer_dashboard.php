<?php

session_start();
if(!isset($_SESSION["username"])){
    header("location: ../PUBLIC PAGES/login.php");
    exit;
}

date_default_timezone_set('Africa/Johannesburg');

require_once "Error404Connection.php";
require_once "../PUBLIC PAGES/publicFaultBoardMainLogic.php";

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function getStaffGreeting(string $userName): string
{
    $currentHour = (int)date('G');

    if ($currentHour >= 5 && $currentHour < 12) {$timeGreeting = 'Good Morning';
    } elseif ($currentHour >= 12 && $currentHour < 18) {$timeGreeting = 'Good Afternoon';
    } else {
        $timeGreeting = 'Good Evening';
    }

    return sprintf('%s, %s', $timeGreeting, e($userName));
}

$sessionUserId   =$_SESSION['username']   ?? $_SESSION['profile_id']    ?? $_SESSION['User_role'] ?? null;
$sessionUsername = $_SESSION['username'] ?? $_SESSION['first_name'] ?? $_SESSION['surname']    ?? $_SESSION['profile_id'] ?? null;

$loggedInUser = '';
$userProfilePic = '';
 
/**
 * Display-name helpers: a login email/username with an "@" is never shown as the name.
 */
if (!function_exists('dashIsEmailLike')) {
    function dashIsEmailLike($value): bool
    {
        return strpos((string)$value, '@') !== false;
    }
}
if (!function_exists('dashPickDisplayName')) {
    function dashPickDisplayName(...$candidates): string
    {
        foreach ($candidates as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate !== '' && !dashIsEmailLike($candidate)) {
                return $candidate;
            }
        }
        return '';
    }
}
 
// 2a. Fetch the user's ACTUAL real name directly from the database (by username or profile_id)
if ($sessionUserId) {
    try {
        // If session holding numeric ID, query profile_id; otherwise query username column
        if (is_numeric($sessionUserId)) {
            $stmt = $conn->prepare("SELECT u.surname, u.first_Name, u.username, u.profile_id, p.image_path FROM user u LEFT JOIN profile_images p ON u.profile_id = p.profile_id WHERE u.profile_id = ? LIMIT 1");
            $stmt->bind_param("i", $sessionUserId);
        } else {
            $stmt = $conn->prepare("SELECT u.surname, u.first_Name, u.username, u.profile_id, p.image_path FROM user u LEFT JOIN profile_images p ON u.profile_id = p.profile_id WHERE u.username = ? LIMIT 1");
            $stmt->bind_param("s", $sessionUserId); // Fix: Use "s" for string usernames
        }

        if ($stmt) {
            $stmt->execute();
            $res = $stmt->get_result();
            if ($userRow = $res->fetch_assoc()) {
                // Prioritize First Name + Surname over isolated fields
                $fullName = trim(($userRow['first_Name'] ?? '') . ' ' . ($userRow['surname'] ?? ''));
                
                $loggedInUser = dashPickDisplayName(
                    $fullName,
                    $userRow['first_Name'] ?? '',
                    $userRow['surname'] ?? '',
                    $userRow['username'] ?? ''
                );

                if (!empty($userRow['image_path'])) {
                    $userProfilePic = $userRow['image_path'];
                }
            }
            $stmt->close();
        }
    } catch (Throwable $nameLookupError) {
        // Fall back gracefully if error occurs
    }
}
 
// 3. Fallback: a non-email session value, otherwise a neutral label (never the raw email address)
if ($loggedInUser === '') {
    $loggedInUser = dashPickDisplayName($sessionUsername, $_SESSION['name'] ?? '') ?: 'Officer';
}
 
$staffGreeting = getStaffGreeting($loggedInUser);

// Database Dashboard Queries
$totalIncidents = 0;
$sql = "SELECT COUNT(*) AS total FROM incident_ticket";
$result = $conn->query($sql);
if ($result) {
    $row =$result->fetch_assoc();
    $totalIncidents = (int)$row['total'];
}

$totalWards = 0;
$sql = "SELECT COUNT(*) AS total FROM ward";
$result = $conn->query($sql);
if ($result) {
    $row =$result->fetch_assoc();
    $totalWards = (int)$row['total'];
}

$totalServices = 0;
$sql = "SELECT COUNT(*) AS total FROM municipal_service";
$result = $conn->query($sql);
if ($result) {
    $row =$result->fetch_assoc();
    $totalServices = (int)$row['total'];
}

$highPriorityIncidents = 0;
$sql = "SELECT COUNT(*) AS total FROM incident_ticket WHERE priority = 'High'";
$result = $conn->query($sql);
if ($result) {
    $row =$result->fetch_assoc();
    $highPriorityIncidents = (int)$row['total'];
}

$wardLabels = [];
$wardData = [];$sql = "SELECT w.ward_name, COUNT(i.incident_id) AS total_incident_tickets_per_ward
    FROM incident_ticket i
    JOIN ward w ON i.ward_id = w.ward_id
    GROUP BY w.ward_id, w.ward_name
    ORDER BY w.ward_id";
$result = $conn->query($sql);
if ($result) {
    while ($row =$result->fetch_assoc()) {
        $wardLabels[] =$row['ward_name'];
        $wardData[] = (int)$row['total_incident_tickets_per_ward'];
    }
}

$serviceLabels = [];
$serviceData = [];$sql = "SELECT ms.service_type, COUNT(i.incident_id) AS total_service_problems_ticket
    FROM incident_ticket i
    JOIN municipal_service ms ON i.service_id = ms.service_id
    GROUP BY ms.service_id, ms.service_type
    ORDER BY ms.service_id";
$result = $conn->query($sql);
if ($result) {
    while ($row =$result->fetch_assoc()) {
        $serviceLabels[] =$row['service_type'];
        $serviceData[] = (int)$row['total_service_problems_ticket'];
    }
}

$priorityLabels = [];
$priorityData = [];$sql = "SELECT priority, COUNT(*) AS total
    FROM incident_ticket
    GROUP BY priority
    ORDER BY FIELD(priority, 'Low', 'Medium', 'High')";
$result = $conn->query($sql);
if ($result) {
    while ($row =$result->fetch_assoc()) {
        $priorityLabels[] =$row['priority'];
        $priorityData[] = (int)$row['total'];
    }
}

$recentIncidents = [];$sql = "SELECT i.incident_id, ms.service_type, w.ward_name, i.incident_description, i.priority, i.date_reported
    FROM incident_ticket i
    JOIN municipal_service ms ON i.service_id = ms.service_id
    JOIN ward w ON i.ward_id = w.ward_id
    ORDER BY i.date_reported DESC
    LIMIT 5";
$result = $conn->query($sql);
if ($result) {
    while ($row =$result->fetch_assoc()) {
        $recentIncidents[] =$row;
    }
}

$dateLabels = [];
$dateData = [];$sql = "SELECT DATE(date_reported) AS incident_date, COUNT(*) AS total_incidents
        FROM incident_ticket
        GROUP BY DATE(date_reported)
        ORDER BY DATE(date_reported) ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row =$result->fetch_assoc()) {
        $dateLabels[] = date('d M Y', strtotime($row['incident_date']));
        $dateData[] = (int)$row['total_incidents'];
    }
}

$dashboardData = [
    'wardLabels' => $wardLabels,
    'wardData' => $wardData,
    'serviceLabels' => $serviceLabels,
    'serviceData' => $serviceData,
    'priorityLabels' => $priorityLabels,
    'priorityData' => $priorityData,
    'dateLabels' => $dateLabels,
    'dateData' => $dateData
];
// ---------------------------------------------------------------
// Unread notification count for the (old-style) topbar bell badge
// ---------------------------------------------------------------
$olUnreadNotifications = 0;
$olNotificationUsername = $_SESSION["username"] ?? "";
if ($olNotificationUsername !== "") {
    try {
        $olNotifStmt = $conn->prepare(
            "SELECT COUNT(*) AS unread_count
             FROM notification
             WHERE username = ?
             AND notification_type = 'ticket_update'
             AND is_read = 0"
        );
        if ($olNotifStmt) {
            $olNotifStmt->bind_param("s", $olNotificationUsername);
            $olNotifStmt->execute();
            $olNotifResult = $olNotifStmt->get_result();
            if ($olNotifResult && ($olNotifRow = $olNotifResult->fetch_assoc())) {
                $olUnreadNotifications = (int)$olNotifRow["unread_count"];
            }
            $olNotifStmt->close();
        }
    } catch (Throwable $olNotifError) {
        $olUnreadNotifications = 0; // never let the badge break the dashboard
    }
}
// ---------------------------------------------------------------
// Unread internal messages for the sidebar "Messages" badge
// ---------------------------------------------------------------
$olUnreadMessages = 0;
if ($olNotificationUsername !== "") {
    try {
        $olMsgStmt = $conn->prepare(
            "SELECT COUNT(*) AS unread_count
             FROM internal_messages
             WHERE receiver_username = ?
             AND is_read = 0"
        );
        if ($olMsgStmt) {
            $olMsgStmt->bind_param("s", $olNotificationUsername);
            $olMsgStmt->execute();
            $olMsgResult = $olMsgStmt->get_result();
            if ($olMsgResult && ($olMsgRow = $olMsgResult->fetch_assoc())) {
                $olUnreadMessages = (int)$olMsgRow["unread_count"];
            }
            $olMsgStmt->close();
        }
    } catch (Throwable $olMsgError) {
        $olUnreadMessages = 0; // never let the badge break the dashboard
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Makana System Overview | Communication Officer</title>
    <link rel="stylesheet" href="communication_officer_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,700;1,700&family=Ubuntu:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <style>
        .search-status-feedback {
            font-size: 0.85rem;
            color: #555;
            margin-top: 6px;
            font-style: italic;
        }
        .map-wrapper-relative {
            position: relative;
        }
        .map-loading-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.85);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            font-size: 0.9rem;
            color: #333;
            transition: opacity 0.4s ease, visibility 0.4s;
        }
        .map-loading-overlay.hidden {
            opacity: 0;
            visibility: hidden;
        }
    </style>
    <style>
    /* =====================================================
       OLD M-LINK SIDEBAR + TOPBAR  (all classes prefixed "ol-"
       so nothing collides with communication_officer_dashboard.css)
    ===================================================== */
    :root {
        --ol-sidebar-width: 270px;
        --ol-sidebar-collapsed-width: 85px;
        --ol-green: #4b561b;
        --ol-brown: #955b24;
        --ol-text: #263238;
        --ol-white: #ffffff;
        --ol-border: #e4e0d7;
    }
 
    .ol-sidebar, .ol-sidebar *,
    .ol-topbar, .ol-topbar * { box-sizing: border-box; }
    .ol-sidebar a, .ol-topbar a { text-decoration: none; }
 
    /* ---------- SIDEBAR ---------- */
    .ol-sidebar {
        position: fixed;
        top: 0; left: 0; bottom: 0;
        width: var(--ol-sidebar-width);
        background: var(--ol-green);
        color: var(--ol-white);
        padding: 30px 18px;
        z-index: 1200;
        overflow-x: hidden;
        overflow-y: auto;
        font-family: "Ubuntu", sans-serif;
        transition: width 0.3s ease;
    }
    .ol-sidebar-profile {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 5px 7px 25px;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        overflow: hidden;
    }
    .ol-sidebar-avatar {
        width: 48px; height: 48px; min-width: 48px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
    }
    .ol-sidebar-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .ol-sidebar-avatar .material-icons { font-size: 42px; color: var(--ol-white); }
    .ol-sidebar-profile-info {
        display: flex; flex-direction: column; min-width: 0;
        transition: opacity 0.2s ease;
    }
    .ol-sidebar-profile-info strong {
        font-size: 14px; font-weight: 600; color: var(--ol-white);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        max-width: 170px;
    }
    .ol-sidebar-profile-info span {
        margin-top: 3px; font-size: 11px; color: rgba(255, 255, 255, 0.75);
    }
    .ol-sidebar-menu { display: flex; flex-direction: column; gap: 8px; }
    .ol-sidebar-link {
        min-height: 48px;
        display: flex; align-items: center; gap: 15px;
        padding: 11px 14px;
        border-radius: 8px;
        color: rgba(255, 255, 255, 0.85);
        font-size: 14px; font-weight: 500;
        white-space: nowrap;
        transition: background 0.2s ease, color 0.2s ease;
    }
    .ol-sidebar-link:hover  { background: rgba(255, 255, 255, 0.12); color: var(--ol-white); }
    .ol-sidebar-link.active { background: rgba(255, 255, 255, 0.18); color: var(--ol-white); }
    .ol-sidebar-icon {
        width: 24px; height: 24px; min-width: 24px;
        object-fit: contain;
        filter: brightness(0) invert(1);
    }
    .ol-sidebar-material-icon {
        width: 24px; min-width: 24px;
        font-size: 24px; text-align: center; color: var(--ol-white);
    }
 
    /* collapsed (desktop) */
    .ol-sidebar.collapsed { width: var(--ol-sidebar-collapsed-width); }
    .ol-sidebar.collapsed .ol-sidebar-profile { justify-content: center; padding-left: 0; padding-right: 0; }
    .ol-sidebar.collapsed .ol-sidebar-profile-info,
    .ol-sidebar.collapsed .ol-sidebar-link span:not(.material-icons) { display: none; }
    .ol-sidebar.collapsed .ol-sidebar-link { justify-content: center; padding-left: 0; padding-right: 0; }
 
    /* unread badge on a sidebar link (e.g. Messages) */
    .ol-sidebar-link { position: relative; }
    .ol-nav-badge {
        margin-left: auto;
        min-width: 20px; height: 20px; padding: 0 6px;
        display: flex; align-items: center; justify-content: center;
        background: var(--ol-brown); color: var(--ol-white);
        border-radius: 20px;
        font-size: 11px; font-weight: 700; line-height: 1;
    }
    .ol-sidebar.collapsed .ol-sidebar-link span.ol-nav-badge {
        display: flex; position: absolute; top: 5px; right: 8px;
        margin-left: 0; min-width: 17px; height: 17px; padding: 0 4px;
        font-size: 9px; box-shadow: 0 0 0 2px var(--ol-green);
    }
 
    .ol-sidebar-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0, 0, 0, 0.45);
        z-index: 1190;
    }
 
    /* ---------- MAIN WRAPPER ---------- */
    .ol-main-wrapper {
        min-height: 100vh;
        margin-left: var(--ol-sidebar-width);
        width: calc(100% - var(--ol-sidebar-width));
        transition: margin-left 0.3s ease, width 0.3s ease;
    }
    .ol-main-wrapper.sidebar-collapsed {
        margin-left: var(--ol-sidebar-collapsed-width);
        width: calc(100% - var(--ol-sidebar-collapsed-width));
    }
    /* the wrapper now owns the sidebar offset, so the dashboard's own
       .main-content must not add a second one */
    .ol-main-wrapper > .main-content {
        margin-left: 0;
        width: 100% ;
        max-width: none ;
    }
 
    /* ---------- TOPBAR ---------- */
    .ol-topbar {
        width: 100%;
        height: 95px;
        background: var(--ol-white);
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 35px;
        border-bottom: 1px solid var(--ol-border);
        position: sticky; top: 0;
        z-index: 1100; /* above Leaflet's map controls (z-index 1000) */
        font-family: "Ubuntu", sans-serif;
        color: var(--ol-text);
    }
    .ol-topbar-left, .ol-topbar-right { display: flex; align-items: center; }
    .ol-topbar-left  { gap: 20px; }
    .ol-topbar-right { gap: 28px; }
 
    .ol-menu-btn {
        width: 42px; height: 42px; padding: 0;
        border: none; background: transparent; border-radius: 0;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; color: var(--ol-text);
    }
    .ol-menu-btn .material-icons { font-size: 29px; }
    .ol-menu-btn:hover { background: transparent; color: var(--ol-green); }
 
    .ol-topbar-logo-link { display: flex; align-items: center; }
    .ol-topbar-logo { width: 64px; height: 64px; object-fit: contain; display: block; }
 
    .ol-topbar-icon-btn {
        display: flex; align-items: center; gap: 8px;
        color: var(--ol-text); font-size: 12px; font-weight: 500;
        white-space: nowrap; transition: color 0.2s ease;
    }
    .ol-topbar-icon-btn:hover { color: var(--ol-brown); }
    .ol-topbar-icon-img { width: 26px; height: 26px; object-fit: contain; display: block; }
 
    .ol-notification-icon-wrapper {
        position: relative; width: 28px; height: 28px;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .ol-notification-icon-wrapper .material-icons { font-size: 26px; }
    .ol-notification-badge {
        position: absolute; top: -8px; right: -9px;
        min-width: 19px; height: 19px; padding: 0 5px;
        display: flex; align-items: center; justify-content: center;
        background: var(--ol-brown); color: var(--ol-white);
        border: 2px solid var(--ol-white); border-radius: 20px;
        font-family: "Ubuntu", sans-serif; font-size: 9px; font-weight: 700; line-height: 1;
    }
    .ol-notification-text { font-size: 12px; font-weight: 500; color: var(--ol-text); }
    .ol-topbar-icon-btn:hover .ol-notification-text { color: var(--ol-brown); }
 
    .ol-topbar-profile {
        display: flex; align-items: center; gap: 10px;
        padding-left: 20px;
        border-left: 1px solid var(--ol-border);
        color: var(--ol-text);
    }
    .ol-topbar-avatar { width: 42px; height: 42px; border-radius: 50%; overflow: hidden; flex-shrink: 0; }
    .ol-topbar-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .ol-topbar-avatar .material-icons { font-size: 40px; color: var(--ol-green); }
    .ol-topbar-profile-info { display: flex; flex-direction: column; }
    .ol-topbar-profile-info strong { font-size: 13px; font-weight: 600; color: var(--ol-text); }
    .ol-topbar-profile-info span   { margin-top: 2px; font-size: 10px; color: #777; }
 
    /* ---------- TABLET ---------- */
    @media (max-width: 1000px) {
        .ol-topbar-right { gap: 18px; }
        .ol-notification-text { display: none; }
    }
 
    /* ---------- MOBILE ---------- */
    @media (max-width: 750px) {
        .ol-sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
        .ol-sidebar.mobile-open { transform: translateX(0); }
        .ol-sidebar-overlay.show { display: block; }
        .ol-main-wrapper,
        .ol-main-wrapper.sidebar-collapsed { margin-left: 0; width: 100%; }
        .ol-topbar { height: 80px; padding: 0 18px; }
        .ol-topbar-left  { gap: 12px; }
        .ol-topbar-right { gap: 14px; }
        .ol-topbar-logo  { width: 52px; height: 52px; }
        .ol-topbar-profile-info { display: none; }
        .ol-topbar-profile { padding-left: 12px; }
        .ol-topbar-avatar  { width: 37px; height: 37px; }
    }
 
    /* ---------- SMALL MOBILE ---------- */
    @media (max-width: 480px) {
        .ol-topbar { padding: 0 12px; }
        .ol-topbar-right { gap: 10px; }
        .ol-topbar-logo  { width: 45px; height: 45px; }
        .ol-menu-btn { width: 34px; height: 34px; }
        .ol-menu-btn .material-icons { font-size: 26px; }
        .ol-topbar-profile { padding-left: 8px; }
        .ol-topbar-avatar  { width: 34px; height: 34px; }
    }
    </style>
</head>

<body>

<div id="sidebar" style="display:none" aria-hidden="true"></div>
<button type="button" id="menuToggle" style="display:none" aria-hidden="true" tabindex="-1"></button>
<div id="sidebarOverlay" style="display:none" aria-hidden="true"></div>
 

<aside class="ol-sidebar" id="olSidebar" aria-label="Main Navigation">
    <div class="ol-sidebar-profile">
        <div class="ol-sidebar-avatar">
            <?php if (!empty($userProfilePic)): ?>
                <img src="<?= e($userProfilePic) ?>" alt="Profile">
            <?php else: ?>
                <span class="material-icons">account_circle</span>
            <?php endif; ?>
        </div>
        <div class="ol-sidebar-profile-info">
            <strong><?= e($loggedInUser) ?></strong>
            <span>Communication Officer</span>
        </div>
    </div>
 
    <nav class="ol-sidebar-menu">
        <a href="communication_officer_dashboard.php" class="ol-sidebar-link active" aria-current="page">
            <img src="../icons/home.svg" alt="" class="ol-sidebar-icon"
                 onerror="var s=document.createElement('span');s.className='material-icons ol-sidebar-material-icon';s.textContent='home';this.replaceWith(s);">
            <span>Dashboard</span>
        </a>
 
        <a href="all_incidents.php" class="ol-sidebar-link">
            <span class="material-icons ol-sidebar-material-icon">confirmation_number</span>
            <span>View Incident Tickets</span>
        </a>
 
        <a href="manageNotices.php" class="ol-sidebar-link">
            <span class="material-icons ol-sidebar-material-icon">edit_note</span>
            <span>Create Notice</span>
        </a>
 
        <a href="viewnotices.php" class="ol-sidebar-link">
            <span class="material-icons ol-sidebar-material-icon">campaign</span>
            <span>View Notices</span>
        </a>
 
        <a href="messages.php" class="ol-sidebar-link">
            <span class="material-icons ol-sidebar-material-icon">chat</span>
            <span>Messages</span>
            <?php if ($olUnreadMessages > 0): ?>
                <span class="ol-nav-badge"><?= $olUnreadMessages > 99 ? "99+" : (int)$olUnreadMessages ?></span>
            <?php endif; ?>
        </a>
 
        <a href="../USER_DASHBOARD/reportFaultTicketForm.php" class="ol-sidebar-link">
            <span class="material-icons ol-sidebar-material-icon">report_problem</span>
            <span>Report an Incident</span>
        </a>
 
        <a href="fault_progress.php" class="ol-sidebar-link">
            <span class="material-icons ol-sidebar-material-icon">engineering</span>
            <span>Service Fault Progress</span>
        </a>
 
        <a href="settings.php" class="ol-sidebar-link">
            <span class="material-icons ol-sidebar-material-icon">settings</span>
            <span>Profile</span>
        </a>
 
        <a href="logout.php" class="ol-sidebar-link">
            <span class="material-icons ol-sidebar-material-icon">logout</span>
            <span>Logout</span>
        </a>
    </nav>
</aside>
<div class="ol-sidebar-overlay" id="olSidebarOverlay"></div>
 
<!-- MAIN PAGE -->
<div class="ol-main-wrapper" id="olMainWrapper">
 
    <header class="ol-topbar">
        <div class="ol-topbar-left">
            <button type="button" class="ol-menu-btn" id="olMenuBtn" aria-label="Toggle Navigation" aria-controls="olSidebar">
                <span class="material-icons">menu</span>
            </button>
 
            <a href="../PUBLIC%20PAGES/homepage.php" class="ol-topbar-logo-link" title="Go to M-LINK Homepage">
                <img src="../USER_DASHBOARD/mlinkLogo.png"
                     data-fb="mlinkLogo.png|../assets/images/mlinkLogo.png|../icons/mlinkLogo.png"
                     alt="M-LINK Logo" class="ol-topbar-logo"
                     onerror="var f=(this.dataset.fb||'').split('|').filter(Boolean);if(f.length){this.dataset.fb=f.slice(1).join('|');this.src=f[0];}">
            </a>
        </div>
 
        <div class="ol-topbar-right">
            <!-- NOTIFICATIONS -->
            <a href="../USER_DASHBOARD/notifications.php" class="ol-topbar-icon-btn" title="Notifications">
                <div class="ol-notification-icon-wrapper">
                    <img src="../icons/add_alert.svg" alt="Notifications" class="ol-topbar-icon-img"
                         onerror="var s=document.createElement('span');s.className='material-icons ';s.textContent='notifications';this.replaceWith(s);">
                    <?php if ($olUnreadNotifications > 0): ?>
                        <span class="ol-notification-badge"><?= $olUnreadNotifications > 99 ? "99+" : (int)$olUnreadNotifications ?></span>
                    <?php endif; ?>
                </div>
                <span class="ol-notification-text">Notifications</span>
            </a>
 
            <!-- PROFILE -->
            <a href="settings.php" class="ol-topbar-profile">
                <div class="ol-topbar-avatar">
                    <?php if (!empty($userProfilePic)): ?>
                        <img src="<?= e($userProfilePic) ?>" alt="Profile Picture">
                    <?php else: ?>
                        <span class="material-icons">account_circle</span>
                    <?php endif; ?>
                </div>
                <div class="ol-topbar-profile-info">
                    <strong><?= e($loggedInUser) ?></strong>
                    <span>Communication Officer</span>
                </div>
            </a>
        </div>
    </header>
 
<main class="main-content">

    <section class="overview-section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">SYSTEM OVERVIEW</p>
                <h2>Makana System Overview</h2>
                <p>System-wide information from municipal incident records.</p>
            </div>
        </div>

        <div class="overview-grid">
            <article class="overview-card">
                <div>
                    <span>Incident Tickets</span>
                    <strong class="counter"><?= e($totalIncidents) ?></strong>
                    <small>Total recorded tickets</small>
                </div>
            </article>

            <article class="overview-card">
                <div>
                    <span>Wards</span>
                    <strong class="counter"><?= e($totalWards) ?></strong>
                    <small>Wards in the system</small>
                </div>
            </article>

            <article class="overview-card">
                <div>
                    <span>Municipal Services</span>
                    <strong class="counter"><?= e($totalServices) ?></strong>
                    <small>Services in the system</small>
                </div>
            </article>

            <article class="overview-card">
                <div>
                    <span>High Priority</span>
                    <strong class="counter"><?= e($highPriorityIncidents) ?></strong>
                    <small>High-priority tickets</small>
                </div>
            </article>
        </div>
    </section>

    <section class="charts-section">
        <article class="chart-card large-chart">
            <div class="chart-heading">
                <div>
                    <p class="eyebrow">SYSTEM-WIDE TRACKING</p>
                    <h3>Incidents by Ward</h3>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="wardChart"></canvas>
            </div>
        </article>

        <article class="chart-card large-chart">
            <div class="chart-heading">
                <div>
                    <p class="eyebrow">MUNICIPAL SERVICES</p>
                    <h3>Municipal Service-Fault</h3>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="serviceChart"></canvas>
            </div>
        </article>

        <article class="chart-card large-chart">
            <div class="chart-heading">
                <div>
                    <p class="eyebrow">INCIDENT</p>
                    <h3>Incidents Reported Over Time</h3>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="dateChart"></canvas>
            </div>
        </article>

        <div class="card card-full-width map-wrapper-relative" style="background: #fff; padding: 1.5rem; border-radius: 12px;">
            <h3>Fault Hot-Spot Visual</h3>
            <div id="mapLoader" class="map-loading-overlay">
                <span>Rendering geospatial incident data...</span>
            </div>
            <div id="grahamstownMap"></div>
        </div>

        <article class="chart-card priority-chart-card">
            <div class="chart-heading">
                <div>
                    <p class="eyebrow">INCIDENTS</p>
                    <h3>Priority Distribution</h3>
                </div>
            </div>
            <div class="chart-wrapper priority-wrapper">
                <canvas id="priorityChart"></canvas>
            </div>
        </article>
    </section>

    <section class="incidents-section">
        <div class="section-heading incidents-heading">
            <div>
                <p class="eyebrow">SYSTEM-WIDE TICKET TRACKING</p>
                Recent Incidents Tickets</h2>
            </div>
            <a href="all_incidents.php" class="view-all">View All</a>
        </div>

        <div class="search-area">
            <input type="text" id="incidentSearch" placeholder="Search incident tickets..." aria-label="Search incident tickets">
            <div id="searchFeedback" class="search-status-feedback" aria-live="polite"></div>
        </div>
        <div class="table-container">
            <table id="incidentTable">
                <thead>
                    <tr>
                        <th>Incident ID</th>
                        <th>Service</th>
                        <th>Ward</th>
                        <th>Incident Description</th>
                        <th>Priority</th>
                        <th>Date Reported</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($recentIncidents)): ?>
                    <?php foreach ($recentIncidents as$incident): ?>
                        <tr class="incident-row">
                            <td class="incident-id"><?= e($incident['incident_id']) ?></td>
                            <td><?= e($incident['service_type']) ?></td>
                            <td><?= e($incident['ward_name']) ?></td>
                            <td class="incident-description"><?= e($incident["incident_description"]) ?></td>
                            <td>
                                <span class="priority-badge priority-<?= strtolower(e($incident['priority'])) ?>">
                                    <?= e($incident['priority']) ?>
                                </span>
                            </td>
                            <td><?= e(date('d M Y, H:i', strtotime($incident['date_reported']))) ?></td>
                            <td>
                                <a href="view_incident.php?id=<?= e($incident['incident_id']) ?>" class="view-button">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="noDataRow">
                        <td colspan="7" class="no-data">No incident tickets recorded in the system.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</div>

<script>
window.dashboardData = <?= json_encode($dashboardData, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
</script>
<script src="communication_dashboard.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="../PUBLIC PAGES/publicFaultBoard.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const activeTickets = <?php echo json_encode($ticketArray ?? []); ?>;
        const mapLoader = document.getElementById("mapLoader");
        
        if (typeof initGrahamstownMap === 'function') {
            initGrahamstownMap(activeTickets);
            if (mapLoader) mapLoader.classList.add("hidden");
        } else {
            if (mapLoader) mapLoader.textContent = "Map failed to load.";
        }

        // Search Status Feedback
        const searchInput = document.getElementById("incidentSearch");
        const searchFeedback = document.getElementById("searchFeedback");
        const rows = document.querySelectorAll("#incidentTable tbody tr.incident-row");

        if (searchInput) {
            searchInput.addEventListener("input", function() {
                const query = this.value.toLowerCase().trim();
                let matchCount = 0;

                rows.forEach(row => {
                    const content = row.textContent.toLowerCase();
                    if (content.includes(query)) {
                        row.style.display = "";
                        matchCount++;
                    } else {
                        row.style.display = "none";
                    }
                });

                if (query.length > 0) {
                    searchFeedback.textContent = `Showing ${matchCount} matching ticket(s) for "${query}".`;
                } else {
                    searchFeedback.textContent = "";
                }
            });
        }
    });
</script>
<script>


/* Old-style sidebar behaviour: desktop = collapse to icons, mobile = slide-in drawer */
(function () {
    var sidebar = document.getElementById("olSidebar");
    var wrapper = document.getElementById("olMainWrapper");
    var btn     = document.getElementById("olMenuBtn");
    var overlay = document.getElementById("olSidebarOverlay");
    if (!sidebar || !wrapper || !btn) return;
 
    var mq = window.matchMedia("(max-width: 750px)");
 
    function refreshLayout() {
        // let Leaflet / Chart.js re-measure once the width transition is done
        setTimeout(function () { window.dispatchEvent(new Event("resize")); }, 320);
    }
    function setMobileOpen(open) {
        sidebar.classList.toggle("mobile-open", open);
        if (overlay) overlay.classList.toggle("show", open);
        btn.setAttribute("aria-expanded", String(open));
    }
    function resetForBreakpoint() {
        setMobileOpen(false);
        if (mq.matches) {
            sidebar.classList.remove("collapsed");
            wrapper.classList.remove("sidebar-collapsed");
            btn.setAttribute("aria-expanded", "false");
        } else {
            btn.setAttribute("aria-expanded", String(!sidebar.classList.contains("collapsed")));
        }
    }
 
    btn.addEventListener("click", function () {
        if (mq.matches) {
            setMobileOpen(!sidebar.classList.contains("mobile-open"));
        } else {
            var collapsed = sidebar.classList.toggle("collapsed");
            wrapper.classList.toggle("sidebar-collapsed", collapsed);
            btn.setAttribute("aria-expanded", String(!collapsed));
            refreshLayout();
        }
    });
 
    if (overlay) overlay.addEventListener("click", function () { setMobileOpen(false); });
    document.addEventListener("keydown", function (ev) {
        if (ev.key === "Escape") setMobileOpen(false);
    });
    sidebar.addEventListener("click", function (ev) {
        if (mq.matches && ev.target.closest && ev.target.closest("a")) setMobileOpen(false);
    });
 
    if (mq.addEventListener) mq.addEventListener("change", resetForBreakpoint);
    else if (mq.addListener) mq.addListener(resetForBreakpoint);
 
    resetForBreakpoint();
})();
</script>
<script>
/* Hand the officer's display name to the other pages so the shared sidebar/topbar
   (global_dashboard_layout.js) shows exactly the same name straight away. */
(function () {
    try {
        sessionStorage.setItem("olLayoutUser", JSON.stringify({
            enabled: true,
            uid:    <?= json_encode((string)($_SESSION["username"] ?? ""), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            name:   <?= json_encode((string)$loggedInUser,   JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            pic:    <?= json_encode((string)$userProfilePic, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            unread: <?= (int)$olUnreadNotifications ?>,
            messages: <?= (int)$olUnreadMessages ?>
        }));
    } catch (e) { /* storage unavailable: the pages fall back to layout_user_info.php */ }
})();
</script>
</body>
</html>